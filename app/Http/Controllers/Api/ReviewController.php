<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use App\Models\Listing;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Review Controller - Hệ thống đánh giá
 * 
 * Nghiệp vụ 3.7: Đánh giá & Xếp hạng Đối tác
 * - Tiêu chí: uy tín, chất lượng sản phẩm, thái độ phục vụ, thời gian giao hàng
 * - Hệ thống hiển thị điểm đánh giá và nhận xét công khai
 */
class ReviewController extends Controller
{
    /**
     * 1. GET: Danh sách đánh giá (Public - không cần auth)
     * Filter: listing_id, shop_id, rating, verified
     * Sort: helpful_count, created_at
     * Pagination
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'listing', 'shop']);

        // Filter by listing
        if ($request->has('listing_id')) {
            $query->byListing($request->listing_id);
        }

        // Filter by shop
        if ($request->has('shop_id')) {
            $query->byShop($request->shop_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->byRating($request->rating);
        }

        // Filter verified purchases only
        if ($request->has('verified') && $request->verified == 'true') {
            $query->verified();
        }

        // Filter with seller reply
        if ($request->has('with_reply') && $request->with_reply == 'true') {
            $query->withSellerReply();
        }

        // Filter with images
        if ($request->has('with_images') && $request->with_images == 'true') {
            $query->withImages();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['helpful_count', 'rating', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $reviews = $query->paginate($perPage);

        // Add is_helpful flag for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            foreach ($reviews as $review) {
                $review->is_helpful = $review->isHelpfulBy($user);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * 2. GET: Chi tiết đánh giá (Public)
     */
    public function show($id)
    {
        $review = Review::with(['user', 'listing', 'shop', 'order'])->find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        // Add is_helpful flag for authenticated users
        if (Auth::check()) {
            $review->is_helpful = $review->isHelpfulBy(Auth::user());
        }

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }

