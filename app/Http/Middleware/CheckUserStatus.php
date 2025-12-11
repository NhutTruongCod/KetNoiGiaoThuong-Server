<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    /**
     * Check if authenticated user is banned, suspended or inactive.
     * If so, return 403 with error code for FE to handle logout.
     * 
     * Database user.status values: 'active', 'suspended', 'banned'
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $next($request);
        }

        // Check if user is banned
        if ($user->status === 'banned') {
            // Invalidate token
            try {
                auth('api')->invalidate(true);
            } catch (\Exception $e) {
                // Token may already be invalid
            }
            
            return response()->json([
                'status' => 'error',
                'error_code' => 'USER_BANNED',
                'message' => 'Tai khoan cua ban da bi khoa vinh vien. Vui long lien he ho tro.',
                'force_logout' => true,
            ], 403);
        }

        // Check if user is suspended
        if ($user->status === 'suspended') {
            // Invalidate token
            try {
                auth('api')->invalidate(true);
            } catch (\Exception $e) {
                // Token may already be invalid
            }
            
            return response()->json([
                'status' => 'error',
                'error_code' => 'USER_SUSPENDED',
                'message' => 'Tai khoan cua ban da bi tam khoa. Vui long lien he ho tro.',
                'force_logout' => true,
            ], 403);
        }

        // Check if user is_active = false (additional check)
        if (isset($user->is_active) && $user->is_active === false) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'USER_INACTIVE',
                'message' => 'Tai khoan cua ban da bi vo hieu hoa.',
                'force_logout' => true,
            ], 403);
        }

        return $next($request);
    }
}
