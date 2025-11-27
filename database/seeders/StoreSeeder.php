<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        Store::create([
            'name' => 'Nhà sách Minh Tâm',
            'owner_name' => 'Nguyễn Văn A',
            'email' => 'minhtam@example.com',
            'phone' => '0909123456',
            'address' => '12 Nguyễn Trãi, Hà Nội',
        ]);

        Store::create([
            'name' => 'Cửa hàng điện tử TechZone',
            'owner_name' => 'Trần Thị B',
            'email' => 'techzone@example.com',
            'phone' => '0909988776',
            'address' => '25 Lý Thường Kiệt, TP.HCM',
        ]);
    }
}