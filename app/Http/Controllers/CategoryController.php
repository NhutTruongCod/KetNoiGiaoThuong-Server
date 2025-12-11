<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Shop;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * GET - Lấy danh sách danh mục của shop
     */
    public function index(Request $request, Shop $shop): JsonResponse
    {
        try {
            $query = $shop->categories()->active();

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
     * POST - Thêm danh mục mới cho shop
     */
    public function store(Request $request, Shop $shop): JsonResponse
    {
        try {
            // Check ownership
            if ($shop->owner_user_id !== auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only create categories for your own shop'
                ], 403);
            }

            DB::beginTransaction();

            $category = $shop->categories()->create([
                'user_id' => auth()->id(),
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'is_active' => $request->is_active ?? true,
                'status' => 'approved', // Auto approve for shop owner
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => $category->load('user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category name or slug already exists in this shop'
                ], 409);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT - Cập nhật danh mục
     */
    public function update(Request $request, Shop $shop, Category $category): JsonResponse
    {
        try {
            // Check ownership: Owner hoặc Admin
            $isOwner = $shop->owner_user_id === auth()->id();
            $isAdmin = auth()->user()->role === 'admin';
            
            if (!$isOwner && !$isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only update categories of your own shop'
                ], 403);
            }

            // Check category belongs to shop
            if ($category->shop_id !== $shop->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category does not belong to this shop'
                ], 404);
            }

            DB::beginTransaction();

            $category->update($request->only([
                'name',
                'slug',
                'description',
                'parent_id',
                'is_active',
            ]));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => $category->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category name or slug already exists in this shop'
                ], 400);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * DELETE - Xóa danh mục
     */
    public function destroy(Shop $shop, Category $category): JsonResponse
    {
        try {
            // Check ownership: Owner hoặc Admin
            $isOwner = $shop->owner_user_id === auth()->id();
            $isAdmin = auth()->user()->role === 'admin';
            
            if (!$isOwner && !$isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only delete categories of your own shop'
                ], 403);
            }

            // Check category belongs to shop
            if ($category->shop_id !== $shop->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category does not belong to this shop'
                ], 404);
            }

            DB::beginTransaction();

            // Kiểm tra xem danh mục có sản phẩm không
            if ($category->hasListings()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with active listings'
                ], 409);
            }

            $category->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * GET - Hiển thị thông tin chi tiết danh mục
     */
    public function show(Shop $shop, Category $category): JsonResponse
    {
        // Check category belongs to shop
        if ($category->shop_id !== $shop->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category does not belong to this shop'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $category->load(['user', 'parent', 'children'])
        ]);
    }

    /**
     * GET - Lấy danh sách danh mục đơn giản của shop (cho dropdown)
     */
    public function simpleList(Shop $shop): JsonResponse
    {
        try {
            $categories = $shop->categories()
                ->active()
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'slug']);

            return response()->json([
                'status' => 'success',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * GET - Lấy TẤT CẢ categories từ mọi shops (cho trang chủ, search)
     */
    public function allCategories(Request $request): JsonResponse
    {
        try {
            $query = Category::with(['shop:id,name,slug'])
                ->active();

            // Tìm kiếm theo tên
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Lọc theo shop (optional)
            if ($request->has('shop_id') && $request->shop_id) {
                $query->byShop($request->shop_id);
            }

            // Sắp xếp
            $query->orderBy('name', 'asc');

            // Phân trang
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 50);
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
     * GET - Lấy danh sách gọn TẤT CẢ categories (cho dropdown trang chủ)
     */
    public function allCategoriesSimple(Request $request): JsonResponse
    {
        try {
            $query = Category::with(['shop:id,name'])
                ->active();

            // Lọc theo shop (optional)
            if ($request->has('shop_id') && $request->shop_id) {
                $query->byShop($request->shop_id);
            }

            $categories = $query
                ->orderBy('name', 'asc')
                ->get(['id', 'shop_id', 'name', 'slug'])
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'shop' => [
                            'id' => $category->shop->id,
                            'name' => $category->shop->name,
                        ]
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }
}