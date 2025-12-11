<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginHistoryController extends Controller
{
    /**
     * 1. GET /login-history - User xem lịch sử đăng nhập của mình
     */
    public function myHistory(Request $request)
    {
        $user = $request->user('api');

        $query = LoginHistory::where('user_id', $user->id);

        // Filters
        if ($request->has('date_from')) {
            $query->where('login_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('login_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $history = $query->orderBy('login_at', 'desc')->paginate($perPage);

        // Summary statistics
        $summary = [
            'total_logins' => LoginHistory::where('user_id', $user->id)->count(),
            'successful_logins' => LoginHistory::where('user_id', $user->id)->successful()->count(),
            'failed_logins' => LoginHistory::where('user_id', $user->id)->failed()->count(),
            'unique_devices' => LoginHistory::where('user_id', $user->id)
                ->distinct('device_type')
                ->count('device_type'),
            'last_login' => LoginHistory::where('user_id', $user->id)
                ->successful()
                ->orderBy('login_at', 'desc')
                ->value('login_at'),
        ];

        return response()->json([
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * 2. GET /admin/login-history - Admin xem tất cả lịch sử
     */
    public function adminIndex(Request $request)
    {
        $query = LoginHistory::with('user:id,full_name,email,role');

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('is_successful')) {
            $isSuccessful = filter_var($request->is_successful, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_successful', $isSuccessful);
        }

        if ($request->has('ip_address')) {
            $query->byIp($request->ip_address);
        }

        if ($request->has('device_type')) {
            $query->byDevice($request->device_type);
        }

        if ($request->has('date_from') || $request->has('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort', 'login_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);

        // Pagination
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $history = $query->paginate($perPage);

        // Summary statistics for today
        $today = now()->startOfDay();
        $summary = [
            'total_logins_today' => LoginHistory::where('login_at', '>=', $today)->count(),
            'successful_logins_today' => LoginHistory::where('login_at', '>=', $today)->successful()->count(),
            'failed_logins_today' => LoginHistory::where('login_at', '>=', $today)->failed()->count(),
            'unique_users_today' => LoginHistory::where('login_at', '>=', $today)
                ->distinct('user_id')
                ->count('user_id'),
            'device_breakdown' => [
                'desktop' => LoginHistory::where('login_at', '>=', $today)->byDevice('desktop')->count(),
                'mobile' => LoginHistory::where('login_at', '>=', $today)->byDevice('mobile')->count(),
                'tablet' => LoginHistory::where('login_at', '>=', $today)->byDevice('tablet')->count(),
            ],
        ];

        return response()->json([
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * 3. GET /admin/users/{userId}/login-history - Admin xem lịch sử của user cụ thể
     */
    public function adminUserHistory(Request $request, $userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $query = LoginHistory::where('user_id', $user->id);

        // Filters
        if ($request->has('is_successful')) {
            $isSuccessful = filter_var($request->is_successful, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_successful', $isSuccessful);
        }

        if ($request->has('date_from') || $request->has('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $history = $query->orderBy('login_at', 'desc')->paginate($perPage);

        // Summary statistics for this user
        $allHistory = LoginHistory::where('user_id', $user->id);
        
        $summary = [
            'total_logins' => $allHistory->count(),
            'successful_logins' => $allHistory->successful()->count(),
            'failed_logins' => $allHistory->failed()->count(),
            'unique_ips' => LoginHistory::where('user_id', $user->id)
                ->distinct('ip_address')
                ->count('ip_address'),
            'unique_devices' => LoginHistory::where('user_id', $user->id)
                ->distinct('device_type')
                ->count('device_type'),
            'first_login' => LoginHistory::where('user_id', $user->id)
                ->orderBy('login_at', 'asc')
                ->value('login_at'),
            'last_login' => LoginHistory::where('user_id', $user->id)
                ->orderBy('login_at', 'desc')
                ->value('login_at'),
            'most_used_device' => LoginHistory::where('user_id', $user->id)
                ->select('device_type', DB::raw('count(*) as count'))
                ->groupBy('device_type')
                ->orderBy('count', 'desc')
                ->value('device_type'),
            'most_used_browser' => LoginHistory::where('user_id', $user->id)
                ->select('browser', DB::raw('count(*) as count'))
                ->groupBy('browser')
                ->orderBy('count', 'desc')
                ->value('browser'),
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ],
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ],
            'summary' => $summary,
        ]);
    }
}

