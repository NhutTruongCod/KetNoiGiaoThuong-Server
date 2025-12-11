<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\Notification;
use App\Models\User;
use App\Models\PlatformRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * 1. POST /subscriptions - Đăng ký gói mới
     */
    public function subscribe(Request $request)
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|in:vnpay,momo,bank_transfer',
            'duration_months' => 'nullable|integer|in:1,3,6,12',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user already has active subscription
        $activeSubscription = UserSubscription::where('user_id', $user->id)
            ->active()
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'message' => 'You already have an active subscription. Please cancel it first or wait until it expires.'
            ], 400);
        }

        $plan = SubscriptionPlan::active()->find($request->plan_id);
        if (!$plan) {
            return response()->json([
                'message' => 'Plan not available'
            ], 404);
        }

        $durationMonths = $request->duration_months ?? 1;

        // Calculate pricing with discount
        $pricing = $plan->calculatePrice($durationMonths);

        // TODO: Apply coupon if provided
        // For now, just use calculated pricing

        $startDate = now()->toDateString();
        $endDate = now()->addMonths($durationMonths)->toDateString();

        // Create subscription
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration_months' => $durationMonths,
            'price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_amount' => $pricing['final_amount'],
            'payment_method' => $request->payment_method,
            'coupon_code' => $request->coupon_code,
            'status' => 'pending',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'started_at' => now(),
            'expires_at' => now()->addMonths($durationMonths),
            'is_active' => false,
        ]);

        // Generate payment URL (mock for now)
        $paymentUrl = $this->generatePaymentUrl($subscription, $request->payment_method);

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Đăng ký gói thành viên',
            'message' => "Bạn đã đăng ký gói {$plan->name}. Vui lòng thanh toán để kích hoạt.",
            'data' => ['subscription_id' => $subscription->id],
            'icon' => 'credit-card',
            'priority' => 'high',
        ]);

        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                ],
                'duration_months' => $subscription->duration_months,
                'price' => $subscription->price,
                'discount_amount' => $subscription->discount_amount,
                'final_amount' => $subscription->final_amount,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'payment_method' => $subscription->payment_method,
                'payment_url' => $paymentUrl,
                'created_at' => $subscription->created_at,
            ]
        ], 201);
    }

    /**
     * 2. PUT /subscriptions/{id}/renew - Gia hạn gói
     */
    public function renew(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)->find($id);
        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration_months' => 'nullable|integer|in:1,3,6,12',
            'payment_method' => 'required|in:vnpay,momo,bank_transfer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $plan = $subscription->plan;
        if (!$plan || !$plan->is_active) {
            return response()->json([
                'message' => 'Plan not available for renewal'
            ], 400);
        }

        $durationMonths = $request->duration_months ?? 1;

        // Calculate pricing with discount
        $pricing = $plan->calculatePrice($durationMonths);

        $oldEndDate = $subscription->end_date;
        
        // Calculate new end date from current end date or now (whichever is later)
        $baseDate = Carbon::parse($subscription->end_date)->isFuture() 
            ? Carbon::parse($subscription->end_date) 
            : Carbon::now();
        
        $newEndDate = $baseDate->addMonths($durationMonths)->toDateString();

        // Update subscription
        $subscription->update([
            'duration_months' => $durationMonths,
            'price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_amount' => $pricing['final_amount'],
            'payment_method' => $request->payment_method,
            'end_date' => $newEndDate,
            'expires_at' => Carbon::parse($newEndDate),
            'status' => 'pending',
        ]);

        // Generate payment URL
        $paymentUrl = $this->generatePaymentUrl($subscription, $request->payment_method);

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Gia hạn gói thành viên',
            'message' => "Bạn đã gia hạn gói {$plan->name}. Vui lòng thanh toán.",
            'data' => ['subscription_id' => $subscription->id],
            'icon' => 'refresh',
            'priority' => 'normal',
        ]);

        return response()->json([
            'message' => 'Subscription renewed successfully',
            'data' => [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'duration_months' => $subscription->duration_months,
                'price' => $subscription->price,
                'discount_amount' => $subscription->discount_amount,
                'final_amount' => $subscription->final_amount,
                'old_end_date' => $oldEndDate,
                'new_end_date' => $subscription->end_date,
                'payment_url' => $paymentUrl,
                'created_at' => $subscription->created_at,
            ]
        ]);
    }

    /**
     * 3. GET /subscriptions/current - Gói hiện tại
     */
    public function current(Request $request)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::with('plan')
            ->where('user_id', $user->id)
            ->active()
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        return response()->json([
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'slug' => $subscription->plan->slug,
                'price' => $subscription->plan->price,
                'features' => $subscription->plan->features,
            ],
            'status' => $subscription->status,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'days_remaining' => $subscription->days_remaining,
            'usage' => $subscription->usage,
            'auto_renew' => $subscription->auto_renew,
            'created_at' => $subscription->created_at,
            'updated_at' => $subscription->updated_at,
        ]);
    }

    /**
     * 4. GET /subscriptions/history - Lịch sử
     */
    public function history(Request $request)
    {
        $user = $request->user('api');

        $query = UserSubscription::with('plan:id,name,price')
            ->where('user_id', $user->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    /**
     * 5. DELETE /subscriptions/{id}/cancel - Hủy gói
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->find($id);

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found or already inactive'
            ], 404);
        }

        // Calculate refund
        $refundInfo = $subscription->calculateRefund();

        // Cancel subscription
        $subscription->cancel($request->reason);

        $plan = $subscription->plan;

        // Create notification
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Gói thành viên đã được hủy',
            'message' => $plan ? "Gói {$plan->name} của bạn đã được hủy." : 'Gói thành viên đã được hủy.',
            'data' => [
                'subscription_id' => $subscription->id,
                'refund_amount' => $refundInfo['refund_amount'],
            ],
            'icon' => 'x-circle',
            'priority' => 'normal',
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'data' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'cancelled_at' => $subscription->canceled_at,
                'refund_amount' => $refundInfo['refund_amount'],
                'refund_note' => $refundInfo['refund_note'],
            ]
        ]);
    }

    /**
     * 6. GET /subscriptions/plans - Danh sách gói
     */
    public function plans(Request $request)
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'duration_days' => $plan->duration_days,
                    'commission_rate' => $plan->commission_rate,
                    'search_boost' => $plan->search_boost,
                    'free_promotions' => $plan->free_promotions,
                    'badge' => $plan->badge,
                    'features' => $plan->features,
                    'benefits' => $plan->benefits,
                    'is_popular' => $plan->is_popular,
                    'pricing' => [
                        '1_month' => $plan->calculatePrice(1),
                        '3_months' => $plan->calculatePrice(3),
                        '6_months' => $plan->calculatePrice(6),
                        '12_months' => $plan->calculatePrice(12),
                    ],
                ];
            });

        return response()->json([
            'data' => $plans
        ]);
    }

    /**
     * 7. POST /subscriptions/{id}/confirm-transfer - User xác nhận đã chuyển khoản
     */
    public function confirmTransfer(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->find($id);

        if (!$subscription) {
            return response()->json([
                'message' => 'Subscription not found or already processed'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_proof' => 'nullable|string|max:500', // URL ảnh chứng từ
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $subscription->update([
            'status' => 'processing',
            'payment_proof' => $request->payment_proof,
        ]);

        // Notify admins
        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'system',
                'title' => 'Yêu cầu duyệt gói đăng ký',
                'message' => "{$user->full_name} đã xác nhận chuyển khoản cho gói {$subscription->plan->name}. Mã: {$subscription->payment_code}",
                'data' => ['subscription_id' => $subscription->id],
            ]);
        }

        return response()->json([
            'message' => 'Đã xác nhận chuyển khoản. Vui lòng chờ admin duyệt.',
            'data' => $subscription->fresh(['plan'])
        ]);
    }

    /**
     * 8. GET /admin/subscriptions - Admin xem danh sách đăng ký
     */
    public function adminList(Request $request)
    {
        $query = UserSubscription::with(['user:id,full_name,email', 'plan:id,name,price,badge']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    /**
     * 9. PUT /admin/subscriptions/{id}/approve - Admin duyệt đăng ký
     */
    public function adminApprove(Request $request, $id)
    {
        $admin = $request->user('api');

        $subscription = UserSubscription::with(['user', 'plan'])->find($id);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        if (!in_array($subscription->status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Subscription already processed'], 400);
        }

        DB::beginTransaction();
        try {
            // Activate subscription
            $subscription->update([
                'status' => 'active',
                'is_active' => true,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_note' => $request->admin_note,
            ]);

            // Update user's subscription
            $subscription->user->update([
                'subscription_plan_id' => $subscription->plan_id,
                'subscription_expires_at' => $subscription->expires_at,
            ]);

            // Record platform revenue
            if ($subscription->final_amount > 0) {
                PlatformRevenue::record(
                    PlatformRevenue::TYPE_SUBSCRIPTION_FEE,
                    $subscription->final_amount,
                    'user_subscription',
                    $subscription->id,
                    $subscription->user_id,
                    "Gói {$subscription->plan->name} - {$subscription->duration_months} tháng"
                );
            }

            // Notify user
            Notification::create([
                'user_id' => $subscription->user_id,
                'type' => 'system',
                'title' => 'Gói đăng ký đã được kích hoạt',
                'message' => "Gói {$subscription->plan->name} của bạn đã được kích hoạt. Hết hạn: " . $subscription->end_date->format('d/m/Y'),
                'data' => ['subscription_id' => $subscription->id],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Đã duyệt và kích hoạt gói đăng ký',
                'data' => $subscription->fresh(['user', 'plan'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 10. PUT /admin/subscriptions/{id}/reject - Admin từ chối đăng ký
     */
    public function adminReject(Request $request, $id)
    {
        $admin = $request->user('api');

        $subscription = UserSubscription::with(['user', 'plan'])->find($id);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        if (!in_array($subscription->status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Subscription already processed'], 400);
        }

        $subscription->update([
            'status' => 'rejected',
            'is_active' => false,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'admin_note' => $request->admin_note ?? 'Không xác nhận được giao dịch',
        ]);

        // Notify user
        Notification::create([
            'user_id' => $subscription->user_id,
            'type' => 'system',
            'title' => 'Gói đăng ký bị từ chối',
            'message' => "Yêu cầu đăng ký gói {$subscription->plan->name} bị từ chối. " . ($request->admin_note ? "Lý do: {$request->admin_note}" : ''),
            'data' => ['subscription_id' => $subscription->id],
        ]);

        return response()->json([
            'message' => 'Đã từ chối yêu cầu đăng ký',
            'data' => $subscription->fresh()
        ]);
    }

    /**
     * 11. GET /admin/subscriptions/stats - Thống kê gói đăng ký
     */
    public function adminStats(Request $request)
    {
        $thisMonth = now()->startOfMonth();

        return response()->json([
            'data' => [
                'total_subscriptions' => UserSubscription::count(),
                'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
                'pending_subscriptions' => UserSubscription::whereIn('status', ['pending', 'processing'])->count(),
                'month_revenue' => UserSubscription::where('status', 'active')
                    ->where('approved_at', '>=', $thisMonth)
                    ->sum('final_amount'),
                'total_revenue' => UserSubscription::where('status', 'active')->sum('final_amount'),
                'by_plan' => SubscriptionPlan::withCount(['subscriptions as active_count' => function ($q) {
                    $q->where('status', 'active');
                }])->get(['id', 'name', 'price', 'badge']),
            ]
        ]);
    }

    /**
     * Generate payment URL with QR code
     */
    private function generatePaymentUrl($subscription, $paymentMethod)
    {
        if ($paymentMethod === 'bank_transfer') {
            // Generate payment code
            $paymentCode = 'SUB' . time() . rand(1000, 9999);
            $subscription->update(['payment_code' => $paymentCode]);

            // Bank config
            $bankConfig = [
                'bank_code' => 'VCB',
                'bank_name' => 'Vietcombank',
                'account_number' => '1234567890',
                'account_holder' => 'CONG TY TNHH TRADEHUB',
            ];

            $transferContent = 'GOITV ' . $paymentCode;
            $amount = $subscription->final_amount;
            $accountName = urlencode($bankConfig['account_holder']);
            $encodedContent = urlencode($transferContent);

            $qrUrl = "https://img.vietqr.io/image/{$bankConfig['bank_code']}-{$bankConfig['account_number']}-compact2.png?amount={$amount}&addInfo={$encodedContent}&accountName={$accountName}";

            return [
                'type' => 'bank_transfer',
                'qr_url' => $qrUrl,
                'bank_name' => $bankConfig['bank_name'],
                'bank_code' => $bankConfig['bank_code'],
                'account_number' => $bankConfig['account_number'],
                'account_holder' => $bankConfig['account_holder'],
                'transfer_content' => $transferContent,
                'amount' => $amount,
            ];
        }

        // Other payment methods (mock)
        $baseUrls = [
            'vnpay' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'momo' => 'https://test-payment.momo.vn/gw_payment/transactionProcessor',
        ];

        $baseUrl = $baseUrls[$paymentMethod] ?? null;

        if (!$baseUrl) {
            return null;
        }

        return [
            'type' => $paymentMethod,
            'url' => $baseUrl . '?amount=' . ($subscription->final_amount * 100)
                . '&orderInfo=Subscription-' . $subscription->id
                . '&returnUrl=' . url('/api/subscriptions/payment/callback'),
        ];
    }
}
