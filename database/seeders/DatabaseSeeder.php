<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Tạo ông Admin (Mật khẩu là: password)
        User::create([
            'name' => 'Admin',
            'full_name' => 'Super Admin',
            'email' => 'admin@gmail.com',
            'phone' => '0900000001',
            'password' => Hash::make('password'), // Tự động mã hóa
            'role' => 'Admin',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        // 2. Tạo thêm ông User thường để test (Mật khẩu: password)
        User::create([
            'name' => 'User Test',
            'full_name' => 'Khách Mua Hàng',
            'email' => 'buyer@gmail.com',
            'phone' => '0900000002',
            'password' => 'password',
            'role' => 'User',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);
    }
}
