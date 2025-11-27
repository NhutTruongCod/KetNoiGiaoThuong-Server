<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;

class PlanController extends Controller
{
    /**
     * GET /api/plans
     * Danh sach cac goi dang hoat dong
     */
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $plans,
        ]);
    }

    /**
     * GET /api/plans/{id}
     * Chi tiet mot goi cu the
     */
    public function show($id)
    {
        $plan = SubscriptionPlan::where('is_active', true)->find($id);

        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Plan not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $plan,
        ]);
    }
}

