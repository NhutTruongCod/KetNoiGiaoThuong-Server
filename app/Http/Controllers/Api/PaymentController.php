<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * 1. GET /payments - Danh sách payments với filter, pagination, auth
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Payment::with(['payer', 'payee', 'payable']);
        
        // Admin xem tất cả, user chỉ xem của mình
        if ($user->role !== 'admin') {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('payer_id', $user->id)
                  ->orWhere('payee_id', $user->id);
            });
        }
        
        // Filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('method')) {
            $query->byMethod($request->method);
        }
        
        if ($request->has('payment_type')) {
            $query->byType($request->payment_type);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $payments = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
    
    /**
     * 2. GET /payments/{id} - Chi tiết payment với check ownership
     */
    public function show($id)
    {
        $payment = Payment::with(['payer', 'payee', 'payable', 'order'])->find($id);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }
        
        $user = Auth::user();
        
        // Check ownership
        if ($user->role !== 'admin') {
            if ($payment->user_id != $user->id && 
                $payment->payer_id != $user->id && 
                $payment->payee_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own payments'
                ], 403);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }
    
    /**
     * 3. POST /payments - Tạo payment (hỗ trợ nhiều loại)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'method' => 'required|in:cod,vnpay,momo,zalopay,bank_transfer',
            'amount' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::user();
        $order = Order::with('listing.shop')->find($request->order_id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        // Check if order already has payment
        $existingPayment = Payment::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->first();
            
        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Order already has a payment'
            ], 400);
        }
        
        // Check amount matches order total
        if ($request->amount != $order->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount does not match order total',
                'order_total' => $order->total_amount,
                'payment_amount' => $request->amount
            ], 400);
        }
        
        $method = $request->method;
        
        // Route to appropriate payment method
        switch ($method) {
            case 'cod':
                return $this->createCODPayment($order, $user);
            case 'vnpay':
                return $this->createVNPayPayment($order, $user, $request);
            case 'momo':
                return $this->createMomoPayment($order, $user, $request);
            case 'zalopay':
                return $this->createZaloPayPayment($order, $user, $request);
            case 'bank_transfer':
                return $this->createBankTransferPayment($order, $user);
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not supported'
                ], 400);
        }
    }
    
    /**
     * COD Payment
     */
    private function createCODPayment($order, $user)
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'payable_type' => 'App\\Models\\Order',
            'payable_id' => $order->id,
            'payment_type' => 'order',
            'payer_id' => $order->buyer_id,
            'payee_id' => $order->listing->shop->user_id ?? null,
            'method' => 'cod',
            'amount' => $order->total_amount,
            'currency' => 'VND',
            'status' => 'pending',
            'description' => "Thanh toán đơn hàng #{$order->order_number}",
            'transaction_id' => 'COD-' . time() . '-' . $order->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'COD payment created successfully. Pay when you receive the order.',
            'data' => $payment
        ], 201);
    }
    
    /**
     * VNPay Payment (Placeholder - cần implement đầy đủ)
     */
    private function createVNPayPayment($order, $user, $request)
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'payable_type' => 'App\\Models\\Order',
            'payable_id' => $order->id,
            'payment_type' => 'order',
            'payer_id' => $order->buyer_id,
            'payee_id' => $order->listing->shop->user_id ?? null,
            'method' => 'vnpay',
            'payment_gateway' => 'vnpay',
            'amount' => $order->total_amount,
            'currency' => 'VND',
            'status' => 'pending',
            'description' => "Thanh toán đơn hàng #{$order->order_number}",
            'transaction_id' => 'VNP-' . time() . '-' . $order->id,
            'return_url' => $request->return_url ?? url('/payments/vnpay/callback'),
        ]);
        
        // TODO: Implement VNPay API call
        $paymentUrl = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?...";
        
        return response()->json([
            'success' => true,
            'message' => 'VNPay payment created. Redirect to payment URL.',
            'data' => [
                'payment' => $payment,
                'payment_url' => $paymentUrl,
                'qr_code' => null, // TODO: Generate QR code
            ]
        ], 201);
    }
    
    /**
     * Momo Payment
     */
    private function createMomoPayment($order, $user, $request)
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'payable_type' => 'App\\Models\\Order',
            'payable_id' => $order->id,
            'payment_type' => 'order',
            'payer_id' => $order->buyer_id,
            'payee_id' => $order->listing->shop->user_id ?? null,
            'method' => 'momo',
            'payment_gateway' => 'momo',
            'amount' => $order->total_amount,
            'currency' => 'VND',
            'status' => 'pending',
            'description' => "Thanh toán đơn hàng #{$order->order_number}",
            'transaction_id' => 'MOMO-' . time() . '-' . $order->id,
            'return_url' => $request->return_url ?? url('/payments/momo/callback'),
        ]);
        
        // TODO: Call Momo API (use existing code from old controller)
        $paymentUrl = "https://test-payment.momo.vn/...";
        
        return response()->json([
            'success' => true,
            'message' => 'Momo payment created. Redirect to payment URL.',
            'data' => [
                'payment' => $payment,
                'payment_url' => $paymentUrl,
                'deeplink' => null, // TODO: Generate deeplink
                'qr_code' => null, // TODO: Generate QR code
            ]
        ], 201);
    }
    
    /**
     * ZaloPay Payment (Placeholder)
     */
    private function createZaloPayPayment($order, $user, $request)
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'payable_type' => 'App\\Models\\Order',
            'payable_id' => $order->id,
            'payment_type' => 'order',
            'payer_id' => $order->buyer_id,
            'payee_id' => $order->listing->shop->user_id ?? null,
            'method' => 'zalopay',
            'payment_gateway' => 'zalopay',
            'amount' => $order->total_amount,
            'currency' => 'VND',
            'status' => 'pending',
            'description' => "Thanh toán đơn hàng #{$order->order_number}",
            'transaction_id' => 'ZLP-' . time() . '-' . $order->id,
            'return_url' => $request->return_url ?? url('/payments/zalopay/callback'),
        ]);
        
        // TODO: Implement ZaloPay API
        
        return response()->json([
            'success' => true,
            'message' => 'ZaloPay payment created.',
            'data' => $payment
        ], 201);
    }
    
    /**
     * Bank Transfer Payment
     */
    private function createBankTransferPayment($order, $user)
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'payable_type' => 'App\\Models\\Order',
            'payable_id' => $order->id,
            'payment_type' => 'order',
            'payer_id' => $order->buyer_id,
            'payee_id' => $order->listing->shop->user_id ?? null,
            'method' => 'bank_transfer',
            'amount' => $order->total_amount,
            'currency' => 'VND',
            'status' => 'pending',
            'description' => "Thanh toán đơn hàng #{$order->order_number}",
            'transaction_id' => 'BANK-' . time() . '-' . $order->id,
            'metadata' => [
                'bank_info' => [
                    'bank_name' => 'Vietcombank',
                    'account_number' => '1234567890',
                    'account_name' => 'CONG TY TRADEHUB',
                    'transfer_content' => $payment->transaction_id ?? 'Payment for order ' . $order->order_number,
                ]
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Bank transfer payment created. Please transfer to the bank account below.',
            'data' => [
                'payment' => $payment,
                'bank_info' => $payment->metadata['bank_info'],
            ]
        ], 201);
    }
    
    /**
     * 4. GET /payments/my-payments - Payments của user
     */
    public function myPayments(Request $request)
    {
        $user = Auth::user();
        
        $query = Payment::with(['payable'])
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('payer_id', $user->id);
            });
        
        // Filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        if ($request->has('payment_type')) {
            $query->byType($request->payment_type);
        }
        
        // Sort & pagination
        $query->orderBy('created_at', 'desc');
        $perPage = $request->get('per_page', 20);
        
        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)
        ]);
    }
    
    /**
     * 5. POST /payments/{id}/refund - Hoàn tiền
     */
    public function refund(Request $request, $id)
    {
        $payment = Payment::find($id);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }
        
        if ($payment->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Can only refund completed payments'
            ], 400);
        }
        
        $payment->refund($request->reason);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment refunded successfully',
            'data' => $payment
        ]);
    }
    
    /**
     * 6. POST /payments/{id}/cancel - Hủy payment
     */
    public function cancel(Request $request, $id)
    {
        $payment = Payment::find($id);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }
        
        if (!in_array($payment->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Can only cancel pending or processing payments'
            ], 400);
        }
        
        $payment->cancel($request->reason);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
            'data' => $payment
        ]);
    }
    
    /**
     * 7. POST /payments/vnpay/callback - VNPay callback
     */
    public function vnpayCallback(Request $request)
    {
        // TODO: Implement VNPay callback verification
        
        return response()->json([
            'success' => true,
            'message' => 'VNPay callback received'
        ]);
    }
    
    /**
     * 8. POST /payments/momo/callback - Momo callback
     */
    public function momoCallback(Request $request)
    {
        // TODO: Implement Momo callback verification
        
        return response()->json([
            'success' => true,
            'message' => 'Momo callback received'
        ]);
    }
    
    /**
     * 9. POST /payments/zalopay/callback - ZaloPay callback
     */
    public function zalopayCallback(Request $request)
    {
        // TODO: Implement ZaloPay callback verification
        
        return response()->json([
            'success' => true,
            'message' => 'ZaloPay callback received'
        ]);
    }
}
