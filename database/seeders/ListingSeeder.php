<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Listing;
use App\Models\User;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Str;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', 'seller')->get();
        
        if ($sellers->count() === 0) {
            $this->command->warn('⚠️  No sellers found. Please run UserSeeder first.');
            return;
        }

        $shops = \App\Models\Shop::all();

        $listings = [
            [
                'title' => 'iPhone 15 Pro Max 256GB - Chính hãng VN/A',
                'description' => 'iPhone 15 Pro Max mới 100%, nguyên seal, chính hãng Apple Việt Nam. Bảo hành 12 tháng.',
                'price_cents' => 3490000000, // 34,900,000 VND
                'category' => 'Điện thoại',
                'type' => 'sell',
                'stock_qty' => 10,
                'location_text' => 'Hà Nội',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/FF6B6B/FFFFFF?text=iPhone+15+Pro+Max',
                    'https://via.placeholder.com/800x600/4ECDC4/FFFFFF?text=iPhone+Back',
                    'https://via.placeholder.com/800x600/45B7D1/FFFFFF?text=iPhone+Side',
                ],
            ],
            [
                'title' => 'Laptop Dell XPS 13 - Core i7 Gen 13',
                'description' => 'Laptop Dell XPS 13 inch, chip Intel Core i7 thế hệ 13, RAM 16GB, SSD 512GB. Mỏng nhẹ, hiệu năng cao.',
                'price_cents' => 2890000000, // 28,900,000 VND
                'category' => 'Máy tính',
                'type' => 'sell',
                'stock_qty' => 5,
                'location_text' => 'TP.HCM',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/96CEB4/FFFFFF?text=Dell+XPS+13',
                    'https://via.placeholder.com/800x600/FFEAA7/000000?text=Laptop+Open',
                ],
            ],
            [
                'title' => 'Áo sơ mi nam công sở cao cấp',
                'description' => 'Áo sơ mi nam chất liệu cotton 100%, form dáng chuẩn, phù hợp đi làm và dự tiệc.',
                'price_cents' => 35000000, // 350,000 VND
                'category' => 'Thời trang',
                'type' => 'sell',
                'stock_qty' => 50,
                'location_text' => 'Đà Nẵng',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/DFE6E9/000000?text=Ao+So+Mi',
                ],
            ],
            [
                'title' => 'Bàn làm việc gỗ công nghiệp cao cấp',
                'description' => 'Bàn làm việc gỗ MDF phủ melamine, kích thước 120x60cm, chân sắt sơn tĩnh điện.',
                'price_cents' => 189000000, // 1,890,000 VND
                'category' => 'Nội thất',
                'type' => 'sell',
                'stock_qty' => 20,
                'location_text' => 'Hà Nội',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/A29BFE/FFFFFF?text=Ban+Lam+Viec',
                ],
            ],
            [
                'title' => 'Xe máy Honda SH 350i 2024',
                'description' => 'Honda SH 350i phiên bản 2024, màu đen, mới 100%, chưa đăng ký.',
                'price_cents' => 14500000000, // 145,000,000 VND
                'category' => 'Xe máy',
                'type' => 'sell',
                'stock_qty' => 3,
                'location_text' => 'TP.HCM',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/FD79A8/FFFFFF?text=Honda+SH+350i',
                    'https://via.placeholder.com/800x600/FDCB6E/000000?text=SH+Side+View',
                ],
            ],
            [
                'title' => 'Dịch vụ thiết kế website chuyên nghiệp',
                'description' => 'Thiết kế website theo yêu cầu, responsive, SEO chuẩn. Bảo hành 12 tháng.',
                'price_cents' => 500000000, // 5,000,000 VND
                'category' => 'Dịch vụ',
                'type' => 'service',
                'stock_qty' => 0,
                'location_text' => 'Toàn quốc',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/74B9FF/FFFFFF?text=Web+Design+Service',
                ],
            ],
            [
                'title' => 'Tai nghe AirPods Pro 2 - Chính hãng Apple',
                'description' => 'AirPods Pro thế hệ 2, chống ồn chủ động, sạc MagSafe, bảo hành 12 tháng.',
                'price_cents' => 649000000, // 6,490,000 VND
                'category' => 'Phụ kiện',
                'type' => 'sell',
                'stock_qty' => 15,
                'location_text' => 'Hà Nội',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/00B894/FFFFFF?text=AirPods+Pro+2',
                ],
            ],
            [
                'title' => 'Giày thể thao Nike Air Max 2024',
                'description' => 'Giày Nike Air Max 2024, chính hãng, đủ size, nhiều màu sắc.',
                'price_cents' => 329000000, // 3,290,000 VND
                'category' => 'Giày dép',
                'type' => 'sell',
                'stock_qty' => 30,
                'location_text' => 'TP.HCM',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/E17055/FFFFFF?text=Nike+Air+Max',
                ],
            ],
            [
                'title' => 'Tủ lạnh Samsung Inverter 360L',
                'description' => 'Tủ lạnh Samsung 360 lít, công nghệ Inverter tiết kiệm điện, bảo hành 12 tháng.',
                'price_cents' => 890000000, // 8,900,000 VND
                'category' => 'Đồ gia dụng',
                'type' => 'sell',
                'stock_qty' => 8,
                'location_text' => 'Đà Nẵng',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/0984E3/FFFFFF?text=Tu+Lanh+Samsung',
                ],
            ],
            [
                'title' => 'Dịch vụ vận chuyển hàng hóa toàn quốc',
                'description' => 'Vận chuyển hàng hóa nhanh chóng, an toàn. Giá cả cạnh tranh.',
                'price_cents' => 0, // Liên hệ
                'category' => 'Vận chuyển',
                'type' => 'service',
                'stock_qty' => 0,
                'location_text' => 'Toàn quốc',
                'status' => 'published',
                'images' => [
                    'https://via.placeholder.com/800x600/6C5CE7/FFFFFF?text=Van+Chuyen+Service',
                ],
            ],
        ];

        foreach ($listings as $index => $listingData) {
            // Chọn seller theo vòng lặp
            $seller = $sellers[$index % $sellers->count()];
            
            // Tìm shop thuộc về seller này (quan trọng: phải cùng owner)
            $shop = \App\Models\Shop::where('owner_user_id', $seller->id)->first();
            
            // Nếu seller chưa có shop, bỏ qua listing này
            if (!$shop) {
                $this->command->warn("⚠️  Seller {$seller->email} chưa có shop, bỏ qua listing: {$listingData['title']}");
                continue;
            }

            $listingData['user_id'] = $seller->id;
            $listingData['shop_id'] = $shop->id;
            $listingData['slug'] = Str::slug($listingData['title']) . '-' . time() . '-' . $index;
            $listingData['is_active'] = true;
            $listingData['is_public'] = true;
            $listingData['currency'] = 'VND';

            Listing::create($listingData);
        }

        $this->command->info(' Created ' . count($listings) . ' listings');
    }
}
