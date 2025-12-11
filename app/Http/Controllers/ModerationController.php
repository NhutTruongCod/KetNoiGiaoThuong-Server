<?php

namespace App\Http\Controllers;

use App\Models\ModerationReport;
use App\Models\Listing;
use App\Models\Shop;
use App\Models\User;
use App\Models\Review;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
                'reportable_type' => 'required|in:listing,shop,user,review',
                'reportable_id' => 'required|integer',
                'reason' => 'required|in:spam,fraud,inappropriate,fake,copyright,other',
                'description' => 'required|string|min:10|max:1000',
                'evidence_images' => 'nullable|array|max:5',
                'evidence_images.*' => 'url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Validate reportable exists
            $reportable = $this->getReportable($request->reportable_type, $request->reportable_id);
            if (!$reportable) {
                return response()->json([
                    'status' => 'error',
                    'message' => ucfirst($request->reportable_type) . ' not found'
                ], 404);
            }

            // Không thể tự báo cáo mình
            if ($request->reportable_type === 'user' && $request->reportable_id == $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot report yourself'
                ], 400);
            }

            // Kiểm tra không báo cáo trùng lặp
            $existingReport = ModerationReport::where('reporter_id', $user->id)
                ->where('reportable_type', $request->reportable_type)
                ->where('reportable_id', $request->reportable_id)
                ->where('status', 'pending')
                ->first();

            if ($existingReport) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already reported this item'
                ], 409);
            }

            // Tạo báo cáo
            $report = ModerationReport::create([
                'reporter_id' => $user->id,
                'reportable_type' => $request->reportable_type,
                'reportable_id' => $request->reportable_id,
                'reason' => $request->reason,
                'description' => $request->description,
                'evidence_images' => $request->evidence_images,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Report submitted successfully. We will review it soon.',
                'data' => [
                    'id' => $report->id,
                    'reporter_id' => $report->reporter_id,
                    'reportable_type' => $report->reportable_type,
                    'reportable_id' => $report->reportable_id,
                    'reason' => $report->reason,
                    'description' => $report->description,
                    'status' => $report->status,
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

            $query = ModerationReport::where('reporter_id', $user->id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by reportable_type
            if ($request->has('reportable_type')) {
                $query->where('reportable_type', $request->reportable_type);
            }

            $perPage = $request->get('per_page', 20);
            $reports = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Load reportable relationships
            $reports->getCollection()->transform(function ($report) {
                $reportable = $this->getReportable($report->reportable_type, $report->reportable_id);
                $report->reportable = $reportable ? $this->formatReportable($reportable, $report->reportable_type) : null;
                return $report;
            });

            return response()->json([
                'status' => 'success',
                'data' => $reports->items(),
                'meta' => [
                    'current_page' => $reports->currentPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'last_page' => $reports->lastPage(),
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
            $query = ModerationReport::with(['reporter:id,name,email']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by reportable_type
            if ($request->has('reportable_type')) {
                $query->where('reportable_type', $request->reportable_type);
            }

            // Filter by reason
            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhereHas('reporter', function ($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Sort
            $sortBy = $request->get('sort', 'created_at');
            $order = $request->get('order', 'desc');
            $query->orderBy($sortBy, $order);

            $perPage = $request->get('per_page', 20);
            $reports = $query->paginate($perPage);

            // Load reportable relationships
            $reports->getCollection()->transform(function ($report) {
                $reportable = $this->getReportable($report->reportable_type, $report->reportable_id);
                $report->reportable = $reportable ? $this->formatReportable($reportable, $report->reportable_type) : null;
                return $report;
            });

            // Summary statistics
            $summary = [
                'total_reports' => ModerationReport::count(),
                'pending' => ModerationReport::where('status', 'pending')->count(),
                'resolved' => ModerationReport::where('status', 'resolved')->count(),
                'dismissed' => ModerationReport::where('status', 'dismissed')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $reports->items(),
                'meta' => [
                    'current_page' => $reports->currentPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'last_page' => $reports->lastPage(),
                ],
                'summary' => $summary
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
                'reporter:id,name,email,phone',
                'reviewedBy:id,name,email'
            ])->find($id);

            if (!$report) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report not found'
                ], 404);
            }

            // Load reportable with full details
            $reportable = $this->getReportable($report->reportable_type, $report->reportable_id);
            $report->reportable = $reportable ? $this->formatReportableDetail($reportable, $report->reportable_type) : null;

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
     * Admin xử lý báo cáo
     */
    public function resolveReport(Request $request, $id)
    {
        try {
            $admin = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'resolution' => 'required|in:resolved,dismissed',
                'admin_notes' => 'required|string|max:1000',
                'action_taken' => 'nullable|in:listing_deleted,shop_suspended,user_banned,warning_sent,no_action',
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

            DB::beginTransaction();

            // Update report
            $report->status = $request->resolution;
            $report->resolution = $request->resolution;
            $report->admin_notes = $request->admin_notes;
            $report->action_taken = $request->action_taken;
            $report->reviewed_by = $admin->id;
            $report->reviewed_at = now();
            $report->save();

            // Thực hiện action nếu resolved
            if ($request->resolution === 'resolved' && $request->action_taken) {
                $this->executeAction($report, $request->action_taken);
            }

            // Gửi notification cho reporter
            Notification::create([
                'user_id' => $report->reporter_id,
                'title' => 'Báo cáo của bạn đã được xử lý',
                'message' => "Báo cáo #{$report->id} đã được xem xét. Kết quả: " . ($request->resolution === 'resolved' ? 'Đã xử lý' : 'Bỏ qua'),
                'type' => 'system',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Report resolved successfully',
                'data' => [
                    'id' => $report->id,
                    'status' => $report->status,
                    'resolution' => $report->resolution,
                    'admin_notes' => $report->admin_notes,
                    'action_taken' => $report->action_taken,
                    'resolved_by' => $admin->id,
                    'resolved_at' => $report->reviewed_at,
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

    // Helper methods
    private function getReportable($type, $id)
    {
        switch ($type) {
            case 'listing': return Listing::find($id);
            case 'shop': return Shop::find($id);
            case 'user': return User::find($id);
            case 'review': return Review::find($id);
            default: return null;
        }
    }

    private function formatReportable($reportable, $type)
    {
        if (!$reportable) return null;
        switch ($type) {
            case 'listing':
                return ['id' => $reportable->id, 'title' => $reportable->title, 'status' => $reportable->status];
            case 'shop':
                return ['id' => $reportable->id, 'name' => $reportable->name, 'is_active' => $reportable->is_active];
            case 'user':
                return ['id' => $reportable->id, 'name' => $reportable->name, 'email' => $reportable->email, 'status' => $reportable->status];
            case 'review':
                return ['id' => $reportable->id, 'rating' => $reportable->rating, 'comment' => substr($reportable->comment ?? '', 0, 100)];
            default: return null;
        }
    }

    private function formatReportableDetail($reportable, $type)
    {
        if (!$reportable) return null;
        switch ($type) {
            case 'listing':
                return [
                    'id' => $reportable->id,
                    'title' => $reportable->title,
                    'description' => $reportable->description,
                    'price' => $reportable->price,
                    'shop_id' => $reportable->shop_id,
                    'shop' => $reportable->shop ? ['id' => $reportable->shop->id, 'name' => $reportable->shop->name, 'owner_user_id' => $reportable->shop->owner_user_id] : null,
                    'status' => $reportable->status,
                    'created_at' => $reportable->created_at,
                ];
            case 'shop':
                return [
                    'id' => $reportable->id,
                    'name' => $reportable->name,
                    'description' => $reportable->description,
                    'owner_user_id' => $reportable->owner_user_id,
                    'is_active' => $reportable->is_active,
                    'created_at' => $reportable->created_at,
                ];
            case 'user':
                return [
                    'id' => $reportable->id,
                    'name' => $reportable->name,
                    'email' => $reportable->email,
                    'phone' => $reportable->phone,
                    'role' => $reportable->role,
                    'status' => $reportable->status,
                    'created_at' => $reportable->created_at,
                ];
            case 'review':
                return [
                    'id' => $reportable->id,
                    'rating' => $reportable->rating,
                    'comment' => $reportable->comment,
                    'reviewer_id' => $reportable->reviewer_id,
                    'created_at' => $reportable->created_at,
                ];
            default: return null;
        }
    }

    private function executeAction($report, $action)
    {
        $reportable = $this->getReportable($report->reportable_type, $report->reportable_id);
        if (!$reportable) return;

        switch ($action) {
            case 'listing_deleted':
                if ($report->reportable_type === 'listing') {
                    $reportable->status = 'deleted';
                    $reportable->save();
                }
                break;
            case 'shop_suspended':
                if ($report->reportable_type === 'shop') {
                    $reportable->is_active = false;
                    $reportable->save();
                }
                break;
            case 'user_banned':
                if ($report->reportable_type === 'user') {
                    $reportable->status = 'banned';
                    $reportable->save();
                }
                break;
            case 'warning_sent':
                if ($report->reportable_type === 'user') {
                    Notification::create([
                        'user_id' => $reportable->id,
                        'title' => 'Cảnh báo vi phạm',
                        'message' => 'Tài khoản của bạn đã nhận được cảnh báo do vi phạm quy định.',
                        'type' => 'warning',
                    ]);
                }
                break;
        }
    }
}
