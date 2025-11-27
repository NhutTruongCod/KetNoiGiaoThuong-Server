<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Sách',
                'slug' => 'sach',
                'description' => 'Sách các loại'
            ],
            [
                'name' => 'Điện tử',
                'slug' => 'dien-tu',
                'description' => 'Thiết bị điện tử'
            ],
            [
                'name' => 'Thời trang',
                'slug' => 'thoi-trang',
                'description' => 'Quần áo và phụ kiện thời trang'
            ],
            [
                'name' => 'Đồ gia dụng',
                'slug' => 'do-gia-dung',
                'description' => 'Đồ dùng gia đình'
            ],
            [
                'name' => 'Thể thao',
                'slug' => 'the-thao',
                'description' => 'Dụng cụ và trang phục thể thao'
            ],
            [
                'name' => 'Mỹ phẩm',
                'slug' => 'my-pham',
                'description' => 'Sản phẩm làm đẹp'
            ],
            [
                'name' => 'Đồ chơi',
                'slug' => 'do-choi',
                'description' => 'Đồ chơi trẻ em'
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}