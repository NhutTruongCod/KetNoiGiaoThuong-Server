<?php

namespace App\Http\Controllers;

use App\Models\PromotionCostEstimation;
use App\Http\Requests\PromotionCostEstimationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionCostEstimationController extends Controller
{
    /**
     * GET – Lấy danh sách hoặc chi tiết ước tính chi phí
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PromotionCostEstimation::with(['store', 'listing']);

            // Lọc theo store
            if ($request->has('store_id') && $request->store_id) {
                $query->byStore($request->store_id);
            }

            // Lọc theo listing
            if ($request->has('listing_id') && $request->listing_id) {
                $query->byListing($request->listing_id);
            }

            // Lọc theo status
            if ($request->has('status') && $request->status) {
                $query->byStatus($request->status);
            }

            // Lọc theo promotion type
            if ($request->has('promotion_type') && $request->promotion_type) {
                $query->byPromotionType($request->promotion_type);
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('created_at', 'desc');

            // Phân trang hoặc lấy tất cả
            if ($request->has('limit')) {
                $limit = $request->get('limit', 15);
                $estimations = $query->paginate($limit);
                
                return response()->json([
                    'success' => true,
                    'data' => $estimations->items(),
                    'pagination' => [
                        'current_page' => $estimations->currentPage(),
                        'per_page' => $estimations->perPage(),
                        'total' => $estimations->total(),
                        'last_page' => $estimations->lastPage(),
                    ]
                ]);
            } else {
                $estimations = $query->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $estimations
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ nội bộ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET – Lấy chi tiết ước tính chi phí theo ID
     */
    public function show($id): JsonResponse
    {
        try {
            $estimation = PromotionCostEstimation::with(['store', 'listing'])->find($id);

            if (!$estimation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bản ước tính chi phí'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estimation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ nội bộ'
            ], 500);
        }
    }

    /**
     * POST – Tạo mới ước tính chi phí quảng cáo
     */
    public function store(PromotionCostEstimationRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Tạo estimation instance để tính toán
            $estimation = new PromotionCostEstimation($request->validated());
            
            // Tính toán chi phí ước tính
            $estimatedCost = $estimation->calculateEstimatedCost();
            
            // Ước tính hiệu suất
            $performance = $estimation->estimatePerformance();

            // Tạo bản ghi với dữ liệu tính toán
            $estimation = PromotionCostEstimation::create([
                'store_id' => $request->store_id,
                'listing_id' => $request->listing_id,
                'promotion_type' => $request->promotion_type,
                'duration_days' => $request->duration_days,
                'budget' => $request->budget,
                'estimated_cost' => $estimatedCost,
                'currency' => $request->currency ?? 'VND',
                'calculation_method' => 'AI-based model',
                'calculation_details' => json_encode([
                    'base_budget' => $request->budget,
                    'promotion_type_multiplier' => $this->getPromotionTypeMultiplier($request->promotion_type),
                    'duration_multiplier' => min(1.0 + ($request->duration_days / 30 * 0.1), 1.5),
                    'final_estimation' => $estimatedCost
                ]),
                'estimated_impressions' => $performance['impressions'],
                'estimated_clicks' => $performance['clicks'],
                'estimated_conversions' => $performance['conversions'],
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo bản ước tính chi phí quảng cáo mới.',
                'data' => [
                    'id' => $estimation->id,
                    'estimated_cost' => $estimation->estimated_cost,
                    'calculation_method' => $estimation->calculation_method,
                    'estimated_impressions' => $estimation->estimated_impressions,
                    'estimated_clicks' => $estimation->estimated_clicks,
                    'estimated_conversions' => $estimation->estimated_conversions,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT – Cập nhật thông tin ước tính chi phí
     */
    public function update(PromotionCostEstimationRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $estimation = PromotionCostEstimation::find($id);

            if (!$estimation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bản ước tính chi phí'
                ], 404);
            }

            $updateData = $request->validated();

            // Nếu có thay đổi về budget, duration hoặc promotion type, tính toán lại
            if (isset($updateData['budget']) || isset($updateData['duration_days']) || isset($updateData['promotion_type'])) {
                $tempEstimation = clone $estimation;
                
                if (isset($updateData['budget'])) {
                    $tempEstimation->budget = $updateData['budget'];
                }
                if (isset($updateData['duration_days'])) {
                    $tempEstimation->duration_days = $updateData['duration_days'];
                }
                if (isset($updateData['promotion_type'])) {
                    $tempEstimation->promotion_type = $updateData['promotion_type'];
                }

                $estimatedCost = $tempEstimation->calculateEstimatedCost();
                $performance = $tempEstimation->estimatePerformance();

                $updateData['estimated_cost'] = $estimatedCost;
                $updateData['estimated_impressions'] = $performance['impressions'];
                $updateData['estimated_clicks'] = $performance['clicks'];
                $updateData['estimated_conversions'] = $performance['conversions'];
                $updateData['calculation_details'] = json_encode([
                    'base_budget' => $updateData['budget'] ?? $estimation->budget,
                    'promotion_type_multiplier' => $this->getPromotionTypeMultiplier($updateData['promotion_type'] ?? $estimation->promotion_type),
                    'duration_multiplier' => min(1.0 + (($updateData['duration_days'] ?? $estimation->duration_days) / 30 * 0.1), 1.5),
                    'final_estimation' => $estimatedCost
                ]);
            }

            $estimation->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật bản ước tính chi phí thành công.',
                'data' => [
                    'id' => $estimation->id,
                    'estimated_cost' => $estimation->estimated_cost,
                    'status' => $estimation->status,
                    'estimated_impressions' => $estimation->estimated_impressions,
                    'estimated_clicks' => $estimation->estimated_clicks,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE – Xóa bản ước tính chi phí
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $estimation = PromotionCostEstimation::find($id);

            if (!$estimation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bản ước tính chi phí'
                ], 404);
            }

            // Không cho phép xóa nếu đã approved hoặc completed
            if (in_array($estimation->status, ['approved', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa bản ước tính đã được phê duyệt hoặc hoàn thành'
                ], 409);
            }

            $estimation->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa bản ước tính chi phí quảng cáo thành công.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH – Cập nhật trạng thái
     */
    public function updateStatus($id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,approved,rejected,completed,cancelled'
            ]);

            $estimation = PromotionCostEstimation::find($id);

            if (!$estimation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bản ước tính chi phí'
                ], 404);
            }

            $estimation->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'data' => $estimation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST – Tính toán nhanh chi phí ước tính
     */
    public function quickCalculate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'promotion_type' => 'required|in:banner,video_ads,social_media,search_ads,email_marketing,in_app_ads',
                'duration_days' => 'required|integer|min:1|max:365',
                'budget' => 'required|numeric|min:10000',
            ]);

            $tempEstimation = new PromotionCostEstimation([
                'promotion_type' => $request->promotion_type,
                'duration_days' => $request->duration_days,
                'budget' => $request->budget,
            ]);

            $estimatedCost = $tempEstimation->calculateEstimatedCost();
            $performance = $tempEstimation->estimatePerformance();

            return response()->json([
                'success' => true,
                'data' => [
                    'estimated_cost' => $estimatedCost,
                    'estimated_impressions' => $performance['impressions'],
                    'estimated_clicks' => $performance['clicks'],
                    'estimated_conversions' => $performance['conversions'],
                    'cost_per_impression' => $estimatedCost / max($performance['impressions'], 1),
                    'cost_per_click' => $estimatedCost / max($performance['clicks'], 1),
                    'cost_per_conversion' => $estimatedCost / max($performance['conversions'], 1),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tính toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method để lấy multiplier cho loại quảng cáo
     */
    private function getPromotionTypeMultiplier($promotionType)
    {
        $multipliers = [
            'banner' => 1.0,
            'video_ads' => 1.5,
            'social_media' => 1.2,
            'search_ads' => 1.3,
            'email_marketing' => 0.8,
            'in_app_ads' => 1.4,
        ];

        return $multipliers[$promotionType] ?? 1.0;
    }
}