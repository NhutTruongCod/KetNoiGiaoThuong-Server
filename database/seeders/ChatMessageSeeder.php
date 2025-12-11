<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Listing;

class ChatMessageSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::where('role', 'buyer')->get();
        $sellers = User::where('role', 'seller')->get();
        $listings = Listing::all();

        if ($buyers->count() === 0 || $sellers->count() === 0) {
            $this->command->warn('⚠️  No buyers or sellers found. Skipping chat seeder.');
            return;
        }

        $conversations = [
            [
                'buyer_index' => 0,
                'seller_index' => 0,
                'listing_index' => 0,
                'messages' => [
                    ['from' => 'buyer', 'body' => 'Xin chào, sản phẩm này còn hàng không ạ?'],
                    ['from' => 'seller', 'body' => 'Dạ còn ạ, bạn cần bao nhiêu?'],
                    ['from' => 'buyer', 'body' => 'Mình cần 2 cái, có giảm giá không shop?'],
                    ['from' => 'seller', 'body' => 'Mua 2 cái mình giảm 5% cho bạn nhé'],
                    ['from' => 'buyer', 'body' => 'Ok, vậy mình đặt hàng luôn'],
                ],
            ],
            [
                'buyer_index' => 1,
                'seller_index' => 0,
                'listing_index' => 1,
                'messages' => [
                    ['from' => 'buyer', 'body' => 'Sản phẩm này có bảo hành không shop?'],
                    ['from' => 'seller', 'body' => 'Có bảo hành 12 tháng chính hãng ạ'],
                ],
            ],
            [
                'buyer_index' => 0,
                'seller_index' => 1,
                'listing_index' => 2,
                'messages' => [
                    ['from' => 'buyer', 'body' => 'Shop giao hàng mất bao lâu?'],
                    ['from' => 'seller', 'body' => 'Nội thành 1-2 ngày, ngoại thành 3-5 ngày ạ'],
                    ['from' => 'buyer', 'body' => 'Được, cảm ơn shop'],
                ],
            ],
            [
                'buyer_index' => 2,
                'seller_index' => 1,
                'listing_index' => 3,
                'messages' => [
                    ['from' => 'buyer', 'body' => 'Cho mình xem thêm ảnh thực tế được không?'],
                    ['from' => 'seller', 'body' => 'Được ạ, mình gửi qua Zalo cho bạn nhé'],
                ],
            ],
        ];

        $count = 0;
        foreach ($conversations as $conv) {
            if ($buyers->count() <= $conv['buyer_index'] || 
                $sellers->count() <= $conv['seller_index']) {
                continue;
            }

            $buyer = $buyers[$conv['buyer_index']];
            $seller = $sellers[$conv['seller_index']];
            $listing = $listings->count() > $conv['listing_index'] 
                ? $listings[$conv['listing_index']] 
                : null;

            foreach ($conv['messages'] as $index => $msg) {
                $fromUser = $msg['from'] === 'buyer' ? $buyer : $seller;
                $toUser = $msg['from'] === 'buyer' ? $seller : $buyer;
                
                ChatMessage::create([
                    'from_user_id' => $fromUser->id,
                    'to_user_id' => $toUser->id,
                    'listing_id' => $listing?->id,
                    'body' => $msg['body'],
                    'is_read' => $index < count($conv['messages']) - 1, // Last message unread
                    'created_at' => now()->subDays(rand(1, 7))->addMinutes($index * 5),
                ]);
                $count++;
            }
        }

        $this->command->info('✅ Created ' . $count . ' chat messages');
    }
}
