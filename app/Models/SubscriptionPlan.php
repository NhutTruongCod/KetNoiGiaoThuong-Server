<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'commission_rate',
        'search_boost',
        'free_promotions',
        'badge',
        'features',
        'benefits',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    public $timestamps = true;

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'commission_rate' => 'decimal:2',
        'search_boost' => 'integer',
        'free_promotions' => 'integer',
        'features' => 'array',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Accessors
     */
    public function getTotalSubscribersAttribute()
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }

    public function getMonthlyPriceAttribute()
    {
        return $this->price;
    }

    /**
     * Check if plan is free
     */
    public function isFree()
    {
        return $this->price == 0;
    }

    /**
     * Calculate price for multiple months with discount
     */
    public function calculatePrice($months = 1)
    {
        $basePrice = $this->price * $months;
        $discount = 0;

        // Discount tiers
        if ($months >= 12) {
            $discount = 0.20; // 20% off for 12 months
        } elseif ($months >= 6) {
            $discount = 0.15; // 15% off for 6 months
        } elseif ($months >= 3) {
            $discount = 0.10; // 10% off for 3 months
        }

        $discountAmount = $basePrice * $discount;
        $finalAmount = $basePrice - $discountAmount;

        return [
            'base_price' => $basePrice,
            'discount_percent' => $discount * 100,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }
}

