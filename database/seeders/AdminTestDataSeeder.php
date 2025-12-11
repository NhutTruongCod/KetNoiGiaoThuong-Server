<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WalletTransaction;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Models\AuctionPayment;
use App\Models\UserSubscription;
use App\Models\ModerationReport;
use App\Models\PlatformRevenue;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Auction;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class AdminTestDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Creating admin test data...');

        // Get users
        $buyers = User::where('role', 'buyer')->get();
        $sellers = User::where('role', 'seller')->get();

        // 1. Create Deposit Requests
        $this->command->info('Creating deposit requests...');
        foreach ($buyers as $buyer) {
            $wallet = Wallet::where('user_id', $buyer->id)->first();
            if (!$wallet) continue;

            // Pending deposit
            DepositRequest::create([
                'user_id' => $buyer->id,
                'wallet_id' => $wallet->id,
                'request_code' => 'DEP' . time() . rand(1000, 9999),
                'amount' => rand(1, 5) * 100000,
                'payment_method' => 'bank_transfer',
                'status' => 'pending',
                'note' => 'Test deposit',
            ]);

            // Processing deposit
            DepositRequest::create([
                'user_id' => $buyer->id,
                'wallet_id' => $wallet->id,
                'request_code' => 'DEP' . time() . rand(1000, 9999),
                'amount' => rand(1, 5) * 100000,
                'payment_method' => 'bank_transfer',
                'status' => 'processing',
                'note' => 'User confirmed transfer',
            ]);
        }


        // 2. Create Withdraw Requests
        $this->command->info('Creating withdraw requests...');
        foreach ($sellers as $seller) {
            $wallet = Wallet::where('user_id', $seller->id)->first();
            if (!$wallet) continue;

            // Pending withdraw
            WithdrawRequest::create([
                'user_id' => $seller->id,
                'wallet_id' => $wallet->id,
                'request_code' => 'WDR' . time() . rand(1000, 9999),
                'amount' => 500000,
                'fee' => 5000,
                'vat_fee' => 50000,
                'pit_fee' => 0,
                'total_fee' => 55000,
                'actual_amount' => 445000,
                'bank_name' => 'Vietcombank',
                'bank_account' => '123456789' . $seller->id,
                'account_holder' => $seller->full_name,
                'status' => 'pending',
            ]);
        }

        // 3. Create Wallet Transactions
        $this->command->info('Creating wallet transactions...');
        foreach (User::whereIn('role', ['buyer', 'seller'])->get() as $user) {
            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) continue;

            // Deposit transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'transaction_code' => 'TXN' . time() . rand(1000, 9999),
                'type' => 'deposit',
                'amount' => 50000000,
                'balance_before' => 0,
                'balance_after' => 50000000,
                'description' => 'Nap tien khoi tao',
                'status' => 'completed',
            ]);
        }

        // 4. Create Auction Payments (if auctions with winners exist)
        $this->command->info('Creating auction payments...');
        $endedAuctions = Auction::where('status', 'ended')->whereNotNull('winner_id')->get();
        foreach ($endedAuctions as $auction) {
            if (AuctionPayment::where('auction_id', $auction->id)->exists()) continue;

            $amount = $auction->current_price_cents / 100;
            $platformFee = $amount * 0.05;
            $sellerReceive = $amount - $platformFee;

            AuctionPayment::create([
                'auction_id' => $auction->id,
                'winner_id' => $auction->winner_id,
                'seller_id' => $auction->created_by,
                'payment_code' => 'AUC' . time() . rand(1000, 9999),
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'seller_receive' => $sellerReceive,
                'status' => 'pending',
                'payment_deadline' => now()->addDays(3),
            ]);
        }

        // 5. Create User Subscriptions
        $this->command->info('Creating user subscriptions...');
        $plans = SubscriptionPlan::all();
        if ($plans->count() > 0) {
            foreach ($sellers->take(2) as $index => $seller) {
                $plan = $plans[$index % $plans->count()];
                
                // Pending subscription
                UserSubscription::create([
                    'user_id' => $seller->id,
                    'plan_id' => $plan->id,
                    'duration_months' => 1,
                    'price' => $plan->price,
                    'discount_amount' => 0,
                    'final_amount' => $plan->price,
                    'payment_method' => 'bank_transfer',
                    'payment_code' => 'SUB' . time() . rand(1000, 9999),
                    'status' => 'pending',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonth()->toDateString(),
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'is_active' => false,
                ]);
            }
        }


        // 6. Create Moderation Reports
        $this->command->info('Creating moderation reports...');
        $listings = \App\Models\Listing::take(3)->get();
        foreach ($listings as $listing) {
            ModerationReport::create([
                'reporter_id' => $buyers->first()->id,
                'reportable_type' => 'App\\Models\\Listing',
                'reportable_id' => $listing->id,
                'reason' => 'spam',
                'description' => 'Test report for listing: ' . $listing->title,
                'status' => 'pending',
            ]);
        }

        // 7. Create Platform Revenue records
        $this->command->info('Creating platform revenue records...');
        
        // Withdraw fees
        PlatformRevenue::record(
            PlatformRevenue::TYPE_WITHDRAW_FEE,
            55000,
            'withdraw_request',
            1,
            $sellers->first()->id,
            'Phi rut tien test'
        );

        // Subscription fees
        PlatformRevenue::record(
            PlatformRevenue::TYPE_SUBSCRIPTION_FEE,
            199000,
            'user_subscription',
            1,
            $sellers->first()->id,
            'Goi Basic - 1 thang'
        );

        // Auction fees
        PlatformRevenue::record(
            PlatformRevenue::TYPE_AUCTION_FEE,
            50000,
            'auction_payment',
            1,
            $buyers->first()->id,
            'Phi dau gia test'
        );

        // Promotion fees
        PlatformRevenue::record(
            PlatformRevenue::TYPE_PROMOTION_FEE,
            100000,
            'promotion',
            1,
            $sellers->first()->id,
            'Phi quang cao test'
        );

        // Order fees
        PlatformRevenue::record(
            PlatformRevenue::TYPE_ORDER_FEE,
            25000,
            'order',
            1,
            $sellers->first()->id,
            'Phi giao dich test'
        );

        $this->command->info('Admin test data created successfully!');
        $this->command->info('- Deposit Requests: ' . DepositRequest::count());
        $this->command->info('- Withdraw Requests: ' . WithdrawRequest::count());
        $this->command->info('- Wallet Transactions: ' . WalletTransaction::count());
        $this->command->info('- Auction Payments: ' . AuctionPayment::count());
        $this->command->info('- User Subscriptions: ' . UserSubscription::count());
        $this->command->info('- Moderation Reports: ' . ModerationReport::count());
        $this->command->info('- Platform Revenue: ' . PlatformRevenue::count());
    }
}
