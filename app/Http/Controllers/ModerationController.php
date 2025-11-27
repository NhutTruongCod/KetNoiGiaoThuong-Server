<?php

namespace App\Http\Controllers;

use App\Models\ModerationReport;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModerationController extends Controller
{
    /**
     * POST /api/moderation/report
     * Gửi báo cáo vi phạm
     */
    public function report(Request $request)
    {
        try {
            $user = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'target_user_id' => 'nullable|exists:users,id|different:reporter_id',
                'target_post_id' => 'nullable|exists:trade_posts,id',
                'reason' => 'required|string|max:255|min:10',
            ]);

            // Custom validation: phải có ít nhất 1 target
            $validator->after(function ($validator) use ($request) {
                if (!$request->target_user_id && !$request->target_post_id) {
                    $validator->errors()->add('target', 'Must specify either target_user_id or target_post_id');
                }

                if ($request->target_user_id && $request->target_post_id) {
                    $validator->errors()->add('target', 'Cannot report both user and post at the same time');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Kiểm tra không tự báo cáo mình
            if ($request->target_user_id == $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot report yourself'
                ], 400);
            }

            // Kiểm tra không báo cáo trùng lặp trong 24h
            $existingReport = ModerationReport::where('reporter_id', $user->id)
                ->where(function ($query) use ($request) {
                    if ($request->target_user_id) {
                        $query->where('target_user_id', $request->target_user_id);
                    }
                    if ($request->target_post_id) {
                        $query->where('target_post_id', $request->target_post_id);
                    }
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existingReport) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already reported this within 24 hours'
                ], 409);
            }

            // Tạo báo cáo
            $report = ModerationReport::create([
                'reporter_id' => $user->id,
                'target_user_id' => $request->target_user_id,
                'target_post_id' => $request->target_post_id,
                'reason' => $request->reason,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Report submitted successfully',
                'data' => [
                    'id' => $report->id,
                    'status' => $report->status,
                    'target_type' => $report->target_type,
                    'created_at' => $report->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/moderation/my-reports
     * Xem báo cáo đã gửi của user hiện tại
     */
    public function myReports(Request $request)
    {
        try {
            $user = auth('api')->user();

            $reports = ModerationReport::byReporter($user->id)
                ->with(['targetUser:id,full_name', 'reviewedBy:id,full_name'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'status' => 'success',
                'data' => $reports->items(),
                'meta' => [
                    'current_page' => $reports->currentPage(),
                    'total_pages' => $reports->lastPage(),
                    'total_items' => $reports->total(),
                    'per_page' => $reports->perPage(),
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
     * GET /api/moderation/reports
     * Admin xem tất cả báo cáo (có filter)
     */
    public function getReports(Request $request)
    {
        try {
            $query = ModerationReport::with([
                'reporter:id,full_name,email',
                'targetUser:id,full_name,email',
                'reviewedBy:id,full_name'
            ]);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by target type
            if ($request->target_type === 'user') {
                $query->whereNotNull('target_user_id');
            } elseif ($request->target_type === 'post') {
                $query->whereNotNull('target_post_id');
            }

            // Search by reporter or target user
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('reporter', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('targetUser', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $reports = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $reports->items(),
                'meta' => [
                    'current_page' => $reports->currentPage(),
                    'total_pages' => $reports->lastPage(),
                    'total_items' => $reports->total(),
                    'per_page' => $reports->perPage(),
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
     * GET /api/moderation/reports/{id}
     * Admin xem chi tiết báo cáo
     */
    public function getReport($id)
    {
        try {
            $report = ModerationReport::with([
                'reporter:id,full_name,email,created_at',
                'targetUser:id,full_name,email,created_at',
                'reviewedBy:id,full_name'
            ])->find($id);

            if (!$report) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $report
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
     * PUT /api/moderation/reports/{id}/resolve
     * Admin xử lý báo cáo (duyệt/từ chối)
     */
    public function resolveReport(Request $request, $id)
    {
        try {
            $admin = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:action_taken,dismissed',
                'admin_note' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            $report = ModerationReport::find($id);
            if (!$report) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report not found'
                ], 404);
            }

            if ($report->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report has already been processed'
                ], 409);
            }

            // Update report
            $report->status = $request->action;
            $report->reviewed_by = $admin->id;
            $report->reviewed_at = now();
            $report->save();

            // TODO: Implement actual actions based on action_taken
            // - If action_taken: ban user, delete post, send warning, etc.
            // - If dismissed: just mark as resolved

            return response()->json([
                'status' => 'success',
                'message' => 'Report resolved successfully',
                'data' => [
                    'id' => $report->id,
                    'status' => $report->status,
                    'reviewed_by' => $admin->full_name,
                    'reviewed_at' => $report->reviewed_at,
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
     * DELETE /api/moderation/reports/{id}
     * Admin xóa báo cáo
     */
    public function deleteReport($id)
    {
        try {
            $report = ModerationReport::find($id);
            if (!$report) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report not found'
                ], 404);
            }

            $report->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Report deleted successfully'
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