    /**
     * 3. POST: Tạo đánh giá mới (Auth required)
     * - Chỉ đánh giá được đơn đã completed
     * - Tự động set is_verified_purchase
     * - Upload images
     * - Update listing/shop rating
     */
    public function store(Request $request)
    {
        // Validation rules - hỗ trợ cả JSON và form-data
        $rules = [
            'order_id' => 'required|exists:orders,id',
            'listing_id' => 'nullable|exists:listings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
        ];

        // Nếu có file upload (form-data)
        if ($request->hasFile('images')) {
            $rules['images'] = 'nullable|array|max:5';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg|max:2048';
        } 
        // Nếu có array URLs (JSON)
        else if ($request->has('images') && is_array($request->images)) {
            $rules['images'] = 'nullable|array|max:5';
            $rules['images.*'] = 'string|url';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $orderId = $request->order_id;

        // Kiểm tra order tồn tại và thuộc về user
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng không tồn tại'
            ], 404);
        }

        if ($order->buyer_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chỉ có thể đánh giá đơn hàng của chính mình'
            ], 403);
        }

        // Kiểm tra order đã completed
        if ($order->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể đánh giá đơn hàng đã hoàn thành'
            ], 400);
        }

        // Kiểm tra đã đánh giá chưa
        $exists = Review::where('order_id', $orderId)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này đã được đánh giá'
            ], 400);
        }

        // Xử lý images
        $imageUrls = [];
        
        // Cách 1: Upload files (form-data)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $imageUrls[] = Storage::url($path);
            }
        }
        // Cách 2: Array URLs (JSON)
        else if ($request->has('images') && is_array($request->images)) {
            $imageUrls = $request->images;
        }

        // Get listing and shop from order
        $listingId = $request->listing_id ?? $order->listing_id;
        $listing = Listing::find($listingId);
        $shopId = $listing ? $listing->shop_id : null;

        // Create review
        $review = Review::create([
            'order_id' => $orderId,
            'listing_id' => $listingId,
            'shop_id' => $shopId,
            'user_id' => $userId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => $imageUrls,
            'is_verified_purchase' => true, // Auto set for order-based reviews
        ]);

        // Update listing and shop ratings
        $review->updateRatings();

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá thành công',
            'data' => $review->load(['user', 'listing', 'shop'])
        ], 201);
    }

    /**
     * 4. PUT: Cập nhật đánh giá (Auth required)
     */
    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        // Check ownership
        if ($review->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật đánh giá này'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|min:10|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload new images if provided
        if ($request->hasFile('images')) {
            // Delete old images
            if ($review->images) {
                foreach ($review->images as $imageUrl) {
                    $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
                    Storage::disk('public')->delete($path);
                }
            }

            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $imageUrls[] = Storage::url($path);
            }
            $review->images = $imageUrls;
        }

        // Update fields
        if ($request->has('rating')) {
            $review->rating = $request->rating;
        }
        if ($request->has('comment')) {
            $review->comment = $request->comment;
        }

        $review->save();

        // Update ratings
        $review->updateRatings();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đánh giá thành công',
            'data' => $review->fresh(['user', 'listing', 'shop'])
        ]);
    }

    /**
     * 5. DELETE: Xóa đánh giá (Auth required)
     */
    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        // Check ownership or admin
        $user = Auth::user();
        if ($review->user_id != $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa đánh giá này'
            ], 403);
        }

        // Delete images
        if ($review->images) {
            foreach ($review->images as $imageUrl) {
                $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
                Storage::disk('public')->delete($path);
            }
        }

        $listingId = $review->listing_id;
        $shopId = $review->shop_id;

        $review->delete();

        // Update ratings after deletion
        if ($listingId) {
            $listing = Listing::find($listingId);
            if ($listing) {
                $avgRating = Review::where('listing_id', $listingId)->avg('rating');
                $totalReviews = Review::where('listing_id', $listingId)->count();
                $listing->update([
                    'rating' => round($avgRating, 2),
                    'total_reviews' => $totalReviews,
                ]);
            }
        }

        if ($shopId) {
            $shop = Shop::find($shopId);
            if ($shop) {
                $avgRating = Review::where('shop_id', $shopId)->avg('rating');
                $totalReviews = Review::where('shop_id', $shopId)->count();
                $shop->update([
                    'rating' => round($avgRating, 2),
                    'total_reviews' => $totalReviews,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa đánh giá thành công'
        ]);
    }

    /**
     * 6. POST: Mark review as helpful (Auth required)
     */
    public function markAsHelpful($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        $user = Auth::user();
        $result = $review->markAsHelpful($user);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đánh dấu đánh giá này là hữu ích rồi'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu đánh giá là hữu ích',
            'data' => [
                'helpful_count' => $review->fresh()->helpful_count
            ]
        ]);
    }

    /**
     * 7. DELETE: Unmark review as helpful (Auth required)
     */
    public function unmarkAsHelpful($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        $user = Auth::user();
        $result = $review->unmarkAsHelpful($user);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đánh dấu đánh giá này là hữu ích'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã bỏ đánh dấu hữu ích',
            'data' => [
                'helpful_count' => $review->fresh()->helpful_count
            ]
        ]);
    }

    /**
     * 8. POST: Seller reply to review (Auth required - seller only)
     */
    public function addSellerReply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|min:10|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        $user = Auth::user();

        // Check if user is the shop owner
        if ($review->shop && $review->shop->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ shop mới có thể phản hồi đánh giá'
            ], 403);
        }

        $review->addSellerReply($request->reply, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Đã phản hồi đánh giá',
            'data' => $review->fresh(['user', 'listing', 'shop'])
        ]);
    }

    /**
     * 9. GET: Rating summary and distribution (Public)
     */
    public function getSummary(Request $request)
    {
        $listingId = $request->get('listing_id');
        $shopId = $request->get('shop_id');

        if (!$listingId && !$shopId) {
            return response()->json([
                'success' => false,
                'message' => 'Cần cung cấp listing_id hoặc shop_id'
            ], 400);
        }

        $summary = Review::getSummary($listingId, $shopId);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * 10. GET: My reviews (Auth required)
     */
    public function myReviews(Request $request)
    {
        $userId = Auth::id();
        
        $query = Review::with(['listing', 'shop', 'order'])
            ->where('user_id', $userId);

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['rating', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}