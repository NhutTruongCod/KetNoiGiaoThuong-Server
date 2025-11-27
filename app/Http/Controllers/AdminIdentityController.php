<?php

namespace App\Http\Controllers;

use App\Models\IdentityVerificationRequest;
use Illuminate\Http\Request;

class AdminIdentityController extends Controller
{
    /**
     * GET /api/identity/verify-requests
     * Admin xem danh tất cả yêu cầu xác minh (có filter + phân trang)
     */
    public function getVerifyRequests(Request $request)
    {
        try {
            $query = IdentityVerificationRequest::with(['user:id,full_name,email', 'approver:id,full_name,email']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('from')) {
                $query->where('created_at', '>=', $request->from);
            }

            if ($request->filled('to')) {
                $query->where('created_at', '<=', $request->to);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $perPage = (int) $request->get('per_page', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

            $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $requests->items(),
                'meta' => [
                    'current_page' => $requests->currentPage(),
                    'total_pages' => $requests->lastPage(),
                    'total_items' => $requests->total(),
                    'per_page' => $requests->perPage(),
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
     * GET /api/identity/verify-requests/{id}
     * Admin xem chi tiết một yêu cầu xác minh
     */
    public function getVerifyRequest($id)
    {
        try {
            $requestRecord = IdentityVerificationRequest::with([
                'user:id,full_name,email,created_at',
                'approver:id,full_name,email'
            ])->find($id);

            if (!$requestRecord) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification request not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $requestRecord
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

