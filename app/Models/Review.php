<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';

    protected $fillable = [
        'order_id',
        'listing_id',
        'shop_id',
        'user_id',
        'rating',
        'comment',
        'images',
        'helpful_count',
        'is_verified_purchase',
        'seller_reply',
        'seller_reply_at',
    ];

    protected $casts = [
        'images' => 'array',
        'seller_reply' => 'array',
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'is_verified_purchase' => 'boolean',
        'seller_reply_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Legacy support
    public function reviewer()
    {
        return $this->user();
    }

    public function helpfulUsers()
    {
        return $this->belongsToMany(User::class, 'review_helpful', 'review_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeByListing($query, $listingId)
    {
        return $query->where('listing_id', $listingId);
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeWithSellerReply($query)
    {
        return $query->whereNotNull('seller_reply');
    }

    public function scopeWithImages($query)
    {
        return $query->whereNotNull('images');
    }

    /**
     * Methods
     */
    public function markAsHelpful(User $user)
    {
        // Check if already marked
        $exists = \DB::table('review_helpful')
            ->where('review_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return false;
        }

        \DB::table('review_helpful')->insert([
            'review_id' => $this->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->increment('helpful_count');

        return true;
    }

    public function unmarkAsHelpful(User $user)
    {
        $deleted = \DB::table('review_helpful')
            ->where('review_id', $this->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            $this->decrement('helpful_count');
            return true;
        }

        return false;
    }

    public function isHelpfulBy(User $user)
    {
        return \DB::table('review_helpful')
            ->where('review_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function addSellerReply($content, $userId)
    {
        $this->seller_reply = [
            'content' => $content,
            'user_id' => $userId,
            'created_at' => now()->toISOString(),
        ];
        $this->seller_reply_at = now();
        $this->save();

        return $this;
    }

    public function hasSellerReply()
    {
        return !is_null($this->seller_reply);
    }

    /**
     * Update listing and shop ratings after review
     */
    public function updateRatings()
    {
        // Update listing rating
        if ($this->listing) {
            $avgRating = self::where('listing_id', $this->listing_id)->avg('rating');
            $totalReviews = self::where('listing_id', $this->listing_id)->count();
            
            $this->listing->update([
                'rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ]);
        }

        // Update shop rating
        if ($this->shop) {
            $avgRating = self::where('shop_id', $this->shop_id)->avg('rating');
            $totalReviews = self::where('shop_id', $this->shop_id)->count();
            
            $this->shop->update([
                'rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ]);
        }
    }

    /**
     * Get rating distribution for a listing or shop
     */
    public static function getRatingDistribution($listingId = null, $shopId = null)
    {
        $query = self::query();

        if ($listingId) {
            $query->where('listing_id', $listingId);
        }

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $distribution = $query->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Fill missing ratings with 0
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($distribution[$i])) {
                $distribution[$i] = 0;
            }
        }

        ksort($distribution);

        return $distribution;
    }

    /**
     * Get summary statistics
     */
    public static function getSummary($listingId = null, $shopId = null)
    {
        $query = self::query();

        if ($listingId) {
            $query->where('listing_id', $listingId);
        }

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $totalReviews = $query->count();
        $averageRating = $query->avg('rating');
        $distribution = self::getRatingDistribution($listingId, $shopId);

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $distribution,
        ];
    }
}
