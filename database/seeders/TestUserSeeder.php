<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Tạo các user test để đăng nhập
     */
    public function run()
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@tradehub.com'],
            [
                'full_name' => 'Admin User',
                'password_hash' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'is_verified' => true,
                'is_active' => true,
                'provider' => 'local',
                'email_verified_at' => now(),
            ]
        );

        // Seller user
        User::updateOrCreate(
            ['email' => 'seller@tradehub.com'],
            [
                'full_name' => 'Seller User',
                'password_hash' => Hash::make('seller123'),
                'role' => 'seller',
                'status' => 'active',
                'is_verified' => true,
                'is_active' => true,
                'provider' => 'local',
                'email_verified_at' => now(),
            ]
        );

        // Buyer user
        User::updateOrCreate(
            ['email' => 'buyer@tradehub.com'],
            [
                'full_name' => 'Buyer User',
                'password_hash' => Hash::make('buyer123'),
                'role' => 'buyer',
                'status' => 'active',
                'is_verified' => true,
                'is_active' => true,
                'provider' => 'local',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Created 3 test users:');
        $this->command->info('   - admin@tradehub.com / admin123 (Admin)');
        $this->command->info('   - seller@tradehub.com / seller123 (Seller)');
        $this->command->info('   - buyer@tradehub.com / buyer123 (Buyer)');
    }
}
