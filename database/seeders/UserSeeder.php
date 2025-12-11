<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'email' => 'admin@tradehub.com',
            'password_hash' => Hash::make('admin123'),
            'full_name' => 'Admin TradeHub',
            'phone' => '0901000001',
            'role' => 'admin',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        // Sellers
        User::create([
            'email' => 'seller1@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Công ty TNHH ABC',
            'phone' => '0901000002',
            'role' => 'seller',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        User::create([
            'email' => 'seller2@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Doanh nghiệp XYZ',
            'phone' => '0901000003',
            'role' => 'seller',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        User::create([
            'email' => 'seller3@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Cửa hàng Điện tử 123',
            'phone' => '0901000004',
            'role' => 'seller',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        // Buyers
        User::create([
            'email' => 'buyer1@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0902000001',
            'role' => 'buyer',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        User::create([
            'email' => 'buyer2@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Trần Thị B',
            'phone' => '0902000002',
            'role' => 'buyer',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        User::create([
            'email' => 'buyer3@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Lê Văn C',
            'phone' => '0902000003',
            'role' => 'buyer',
            'status' => 'active',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $this->command->info('Created 7 users (1 admin, 3 sellers, 3 buyers)');
    }
}
