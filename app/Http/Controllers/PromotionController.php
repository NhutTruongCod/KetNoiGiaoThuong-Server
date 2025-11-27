<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Http\Requests\PromotionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PromotionController extends Controller
{
    /**
     * GET – Lấy danh sách hoặc chi tiết khuyến mãi
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Promotion::query();

            // Lọc theo status
            if ($request->has('status') && $request->status) {
                $query->byStatus($request->status);
            }

            // Lấy featured promotions
            if ($request->boolean('featured')) {
                $query->featured();
            }

            // Sắp xếp
            $query->orderBy('is_featured', 'desc')
                  ->orderBy('start_date', 'desc');

            // Phân trang hoặc lấy tất cả
            if ($request->has('limit')) {
                $limit = $request->get('limit', 15);
                $promotions = $query->paginate($limit);
                
                return response()->json([
                    'success' => true,
                    'data' => $promotions->items(),
                    'pagination' => [
                        'current_page' => $promotions->currentPage(),
                        'per_page' => $promotions->perPage(),
                        'total' => $promotions->total(),
                        'last_page' => $promotions->lastPage(),
                    ]
                ]);
            } else {
                $promotions = $query->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $promotions
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET – Lấy chi tiết khuyến mãi theo ID
     */
    public function show($id): JsonResponse
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy khuyến mãi'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $promotion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST – Thêm khuyến mãi mới
     */
    public function store(PromotionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $promotion = Promotion::create($request->validated());

            // Auto-update status based on dates
            $promotion->updateStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm khuyến mãi thành công.',
                'promotion_id' => $promotion->id
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
     * PUT – Cập nhật thông tin khuyến mãi
     */
    public function update(PromotionRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy khuyến mãi'
                ], 404);
            }

            $promotion->update($request->validated());

            // Auto-update status after update
            $promotion->updateStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin khuyến mãi thành công.',
                'data' => $promotion->fresh()
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
     * DELETE – Xóa khuyến mãi
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy khuyến mãi'
                ], 404);
            }

            // Kiểm tra nếu khuyến mãi đang active
            if ($promotion->is_currently_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa khuyến mãi đang hoạt động'
                ], 409);
            }

            $promotion->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa khuyến mãi thành công.'
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
     * GET – Lấy danh sách khuyến mãi active
     */
    public function activePromotions(): JsonResponse
    {
        try {
            $promotions = Promotion::active()
                ->orderBy('is_featured', 'desc')
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ nội bộ'
            ], 500);
        }
    }

    /**
     * PATCH – Cập nhật trạng thái featured
     */
    public function updateFeatured($id, Request $request): JsonResponse
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy khuyến mãi'
                ], 404);
            }

            $request->validate([
                'is_featured' => 'required|boolean'
            ]);

            $promotion->update([
                'is_featured' => $request->is_featured
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái featured thành công.',
                'data' => $promotion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }
}