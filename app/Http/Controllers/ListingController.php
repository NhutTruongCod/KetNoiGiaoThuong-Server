<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Http\Requests\ListingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingController extends Controller
{
    /**
     * GET - Lấy danh sách bài đăng
     * Ưu tiên hiển thị listings có promotion (top_search, featured) lên đầu
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Listing::with(['user', 'shop'])
                ->where('listings.is_active', true);

            // Tìm kiếm theo tiêu đề
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('listings.title', 'like', "%{$search}%")
                      ->orWhere('listings.description', 'like', "%{$search}%");
                });
            }

            // Lọc theo category
            if ($request->has('category') && $request->category) {
                $query->where('listings.category', $request->category);
            }

            // Lọc theo shop_id
            if ($request->has('shop_id') && $request->shop_id) {
                $query->where('listings.shop_id', $request->shop_id);
            }

            // Lọc theo type
            if ($request->has('type') && $request->type) {
                $query->where('listings.type', $request->type);
            }

            // Lọc theo status
            if ($request->has('status') && $request->status) {
                $query->where('listings.status', $request->status);
            }

            // Join với promotions để ưu tiên listings có quảng cáo lên đầu
            $query->leftJoin('promotions', function ($join) {
                $join->on('listings.id', '=', 'promotions.listing_id')
                     ->where('promotions.status', '=', 'active')
                     ->whereIn('promotions.type', ['top_search', 'featured'])
                     ->whereRaw('promotions.end_date >= CURDATE()');
            })
            ->select('listings.*')
            ->selectRaw('CASE WHEN promotions.id IS NOT NULL THEN 1 ELSE 0 END as has_promotion')
            ->selectRaw('COALESCE(promotions.featured_position, 999) as promo_position')
            ->selectRaw('promotions.id as promotion_id')
            ->selectRaw('promotions.type as promotion_type');

            // Phân trang - ưu tiên có promotion lên đầu
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 15);
            $listings = $query->orderByDesc('has_promotion')
                            ->orderBy('promo_position')
                            ->orderBy('listings.created_at', 'desc')
                            ->paginate($limit, ['*'], 'page', $page);

            // Thêm stats và thông tin promotion cho mỗi listing
            $listingsWithStats = collect($listings->items())->map(function ($listing) {
                $stats = [
                    'views_count' => DB::table('page_views')->where('listing_id', $listing->id)->count(),
                    'likes_count' => DB::table('listing_likes')->where('listing_id', $listing->id)->count(),
                    'comments_count' => DB::table('listing_comments')->where('listing_id', $listing->id)->count(),
                    'bookmarks_count' => DB::table('bookmarks')->where('listing_id', $listing->id)->count(),
                    // Thông tin promotion
                    'has_promotion' => (bool) ($listing->has_promotion ?? false),
                    'promotion_id' => $listing->promotion_id ?? null,
                    'promotion_type' => $listing->promotion_type ?? null,
                ];
                
                return array_merge($listing->toArray(), $stats);
            });

            return response()->json([
                'status' => 'success',
                'data' => $listingsWithStats,
                'pagination' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
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
     * GET - Kiểm tra quyền lợi subscription trước khi đăng tin
     * FE gọi API này để hiển thị thông tin gói hiện tại của user
     * KHÔNG cần chọn gói cho từng tin - quyền lợi áp dụng tự động theo gói tháng
     */
    public function checkSubscriptionBenefits(Request $request): JsonResponse
    {
        $user = $request->user();

        // Kiểm tra quyền seller
        if (!in_array($user->role, ['seller', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chỉ người bán (seller) mới có quyền đăng tin.'
            ], 403);
        }

        $subscriptionInfo = $this->getUserSubscriptionInfo($user);

        // Đếm số tin đã đăng trong tháng này
        $listingsThisMonth = Listing::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscription' => $subscriptionInfo,
                'listings_this_month' => $listingsThisMonth,
                'can_post' => true, // Có thể thêm logic giới hạn số tin nếu cần
            ]
        ]);
    }

    /**
     * GET - Lấy danh sách sản phẩm của seller đang đăng nhập
     */
    public function myListings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = Listing::with(['shop'])
                ->where('user_id', $user->id);

            // Lọc theo status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Phân trang
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 15);
            $listings = $query->orderBy('created_at', 'desc')
                            ->paginate($limit, ['*'], 'page', $page);

            // Thêm stats cho mỗi listing
            $listingsWithStats = collect($listings->items())->map(function ($listing) {
                $stats = [
                    'views_count' => DB::table('page_views')->where('listing_id', $listing->id)->count(),
                    'likes_count' => DB::table('listing_likes')->where('listing_id', $listing->id)->count(),
                    'comments_count' => DB::table('listing_comments')->where('listing_id', $listing->id)->count(),
                    'bookmarks_count' => DB::table('bookmarks')->where('listing_id', $listing->id)->count(),
                    'orders_count' => DB::table('orders')->where('listing_id', $listing->id)->count(),
                ];
                
                // Lấy comments mới nhất (chưa đọc)
                $recentComments = DB::table('listing_comments')
                    ->join('users', 'listing_comments.user_id', '=', 'users.id')
                    ->where('listing_comments.listing_id', $listing->id)
                    ->select(
                        'listing_comments.id',
                        'listing_comments.body',
                        'listing_comments.created_at',
                        'users.full_name as user_name'
                    )
                    ->orderBy('listing_comments.created_at', 'desc')
                    ->limit(3)
                    ->get();
                
                return array_merge($listing->toArray(), $stats, ['recent_comments' => $recentComments]);
            });

            // Tổng hợp thống kê
            $summary = [
                'total_listings' => Listing::where('user_id', $user->id)->count(),
                'total_views' => DB::table('page_views')
                    ->whereIn('listing_id', Listing::where('user_id', $user->id)->pluck('id'))
                    ->count(),
                'total_likes' => DB::table('listing_likes')
                    ->whereIn('listing_id', Listing::where('user_id', $user->id)->pluck('id'))
                    ->count(),
                'total_comments' => DB::table('listing_comments')
                    ->whereIn('listing_id', Listing::where('user_id', $user->id)->pluck('id'))
                    ->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $listingsWithStats,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
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
     * POST - Thêm bài đăng mới
     * 
     * Quyền lợi subscription được tự động áp dụng theo gói tháng của user:
     * - search_boost: Ưu tiên hiển thị trong tìm kiếm
     * - badge: Hiển thị badge trên tin đăng
     * - commission_rate: Chiết khấu khi bán hàng
     * 
     * QUAN TRỌNG: API này chỉ tạo listing, KHÔNG xử lý upload ảnh.
     * Để upload ảnh, FE cần gọi API riêng: POST /api/listings/{id}/images
     */
    public function store(ListingRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ seller và admin mới được đăng tin
            if (!in_array($user->role, ['seller', 'admin'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Chỉ người bán (seller) mới có quyền đăng tin. Vui lòng nâng cấp tài khoản.'
                ], 403);
            }

            // Kiểm tra idempotency - tránh tạo duplicate khi FE gọi 2 lần
            $idempotencyKey = $request->header('X-Idempotency-Key');
            if ($idempotencyKey) {
                $existingListing = Listing::where('user_id', $user->id)
                    ->where('meta->idempotency_key', $idempotencyKey)
                    ->first();
                
                if ($existingListing) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Bài đăng đã được tạo trước đó (idempotent)',
                        'data' => $existingListing->load(['user', 'shop']),
                        'subscription_benefits' => $this->getUserSubscriptionInfo($user),
                        'is_duplicate' => true
                    ], 200);
                }
            }

            DB::beginTransaction();

            $data = $request->validated();
            $data['user_id'] = $user->id;
            
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['title']) . '-' . time() . '-' . uniqid();
            }
            
            // Set defaults
            $data['currency'] = $data['currency'] ?? 'VND';
            $data['status'] = $data['status'] ?? 'draft';
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_public'] = $data['is_public'] ?? true;
            
            // Lưu idempotency key vào meta nếu có
            if ($idempotencyKey) {
                $data['meta'] = array_merge($data['meta'] ?? [], ['idempotency_key' => $idempotencyKey]);
            }

            $listing = Listing::create($data);

            DB::commit();

            // Lấy thông tin subscription của user (quyền lợi tự động áp dụng theo gói tháng)
            $subscriptionInfo = $this->getUserSubscriptionInfo($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Bài đăng đã được tạo thành công',
                'data' => $listing->load(['user', 'shop']),
                'subscription_benefits' => $subscriptionInfo,
                'next_step' => 'Để upload ảnh, gọi POST /api/listings/' . $listing->id . '/images với field name "images[]"'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin subscription và quyền lợi của user
     * Quyền lợi được áp dụng tự động cho TẤT CẢ tin đăng trong thời gian gói còn hiệu lực
     */
    private function getUserSubscriptionInfo($user): array
    {
        $hasActiveSubscription = $user->subscription_plan_id 
            && $user->subscription_expires_at 
            && $user->subscription_expires_at->isFuture();

        if (!$hasActiveSubscription) {
            return [
                'has_active_plan' => false,
                'plan_name' => 'Free',
                'badge' => null,
                'search_boost' => 0,
                'commission_rate' => 10.00,
                'expires_at' => null,
                'days_remaining' => 0,
                'message' => 'Bạn đang sử dụng gói miễn phí. Nâng cấp để được ưu tiên hiển thị và giảm chiết khấu.'
            ];
        }

        $plan = $user->subscriptionPlan;
        $daysRemaining = now()->diffInDays($user->subscription_expires_at);

        return [
            'has_active_plan' => true,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'badge' => $plan->badge,
            'search_boost' => $plan->search_boost ?? 0,
            'commission_rate' => $plan->commission_rate ?? 10.00,
            'free_promotions' => $plan->free_promotions ?? 0,
            'expires_at' => $user->subscription_expires_at->toIso8601String(),
            'days_remaining' => $daysRemaining,
            'message' => "Tin đăng của bạn được hưởng quyền lợi gói {$plan->name}: ưu tiên tìm kiếm +{$plan->search_boost}, chiết khấu {$plan->commission_rate}%"
        ];
    }

    /**
     * PUT - Cập nhật bài đăng
     */
    public function update(ListingRequest $request, Listing $listing): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ chủ listing hoặc admin mới được cập nhật
            if ($listing->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền cập nhật bài đăng này'
                ], 403);
            }

            DB::beginTransaction();

            $data = $request->validated();
            
            // Auto-generate slug if title changed and slug not provided
            if (isset($data['title']) && $data['title'] !== $listing->title && empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['title']) . '-' . time();
            }

            $listing->update($data);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bài đăng đã được cập nhật thành công',
                'data' => $listing->fresh(['user', 'shop'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE - Xóa bài đăng
     */
    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ chủ listing hoặc admin mới được xóa
            if ($listing->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xóa bài đăng này'
                ], 403);
            }

            DB::beginTransaction();

            // Kiểm tra xem bài đăng có đang trong chiến dịch quảng cáo không
            if ($listing->hasActivePromotions()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa vì bài đăng đang trong chiến dịch quảng cáo'
                ], 409);
            }

            $listing->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bài đăng đã được xóa thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT - Admin duyệt bài đăng
     */
    public function approve(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Chỉ admin mới có quyền duyệt bài đăng'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:published,archived',
            'reason' => 'nullable|string|max:500',
        ]);

        $listing->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $validated['status'] === 'published' 
                ? 'Bài đăng đã được duyệt' 
                : 'Bài đăng đã bị từ chối',
            'data' => $listing->load(['user', 'shop'])
        ]);
    }

    /**
     * POST - Upload ảnh cho listing
     * Hỗ trợ upload nhiều file cùng lúc
     * 
     * FE cần gửi: multipart/form-data với field "images[]" hoặc "images"
     * 
     * QUAN TRỌNG cho FE:
     * - Content-Type: multipart/form-data (KHÔNG set thủ công, để browser tự set)
     * - Field name: images[] (có dấu ngoặc vuông)
     * - Không gửi Content-Type: application/json
     */
    public function uploadImages(Request $request, Listing $listing): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền
            if ($listing->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền upload ảnh cho bài đăng này'
                ], 403);
            }

            // Debug: Log request info để dễ debug
            \Log::info('Upload images request', [
                'listing_id' => $listing->id,
                'has_images' => $request->hasFile('images'),
                'has_image' => $request->hasFile('image'),
                'has_files' => $request->hasFile('files'),
                'all_files' => array_keys($request->allFiles()),
                'content_type' => $request->header('Content-Type'),
            ]);

            // Hỗ trợ nhiều field name khác nhau
            $files = null;
            $fieldName = null;
            
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                $fieldName = 'images';
            } elseif ($request->hasFile('image')) {
                // Hỗ trợ field name "image" (không có s)
                $files = $request->file('image');
                $fieldName = 'image';
                // Đảm bảo là array
                if (!is_array($files)) {
                    $files = [$files];
                }
            } elseif ($request->hasFile('files')) {
                // Hỗ trợ field name "files"
                $files = $request->file('files');
                $fieldName = 'files';
            }

            // Kiểm tra xem có file không
            if (!$files || (is_array($files) && count($files) === 0)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy file ảnh. Vui lòng gửi với field name "images[]"',
                    'debug' => [
                        'expected_field' => 'images[]',
                        'content_type' => 'multipart/form-data (để browser tự set, KHÔNG set thủ công)',
                        'received_files' => array_keys($request->allFiles()),
                        'hint' => 'Đảm bảo form có enctype="multipart/form-data" và input có name="images[]"'
                    ]
                ], 422);
            }

            // Đảm bảo files là array
            if (!is_array($files)) {
                $files = [$files];
            }

            // Validate số lượng và kích thước
            if (count($files) > 10) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tối đa 10 ảnh mỗi lần upload'
                ], 422);
            }

            $allowedMimes = ['jpeg', 'png', 'jpg', 'gif', 'webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "File thứ " . ($index + 1) . " không hợp lệ"
                    ], 422);
                }

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, $allowedMimes)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "File thứ " . ($index + 1) . " không đúng định dạng. Chỉ chấp nhận: " . implode(', ', $allowedMimes)
                    ], 422);
                }

                if ($file->getSize() > $maxSize) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "File thứ " . ($index + 1) . " vượt quá 5MB"
                    ], 422);
                }
            }

            $uploadedImages = [];
            $currentMaxOrder = ListingImage::where('listing_id', $listing->id)->max('sort_order') ?? 0;

            foreach ($files as $index => $image) {
                // Tạo tên file unique
                $filename = 'listing_' . $listing->id . '_' . time() . '_' . $index . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Lưu vào storage/app/public/listings
                $path = $image->storeAs('listings/' . $listing->id, $filename, 'public');
                
                // Tạo URL đầy đủ
                $url = asset('storage/' . $path);

                // Lưu vào database
                $listingImage = ListingImage::create([
                    'listing_id' => $listing->id,
                    'url' => $url,
                    'sort_order' => $currentMaxOrder + $index + 1,
                ]);

                $uploadedImages[] = $listingImage;
            }

            // Cập nhật field images trong listing (array URLs)
            $allImages = ListingImage::where('listing_id', $listing->id)
                ->orderBy('sort_order')
                ->pluck('url')
                ->toArray();
            
            $listing->update(['images' => $allImages]);

            return response()->json([
                'status' => 'success',
                'message' => 'Upload ' . count($uploadedImages) . ' ảnh thành công',
                'data' => [
                    'uploaded' => $uploadedImages,
                    'all_images' => $allImages,
                    'listing_id' => $listing->id
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Upload images error: ' . $e->getMessage(), [
                'listing_id' => $listing->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE - Xóa một ảnh của listing
     */
    public function deleteImage(Request $request, Listing $listing, int $imageId): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền
            if ($listing->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xóa ảnh này'
                ], 403);
            }

            $image = ListingImage::where('listing_id', $listing->id)
                ->where('id', $imageId)
                ->first();

            if (!$image) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy ảnh'
                ], 404);
            }

            // Xóa file từ storage
            $path = str_replace(asset('storage/'), '', $image->url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Xóa record
            $image->delete();

            // Cập nhật lại field images trong listing
            $allImages = ListingImage::where('listing_id', $listing->id)
                ->orderBy('sort_order')
                ->pluck('url')
                ->toArray();
            
            $listing->update(['images' => $allImages]);

            return response()->json([
                'status' => 'success',
                'message' => 'Đã xóa ảnh',
                'data' => [
                    'remaining_images' => $allImages
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi xóa ảnh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT - Sắp xếp lại thứ tự ảnh
     */
    public function reorderImages(Request $request, Listing $listing): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền
            if ($listing->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền sắp xếp ảnh'
                ], 403);
            }

            $request->validate([
                'image_ids' => 'required|array',
                'image_ids.*' => 'required|integer|exists:listing_images,id',
            ]);

            DB::beginTransaction();

            foreach ($request->image_ids as $order => $imageId) {
                ListingImage::where('id', $imageId)
                    ->where('listing_id', $listing->id)
                    ->update(['sort_order' => $order]);
            }

            // Cập nhật lại field images trong listing
            $allImages = ListingImage::where('listing_id', $listing->id)
                ->orderBy('sort_order')
                ->pluck('url')
                ->toArray();
            
            $listing->update(['images' => $allImages]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Đã sắp xếp lại ảnh',
                'data' => [
                    'images' => $allImages
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi sắp xếp: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET - Hiển thị thông tin chi tiết bài đăng
     */
    public function show(Request $request, Listing $listing): JsonResponse
    {
        // Tự động tăng view count
        $this->recordPageView($request, $listing);
        
        $listing->load(['user', 'shop', 'listingImages', 'comments.user', 'likes']);

        // Lấy thống kê
        $stats = [
            'views' => DB::table('page_views')->where('listing_id', $listing->id)->count(),
            'likes' => DB::table('listing_likes')->where('listing_id', $listing->id)->count(),
            'comments' => DB::table('listing_comments')->where('listing_id', $listing->id)->count(),
            'bookmarks' => DB::table('bookmarks')->where('listing_id', $listing->id)->count(),
        ];

        // Lấy danh sách comments với thông tin user
        $comments = DB::table('listing_comments')
            ->join('users', 'listing_comments.user_id', '=', 'users.id')
            ->where('listing_comments.listing_id', $listing->id)
            ->select(
                'listing_comments.id',
                'listing_comments.body',
                'listing_comments.created_at',
                'listing_comments.user_id',
                'users.full_name as user_name',
                'users.avatar_url as user_avatar'
            )
            ->orderBy('listing_comments.created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => array_merge($listing->toArray(), [
                'stats' => $stats,
                'comments_list' => $comments
            ])
        ]);
    }

    /**
     * Ghi nhận lượt xem sản phẩm
     * Chỉ tăng 1 view trong 30 phút cho cùng session + listing
     */
    private function recordPageView(Request $request, Listing $listing): void
    {
        try {
            $user = auth('api')->user();
            $sessionId = $request->header('X-Session-ID') ?? $request->ip() ?? session()->getId();
            
            // Kiểm tra xem đã có view trong 30 phút gần đây chưa
            $recentView = DB::table('page_views')
                ->where('listing_id', $listing->id)
                ->where(function ($query) use ($sessionId, $user) {
                    // Check by session_id OR user_id
                    $query->where('session_id', $sessionId);
                    if ($user) {
                        $query->orWhere('user_id', $user->id);
                    }
                })
                ->where('created_at', '>', now()->subSeconds(15))
                ->exists();
            
            // Chỉ tạo view mới nếu chưa có trong 30 phút
            if (!$recentView) {
                DB::table('page_views')->insert([
                    'company_id' => $listing->shop_id,
                    'listing_id' => $listing->id,
                    'user_id' => $user?->id,
                    'session_id' => $sessionId,
                    'path' => '/api/listings/' . $listing->id,
                    'referrer' => $request->header('referer'),
                    'user_agent' => $request->userAgent(),
                    'request_id' => uniqid('req_'),
                    'correlation_id' => $request->header('X-Correlation-ID') ?? uniqid('corr_'),
                    'duration_ms' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Log error nhưng không throw exception để không ảnh hưởng đến response
            \Log::error('Failed to record page view: ' . $e->getMessage());
        }
    }
}