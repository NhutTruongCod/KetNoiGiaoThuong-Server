<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $store1 = Store::first();
        $store2 = Store::where('name', 'Cửa hàng điện tử TechZone')->first();
        
        $bookCategory = Category::where('name', 'Sách')->first();
        $electronicCategory = Category::where('name', 'Điện tử')->first();
        $fashionCategory = Category::where('name', 'Thời trang')->first();

        Listing::create([
            'title' => 'Sách kỹ năng sống',
            'description' => 'Cuốn sách giúp phát triển tư duy và thói quen tốt.',
            'price' => 85000,
            'category_id' => $bookCategory->id,
            'store_id' => $store1->id,
        ]);

        Listing::create([
            'title' => 'Tai nghe Bluetooth',
            'description' => 'Tai nghe không dây âm thanh rõ nét, pin 12 giờ.',
            'price' => 450000,
            'category_id' => $electronicCategory->id,
            'store_id' => $store2->id,
        ]);

        Listing::create([
            'title' => 'Áo sơ mi nam cao cấp',
            'description' => 'Áo sơ mi cotton thoáng mát, phù hợp đi làm.',
            'price' => 299000,
            'category_id' => $fashionCategory->id,
            'store_id' => $store2->id,
        ]);
    }
}