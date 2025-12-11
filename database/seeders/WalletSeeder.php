<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Tạo ví cho tất cả users...');

        $users = User::all();

        foreach ($users as $user) {
            // Tạo ví nếu chưa có
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 0,
                    'frozen_balance' => 0,
                    'currency' => 'VND',
                    'status' => 'active',
                ]
            );

            // Nạp tiền test cho mỗi user
            $initialBalance = match($user->role) {
                'admin' => 100000000,  // 100 triệu cho admin
                'seller' => 50000000,  // 50 triệu cho seller
                'buyer' => 10000000,   // 10 triệu cho buyer
                default => 5000000,    // 5 triệu mặc định
            };

            if ($wallet->balance == 0) {
                $wallet->balance = $initialBalance;
                $wallet->save();

                // Tạo transaction record
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'transaction_code' => 'SEED' . time() . $user->id,
                    'type' => 'deposit',
                    'amount' => $initialBalance,
                    'balance_before' => 0,
                    'balance_after' => $initialBalance,
                    'description' => 'Số dư khởi tạo (test)',
                    'reference_type' => 'seeder',
                    'reference_id' => null,
                    'status' => 'completed',
                ]);

                $this->command->info("Đã tạo ví cho {$user->email} với số dư " . number_format($initialBalance) . " VND");
            }
        }

        $this->command->info('Hoàn tất tạo ví!');
    }
}
