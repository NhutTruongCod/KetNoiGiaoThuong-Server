<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Order;
use App\Models\Listing;
use App\Models\User;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy các đơn hàng đã completed
        $completedOrders = Order::where('status', 'completed')->with('listing')->get();

        if ($completedOrders->isEmpty()) {
            $this->command->warn('⚠️  Không có đơn hàng completed nào để tạo review');
            return;
        }

        $comments = [
            'Sản phẩm tốt, giao hàng nhanh! Rất hài lòng với chất lượng.',
            'Chất lượng ổn, giá hợp lý. Sẽ ủng hộ shop lâu dài.',
            'Rất hài lòng với sản phẩm. Đóng gói cẩn thận, giao hàng đúng hẹn.',
            'Đóng gói cẩn thận, ship nhanh. Sản phẩm đúng như mô tả.',
            'Sản phẩm đúng mô tả, chất lượng tốt. Giá cả phải chăng.',
            'Tạm ổn, có thể cải thiện thêm về bao bì đóng gói.',
            'Không như mong đợi lắm nhưng vẫn chấp nhận được.',
            'Giao hàng hơi chậm nhưng sản phẩm ok, chất lượng tốt.',
            'Sản phẩm chất lượng cao, đáng đồng tiền bát gạo!',
            'Shop phục vụ nhiệt tình, sản phẩm tuyệt vời!',
        ];

        $sampleImages = [
            ['/storage/reviews/sample1.jpg', '/storage/reviews/sample2.jpg'],
            ['/storage/reviews/sample3.jpg'],
            ['/storage/reviews/sample4.jpg', '/storage/reviews/sample5.jpg', '/storage/reviews/sample6.jpg'],
            null, // Không có ảnh
            null,
        ];

        $sellerReplies = [
            'Cảm ơn bạn đã tin tưởng shop! Chúng tôi rất vui khi bạn hài lòng.',
            'Cảm ơn bạn đã đánh giá. Chúng tôi sẽ cố gắng phục vụ tốt hơn!',
            'Rất vui khi bạn hài lòng với sản phẩm. Hẹn gặp lại!',
            'Cảm ơn góp ý của bạn. Chúng tôi sẽ cải thiện dịch vụ.',
            null, // Không có reply
            null,
        ];

        $createdReviews = [];

        foreach ($completedOrders as $index => $order) {
            // Luôn tạo review cho 2 đơn đầu tiên, sau đó 70% cho các đơn còn lại
            $shouldCreateReview = ($index < 2) || (rand(1, 10) <= 7);
            
            if ($shouldCreateReview) {
                $listing = $order->listing;
                if (!$listing) continue;

                $rating = rand(3, 5); // Rating từ 3-5 sao
                $hasImages = rand(1, 10) <= 4; // 40% có ảnh
                $hasReply = rand(1, 10) <= 5; // 50% có seller reply
                
                $images = null;
                if ($hasImages) {
                    $selectedImages = $sampleImages[array_rand($sampleImages)];
                    if ($selectedImages) {
                        $images = $selectedImages;
                    }
                }

                $sellerReply = null;
                $sellerReplyAt = null;
                if ($hasReply) {
                    $replyContent = $sellerReplies[array_rand($sellerReplies)];
                    if ($replyContent) {
                        $sellerReply = [
                            'content' => $replyContent,
                            'user_id' => $listing->shop->user_id ?? 1,
                            'created_at' => now()->subDays(rand(0, 15))->toISOString(),
                        ];
                        $sellerReplyAt = now()->subDays(rand(0, 15));
                    }
                }
                
                $reviewData = [
                    'order_id' => $order->id,
                    'listing_id' => $listing->id,
                    'shop_id' => $listing->shop_id,
                    'user_id' => $order->buyer_id,
                    'rating' => $rating,
                    'comment' => $comments[array_rand($comments)],
                    'images' => $images,
                    'helpful_count' => rand(0, 50),
                    'is_verified_purchase' => true,
                    'seller_reply' => $sellerReply,
                    'seller_reply_at' => $sellerReplyAt,
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ];

                $review = Review::create($reviewData);
                $createdReviews[] = $review;

                // Tạo helpful marks (một số user đánh dấu hữu ích)
                if ($review->helpful_count > 0) {
                    $users = User::inRandomOrder()->limit(min($review->helpful_count, 10))->get();
                    foreach ($users as $user) {
                        try {
                            \DB::table('review_helpful')->insert([
                                'review_id' => $review->id,
                                'user_id' => $user->id,
                                'created_at' => now()->subDays(rand(0, 20)),
                            ]);
                        } catch (\Exception $e) {
                            // Skip if duplicate
                        }
                    }
                }
            }
        }

        if (!empty($createdReviews)) {
            // Cập nhật rating cho listings và shops
            foreach ($createdReviews as $review) {
                $review->updateRatings();
            }
            
            $this->command->info('✅ Đã tạo ' . count($createdReviews) . ' reviews với đầy đủ tính năng');
        } else {
            $this->command->warn('⚠️  Không có review nào được tạo');
        }
    }
}
