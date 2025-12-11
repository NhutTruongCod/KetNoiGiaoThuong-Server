<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Listing;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $listings = Listing::with('shop')->limit(10)->get();
        
        if ($listings->isEmpty()) {
            $this->command->warn('No listings found. Please run ListingSeeder first.');
            return;
        }

        $promotions = [];
        
        foreach ($listings->take(5) as $index => $listing) {
            $startDate = Carbon::now()->subDays(rand(1, 5));
            $durationDays = rand(7, 30);
            $budget = rand(500000, 5000000);
            $spent = rand(100000, $budget * 0.7);
            $impressions = rand(5000, 50000);
            $clicks = rand(100, 1000);
            $conversions = rand(10, 100);
            
            $promotions[] = [
                'shop_id' => $listing->shop_id,
                'listing_id' => $listing->id,
                'type' => ['featured', 'top_search', 'homepage_banner', 'category_banner'][rand(0, 3)],
                'duration_days' => $durationDays,
                'budget' => $budget,
                'spent' => $spent,
                'daily_budget' => $budget / $durationDays,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => round(($clicks / $impressions) * 100, 2),
                'conversions' => $conversions,
                'conversion_rate' => round(($conversions / $clicks) * 100, 2),
                'cost_per_click' => round($spent / $clicks, 2),
                'cost_per_conversion' => round($spent / $conversions, 2),
                'target_audience' => [
                    'locations' => ['Ho Chi Minh', 'Ha Noi'],
                    'age_range' => [25, 45],
                    'interests' => ['electronics', 'technology'],
                ],
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays($durationDays),
                'is_featured' => $index < 2,
                'featured_position' => $index < 2 ? $index + 1 : null,
                'payment_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Add some pending promotions
        foreach ($listings->skip(5)->take(2) as $listing) {
            $startDate = Carbon::now()->addDays(1);
            $durationDays = 14;
            $budget = 1000000;
            
            $promotions[] = [
                'shop_id' => $listing->shop_id,
                'listing_id' => $listing->id,
                'type' => 'featured',
                'duration_days' => $durationDays,
                'budget' => $budget,
                'spent' => 0,
                'daily_budget' => $budget / $durationDays,
                'impressions' => 0,
                'clicks' => 0,
                'ctr' => 0,
                'conversions' => 0,
                'conversion_rate' => 0,
                'cost_per_click' => 0,
                'cost_per_conversion' => 0,
                'target_audience' => null,
                'status' => 'pending',
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays($durationDays),
                'is_featured' => false,
                'featured_position' => null,
                'payment_url' => 'https://vnpay.vn/payment?token=' . uniqid(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Add completed promotion
        $completedListing = $listings->skip(7)->first();
        if ($completedListing) {
            $startDate = Carbon::now()->subDays(20);
            $durationDays = 7;
            $budget = 500000;
            
            $promotions[] = [
                'shop_id' => $completedListing->shop_id,
                'listing_id' => $completedListing->id,
                'type' => 'top_search',
                'duration_days' => $durationDays,
                'budget' => $budget,
                'spent' => $budget,
                'daily_budget' => $budget / $durationDays,
                'impressions' => 10000,
                'clicks' => 300,
                'ctr' => 3.0,
                'conversions' => 20,
                'conversion_rate' => 6.67,
                'cost_per_click' => 1666.67,
                'cost_per_conversion' => 25000,
                'target_audience' => null,
                'status' => 'completed',
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays($durationDays),
                'is_featured' => false,
                'featured_position' => null,
                'payment_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($promotions as $promotion) {
            Promotion::create($promotion);
        }
        
        $this->command->info('Created ' . count($promotions) . ' promotions');
    }
}