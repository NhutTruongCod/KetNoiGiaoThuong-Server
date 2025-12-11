<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Wallet;
use App\Models\Shop;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Default platform fee percentage (for free users)
const DEFAULT_COMMISSION_RATE = 10;
const DEFAULT_SHIPPING_FEE = 22000; // Phí ship mặc định 22k

class OrderController extends Controller
{
    /**
     * POST /api/orders/preview
     * Preview checkout - Tính toán giá trước khi tạo đơn hàng
     * FE gọi API này để hiển thị thông tin checkout
     */
    public function preview(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'listing_id' => 'required|exists:listings,id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Du lieu khong hop le',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get listing with shop
            $listing = Listing::with('shop')->find($request->listing_id);
            
            if (!$listing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'San pham khong ton tai'
                ], 404);
            }

            // Xác định seller
            $sellerId = $listing->user_id ?? ($listing->shop ? $listing->shop->owner_user_id : null);
            $seller = User::find($sellerId);
            
            // Không cho phép mua từ shop của chính mình
            if ($sellerId == $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ban khong the mua san pham cua chinh minh'
                ], 400);
            }

            // Kiểm tra shop đã xác minh chưa
            $shop = $listing->shop;
            $shopVerified = $shop && $shop->is_verified;
            
            // Xác định loại sản phẩm
            $productType = $this->determineProductType($listing);
            
            // Check stock
            $stockQty = $listing->stock_qty ?? 999;
            if ($stockQty < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Khong du hang trong kho',
                    'requested' => $request->quantity,
                    'available' => $stockQty
                ], 400);
            }

            // Calculate amounts
            $unitPrice = ($listing->price_cents ?? 0) / 100;
            if ($unitPrice <= 0) {
                $unitPrice = $listing->price ?? 0;
            }
            
            $totalAmount = $unitPrice * $request->quantity;
            $shippingFee = $productType === 'physical' ? DEFAULT_SHIPPING_FEE : 0;
            $discountAmount = 0;
            $taxAmount = 0;
            $finalAmount = $totalAmount + $shippingFee + $taxAmount - $discountAmount;
            
            // Get seller's commission rate
            $commissionRate = $seller ? $seller->getCommissionRate() : DEFAULT_COMMISSION_RATE;
            $platformFee = $finalAmount * ($commissionRate / 100);
            $sellerReceive = $finalAmount - $platformFee;

            // Kiểm tra ví của buyer
            $wallet = Wallet::where('user_id', $user->id)->first();
            $walletBalance = $wallet ? $wallet->available_balance : 0;
            $canPay = $walletBalance >= $finalAmount;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'listing' => [
                        'id' => $listing->id,
                        'title' => $listing->title,
                        'description' => $listing->description,
                        'images' => $listing->images,
                        'main_image' => $listing->main_image,
                        'type' => $listing->type,
                        'stock_qty' => $stockQty,
                    ],
                    'shop' => [
                        'id' => $shop?->id,
                        'name' => $shop?->name,
                        'is_verified' => $shopVerified,
                        'phone' => $shop?->phone,
                    ],
                    'seller' => [
                        'id' => $sellerId,
                        'name' => $seller?->full_name,
                        'commission_rate' => $commissionRate,
                    ],
                    'product_type' => $productType,
                    'quantity' => $request->quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'platform_fee' => $platformFee,
                    'seller_receive' => $sellerReceive,
                    'final_amount' => $finalAmount,
                    'wallet' => [
                        'balance' => $walletBalance,
                        'can_pay' => $canPay,
                        'need_more' => $canPay ? 0 : ($finalAmount - $walletBalance),
                    ],
                    'requires_shipping_address' => $productType === 'physical',
                    'requires_chat' => $productType === 'digital' && !$this->checkChatHistory($user->id, $sellerId),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders
     * Danh sách đơn hàng
     * 
     * Logic:
     * - Admin: Xem tất cả đơn hàng
     * - User thường: Xem đơn mình mua (buyer_id) + đơn mình bán (seller_id)
     * - Seller có thể vừa mua vừa bán, nên xem cả 2 loại
     */
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = Order::with(['buyer:id,full_name,email,phone', 'seller:id,full_name,email', 'shop:id,name,phone', 'listing:id,title,price_cents']);

            // Admin xem tất cả, user thường chỉ xem đơn liên quan đến mình
            if ($user->role !== 'admin') {
                $query->where(function($q) use ($user) {
                    $q->where('buyer_id', $user->id)      // Đơn mình mua
                      ->orWhere('seller_id', $user->id);  // Đơn mình bán
                });
            }

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->get('sort', 'created_at');
            $order = $request->get('order', 'desc');
            $query->orderBy($sortBy, $order);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $orders = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/orders/{id}
     * Chi tiết đơn hàng
     */
    public function show($id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::with([
                'buyer:id,full_name,email,phone',
                'seller:id,full_name,email',
                'shop:id,name,address,phone,email',
                'listing:id,title,description,price_cents,images,shop_id'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permission
            if ($user->role !== 'admin' && $order->buyer_id != $user->id && $order->seller_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only view your own orders'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/orders
     * Tạo đơn hàng mới (thêm vào giỏ hàng / checkout)
     * 
     * Logic:
     * - Sản phẩm vật lý (physical): Yêu cầu địa chỉ giao hàng
     * - Sản phẩm số (digital): Yêu cầu đã nhắn tin trao đổi trước
     * - Shop phải được xác minh mới nhận được tiền
     * - Thanh toán bằng ví, nếu không đủ thì yêu cầu nạp tiền
     */
    public function store(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'listing_id' => 'required|exists:listings,id',
                'quantity' => 'required|integer|min:1',
                'shipping_address' => 'nullable|array',
                'shipping_address.name' => 'required_with:shipping_address|string|max:100',
                'shipping_address.phone' => 'required_with:shipping_address|string|max:20',
                'shipping_address.address' => 'required_with:shipping_address|string|max:255',
                'shipping_address.district' => 'nullable|string|max:100',
                'shipping_address.city' => 'nullable|string|max:100',
                'note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Du lieu khong hop le',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get listing with shop
            $listing = Listing::with('shop')->find($request->listing_id);
            
            if (!$listing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'San pham khong ton tai'
                ], 404);
            }

            // Xác định seller
            $sellerId = $listing->user_id ?? ($listing->shop ? $listing->shop->owner_user_id : null);
            
            if (!$sellerId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Khong tim thay nguoi ban'
                ], 400);
            }

            // Không cho phép mua từ shop của chính mình
            if ($sellerId == $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ban khong the mua san pham cua chinh minh'
                ], 400);
            }

            // Kiểm tra shop đã xác minh chưa
            $shop = $listing->shop;
            $shopVerified = $shop && $shop->is_verified;
            
            // Xác định loại sản phẩm (physical hoặc digital)
            // Dựa vào type của listing hoặc category
            $productType = $this->determineProductType($listing);
            
            // Nếu là sản phẩm số, kiểm tra đã nhắn tin trao đổi chưa
            if ($productType === 'digital') {
                $hasChat = $this->checkChatHistory($user->id, $sellerId);
                if (!$hasChat) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'San pham so can trao doi truoc khi mua. Vui long nhan tin voi nguoi ban truoc.',
                        'requires_chat' => true,
                        'seller_id' => $sellerId,
                        'seller_name' => User::find($sellerId)?->full_name,
                    ], 422);
                }
            }
            
            // Nếu là sản phẩm vật lý, yêu cầu địa chỉ giao hàng
            if ($productType === 'physical' && !$request->shipping_address) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'San pham vat ly can dia chi giao hang',
                    'requires_shipping_address' => true,
                ], 422);
            }

            // Check stock
            $stockQty = $listing->stock_qty ?? 999;
            if ($stockQty < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Khong du hang trong kho',
                    'requested' => $request->quantity,
                    'available' => $stockQty
                ], 400);
            }

            // Calculate amounts
            $unitPrice = ($listing->price_cents ?? 0) / 100; // Convert cents to VND
            if ($unitPrice <= 0) {
                $unitPrice = $listing->price ?? 0;
            }
            
            $totalAmount = $unitPrice * $request->quantity;
            $shippingFee = $productType === 'physical' ? 22000 : 0; // Phí ship mặc định 22k cho hàng vật lý
            $discountAmount = 0;
            $taxAmount = 0;
            $finalAmount = $totalAmount + $shippingFee + $taxAmount - $discountAmount;
            
            // Get seller's commission rate based on subscription plan
            $seller = User::find($sellerId);
            $commissionRate = $seller ? $seller->getCommissionRate() : DEFAULT_COMMISSION_RATE;
            
            // Platform fee based on seller's subscription plan
            $platformFee = $finalAmount * ($commissionRate / 100);
            $sellerReceive = $finalAmount - $platformFee;

            // Generate order number
            $orderNumber = Order::generateOrderNumber();

            DB::beginTransaction();

            // Create order (chưa thanh toán)
            $order = Order::create([
                'order_number' => $orderNumber,
                'buyer_id' => $user->id,
                'seller_id' => $sellerId,
                'shop_id' => $listing->shop_id,
                'listing_id' => $listing->id,
                'product_type' => $productType,
                'chat_confirmed' => $productType === 'digital',
                'chat_confirmed_at' => $productType === 'digital' ? now() : null,
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'platform_fee' => $platformFee,
                'seller_receive' => $sellerReceive,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'payment_method' => 'wallet',
                'payment_status' => 'unpaid',
                'shipping_address' => $request->shipping_address,
                'note' => $request->note,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Don hang da duoc tao. Vui long thanh toan.',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'listing' => [
                        'id' => $listing->id,
                        'title' => $listing->title,
                        'image' => $listing->main_image,
                    ],
                    'product_type' => $order->product_type,
                    'quantity' => $order->quantity,
                    'unit_price' => $order->unit_price,
                    'total_amount' => $order->total_amount,
                    'shipping_fee' => $order->shipping_fee,
                    'platform_fee' => $order->platform_fee,
                    'final_amount' => $order->final_amount,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'shop' => [
                        'id' => $shop?->id,
                        'name' => $shop?->name,
                        'is_verified' => $shopVerified,
                    ],
                    'seller' => [
                        'id' => $sellerId,
                        'commission_rate' => $commissionRate,
                        'subscription_badge' => $seller?->getSubscriptionBadge(),
                    ],
                    'requires_payment' => true,
                    'created_at' => $order->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Xác định loại sản phẩm (physical hoặc digital)
     */
    private function determineProductType(Listing $listing): string
    {
        // Dựa vào type của listing
        $digitalTypes = ['software', 'digital', 'service', 'online', 'ebook', 'course', 'subscription'];
        
        $type = strtolower($listing->type ?? '');
        $category = strtolower($listing->category ?? '');
        
        if (in_array($type, $digitalTypes) || in_array($category, $digitalTypes)) {
            return 'digital';
        }
        
        // Kiểm tra trong meta
        if (isset($listing->meta['product_type']) && $listing->meta['product_type'] === 'digital') {
            return 'digital';
        }
        
        return 'physical';
    }
    
    /**
     * Kiểm tra đã có lịch sử chat giữa buyer và seller chưa
     */
    private function checkChatHistory(int $buyerId, int $sellerId): bool
    {
        return ChatMessage::where(function($q) use ($buyerId, $sellerId) {
            $q->where('from_user_id', $buyerId)->where('to_user_id', $sellerId);
        })->orWhere(function($q) use ($buyerId, $sellerId) {
            $q->where('from_user_id', $sellerId)->where('to_user_id', $buyerId);
        })->exists();
    }
    
    /**
     * POST /api/orders/{id}/pay
     * Thanh toán đơn hàng bằng ví
     */
    public function payOrder(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::with(['listing.shop', 'seller'])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang khong ton tai'
                ], 404);
            }

            // Chỉ buyer mới được thanh toán
            if ($order->buyer_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ban khong co quyen thanh toan don hang nay'
                ], 403);
            }

            // Kiểm tra trạng thái
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang da duoc thanh toan'
                ], 400);
            }

            if ($order->status === 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang da bi huy'
                ], 400);
            }

            // Kiểm tra shop đã xác minh chưa
            $shop = $order->listing?->shop;
            if (!$shop || !$shop->is_verified) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shop chua duoc xac minh. Nguoi ban chua the nhan tien. Vui long lien he ho tro.',
                    'shop_verified' => false,
                    'shop_name' => $shop?->name,
                ], 422);
            }

            // Kiểm tra ví của buyer
            $wallet = Wallet::where('user_id', $user->id)->first();
            
            if (!$wallet || $wallet->available_balance < $order->final_amount) {
                $currentBalance = $wallet ? $wallet->available_balance : 0;
                $needMore = $order->final_amount - $currentBalance;
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'So du vi khong du. Vui long nap them tien.',
                    'wallet_balance' => $currentBalance,
                    'order_amount' => $order->final_amount,
                    'need_more' => $needMore,
                    'requires_deposit' => true,
                ], 422);
            }

            DB::beginTransaction();

            // Trừ tiền từ ví buyer
            $buyerTransaction = $wallet->deduct(
                $order->final_amount, 
                'payment', 
                'order', 
                $order->id, 
                'Thanh toan don hang #' . $order->order_number
            );

            // Chuyển tiền cho seller (trừ phí sàn)
            $sellerWallet = Wallet::firstOrCreate(
                ['user_id' => $order->seller_id],
                ['balance' => 0, 'frozen_balance' => 0, 'currency' => 'VND', 'status' => 'active']
            );
            
            $sellerTransaction = $sellerWallet->credit(
                $order->seller_receive,
                'receive',
                'order',
                $order->id,
                'Nhan tien don hang #' . $order->order_number
            );

            // Lấy thông tin liên hệ
            $seller = $order->seller;
            $sellerContact = [
                'name' => $seller->full_name,
                'phone' => $seller->phone ?? $shop->phone,
                'email' => $seller->email ?? $shop->email,
            ];
            
            $buyerContact = [
                'name' => $user->full_name,
                'phone' => $user->phone ?? $order->shipping_address['phone'] ?? null,
                'email' => $user->email,
            ];

            // Cập nhật order
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'wallet',
                'wallet_transaction_id' => $buyerTransaction->id ?? null,
                'paid_at' => now(),
                'seller_received_at' => now(),
                'seller_wallet_transaction_id' => $sellerTransaction->id ?? null,
                'status' => $order->product_type === 'digital' ? 'completed' : 'confirmed',
                'seller_contact' => $sellerContact,
                'buyer_contact' => $buyerContact,
            ]);

            // Giảm stock
            if ($order->listing && $order->listing->stock_qty > 0) {
                $order->listing->decrement('stock_qty', $order->quantity);
            }

            // Thông báo cho seller
            Notification::create([
                'user_id' => $order->seller_id,
                'title' => 'Don hang moi da thanh toan',
                'message' => 'Don hang #' . $order->order_number . ' da duoc thanh toan. Ban nhan duoc ' . number_format($order->seller_receive, 0, ',', '.') . ' VND.',
                'type' => 'order',
                'data' => ['order_id' => $order->id],
            ]);

            // Thông báo cho buyer
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Thanh toan thanh cong',
                'message' => 'Ban da thanh toan ' . number_format($order->final_amount, 0, ',', '.') . ' VND cho don hang #' . $order->order_number,
                'type' => 'order',
                'data' => ['order_id' => $order->id],
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Thanh toan thanh cong!',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'paid_at' => $order->paid_at,
                    ],
                    'seller_contact' => $sellerContact,
                    'product_type' => $order->product_type,
                    'next_step' => $order->product_type === 'digital' 
                        ? 'Lien he nguoi ban de nhan san pham' 
                        : 'Cho nguoi ban giao hang',
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/orders/{id}
     * Cập nhật đơn hàng (seller xác nhận, cập nhật tracking)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permission - only seller/shop owner can update
            if ($user->role !== 'admin' && $order->seller_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only shop owner can update order status'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,processing,shipping,delivered,completed,cancelled',
                'tracking_number' => 'nullable|string|max:100',
                'note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Validate status flow
            if (!$order->canUpdate()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update order that is completed, cancelled or refunded'
                ], 400);
            }

            DB::beginTransaction();

            $updateData = ['status' => $request->status];

            // Update timestamps based on status
            if ($request->status === 'shipping' && !$order->shipped_at) {
                $updateData['shipped_at'] = now();
            }

            if ($request->status === 'delivered' && !$order->delivered_at) {
                $updateData['delivered_at'] = now();
                $updateData['payment_status'] = 'paid'; // Auto mark as paid when delivered
            }

            if ($request->has('tracking_number')) {
                $updateData['tracking_number'] = $request->tracking_number;
            }

            $order->update($updateData);

            // Send notification to buyer
            Notification::create([
                'user_id' => $order->buyer_id,
                'title' => 'Cập nhật đơn hàng',
                'message' => "Đơn hàng #{$order->order_number} đã được cập nhật: " . $request->status,
                'type' => 'order',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order updated successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'tracking_number' => $order->tracking_number,
                    'updated_at' => $order->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/orders/{id}
     * Hủy đơn hàng
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permission
            $isBuyer = $order->buyer_id == $user->id;
            $isSeller = $order->seller_id == $user->id;
            $isAdmin = $user->role === 'admin';

            if (!$isBuyer && !$isSeller && !$isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to cancel this order'
                ], 403);
            }

            // Check if can cancel
            if (!$order->canCancel()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel order that is already shipped or delivered'
                ], 400);
            }

            DB::beginTransaction();

            $cancelReason = $request->input('cancel_reason', 'Cancelled by ' . ($isBuyer ? 'buyer' : 'seller'));

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $cancelReason,
            ]);

            // Restore stock if available
            if ($order->listing && isset($order->listing->stock)) {
                $order->listing->increment('stock', $order->quantity);
            }

            // Send notification
            $notifyUserId = $isBuyer ? $order->seller_id : $order->buyer_id;
            if ($notifyUserId) {
                Notification::create([
                    'user_id' => $notifyUserId,
                    'title' => 'Đơn hàng đã bị hủy',
                    'message' => "Đơn hàng #{$order->order_number} đã bị hủy. Lý do: {$cancelReason}",
                    'type' => 'order',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order cancelled successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'cancel_reason' => $order->cancel_reason,
                    'cancelled_at' => $order->cancelled_at,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/orders/{id}/confirm-received
     * Buyer xác nhận đã nhận hàng
     */
    public function confirmReceived(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang khong ton tai'
                ], 404);
            }

            // Chỉ buyer mới được xác nhận
            if ($order->buyer_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ban khong co quyen xac nhan don hang nay'
                ], 403);
            }

            // Kiểm tra trạng thái - chỉ xác nhận khi đang giao hoặc đã giao
            if (!in_array($order->status, ['shipping', 'delivered'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Chi co the xac nhan don hang dang giao hoac da giao'
                ], 400);
            }

            $order->update([
                'status' => 'completed',
                'delivered_at' => $order->delivered_at ?? now(),
            ]);

            // Thông báo cho seller
            Notification::create([
                'user_id' => $order->seller_id,
                'title' => 'Nguoi mua da xac nhan nhan hang',
                'message' => "Don hang #{$order->order_number} da duoc nguoi mua xac nhan nhan hang thanh cong.",
                'type' => 'order',
                'data' => ['order_id' => $order->id],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Da xac nhan nhan hang thanh cong!',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'can_review' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/orders/{id}/request-refund
     * Buyer yêu cầu hoàn tiền
     */
    public function requestRefund(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
                'evidence_images' => 'nullable|array',
                'evidence_images.*' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Du lieu khong hop le',
                    'errors' => $validator->errors()
                ], 400);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang khong ton tai'
                ], 404);
            }

            // Chỉ buyer mới được yêu cầu hoàn tiền
            if ($order->buyer_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ban khong co quyen yeu cau hoan tien don hang nay'
                ], 403);
            }

            // Kiểm tra đã thanh toán chưa
            if ($order->payment_status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang chua thanh toan, khong the yeu cau hoan tien'
                ], 400);
            }

            // Kiểm tra trạng thái - không cho hoàn tiền đơn đã hoàn tiền hoặc đã hủy
            if (in_array($order->status, ['refunded', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang da bi huy hoac da hoan tien'
                ], 400);
            }

            // Cập nhật order với yêu cầu hoàn tiền
            $order->update([
                'refund_requested_at' => now(),
                'refund_reason' => $request->reason,
                'refund_evidence' => $request->evidence_images,
            ]);

            // Thông báo cho seller
            Notification::create([
                'user_id' => $order->seller_id,
                'title' => 'Yeu cau hoan tien',
                'message' => "Nguoi mua yeu cau hoan tien don hang #{$order->order_number}. Ly do: {$request->reason}",
                'type' => 'order',
                'data' => ['order_id' => $order->id],
            ]);

            // Thông báo cho admin
            $admins = User::where('role', 'admin')->pluck('id');
            foreach ($admins as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'title' => 'Yeu cau hoan tien moi',
                    'message' => "Don hang #{$order->order_number} co yeu cau hoan tien. Ly do: {$request->reason}",
                    'type' => 'order',
                    'data' => ['order_id' => $order->id],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Da gui yeu cau hoan tien. Vui long cho admin xu ly.',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'refund_reason' => $order->refund_reason,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/orders/{id}/process-refund (Admin only)
     * Admin xử lý yêu cầu hoàn tiền
     */
    public function processRefund(Request $request, $id)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Chi admin moi co quyen xu ly hoan tien'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:approve,reject',
                'admin_note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Du lieu khong hop le',
                    'errors' => $validator->errors()
                ], 400);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang khong ton tai'
                ], 404);
            }

            if (!$order->refund_requested_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Don hang chua co yeu cau hoan tien'
                ], 400);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Hoàn tiền cho buyer
                $buyerWallet = Wallet::firstOrCreate(
                    ['user_id' => $order->buyer_id],
                    ['balance' => 0, 'frozen_balance' => 0, 'currency' => 'VND', 'status' => 'active']
                );
                $buyerWallet->credit(
                    $order->final_amount,
                    'refund',
                    'order',
                    $order->id,
                    'Hoan tien don hang #' . $order->order_number
                );

                // Trừ tiền từ seller (nếu đã nhận)
                if ($order->seller_received_at) {
                    $sellerWallet = Wallet::where('user_id', $order->seller_id)->first();
                    if ($sellerWallet && $sellerWallet->available_balance >= $order->seller_receive) {
                        $sellerWallet->deduct(
                            $order->seller_receive,
                            'refund',
                            'order',
                            $order->id,
                            'Hoan tien don hang #' . $order->order_number
                        );
                    }
                }

                $order->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                    'refund_processed_at' => now(),
                    'refund_processed_by' => $user->id,
                    'refund_admin_note' => $request->admin_note,
                ]);

                // Thông báo cho buyer
                Notification::create([
                    'user_id' => $order->buyer_id,
                    'title' => 'Hoan tien thanh cong',
                    'message' => "Don hang #{$order->order_number} da duoc hoan tien " . number_format($order->final_amount, 0, ',', '.') . " VND.",
                    'type' => 'wallet',
                    'data' => ['order_id' => $order->id],
                ]);

                // Thông báo cho seller
                Notification::create([
                    'user_id' => $order->seller_id,
                    'title' => 'Don hang da hoan tien',
                    'message' => "Don hang #{$order->order_number} da duoc hoan tien cho nguoi mua.",
                    'type' => 'order',
                    'data' => ['order_id' => $order->id],
                ]);
            } else {
                // Từ chối hoàn tiền
                $order->update([
                    'refund_processed_at' => now(),
                    'refund_processed_by' => $user->id,
                    'refund_admin_note' => $request->admin_note ?? 'Yeu cau hoan tien bi tu choi',
                ]);

                // Thông báo cho buyer
                Notification::create([
                    'user_id' => $order->buyer_id,
                    'title' => 'Yeu cau hoan tien bi tu choi',
                    'message' => "Yeu cau hoan tien don hang #{$order->order_number} da bi tu choi. " . ($request->admin_note ? "Ly do: {$request->admin_note}" : ''),
                    'type' => 'order',
                    'data' => ['order_id' => $order->id],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $request->action === 'approve' ? 'Da hoan tien thanh cong' : 'Da tu choi yeu cau hoan tien',
                'data' => $order->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/orders/my-purchases
     * Lấy danh sách đơn hàng mình đã mua (buyer)
     */
    public function myPurchases(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = Order::with(['seller:id,full_name,email', 'shop:id,name,phone', 'listing:id,title,images,price_cents', 'review:id,order_id,rating'])
                ->where('buyer_id', $user->id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $query->orderBy('created_at', 'desc');
            $orders = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => 'success',
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/orders/my-sales
     * Lấy danh sách đơn hàng mình đã bán (seller)
     */
    public function mySales(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = Order::with(['buyer:id,full_name,email,phone', 'listing:id,title,images,price_cents'])
                ->where('seller_id', $user->id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $query->orderBy('created_at', 'desc');
            $orders = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => 'success',
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/orders/stats
     * Thống kê đơn hàng của user
     */
    public function stats(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Thống kê đơn mua
            $purchases = Order::where('buyer_id', $user->id);
            $purchaseStats = [
                'total' => (clone $purchases)->count(),
                'pending' => (clone $purchases)->where('status', 'pending')->count(),
                'confirmed' => (clone $purchases)->where('status', 'confirmed')->count(),
                'shipping' => (clone $purchases)->where('status', 'shipping')->count(),
                'delivered' => (clone $purchases)->where('status', 'delivered')->count(),
                'completed' => (clone $purchases)->where('status', 'completed')->count(),
                'cancelled' => (clone $purchases)->where('status', 'cancelled')->count(),
                'refunded' => (clone $purchases)->where('status', 'refunded')->count(),
            ];

            // Thống kê đơn bán (nếu là seller)
            $sales = Order::where('seller_id', $user->id);
            $salesStats = [
                'total' => (clone $sales)->count(),
                'pending' => (clone $sales)->where('status', 'pending')->count(),
                'confirmed' => (clone $sales)->where('status', 'confirmed')->count(),
                'shipping' => (clone $sales)->where('status', 'shipping')->count(),
                'delivered' => (clone $sales)->where('status', 'delivered')->count(),
                'completed' => (clone $sales)->where('status', 'completed')->count(),
                'cancelled' => (clone $sales)->where('status', 'cancelled')->count(),
                'total_revenue' => (clone $sales)->where('payment_status', 'paid')->sum('seller_receive'),
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'purchases' => $purchaseStats,
                    'sales' => $salesStats,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
