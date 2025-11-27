<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Http\Requests\StoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * GET - Lấy danh sách cửa hàng
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Store::active();

            // Tìm kiếm theo tên
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Lọc theo category_id (nếu cần tích hợp sau)
            if ($request->has('category_id') && $request->category_id) {
                // Sẽ tích hợp sau khi có bảng categories
            }

            // Phân trang
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 15);
            $stores = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => $stores->items(),
                'pagination' => [
                    'current_page' => $stores->currentPage(),
                    'per_page' => $stores->perPage(),
                    'total' => $stores->total(),
                    'last_page' => $stores->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ nội bộ'
            ], 500);
        }
    }

    /**
     * POST - Thêm cửa hàng mới
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $store = Store::create($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Cửa hàng đã được tạo thành công',
                'data' => $store
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cửa hàng đã tồn tại (trùng email hoặc tên)'
                ], 409);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * PUT - Cập nhật thông tin cửa hàng
     */
    public function update(StoreRequest $request, Store $store): JsonResponse
    {
        try {
            DB::beginTransaction();

            $store->update($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Cửa hàng đã được cập nhật thành công',
                'data' => $store->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ (trùng email hoặc tên)'
                ], 400);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * DELETE - Xóa cửa hàng
     */
    public function destroy(Store $store): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Kiểm tra xem cửa hàng có sản phẩm/bài đăng liên kết không
            // (Sẽ tích hợp sau khi có bảng listings)
            // if ($store->listings()->exists()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Không thể xóa vì cửa hàng có sản phẩm hoặc bài đăng liên kết'
            //     ], 409);
            // }

            $store->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Cửa hàng đã được xóa thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * GET - Hiển thị thông tin chi tiết cửa hàng
     */
    public function show(Store $store): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $store
        ]);
    }
}