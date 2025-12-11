<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();
        
        // 1. Core Users & Authentication
        $this->command->info('📝 Seeding users and authentication...');
        $this->call([
            UserSeeder::class,
            LoginHistorySeeder::class,
        ]);
        
        // 2. Shops & Categories
        $this->command->info('🏪 Seeding shops and categories...');
        $this->call([
            ShopSeeder::class,
            CategorySeeder::class,
        ]);
        
        // 3. Subscription Plans
        $this->command->info('💳 Seeding subscription plans...');
        $this->call([
            SubscriptionPlanSeeder::class,
        ]);
        
        // 4. Listings & Products
        $this->command->info('📦 Seeding listings and products...');
        $this->call([
            ListingSeeder::class,
            DuplicateListingSeeder::class,
        ]);
        
        // 5. Promotions & Advertising
        $this->command->info('📢 Seeding promotions...');
        $this->call([
            PromotionSeeder::class,
            PromotionCostEstimationSeeder::class,
        ]);
        
        // 6. Orders & Payments
        $this->command->info('💰 Seeding orders and payments...');
        $this->call([
            OrderSeeder::class,
            PaymentSeeder::class,
        ]);
        
        // 7. Reviews & Ratings
        $this->command->info('⭐ Seeding reviews...');
        $this->call([
            ReviewSeeder::class,
        ]);
        
        // 8. Auctions
        $this->command->info('🔨 Seeding auctions...');
        $this->call([
            AuctionSeeder::class,
        ]);
        
        // 9. Social Features
        $this->command->info('💬 Seeding social features...');
        $this->call([
            BookmarkSeeder::class,
            ChatMessageSeeder::class,
            SocialSeeder::class,
        ]);
        
        // 10. Inquiries & Support
        $this->command->info('❓ Seeding inquiries and support...');
        $this->call([
            InquirySeeder::class,
            FaqSeeder::class,
        ]);
        
        // 11. Notifications
        $this->command->info('🔔 Seeding notifications...');
        $this->call([
            NotificationSeeder::class,
        ]);
        
        // 12. Analytics & Statistics
        $this->command->info('📊 Seeding analytics...');
        $this->call([
            AnalyticsSeeder::class,
        ]);
        
        $this->command->newLine();
        $this->command->info('✅ Database seeding completed!');
        $this->command->newLine();
        $this->command->info('🔑 Test accounts:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@tradehub.com', 'admin123'],
                ['Seller 1', 'seller1@example.com', 'password123'],
                ['Seller 2', 'seller2@example.com', 'password123'],
                ['Seller 3', 'seller3@example.com', 'password123'],
                ['Buyer 1', 'buyer1@example.com', 'password123'],
                ['Buyer 2', 'buyer2@example.com', 'password123'],
            ]
        );
    }
}
