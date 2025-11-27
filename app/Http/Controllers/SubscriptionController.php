<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\SubscriptionTransaction;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * POST /api/subscriptions
     * Dang ky goi moi
     */
    public function subscribe(Request $request)
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors(),
            ], 400);
        }

        $plan = SubscriptionPlan::where('is_active', true)->find($request->plan_id);
        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Plan not available',
            ], 404);
        }

        // Giả lập thanh toán thành công
        $transaction = SubscriptionTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'payment_method' => $request->payment_method ?? 'free',
            'status' => 'success',
            'created_at' => now(),
        ]);

        // Deactive cac goi hien tai
        UserSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'canceled_at' => now(),
            ]);

        $startedAt = now();
        $expiresAt = now()->addDays($plan->duration_days);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Đăng ký gói thành viên thành công',
            'message' => 'Bạn đã đăng ký gói ' . $plan->name . ' thành công.',
            'type' => 'system',
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription created successfully',
            'data' => [
                'subscription' => $subscription,
                'transaction' => $transaction,
            ],
        ], 201);
    }

    /**
     * PUT /api/subscriptions/{id}/renew
     * Gia han goi hien tai
     */
    public function renew(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)->find($id);
        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subscription not found',
            ], 404);
        }

        $plan = $subscription->plan;
        if (!$plan || !$plan->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Plan not available for renewal',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input data',
                'errors' => $validator->errors(),
            ], 400);
        }

        $transaction = SubscriptionTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'payment_method' => $request->payment_method ?? 'free',
            'status' => 'success',
            'created_at' => now(),
        ]);

        // Neu goi da het han thi bat dau lai tu bay gio
        $baseTime = $subscription->expires_at > now() ? $subscription->expires_at : now();
        $subscription->expires_at = $baseTime->copy()->addDays($plan->duration_days);
        $subscription->is_active = true;
        $subscription->canceled_at = null;
        $subscription->save();

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Gia hạn gói thành viên thành công',
            'message' => 'Gói ' . $plan->name . ' của bạn đã được gia hạn.',
            'type' => 'system',
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription renewed successfully',
            'data' => [
                'subscription' => $subscription,
                'transaction' => $transaction,
            ],
        ]);
    }

    /**
     * GET /api/subscriptions/current
     * Thong tin goi hien tai
     */
    public function current(Request $request)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::with('plan')
            ->active()
            ->where('user_id', $user->id)
            ->orderBy('expires_at', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $subscription,
        ]);
    }

    /**
     * GET /api/subscriptions/history
     * Lich su cac goi da dang ky
     */
    public function history(Request $request)
    {
        $user = $request->user('api');

        $subscriptions = UserSubscription::with('plan')
            ->where('user_id', $user->id)
            ->orderBy('started_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'total_pages' => $subscriptions->lastPage(),
                'total_items' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage(),
            ],
        ]);
    }

    /**
     * DELETE /api/subscriptions/{id}/cancel
     * Huy goi hien tai truoc thoi han (soft cancel)
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user('api');

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->find($id);

        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subscription not found or already inactive',
            ], 404);
        }

        $subscription->is_active = false;
        $subscription->canceled_at = now();
        $subscription->save();

        $plan = $subscription->plan;

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Gói thành viên đã được hủy',
            'message' => $plan ? ('Gói ' . $plan->name . ' của bạn đã được hủy trước thời hạn.') : 'Gói thành viên của bạn đã được hủy.',
            'type' => 'system',
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription cancelled successfully',
        ]);
    }
}

