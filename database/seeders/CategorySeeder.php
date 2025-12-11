<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Shop;
use App\Models\User;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Lấy các shops để tạo categories cho từng shop
        $shops = Shop::all();
        
        if ($shops->isEmpty()) {
            $this->command->warn('⚠️  No shops found. Please run ShopSeeder first.');
            return;
        }

        // Categories mẫu cho từng loại công ty
        $categoriesTemplates = [
            // Shop 1: Công ty Điện Tử
            1 => [
                [
                    'name' => 'Linh kiện điện tử',
                    'slug' => 'linh-kien-dien-tu',
                    'description' => 'IC, transistor, điện trở, tụ điện',
                    'children' => [
                        ['name' => 'IC vi mạch', 'slug' => 'ic-vi-mach'],
                        ['name' => 'Transistor', 'slug' => 'transistor'],
                        ['name' => 'Điện trở - Tụ điện', 'slug' => 'dien-tro-tu-dien'],
                    ]
                ],
                [
                    'name' => 'Thiết bị công nghiệp',
                    'slug' => 'thiet-bi-cong-nghiep',
                    'description' => 'Thiết bị tự động hóa, PLC, cảm biến',
                    'children' => [
                        ['name' => 'PLC', 'slug' => 'plc'],
                        ['name' => 'Cảm biến', 'slug' => 'cam-bien'],
                    ]
                ],
            ],
            // Shop 2: Công ty Thực Phẩm
            2 => [
                [
                    'name' => 'Nguyên liệu thô',
                    'slug' => 'nguyen-lieu-tho',
                    'description' => 'Bột, đường, muối, gia vị',
                    'children' => [
                        ['name' => 'Bột các loại', 'slug' => 'bot-cac-loai'],
                        ['name' => 'Đường - Muối', 'slug' => 'duong-muoi'],
                    ]
                ],
                [
                    'name' => 'Thực phẩm chế biến',
                    'slug' => 'thuc-pham-che-bien',
                    'description' => 'Đồ hộp, đồ đóng gói',
                ],
            ],
            // Shop 3: Công ty Vật Liệu Xây Dựng
            3 => [
                [
                    'name' => 'Vật liệu xây dựng',
                    'slug' => 'vat-lieu-xay-dung',
                    'description' => 'Xi măng, sắt thép, gạch',
                    'children' => [
                        ['name' => 'Xi măng', 'slug' => 'xi-mang'],
                        ['name' => 'Sắt thép', 'slug' => 'sat-thep'],
                        ['name' => 'Gạch ốp lát', 'slug' => 'gach-op-lat'],
                    ]
                ],
            ],
        ];

        foreach ($shops as $index => $shop) {
            $shopIndex = $index + 1;
            $categories = $categoriesTemplates[$shopIndex] ?? [
                [
                    'name' => 'Sản phẩm chung',
                    'slug' => 'san-pham-chung',
                    'description' => 'Danh mục sản phẩm chung',
                ]
            ];

            foreach ($categories as $categoryData) {
                $children = $categoryData['children'] ?? [];
                unset($categoryData['children']);

                $categoryData['shop_id'] = $shop->id;
                $categoryData['user_id'] = $shop->owner_user_id;
                $categoryData['is_active'] = true;
                $categoryData['status'] = 'approved';

                $parent = Category::create($categoryData);

                foreach ($children as $childData) {
                    $childData['shop_id'] = $shop->id;
                    $childData['user_id'] = $shop->owner_user_id;
                    $childData['parent_id'] = $parent->id;
                    $childData['is_active'] = true;
                    $childData['status'] = 'approved';
                    $childData['description'] = null;
                    Category::create($childData);
                }
            }

            $this->command->info("✅ Created categories for shop: {$shop->name}");
        }

        $this->command->info('✅ All shop categories created successfully');
    }
}
