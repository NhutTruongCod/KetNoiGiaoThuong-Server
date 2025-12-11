<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;

class PlanController extends Controller
{
    /**
     * 1. GET /plans - Danh sách gói thành viên
     */
    public function index()
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return response()->json([
            'data' => $plans
        ]);
    }

    /**
     * 2. GET /plans/{id} - Chi tiết gói thành viên
     */
    public function show($id)
    {
        $plan = SubscriptionPlan::active()->find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found'
            ], 404);
        }

        // Add total_subscribers
        $plan->total_subscribers = $plan->total_subscribers;

        return response()->json($plan);
    }
}

