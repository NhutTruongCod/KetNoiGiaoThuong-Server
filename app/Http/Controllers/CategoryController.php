<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * GET - Lấy danh sách danh mục
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::active();

            // Tìm kiếm theo tên
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Sắp xếp
            $query->orderBy('name', 'asc');

            // Phân trang
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 15);
            $categories = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST - Thêm danh mục mới
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $category = Category::create($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Danh mục đã được tạo thành công',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Danh mục đã tồn tại'
                ], 409);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * PUT - Cập nhật danh mục
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        try {
            DB::beginTransaction();

            $category->update($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Danh mục đã được cập nhật thành công',
                'data' => $category->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dữ liệu không hợp lệ (trùng tên hoặc slug)'
                ], 400);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * DELETE - Xóa danh mục
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Kiểm tra xem danh mục có sản phẩm không
            if ($category->hasListings()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa vì danh mục đang chứa sản phẩm'
                ], 409);
            }

            $category->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Danh mục đã được xóa thành công'
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
     * GET - Hiển thị thông tin chi tiết danh mục
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * GET - Lấy danh sách danh mục đơn giản (cho dropdown)
     */
    public function simpleList(): JsonResponse
    {
        try {
            $categories = Category::active()
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'slug']);

            return response()->json([
                'status' => 'success',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ nội bộ'
            ], 500);
        }
    }
}