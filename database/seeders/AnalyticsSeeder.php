<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Listing;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPageViews();
        $this->seedAnalyticsEvents();
        $this->seedCompanyDailyStats();
        
        $this->command->info('Analytics data seeded successfully');
    }

    private function seedPageViews()
    {
        $shops = Shop::all();
        $listings = Listing::all();
        $userIds = DB::table('users')->pluck('id')->toArray();
        
        if ($shops->isEmpty() || $listings->isEmpty()) {
            $this->command->warn('No shops or listings found. Skipping page views seeding.');
            return;
        }

        $pageViews = [];
        $startDate = Carbon::now()->subDays(30);

        // Tạo page views cho TẤT CẢ listings
        foreach ($listings as $listing) {
            // Mỗi listing có 20-50 lượt xem
            $viewCount = rand(20, 50);
            
            for ($i = 0; $i < $viewCount; $i++) {
                $date = $startDate->copy()->addDays(rand(0, 30));

                $pageViews[] = [
                    'company_id' => $listing->shop_id,
                    'listing_id' => $listing->id,
                    'user_id' => (rand(0, 10) > 3 && !empty($userIds)) ? $userIds[array_rand($userIds)] : null,
                    'session_id' => uniqid('sess_'),
                    'path' => '/listings/' . $listing->id,
                    'referrer' => $this->getRandomReferrer(),
                    'user_agent' => $this->getRandomUserAgent(),
                    'request_id' => uniqid('req_'),
                    'correlation_id' => uniqid('corr_'),
                    'duration_ms' => rand(5000, 120000),
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
        }

        DB::table('page_views')->insert($pageViews);
        $this->command->info('Created ' . count($pageViews) . ' page views for ' . $listings->count() . ' listings');
    }

    private function seedAnalyticsEvents()
    {
        $userIds = DB::table('users')->pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->command->warn('No users found. Skipping analytics events seeding.');
            return;
        }

        $events = [];
        $startDate = Carbon::now()->subDays(30);
        $types = ['visit', 'click', 'view_listing', 'add_to_cart', 'purchase'];

        for ($i = 0; $i < 300; $i++) {
            $date = $startDate->copy()->addDays(rand(0, 30));

            $events[] = [
                'request_id' => uniqid('req_'),
                'correlation_id' => uniqid('corr_'),
                'user_id' => rand(0, 10) > 3 ? $userIds[array_rand($userIds)] : null,
                'type' => $types[array_rand($types)],
                'route' => '/api/listings',
                'order_id' => null,
                'ad_campaign_id' => null,
                'utm_source' => $this->getRandomUtmSource(),
                'utm_medium' => $this->getRandomUtmMedium(),
                'utm_campaign' => 'campaign_' . rand(1, 5),
                'value' => rand(0, 10) > 7 ? rand(100000, 5000000) : null,
                'payload' => json_encode(['browser' => 'Chrome', 'os' => 'Windows']),
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        DB::table('analytics_events')->insert($events);
        $this->command->info('Created ' . count($events) . ' analytics events');
    }

    private function seedCompanyDailyStats()
    {
        $shops = Shop::limit(5)->get();
        
        if ($shops->isEmpty()) {
            $this->command->warn('No shops found. Skipping company daily stats seeding.');
            return;
        }

        $stats = [];
        $startDate = Carbon::now()->subDays(30);

        foreach ($shops as $shop) {
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                $stats[] = [
                    'shop_id' => $shop->id,
                    'stat_date' => $date->toDateString(),
                    'page_views' => rand(100, 500),
                    'unique_visitors' => rand(80, 400),
                    'listing_views' => rand(50, 300),
                    'new_listings' => rand(0, 5),
                    'orders_count' => rand(0, 10),
                    'orders_revenue' => rand(0, 10) * rand(500000, 2000000),
                    'ad_impressions' => rand(1000, 5000),
                    'ad_clicks' => rand(30, 150),
                    'ad_conversions' => rand(1, 10),
                    'ad_spent' => rand(50000, 200000),
                    'new_subscriptions' => rand(0, 2),
                    'subscription_revenue' => rand(0, 2) * 500000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('company_daily_stats')->insert($stats);
        $this->command->info('Created ' . count($stats) . ' company daily stats records');
    }

    private function getRandomReferrer()
    {
        $referrers = [
            'https://google.com',
            'https://facebook.com',
            'https://zalo.me',
            'https://shopee.vn',
            null,
        ];

        return $referrers[array_rand($referrers)];
    }

    private function getRandomUserAgent()
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15',
            'Mozilla/5.0 (Linux; Android 13) Chrome/120.0.0.0 Mobile',
        ];

        return $agents[array_rand($agents)];
    }

    private function getRandomUtmSource()
    {
        $sources = ['google', 'facebook', 'zalo', 'direct', 'email', null];
        return $sources[array_rand($sources)];
    }

    private function getRandomUtmMedium()
    {
        $mediums = ['cpc', 'social', 'email', 'organic', 'referral', null];
        return $mediums[array_rand($mediums)];
    }
}
