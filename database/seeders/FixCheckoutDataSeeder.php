<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Shop;
use App\Models\Wallet;
use App\Models\Listing;

class FixCheckoutDataSeeder extends Seeder
{
    /**
     * Fix dữ liệu để checkout hoạt động đúng:
     * 1. Verify tất cả shops
     * 2. Tạo wallet cho tất cả users với số dư 50 triệu
     * 3. Gán shop cho listings không có shop
     */
    public function run(): void
    {
        // 1. Verify tất cả shops
        $shopsUpdated = Shop::query()->update(['is_verified' => true]);
        $this->command->info("✅ Đã verify {$shopsUpdated} shops");
        
        // 1.5. Gán shop cho listings không có shop_id
        $defaultShop = Shop::first();
        if ($defaultShop) {
            $listingsUpdated = Listing::whereNull('shop_id')->update(['shop_id' => $defaultShop->id]);
            $this->command->info("✅ Đã gán shop cho {$listingsUpdated} listings không có shop");
        }

        // 2. Tạo wallet cho tất cả users
        $users = User::all();
        $walletsCreated = 0;
        $walletsUpdated = 0;

        foreach ($users as $user) {
            $wallet = Wallet::where('user_id', $user->id)->first();
            
            if (!$wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 50000000, // 50 triệu VND
                    'frozen_balance' => 0,
                    'currency' => 'VND',
                    'status' => 'active',
                ]);
                $walletsCreated++;
            } else {
                // Nếu wallet đã có nhưng balance = 0, cập nhật
                if ($wallet->balance == 0) {
                    $wallet->update(['balance' => 50000000]);
                    $walletsUpdated++;
                }
            }
        }

        $this->command->info("✅ Đã tạo {$walletsCreated} wallets mới");
        $this->command->info("✅ Đã cập nhật {$walletsUpdated} wallets có balance = 0");
        
        // 3. Hiển thị thông tin
        $this->command->info("\n📊 Thống kê:");
        $this->command->info("- Tổng shops: " . Shop::count());
        $this->command->info("- Shops đã verify: " . Shop::where('is_verified', true)->count());
        $this->command->info("- Tổng users: " . User::count());
        $this->command->info("- Tổng wallets: " . Wallet::count());
        $this->command->info("- Wallets có tiền: " . Wallet::where('balance', '>', 0)->count());
    }
}
