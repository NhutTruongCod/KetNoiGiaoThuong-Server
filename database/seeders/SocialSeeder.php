<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ListingLike;
use App\Models\ListingComment;
use App\Models\Listing;
use App\Models\User;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $listings = Listing::all(); // Lấy TẤT CẢ listings

        if ($users->count() === 0 || $listings->count() === 0) {
            $this->command->warn('⚠️  No users or listings found. Skipping social seeder.');
            return;
        }

        $likesCount = 0;
        $commentsCount = 0;

        // Create likes cho TẤT CẢ listings
        foreach ($listings as $listing) {
            // Random 5-12 users like each listing
            $likeCount = rand(5, 12);
            $randomUsers = $users->random(min($likeCount, $users->count()));

            foreach ($randomUsers as $user) {
                ListingLike::firstOrCreate([
                    'listing_id' => $listing->id,
                    'user_id' => $user->id,
                ]);
                $likesCount++;
            }
        }

        // Create comments cho TẤT CẢ listings
        $comments = [
            'Sản phẩm này có bảo hành không shop?',
            'Còn hàng không ạ?',
            'Giá này đã bao gồm ship chưa?',
            'Sản phẩm chất lượng tốt, mình đã mua rồi!',
            'Shop giao hàng nhanh không?',
            'Có màu khác không shop?',
            'Cho mình xin thêm ảnh thực tế được không?',
            'Sản phẩm này có giảm giá không?',
            'Mình muốn mua 2 cái, có giảm không?',
            'Shop ơi, inbox mình với!',
            'Chất lượng sản phẩm thế nào shop?',
            'Có ship COD không ạ?',
            'Sản phẩm này còn hàng không shop?',
            'Mình ở Hà Nội, bao lâu nhận được hàng?',
            'Có thể xem hàng trước khi mua không?',
        ];

        foreach ($listings as $listing) {
            // Random 3-8 comments per listing
            $commentCount = rand(3, 8);

            for ($i = 0; $i < $commentCount; $i++) {
                $randomUser = $users->random();
                $randomComment = $comments[array_rand($comments)];

                ListingComment::create([
                    'listing_id' => $listing->id,
                    'user_id' => $randomUser->id,
                    'body' => $randomComment,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
                $commentsCount++;
            }
        }

        $this->command->info('✅ Created ' . $likesCount . ' likes and ' . $commentsCount . ' comments for ' . $listings->count() . ' listings');
    }
}
