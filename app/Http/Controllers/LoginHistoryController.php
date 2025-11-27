<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    /**
     * GET /api/login-history
     * User: view own login history
     */
    public function myHistory(Request $request)
    {
        $user = $request->user('api');

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        $history = LoginHistory::where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'total_pages' => $history->lastPage(),
                'total_items' => $history->total(),
                'per_page' => $history->perPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/login-history
     * Admin: view login history for all users (with filters)
     */
    public function adminIndex(Request $request)
    {
        $query = LoginHistory::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('success')) {
            $success = filter_var($request->success, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($success)) {
                $query->where('success', $success);
            }
        }

        if ($request->filled('from')) {
            $query->where('logged_in_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('logged_in_at', '<=', $request->to);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $history = $query->orderBy('logged_in_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'total_pages' => $history->lastPage(),
                'total_items' => $history->total(),
                'per_page' => $history->perPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/users/{userId}/login-history
     * Admin: view login history by user
     */
    public function adminUserHistory(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $history = LoginHistory::where('user_id', $user->id)
            ->orderBy('logged_in_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'total_pages' => $history->lastPage(),
                'total_items' => $history->total(),
                'per_page' => $history->perPage(),
            ],
        ]);
    }
}

