<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;

class BookmarkSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::where('role', 'buyer')->get();
        $listings = Listing::all(); // Lấy TẤT CẢ listings

        if ($buyers->count() === 0 || $listings->count() === 0) {
            $this->command->warn('  No buyers or listings found. Skipping bookmark seeder.');
            return;
        }

        $count = 0;
        
        // Mỗi buyer bookmark 3-6 listings
        foreach ($buyers as $buyer) {
            $bookmarkCount = rand(3, 6);
            $randomListings = $listings->random(min($bookmarkCount, $listings->count()));

            foreach ($randomListings as $listing) {
                Bookmark::firstOrCreate([
                    'user_id' => $buyer->id,
                    'listing_id' => $listing->id,
                ]);
                $count++;
            }
        }
        
        // Thêm: Mỗi listing được bookmark bởi 1-3 buyers ngẫu nhiên
        foreach ($listings as $listing) {
            $bookmarkByCount = rand(1, 3);
            $randomBuyers = $buyers->random(min($bookmarkByCount, $buyers->count()));
            
            foreach ($randomBuyers as $buyer) {
                Bookmark::firstOrCreate([
                    'user_id' => $buyer->id,
                    'listing_id' => $listing->id,
                ]);
                $count++;
            }
        }

        $this->command->info(' Created ' . $count . ' bookmarks for ' . $listings->count() . ' listings');
    }
}
