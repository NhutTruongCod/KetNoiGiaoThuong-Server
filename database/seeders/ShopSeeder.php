<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', 'seller')->get();

        if ($sellers->count() === 0) {
            $this->command->warn('  No sellers found. Skipping shop seeder.');
            return;
        }

        $shops = [
            [
                'name' => 'Cửa hàng Điện tử ABC',
                'description' => 'Chuyên cung cấp thiết bị điện tử chính hãng',
            ],
            [
                'name' => 'Thời trang XYZ',
                'description' => 'Thời trang nam nữ cao cấp',
            ],
            [
                'name' => 'Nội thất 123',
                'description' => 'Nội thất văn phòng và gia đình',
            ],
        ];

        foreach ($shops as $index => $shopData) {
            if (isset($sellers[$index])) {
                $shopData['owner_user_id'] = $sellers[$index]->id;
                Shop::create($shopData);
            }
        }

        $this->command->info(' Created ' . count($shops) . ' shops');
    }
}
