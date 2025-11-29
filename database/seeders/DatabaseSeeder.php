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
        // 1. Admin (mật khẩu: password)
        User::create([
            'full_name'    => 'Super Admin',     // dùng đúng tên cột
            'email'        => 'admin@gmail.com',
            'phone'        => '0900000001',
            'password_hash'=> Hash::make('password'),  // dùng đúng tên cột
            'role'         => 'admin',            // enum: admin/seller/buyer
            'status'       => 'active',
            'is_verified'  => true,
            'is_active'    => true,
        ]);

        // 2. User test (mật khẩu: password)
        User::create([
            'full_name'    => 'Khách Mua Hàng',
            'email'        => 'buyer@gmail.com',
            'phone'        => '0900000002',
            'password_hash'=> Hash::make('password'),
            'role'         => 'buyer',            // role hợp lệ
            'status'       => 'active',
            'is_verified'  => true,
            'is_active'    => true,
        ]);
    }
}
