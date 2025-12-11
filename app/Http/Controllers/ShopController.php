<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    /**
     * GET /api/shops - Danh sách shops
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shop::with('owner:id,full_name,email,phone');

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filter by status
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by user_id
        if ($userId = $request->input('user_id')) {
            $query->where('owner_user_id', $userId);
        }

        // Filter by verified
        if ($request->has('verified')) {
            $query->where('is_verified', $request->boolean('verified'));
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        
        $allowedSorts = ['created_at', 'name', 'rating', 'total_products', 'total_orders', 'followers_count'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $shops = $query->paginate($perPage);

        return response()->json([
            'data' => $shops->items(),
            'meta' => [
                'current_page' => $shops->currentPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
                'last_page' => $shops->lastPage(),
            ]
        ]);
    }

    /**
     * GET /api/shops/{shop} - Chi tiết shop
     */
    public function show(Shop $shop): JsonResponse
    {
        $shop->load('owner:id,full_name,email,phone');

        return response()->json($shop);
    }

    /**
     * POST /api/shops - Tạo shop (chỉ seller)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return response()->json([
                'message' => 'Only sellers can create shops'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191|unique:shops,name',
            'description' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:191',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|url|max:500',
            'banner' => 'nullable|url|max:500',
            'business_name' => 'nullable|string|max:255',
            'business_registration_number' => 'nullable|string|max:50',
            'business_type' => 'nullable|in:individual,company,enterprise',
            'ward' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.zalo' => 'nullable|string|max:20',
            'social_links.youtube' => 'nullable|url|max:255',
            'business_hours' => 'nullable|array',
            'return_policy' => 'nullable|string',
            'shipping_policy' => 'nullable|string',
            'warranty_policy' => 'nullable|string',
        ]);

        // Handle social links
        $socialLinks = $validated['social_links'] ?? [];
        unset($validated['social_links']);

        $shopData = array_merge($validated, [
            'owner_user_id' => $user->id,
            'facebook_url' => $socialLinks['facebook'] ?? null,
            'instagram_url' => $socialLinks['instagram'] ?? null,
            'zalo_phone' => $socialLinks['zalo'] ?? null,
            'youtube_url' => $socialLinks['youtube'] ?? null,
            'is_active' => true,
        ]);

        $shop = Shop::create($shopData);
        $shop->load('owner:id,full_name,email,phone');

        return response()->json([
            'message' => 'Shop created successfully',
            'data' => $shop
        ], 201);
    }

    /**
     * PUT /api/shops/{shop} - Cập nhật shop
     */
    public function update(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();

        if ($shop->owner_user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'You can only update your own shop'
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', Rule::unique('shops')->ignore($shop->id)],
            'description' => 'nullable|string',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:191',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|url|max:500',
            'banner' => 'nullable|url|max:500',
            'business_name' => 'nullable|string|max:255',
            'business_registration_number' => 'nullable|string|max:50',
            'business_type' => 'nullable|in:individual,company,enterprise',
            'ward' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.zalo' => 'nullable|string|max:20',
            'social_links.youtube' => 'nullable|url|max:255',
            'business_hours' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'url',
            'return_policy' => 'nullable|string',
            'shipping_policy' => 'nullable|string',
            'warranty_policy' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|array',
        ]);

        // Handle social links
        if (isset($validated['social_links'])) {
            $socialLinks = $validated['social_links'];
            unset($validated['social_links']);
            
            $validated['facebook_url'] = $socialLinks['facebook'] ?? $shop->facebook_url;
            $validated['instagram_url'] = $socialLinks['instagram'] ?? $shop->instagram_url;
            $validated['zalo_phone'] = $socialLinks['zalo'] ?? $shop->zalo_phone;
            $validated['youtube_url'] = $socialLinks['youtube'] ?? $shop->youtube_url;
        }

        $shop->update($validated);
        $shop->load('owner:id,full_name,email,phone');

        return response()->json([
            'message' => 'Shop updated successfully',
            'data' => $shop
        ]);
    }

    /**
     * DELETE /api/shops/{shop} - Xóa shop
     */
    public function destroy(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();

        if ($shop->owner_user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'You can only delete your own shop'
            ], 403);
        }

        // Check if shop has active listings
        if ($shop->listings()->exists()) {
            return response()->json([
                'message' => 'Cannot delete shop with active listings. Please delete all listings first.'
            ], 400);
        }

        $shop->delete();

        return response()->json([
            'message' => 'Shop deleted successfully'
        ]);
    }

    /**
     * GET /api/shops/{shop}/listings - Lấy danh sách listings của shop
     */
    public function listings(Request $request, Shop $shop): JsonResponse
    {
        $query = $shop->listings()->with(['user']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        
        $allowedSorts = ['created_at', 'title', 'price_cents', 'stock_qty'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $listings = $query->paginate($perPage);

        return response()->json([
            'data' => $listings->items(),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
            ]
        ]);
    }

    /**
     * GET /api/my-shop - Lấy shop của seller hiện tại
     */
    public function myShop(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return response()->json([
                'message' => 'Only sellers can have shops'
            ], 403);
        }

        $shop = Shop::where('owner_user_id', $user->id)->first();

        if (!$shop) {
            return response()->json([
                'message' => 'You do not have a shop yet'
            ], 404);
        }

        $shop->load('owner:id,full_name,email,phone');

        return response()->json([
            'data' => $shop
        ]);
    }
}
