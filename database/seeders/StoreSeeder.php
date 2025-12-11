<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', 'seller')->get();

        if ($sellers->isEmpty()) {
            $this->command->warn('⚠️  No sellers found. Please run UserSeeder first.');
            return;
        }

        $shops = [
            [
                'name' => 'Cửa hàng Điện tử ABC',
                'slug' => 'cua-hang-dien-tu-abc',
                'description' => 'Chuyên cung cấp thiết bị điện tử chính hãng',
                'business_type' => 'retail',
                'rating' => 4.5,
            ],
            [
                'name' => 'Thời trang XYZ',
                'slug' => 'thoi-trang-xyz',
                'description' => 'Thời trang nam nữ cao cấp',
                'business_type' => 'retail',
                'rating' => 4.8,
            ],
            [
                'name' => 'Nội thất 123',
                'slug' => 'noi-that-123',
                'description' => 'Nội thất văn phòng và gia đình',
                'business_type' => 'wholesale',
                'rating' => 4.3,
            ],
        ];

        foreach ($shops as $index => $shopData) {
            if (isset($sellers[$index])) {
                $shopData['user_id'] = $sellers[$index]->id;
                $shopData['is_active'] = true;
                Shop::create($shopData);
            }
        }

        $this->command->info('✅ Created ' . count($shops) . ' shops');
    }
}
