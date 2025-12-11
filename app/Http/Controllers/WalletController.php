<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Models\AuctionPayment;
use App\Models\Notification;
use App\Models\User;
use App\Models\PlatformRevenue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends BaseApiController
{
    /**
     * Notify all admin users
     */
    private function notifyAdmins($title, $message, $type = 'system', $data = [])
    {
        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
            ]);
        }
    }

    private $bankConfig = [
        'bank_code' => 'VCB',
        'bank_name' => 'Vietcombank',
        'account_number' => '1234567890',
        'account_holder' => 'CONG TY TNHH TRADEHUB',
    ];

    public function getWallet(Request $request)
    {
        try {
            $user = $request->user();
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'frozen_balance' => 0, 'currency' => 'VND', 'status' => 'active']
            );
            return $this->ok([
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => (float) $wallet->balance,
                    'frozen_balance' => (float) $wallet->frozen_balance,
                    'available_balance' => (float) $wallet->available_balance,
                    'currency' => $wallet->currency,
                    'status' => $wallet->status,
                ],
                'pending_deposits' => $wallet->depositRequests()->where('status', 'pending')->count(),
                'pending_withdraws' => $wallet->withdrawRequests()->where('status', 'pending')->count(),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                return $this->ok(['transactions' => [], 'meta' => ['total' => 0]]);
            }
            $query = $wallet->transactions()->orderBy('created_at', 'desc');
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }


    public function deposit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10000',
                'payment_method' => 'required|in:bank_transfer,momo,vnpay,zalopay',
            ]);
            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 422);
            }
            $user = $request->user();
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'frozen_balance' => 0, 'currency' => 'VND', 'status' => 'active']
            );
            $requestCode = 'DEP' . time() . rand(1000, 9999);
            $depositRequest = DepositRequest::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'request_code' => $requestCode,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'bank_name' => $request->bank_name,
                'bank_account' => $request->bank_account,
                'note' => $request->note,
                'status' => 'pending',
            ]);
            
            // Notify all admins about new deposit request
            $this->notifyAdmins(
                'Yeu cau nap tien moi',
                $user->full_name . ' yeu cau nap ' . number_format($request->amount, 0, ',', '.') . ' VND. Ma: ' . $requestCode,
                'wallet',
                ['deposit_request_id' => $depositRequest->id]
            );
            
            $transferContent = 'NAP ' . $requestCode;
            $qrUrl = $this->generateVietQRUrl($request->amount, $transferContent);
            return $this->created([
                'deposit_request' => [
                    'id' => $depositRequest->id,
                    'request_code' => $depositRequest->request_code,
                    'amount' => (float) $depositRequest->amount,
                    'status' => $depositRequest->status,
                    'created_at' => $depositRequest->created_at,
                ],
                'payment_info' => [
                    'qr_url' => $qrUrl,
                    'bank_name' => $this->bankConfig['bank_name'],
                    'bank_code' => $this->bankConfig['bank_code'],
                    'account_number' => $this->bankConfig['account_number'],
                    'account_holder' => $this->bankConfig['account_holder'],
                    'transfer_content' => $transferContent,
                    'amount' => (float) $request->amount,
                ],
                'instructions' => [
                    'step_1' => 'Quet ma QR hoac chuyen khoan thu cong',
                    'step_2' => 'Nhap dung so tien va noi dung chuyen khoan',
                    'step_3' => 'Sau khi chuyen tien, bam nut Xac nhan da chuyen',
                    'step_4' => 'Cho admin duyet (thuong trong 5-15 phut)',
                ],
                'message' => 'Yeu cau nap tien da duoc tao. Vui long quet QR de chuyen khoan.',
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateVietQRUrl($amount, $content)
    {
        $bankCode = $this->bankConfig['bank_code'];
        $accountNo = $this->bankConfig['account_number'];
        $accountName = urlencode($this->bankConfig['account_holder']);
        $encodedContent = urlencode($content);
        return "https://img.vietqr.io/image/{$bankCode}-{$accountNo}-compact2.png?amount={$amount}&addInfo={$encodedContent}&accountName={$accountName}";
    }

    public function confirmTransfer(Request $request, DepositRequest $depositRequest)
    {
        try {
            $user = $request->user();
            if ($depositRequest->user_id !== $user->id) {
                return $this->fail(['message' => 'Khong co quyen'], 403);
            }
            if ($depositRequest->status !== 'pending') {
                return $this->fail(['message' => 'Yeu cau da duoc xu ly'], 422);
            }
            $depositRequest->update([
                'status' => 'processing',
                'note' => ($depositRequest->note ? $depositRequest->note . ' | ' : '') . 'User xac nhan da chuyen tien luc ' . now(),
            ]);
            return $this->ok([
                'deposit_request' => [
                    'id' => $depositRequest->id,
                    'request_code' => $depositRequest->request_code,
                    'amount' => (float) $depositRequest->amount,
                    'status' => $depositRequest->status,
                ],
                'message' => 'Da xac nhan chuyen tien. Vui long cho admin duyet (5-15 phut).',
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function checkDepositStatus(DepositRequest $depositRequest)
    {
        try {
            return $this->ok([
                'deposit_request' => [
                    'id' => $depositRequest->id,
                    'request_code' => $depositRequest->request_code,
                    'amount' => (float) $depositRequest->amount,
                    'status' => $depositRequest->status,
                    'status_label' => $this->getStatusLabel($depositRequest->status),
                    'created_at' => $depositRequest->created_at,
                    'confirmed_at' => $depositRequest->confirmed_at,
                    'admin_note' => $depositRequest->admin_note,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Cho chuyen tien',
            'processing' => 'Dang cho duyet',
            'completed' => 'Thanh cong',
            'failed' => 'That bai',
            'cancelled' => 'Da huy',
        ];
        return $labels[$status] ?? $status;
    }

    public function adminDepositRequests(Request $request)
    {
        try {
            $query = DepositRequest::with(['user:id,full_name,email'])->orderBy('created_at', 'desc');
            
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                // Map FE status to DB status
                $statusMap = [
                    'pending' => ['pending'],
                    'processing' => ['processing'],
                    'completed' => ['completed'],
                    'failed' => ['failed'],
                    'cancelled' => ['cancelled'],
                ];
                if (isset($statusMap[$status])) {
                    $query->whereIn('status', $statusMap[$status]);
                } else {
                    $query->where('status', $status);
                }
            }
            // Nếu không có status hoặc status=all thì lấy tất cả
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function adminWithdrawRequests(Request $request)
    {
        try {
            $query = WithdrawRequest::with(['user:id,full_name,email', 'wallet'])->orderBy('created_at', 'desc');
            
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }
            // Nếu không có status hoặc status=all thì lấy tất cả
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    // Cấu hình phí rút tiền
    private $withdrawConfig = [
        'service_fee_rate' => 0.01,  // Phí dịch vụ 1%
        'vat_rate' => 0.10,          // VAT 10% trên số tiền rút
        'min_amount' => 50000,       // Số tiền rút tối thiểu
    ];

    /**
     * Tính phí rút tiền
     * - Phí dịch vụ: 1% số tiền rút
     * - VAT: 10% số tiền rút
     * - Tổng: 11%
     */
    public function calculateWithdrawFee(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:' . $this->withdrawConfig['min_amount'],
            ]);
            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 422);
            }

            $amount = $request->amount;
            $fees = $this->calculateFees($amount);

            return $this->ok([
                'amount' => $amount,
                'fees' => $fees,
                'actual_receive' => $amount - $fees['total_fee'],
                'fee_breakdown' => [
                    'service_fee' => [
                        'rate' => '1%',
                        'description' => 'Phi dich vu platform',
                        'amount' => $fees['service_fee'],
                    ],
                    'vat' => [
                        'rate' => '10%',
                        'description' => 'Thue VAT',
                        'amount' => $fees['vat_fee'],
                    ],
                ],
                'total_fee_rate' => '11%',
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    private function calculateFees($amount)
    {
        // Phí dịch vụ 1% trên số tiền rút
        $serviceFee = $amount * $this->withdrawConfig['service_fee_rate'];
        // VAT 10% trên số tiền rút
        $vatFee = $amount * $this->withdrawConfig['vat_rate'];
        // Tổng phí = 11%
        $totalFee = $serviceFee + $vatFee;

        return [
            'service_fee' => round($serviceFee, 0),
            'vat_fee' => round($vatFee, 0),
            'pit_fee' => 0,
            'total_fee' => round($totalFee, 0),
        ];
    }

    public function withdraw(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:' . $this->withdrawConfig['min_amount'],
                'bank_name' => 'required|string',
                'bank_account' => 'required|string',
                'account_holder' => 'required|string',
            ]);
            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 422);
            }
            $user = $request->user();
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet || !$wallet->canWithdraw($request->amount)) {
                return $this->fail(['message' => 'So du khong du'], 422);
            }

            // Tính phí với thuế
            $fees = $this->calculateFees($request->amount);
            $actualAmount = $request->amount - $fees['total_fee'];

            DB::beginTransaction();
            $wallet->freeze($request->amount, 'withdraw', null, 'Dong bang de rut tien');
            $requestCode = 'WDR' . time() . rand(1000, 9999);
            $withdrawRequest = WithdrawRequest::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'request_code' => $requestCode,
                'amount' => $request->amount,
                'fee' => $fees['service_fee'],
                'vat_fee' => $fees['vat_fee'],
                'pit_fee' => $fees['pit_fee'],
                'total_fee' => $fees['total_fee'],
                'actual_amount' => $actualAmount,
                'bank_name' => $request->bank_name,
                'bank_account' => $request->bank_account,
                'account_holder' => $request->account_holder,
                'note' => $request->note,
                'status' => 'pending',
            ]);
            
            // Notify all admins about new withdraw request
            $this->notifyAdmins(
                'Yeu cau rut tien moi',
                $user->full_name . ' yeu cau rut ' . number_format($actualAmount, 0, ',', '.') . ' VND ve ' . $request->bank_name . '. Ma: ' . $requestCode,
                'wallet',
                ['withdraw_request_id' => $withdrawRequest->id]
            );
            
            DB::commit();
            return $this->created([
                'withdraw_request' => $withdrawRequest,
                'fee_breakdown' => [
                    'service_fee' => $fees['service_fee'],
                    'vat_fee' => $fees['vat_fee'],
                    'total_fee' => $fees['total_fee'],
                    'actual_receive' => $actualAmount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function depositRequests(Request $request)
    {
        try {
            $user = $request->user();
            $query = DepositRequest::where('user_id', $user->id)->orderBy('created_at', 'desc');
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function withdrawRequests(Request $request)
    {
        try {
            $user = $request->user();
            $query = WithdrawRequest::where('user_id', $user->id)->orderBy('created_at', 'desc');
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function payAuction(Request $request, AuctionPayment $auctionPayment)
    {
        try {
            $user = $request->user();
            if ($auctionPayment->winner_id !== $user->id) {
                return $this->fail(['message' => 'Khong co quyen'], 403);
            }
            if (!$auctionPayment->canPay()) {
                return $this->fail(['message' => 'Khong the thanh toan'], 422);
            }
            
            // Validate shipping info
            $validator = Validator::make($request->all(), [
                'shipping_name' => 'required|string|max:255',
                'shipping_phone' => 'required|string|max:20',
                'shipping_address' => 'required|string|max:500',
                'shipping_note' => 'nullable|string|max:500',
                'payment_method' => 'nullable|in:wallet,bank_transfer',
            ]);
            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 422);
            }
            
            $paymentMethod = $request->get('payment_method', 'wallet');
            $wallet = Wallet::where('user_id', $user->id)->first();
            
            // Lấy thông tin seller để lưu vào payment
            $seller = User::find($auctionPayment->seller_id);
            
            // Nếu thanh toán bằng ví
            if ($paymentMethod === 'wallet') {
                if (!$wallet || $wallet->available_balance < $auctionPayment->amount) {
                    // Trả về thông tin để FE hiển thị option chuyển khoản
                    return $this->fail([
                        'message' => 'So du khong du. Ban co the chon phuong thuc chuyen khoan ngan hang.',
                        'wallet_balance' => $wallet ? $wallet->available_balance : 0,
                        'amount_needed' => $auctionPayment->amount,
                        'can_use_bank_transfer' => true,
                        'bank_info' => $this->bankConfig,
                    ], 422);
                }
                
                DB::beginTransaction();
                $wallet->deduct($auctionPayment->amount, 'auction_win', 'auction_payment', $auctionPayment->id, 'Thanh toan dau gia');
                $sellerWallet = Wallet::firstOrCreate(
                    ['user_id' => $auctionPayment->seller_id],
                    ['balance' => 0, 'frozen_balance' => 0, 'currency' => 'VND', 'status' => 'active']
                );
                $sellerWallet->credit($auctionPayment->seller_receive, 'auction_receive', 'auction_payment', $auctionPayment->id, 'Nhan tien dau gia');
                
                $auctionPayment->update([
                    'status' => 'transferred',
                    'paid_at' => now(),
                    'transferred_at' => now(),
                    'payment_method' => 'wallet',
                    'shipping_name' => $request->shipping_name,
                    'shipping_phone' => $request->shipping_phone,
                    'shipping_address' => $request->shipping_address,
                    'shipping_note' => $request->shipping_note,
                    'seller_phone' => $seller->phone ?? null,
                    'seller_email' => $seller->email,
                ]);
                
                // Thông báo cho seller kèm thông tin giao hàng
                Notification::create([
                    'user_id' => $auctionPayment->seller_id,
                    'title' => 'Da nhan tien tu dau gia',
                    'message' => 'Ban da nhan ' . number_format($auctionPayment->seller_receive, 0, ',', '.') . ' VND. Thong tin giao hang: ' . $request->shipping_name . ' - ' . $request->shipping_phone . ' - ' . $request->shipping_address,
                    'type' => 'wallet',
                    'data' => ['auction_payment_id' => $auctionPayment->id],
                ]);
                
                // Thông báo cho winner kèm thông tin liên hệ seller
                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Thanh toan dau gia thanh cong',
                    'message' => 'Ban da thanh toan thanh cong. Lien he nguoi ban: ' . ($seller->phone ?? $seller->email),
                    'type' => 'wallet',
                    'data' => ['auction_payment_id' => $auctionPayment->id],
                ]);
                
                DB::commit();
                
                return $this->ok([
                    'message' => 'Thanh toan thanh cong!',
                    'payment' => $auctionPayment->fresh(),
                    'seller_contact' => [
                        'name' => $seller->full_name,
                        'phone' => $seller->phone,
                        'email' => $seller->email,
                    ],
                ]);
            }
            
            // Nếu thanh toán bằng chuyển khoản ngân hàng
            return $this->fail(['message' => 'Vui long su dung API /auction-payment/{id}/bank-transfer'], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Thanh toán đấu giá bằng chuyển khoản ngân hàng
     */
    public function payAuctionByBankTransfer(Request $request, AuctionPayment $auctionPayment)
    {
        try {
            $user = $request->user();
            if ($auctionPayment->winner_id !== $user->id) {
                return $this->fail(['message' => 'Khong co quyen'], 403);
            }
            if (!$auctionPayment->canPay()) {
                return $this->fail(['message' => 'Khong the thanh toan'], 422);
            }
            
            $validator = Validator::make($request->all(), [
                'shipping_name' => 'required|string|max:255',
                'shipping_phone' => 'required|string|max:20',
                'shipping_address' => 'required|string|max:500',
                'shipping_note' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 422);
            }
            
            // Cập nhật thông tin giao hàng và chuyển sang trạng thái chờ xác nhận
            $auctionPayment->update([
                'payment_method' => 'bank_transfer',
                'status' => 'pending', // Vẫn pending, chờ admin xác nhận
                'shipping_name' => $request->shipping_name,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_note' => $request->shipping_note,
            ]);
            
            // Thông báo cho admin
            $this->notifyAdmins(
                'Yeu cau xac nhan chuyen khoan dau gia',
                $user->full_name . ' yeu cau xac nhan chuyen khoan ' . number_format($auctionPayment->amount, 0, ',', '.') . ' VND cho dau gia. Ma: ' . $auctionPayment->payment_code,
                'auction',
                ['auction_payment_id' => $auctionPayment->id]
            );
            
            $transferContent = 'DAUGIA ' . $auctionPayment->payment_code;
            $qrUrl = $this->generateVietQRUrl($auctionPayment->amount, $transferContent);
            
            return $this->ok([
                'message' => 'Vui long chuyen khoan theo thong tin ben duoi va cho admin xac nhan.',
                'payment' => $auctionPayment->fresh(),
                'payment_info' => [
                    'qr_url' => $qrUrl,
                    'bank_name' => $this->bankConfig['bank_name'],
                    'bank_code' => $this->bankConfig['bank_code'],
                    'account_number' => $this->bankConfig['account_number'],
                    'account_holder' => $this->bankConfig['account_holder'],
                    'transfer_content' => $transferContent,
                    'amount' => (float) $auctionPayment->amount,
                ],
                'instructions' => [
                    'step_1' => 'Quet ma QR hoac chuyen khoan thu cong',
                    'step_2' => 'Nhap dung so tien va noi dung chuyen khoan: ' . $transferContent,
                    'step_3' => 'Sau khi chuyen tien, cho admin xac nhan (thuong trong 5-15 phut)',
                ],
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Admin xác nhận chuyển khoản đấu giá
     */
    public function adminConfirmAuctionBankTransfer(Request $request, AuctionPayment $auctionPayment)
    {
        try {
            if ($auctionPayment->payment_method !== 'bank_transfer') {
                return $this->fail(['message' => 'Khong phai thanh toan chuyen khoan'], 422);
            }
            if ($auctionPayment->status !== 'pending') {
                return $this->fail(['message' => 'Da xu ly'], 422);
            }
            
            DB::beginTransaction();
            
            // Chuyển tiền cho seller
            $sellerWallet = Wallet::firstOrCreate(
                ['user_id' => $auctionPayment->seller_id],
                ['balance' => 0, 'frozen_balance' => 0, 'currency' => 'VND', 'status' => 'active']
            );
            $sellerWallet->credit($auctionPayment->seller_receive, 'auction_receive', 'auction_payment', $auctionPayment->id, 'Nhan tien dau gia (chuyen khoan)');
            
            $seller = User::find($auctionPayment->seller_id);
            
            $auctionPayment->update([
                'status' => 'transferred',
                'paid_at' => now(),
                'transferred_at' => now(),
                'bank_transfer_confirmed_at' => now(),
                'bank_transfer_confirmed_by' => $request->user()->id,
                'admin_note' => $request->admin_note,
                'seller_phone' => $seller->phone ?? null,
                'seller_email' => $seller->email,
            ]);
            
            // Thông báo cho seller
            Notification::create([
                'user_id' => $auctionPayment->seller_id,
                'title' => 'Da nhan tien tu dau gia',
                'message' => 'Ban da nhan ' . number_format($auctionPayment->seller_receive, 0, ',', '.') . ' VND. Thong tin giao hang: ' . $auctionPayment->shipping_name . ' - ' . $auctionPayment->shipping_phone,
                'type' => 'wallet',
                'data' => ['auction_payment_id' => $auctionPayment->id],
            ]);
            
            // Thông báo cho winner
            Notification::create([
                'user_id' => $auctionPayment->winner_id,
                'title' => 'Thanh toan dau gia da duoc xac nhan',
                'message' => 'Chuyen khoan cua ban da duoc xac nhan. Lien he nguoi ban: ' . ($seller->phone ?? $seller->email),
                'type' => 'wallet',
                'data' => ['auction_payment_id' => $auctionPayment->id],
            ]);
            
            DB::commit();
            
            return $this->ok([
                'message' => 'Da xac nhan chuyen khoan thanh cong',
                'payment' => $auctionPayment->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Admin từ chối chuyển khoản đấu giá
     */
    public function adminRejectAuctionBankTransfer(Request $request, AuctionPayment $auctionPayment)
    {
        try {
            if ($auctionPayment->payment_method !== 'bank_transfer') {
                return $this->fail(['message' => 'Khong phai thanh toan chuyen khoan'], 422);
            }
            if ($auctionPayment->status !== 'pending') {
                return $this->fail(['message' => 'Da xu ly'], 422);
            }
            
            $auctionPayment->update([
                'admin_note' => $request->admin_note ?? 'Khong xac nhan duoc giao dich chuyen khoan',
            ]);
            
            // Thông báo cho winner
            Notification::create([
                'user_id' => $auctionPayment->winner_id,
                'title' => 'Chuyen khoan dau gia bi tu choi',
                'message' => 'Chuyen khoan cua ban khong duoc xac nhan. ' . ($request->admin_note ? 'Ly do: ' . $request->admin_note : '') . ' Vui long chuyen khoan lai hoac lien he ho tro.',
                'type' => 'wallet',
                'data' => ['auction_payment_id' => $auctionPayment->id],
            ]);
            
            return $this->ok([
                'message' => 'Da tu choi xac nhan chuyen khoan',
                'payment' => $auctionPayment->fresh(),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Xem chi tiết auction payment (bao gồm thông tin liên hệ nếu đã thanh toán)
     */
    public function auctionPaymentDetail(AuctionPayment $auctionPayment)
    {
        try {
            $user = request()->user();
            
            // Chỉ winner hoặc seller hoặc admin mới xem được
            if ($auctionPayment->winner_id !== $user->id && 
                $auctionPayment->seller_id !== $user->id && 
                !$user->isAdmin()) {
                return $this->fail(['message' => 'Khong co quyen'], 403);
            }
            
            $payment = $auctionPayment->load(['auction.listing', 'winner:id,full_name,email,phone', 'seller:id,full_name,email,phone']);
            
            $response = [
                'payment' => $payment,
                'time_remaining' => $payment->getTimeRemaining(),
                'can_pay' => $payment->canPay(),
            ];
            
            // Nếu đã thanh toán, hiển thị thông tin liên hệ
            if (in_array($payment->status, ['paid', 'transferred'])) {
                if ($user->id === $payment->winner_id) {
                    // Winner xem thông tin seller
                    $response['seller_contact'] = [
                        'name' => $payment->seller->full_name,
                        'phone' => $payment->seller_phone ?? $payment->seller->phone,
                        'email' => $payment->seller_email ?? $payment->seller->email,
                    ];
                } elseif ($user->id === $payment->seller_id) {
                    // Seller xem thông tin giao hàng
                    $response['shipping_info'] = [
                        'name' => $payment->shipping_name,
                        'phone' => $payment->shipping_phone,
                        'address' => $payment->shipping_address,
                        'note' => $payment->shipping_note,
                    ];
                    $response['winner_contact'] = [
                        'name' => $payment->winner->full_name,
                        'phone' => $payment->winner->phone,
                        'email' => $payment->winner->email,
                    ];
                }
            }
            
            return $this->ok($response);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function auctionPayments(Request $request)
    {
        try {
            $user = $request->user();
            $query = AuctionPayment::where(function ($q) use ($user) {
                $q->where('winner_id', $user->id)->orWhere('seller_id', $user->id);
            })->with(['auction.listing', 'winner:id,full_name', 'seller:id,full_name'])->orderBy('created_at', 'desc');
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('role')) {
                if ($request->role === 'buyer') {
                    $query->where('winner_id', $user->id);
                } elseif ($request->role === 'seller') {
                    $query->where('seller_id', $user->id);
                }
            }
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }


    public function confirmDeposit(Request $request, DepositRequest $depositRequest)
    {
        try {
            if (!in_array($depositRequest->status, ['pending', 'processing'])) {
                return $this->fail(['message' => 'Yeu cau da duoc xu ly'], 422);
            }
            DB::beginTransaction();
            $wallet = $depositRequest->wallet;
            $wallet->credit($depositRequest->amount, 'deposit', 'deposit_request', $depositRequest->id, 'Nap tien thanh cong');
            $depositRequest->update([
                'status' => 'completed',
                'confirmed_at' => now(),
                'confirmed_by' => $request->user()->id,
                'admin_note' => $request->admin_note,
            ]);
            Notification::create([
                'user_id' => $depositRequest->user_id,
                'title' => 'Nap tien thanh cong',
                'message' => 'Ban da nap ' . number_format($depositRequest->amount, 0, ',', '.') . ' VND vao vi.',
                'type' => 'wallet',
            ]);
            DB::commit();
            return $this->ok([
                'message' => 'Da duyet nap tien thanh cong',
                'deposit_request' => $depositRequest->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function rejectDeposit(Request $request, DepositRequest $depositRequest)
    {
        try {
            if (!in_array($depositRequest->status, ['pending', 'processing'])) {
                return $this->fail(['message' => 'Yeu cau da duoc xu ly'], 422);
            }
            $depositRequest->update([
                'status' => 'failed',
                'admin_note' => $request->admin_note ?? 'Khong xac nhan duoc giao dich',
                'confirmed_at' => now(),
                'confirmed_by' => $request->user()->id
            ]);
            Notification::create([
                'user_id' => $depositRequest->user_id,
                'title' => 'Nap tien that bai',
                'message' => 'Yeu cau nap ' . number_format($depositRequest->amount, 0, ',', '.') . ' VND bi tu choi.',
                'type' => 'wallet'
            ]);
            return $this->ok([
                'message' => 'Da tu choi yeu cau nap tien',
                'deposit_request' => $depositRequest->fresh(),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function processWithdraw(Request $request, WithdrawRequest $withdrawRequest)
    {
        try {
            if ($withdrawRequest->status !== 'pending') {
                return $this->fail(['message' => 'Da xu ly'], 422);
            }
            DB::beginTransaction();
            $wallet = $withdrawRequest->wallet;
            $wallet->unfreeze($withdrawRequest->amount, 'withdraw', $withdrawRequest->id);
            $wallet->deduct($withdrawRequest->amount, 'withdraw', 'withdraw_request', $withdrawRequest->id, 'Rut tien');
            $withdrawRequest->update(['status' => 'completed', 'processed_at' => now(), 'processed_by' => $request->user()->id]);

            // Ghi nhận doanh thu platform từ phí rút tiền
            $totalFee = $withdrawRequest->total_fee ?? $withdrawRequest->fee;
            if ($totalFee > 0) {
                PlatformRevenue::record(
                    PlatformRevenue::TYPE_WITHDRAW_FEE,
                    $totalFee,
                    'withdraw_request',
                    $withdrawRequest->id,
                    $withdrawRequest->user_id,
                    'Phi rut tien tu ' . $withdrawRequest->request_code,
                    [
                        'service_fee' => $withdrawRequest->fee,
                        'vat_fee' => $withdrawRequest->vat_fee ?? 0,
                        'pit_fee' => $withdrawRequest->pit_fee ?? 0,
                    ]
                );
            }

            Notification::create([
                'user_id' => $withdrawRequest->user_id,
                'title' => 'Rut tien thanh cong',
                'message' => 'Yeu cau rut ' . number_format($withdrawRequest->actual_amount, 0, ',', '.') . ' VND da duoc xu ly. Tien se chuyen vao tai khoan ' . $withdrawRequest->bank_account . ' trong 1-3 ngay lam viec.',
                'type' => 'wallet'
            ]);
            DB::commit();
            return $this->ok([
                'message' => 'Da xu ly rut tien thanh cong',
                'withdraw_request' => $withdrawRequest->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function rejectWithdraw(Request $request, WithdrawRequest $withdrawRequest)
    {
        try {
            if ($withdrawRequest->status !== 'pending') {
                return $this->fail(['message' => 'Da xu ly'], 422);
            }
            DB::beginTransaction();
            $wallet = $withdrawRequest->wallet;
            $wallet->unfreeze($withdrawRequest->amount, 'withdraw', $withdrawRequest->id, 'Hoan tien');
            $withdrawRequest->update(['status' => 'rejected', 'admin_note' => $request->admin_note, 'processed_at' => now(), 'processed_by' => $request->user()->id]);
            Notification::create([
                'user_id' => $withdrawRequest->user_id,
                'title' => 'Yeu cau rut tien bi tu choi',
                'message' => 'Yeu cau rut ' . number_format($withdrawRequest->amount, 0, ',', '.') . ' VND bi tu choi. ' . ($request->admin_note ? 'Ly do: ' . $request->admin_note : '') . ' So tien da duoc hoan lai vao vi.',
                'type' => 'wallet'
            ]);
            DB::commit();
            return $this->ok([
                'message' => 'Da tu choi yeu cau rut tien',
                'withdraw_request' => $withdrawRequest->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
}
