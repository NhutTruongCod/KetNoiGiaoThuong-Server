<?php

namespace Database\Seeders;

use App\Models\PromotionCostEstimation;
use App\Models\Store;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class PromotionCostEstimationSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::limit(2)->get();
        $listings = Listing::limit(3)->get();

        if ($stores->count() > 0 && $listings->count() > 0) {
            $estimations = [
                [
                    'store_id' => $stores[0]->id,
                    'listing_id' => $listings[0]->id,
                    'promotion_type' => 'banner',
                    'duration_days' => 7,
                    'budget' => 350000,
                    'estimated_cost' => 350000,
                    'currency' => 'VND',
                    'status' => 'pending',
                    'estimated_impressions' => 35000,
                    'estimated_clicks' => 700,
                    'estimated_conversions' => 35,
                ],
                [
                    'store_id' => $stores[1]->id,
                    'listing_id' => $listings[1]->id,
                    'promotion_type' => 'video_ads',
                    'duration_days' => 10,
                    'budget' => 500000,
                    'estimated_cost' => 495000,
                    'currency' => 'VND',
                    'status' => 'approved',
                    'estimated_impressions' => 40000,
                    'estimated_clicks' => 1200,
                    'estimated_conversions' => 60,
                ],
                [
                    'store_id' => $stores[0]->id,
                    'listing_id' => $listings[2]->id,
                    'promotion_type' => 'social_media',
                    'duration_days' => 14,
                    'budget' => 800000,
                    'estimated_cost' => 780000,
                    'currency' => 'VND',
                    'status' => 'completed',
                    'estimated_impressions' => 96000,
                    'estimated_clicks' => 1728,
                    'estimated_conversions' => 86,
                ],
            ];

            foreach ($estimations as $estimation) {
                PromotionCostEstimation::create($estimation);
            }
        }
    }
}