<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Listing;
use App\Models\User;

class AuctionSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', 'seller')->get();
        $buyers = User::where('role', 'buyer')->get();
        $listings = Listing::all();

        if ($sellers->count() === 0 || $listings->count() === 0) {
            $this->command->warn('⚠️  No sellers or listings found. Skipping auction seeder.');
            return;
        }

        $statuses = ['upcoming', 'active', 'ended'];
        $count = 0;

        foreach ($listings->take(8) as $index => $listing) {
            $seller = $sellers[$index % $sellers->count()];
            $status = $statuses[$index % count($statuses)];
            
            $startingPrice = $listing->price ?? rand(1000000, 10000000);
            $reservePrice = $startingPrice * 1.5;
            $bidIncrement = rand(100000, 500000);
            
            // Determine dates based on status
            if ($status === 'upcoming') {
                $startsAt = now()->addDays(rand(1, 3));
                $endsAt = $startsAt->copy()->addDays(rand(3, 7));
                $currentPrice = $startingPrice;
                $totalBids = 0;
            } elseif ($status === 'active') {
                $startsAt = now()->subDays(rand(1, 2));
                $endsAt = now()->addDays(rand(1, 5));
                $currentPrice = $startingPrice + (rand(1, 5) * $bidIncrement);
                $totalBids = rand(3, 10);
            } else { // ended
                $startsAt = now()->subDays(rand(5, 10));
                $endsAt = now()->subDays(rand(1, 2));
                $currentPrice = $startingPrice + (rand(5, 15) * $bidIncrement);
                $totalBids = rand(10, 20);
            }

            $auction = Auction::create([
                'listing_id' => $listing->id,
                'shop_id' => $listing->shop_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'starting_price_cents' => $startingPrice,
                'current_price_cents' => $currentPrice,
                'reserve_price_cents' => $reservePrice,
                'bid_increment_cents' => $bidIncrement,
                'total_bids' => $totalBids,
                'auto_extend' => true,
                'extend_minutes' => 5,
                'max_bids_per_user' => 0,
                'status' => $status,
                'created_by' => $listing->user_id ?? $seller->id,
            ]);

            // Create bids for active and ended auctions
            if ($status !== 'upcoming' && $buyers->count() > 0 && $totalBids > 0) {
                $currentBidPrice = $startingPrice;
                
                for ($i = 0; $i < $totalBids; $i++) {
                    $buyer = $buyers[$i % $buyers->count()];
                    $currentBidPrice += $bidIncrement;
                    
                    $isWinning = ($i === $totalBids - 1); // Last bid is winning
                    
                    AuctionBid::create([
                        'auction_id' => $auction->id,
                        'user_id' => $buyer->id,
                        'amount_cents' => $currentBidPrice,
                        'is_winning' => $isWinning,
                        'is_auto_bid' => false,
                        'created_at' => $startsAt->copy()->addHours($i + 1),
                    ]);
                }
                
                // Set winner for ended auctions
                if ($status === 'ended') {
                    $winningBid = $auction->bids()->where('is_winning', true)->first();
                    if ($winningBid) {
                        $auction->winner_id = $winningBid->user_id;
                        $auction->save();
                    }
                }
            }

            $count++;
        }

        $this->command->info('✅ Created ' . $count . ' auctions with bids');
    }
}
