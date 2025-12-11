<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Listing;
use App\Http\Requests\PromotionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionController extends Controller
{
    /**
     * GET /promotion - Danh sách quảng cáo của seller
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $query = Promotion::with(['listing:id,title,price,images', 'shop:id,name'])
                ->whereHas('listing', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $perPage = $request->input('per_page', 20);
            $promotions = $query->orderByDesc('created_at')->paginate($perPage);

            // Calculate summary
            $summary = [
                'total_budget' => $promotions->sum('budget'),
                'total_spent' => $promotions->sum('spent'),
                'total_impressions' => $promotions->sum('impressions'),
                'total_clicks' => $promotions->sum('clicks'),
                'avg_ctr' => $promotions->avg('ctr') ?? 0,
            ];

            return response()->json([
                'data' => $promotions->items(),
                'meta' => [
                    'current_page' => $promotions->currentPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total(),
                ],
                'summary' => $summary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /promotion/active - Quảng cáo đang chạy
     */
    public function activePromotions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $promotions = Promotion::with(['listing:id,title,price,images'])
                ->whereHas('listing', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'data' => $promotions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /promotion/{id} - Chi tiết quảng cáo
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $promotion = Promotion::with(['listing', 'shop'])
                ->whereHas('listing', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

            // Format performance data
            $data = $promotion->toArray();
            $data['performance'] = [
                'impressions' => $promotion->impressions,
                'clicks' => $promotion->clicks,
                'ctr' => $promotion->ctr,
                'conversions' => $promotion->conversions,
                'conversion_rate' => $promotion->conversion_rate,
                'cost_per_click' => $promotion->cost_per_click,
                'cost_per_conversion' => $promotion->cost_per_conversion,
            ];

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Không tìm thấy quảng cáo',
            ], 404);
        }
    }

    /**
     * POST /promotion - Tạo quảng cáo mới
     */
    public function store(PromotionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            
            // Verify listing belongs to user
            $listing = Listing::where('id', $request->listing_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::today();
            $endDate = $startDate->copy()->addDays($request->duration_days);
            
            // Calculate daily budget
            $dailyBudget = $request->budget / $request->duration_days;

            $promotion = Promotion::create([
                'shop_id' => $listing->shop_id,
                'listing_id' => $request->listing_id,
                'type' => $request->type,
                'duration_days' => $request->duration_days,
                'budget' => $request->budget,
                'spent' => 0,
                'daily_budget' => $dailyBudget,
                'target_audience' => $request->target_audience,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'pending',
                'payment_url' => 'https://vnpay.vn/payment?token=' . uniqid(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Promotion created successfully',
                'data' => $promotion,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /promotion/{id} - Cập nhật quảng cáo
     */
    public function update(PromotionRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            
            $promotion = Promotion::whereHas('listing', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

            // Only allow update if pending or active
            if (!in_array($promotion->status, ['pending', 'active'])) {
                return response()->json([
                    'message' => 'Không thể cập nhật quảng cáo đã hoàn thành hoặc hủy',
                ], 400);
            }

            $data = $request->only(['budget', 'duration_days', 'target_audience']);
            
            if (isset($data['duration_days'])) {
                $data['end_date'] = Carbon::parse($promotion->start_date)
                    ->addDays($data['duration_days']);
            }
            
            if (isset($data['budget']) && isset($data['duration_days'])) {
                $data['daily_budget'] = $data['budget'] / $data['duration_days'];
            }

            $promotion->update($data);

            DB::commit();

            return response()->json([
                'message' => 'Promotion updated successfully',
                'data' => $promotion->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /promotion/{id}/featured - Đặt tin nổi bật
     */
    public function updateFeatured(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $promotion = Promotion::whereHas('listing', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

            $validated = $request->validate([
                'is_featured' => 'required|boolean',
                'featured_position' => 'nullable|integer|min:1|max:10',
            ]);

            $promotion->update($validated);

            return response()->json([
                'message' => 'Featured status updated successfully',
                'data' => $promotion,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /promotion/{id} - Hủy quảng cáo
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            
            $promotion = Promotion::whereHas('listing', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

            // Only allow cancel if pending or active
            if (!in_array($promotion->status, ['pending', 'active'])) {
                return response()->json([
                    'message' => 'Không thể hủy quảng cáo đã hoàn thành',
                ], 400);
            }

            // Calculate refund
            $refundAmount = $promotion->budget - $promotion->spent;
            
            $promotion->update([
                'status' => 'cancelled',
                'refund_amount' => $refundAmount,
                'refund_note' => 'Hoàn lại số tiền chưa sử dụng',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Promotion cancelled successfully',
                'data' => [
                    'id' => $promotion->id,
                    'status' => $promotion->status,
                    'refund_amount' => $refundAmount,
                    'refund_note' => 'Hoàn lại số tiền chưa sử dụng',
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }
}