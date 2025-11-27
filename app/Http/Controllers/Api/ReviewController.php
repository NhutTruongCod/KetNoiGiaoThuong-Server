<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // 1. GET: Lấy tất cả đánh giá
    public function index()
    {
        $reviews = Review::with(['reviewer', 'order.shop'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    // 2. GET: Lấy theo ID
    public function show($id)
    {
        $review = Review::with(['reviewer', 'order.shop'])->find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }

    // 3. POST: Tạo đánh giá mới
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reviewer_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'nullable|string'
        ]);

        $order_id = $request->input('order_id');
        $reviewer_id = $request->input('reviewer_id');

        // Kiểm tra: chỉ người mua mới được đánh giá
        $order = Order::find($order_id);
        if ($order->buyer_id != $reviewer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chỉ có thể đánh giá đơn hàng của chính mình'
            ], 403);
        }

        // Kiểm tra: chỉ đánh giá 1 lần
        $exists = Review::where('order_id', $order_id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này đã được đánh giá'
            ], 400);
        }

        $review = Review::create([
            'order_id' => $order_id,
            'reviewer_id' => $reviewer_id,
            'rating' => $request->input('rating'),
            'content' => $request->input('content')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá thành công',
            'data' => $review->load(['reviewer', 'order.shop'])
        ], 201);
    }

    // 4. PUT: Cập nhật đánh giá
    public function update(Request $request, $id)
    {
    $review = Review::find($id);

    if (!$review) {
        return response()->json([
            'success' => false,
            'message' => 'Đánh giá không tồn tại'
        ], 404);
    }

    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'content' => 'nullable|string'
    ]);

    $review->update([
        'rating' => $request->input('rating'),
        'content' => $request->input('content')
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Cập nhật đánh giá thành công',
        'data' => $review->fresh(['reviewer', 'order.shop'])
    ]);
    }

    // 5. DELETE: Xóa đánh giá
    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại'
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đánh giá thành công'
        ]);
    }
}