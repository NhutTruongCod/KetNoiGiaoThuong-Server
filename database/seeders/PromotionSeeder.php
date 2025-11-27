<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'title' => 'Giảm giá 50% mùa hè',
                'description' => 'Khuyến mãi đặc biệt cho sách học tập.',
                'image_url' => 'https://example.com/banner1.jpg',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addDays(25),
                'discount_percentage' => 50.00,
                'min_order_amount' => 0,
                'max_usage' => 1000,
                'promo_code' => 'SUMMER50',
                'is_featured' => true,
            ],
            [
                'title' => 'Khuyến mãi tháng 10',
                'description' => 'Giảm giá 30% tất cả sản phẩm sách mới.',
                'image_url' => 'https://example.com/banner10.jpg',
                'status' => 'upcoming',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(40),
                'discount_percentage' => 30.00,
                'min_order_amount' => 100000,
                'promo_code' => 'OCT30',
                'is_featured' => false,
            ],
            [
                'title' => 'Flash Sale Cuối Tuần',
                'description' => 'Giảm giá sốc các sản phẩm điện tử.',
                'image_url' => 'https://example.com/flash-sale.jpg',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(1),
                'end_date' => Carbon::now()->addDays(2),
                'discount_percentage' => 70.00,
                'min_order_amount' => 500000,
                'max_usage' => 100,
                'promo_code' => 'FLASH70',
                'is_featured' => true,
            ],
            [
                'title' => 'Khuyến mãi đã kết thúc',
                'description' => 'Khuyến mãi cũ đã hết hạn.',
                'image_url' => 'https://example.com/expired.jpg',
                'status' => 'expired',
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->subDays(1),
                'discount_percentage' => 20.00,
                'promo_code' => 'EXPIRED20',
                'is_featured' => false,
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::create($promotion);
        }
    }
}