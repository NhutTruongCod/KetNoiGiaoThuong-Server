<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'listing_id',
        'type',
        'duration_days',
        'budget',
        'spent',
        'daily_budget',
        'impressions',
        'clicks',
        'ctr',
        'conversions',
        'conversion_rate',
        'cost_per_click',
        'cost_per_conversion',
        'target_audience',
        'status',
        'start_date',
        'end_date',
        'is_featured',
        'featured_position',
        'payment_url',
        'refund_amount',
        'refund_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'daily_budget' => 'decimal:2',
        'ctr' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cost_per_click' => 'decimal:2',
        'cost_per_conversion' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'is_featured' => 'boolean',
        'target_audience' => 'array',
    ];

    protected $appends = ['remaining_budget', 'days_remaining'];

    /**
     * Relationship với Listing
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Relationship với Shop
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Scope active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope featured promotions
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->orderBy('featured_position');
    }

    /**
     * Get remaining budget
     */
    public function getRemainingBudgetAttribute()
    {
        return max(0, $this->budget - $this->spent);
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date) {
            return 0;
        }
        
        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);
        
        return max(0, $today->diffInDays($endDate, false));
    }

    /**
     * Calculate and update CTR
     */
    public function updateCtr()
    {
        if ($this->impressions > 0) {
            $this->ctr = ($this->clicks / $this->impressions) * 100;
        } else {
            $this->ctr = 0;
        }
        $this->save();
    }

    /**
     * Calculate and update conversion rate
     */
    public function updateConversionRate()
    {
        if ($this->clicks > 0) {
            $this->conversion_rate = ($this->conversions / $this->clicks) * 100;
        } else {
            $this->conversion_rate = 0;
        }
        $this->save();
    }

    /**
     * Calculate and update cost per click
     */
    public function updateCostPerClick()
    {
        if ($this->clicks > 0) {
            $this->cost_per_click = $this->spent / $this->clicks;
        } else {
            $this->cost_per_click = 0;
        }
        $this->save();
    }

    /**
     * Calculate and update cost per conversion
     */
    public function updateCostPerConversion()
    {
        if ($this->conversions > 0) {
            $this->cost_per_conversion = $this->spent / $this->conversions;
        } else {
            $this->cost_per_conversion = 0;
        }
        $this->save();
    }

    /**
     * Update all performance metrics
     */
    public function updatePerformanceMetrics()
    {
        $this->updateCtr();
        $this->updateConversionRate();
        $this->updateCostPerClick();
        $this->updateCostPerConversion();
    }

    /**
     * Track impression
     */
    public function trackImpression()
    {
        $this->increment('impressions');
        $this->updateCtr();
    }

    /**
     * Track click
     */
    public function trackClick($cost = 0)
    {
        $this->increment('clicks');
        $this->increment('spent', $cost);
        $this->updatePerformanceMetrics();
    }

    /**
     * Track conversion
     */
    public function trackConversion()
    {
        $this->increment('conversions');
        $this->updatePerformanceMetrics();
    }

    /**
     * Check if budget exhausted
     */
    public function isBudgetExhausted()
    {
        return $this->spent >= $this->budget;
    }

    /**
     * Check if expired
     */
    public function isExpired()
    {
        return Carbon::today()->gt($this->end_date);
    }

    /**
     * Auto-complete if budget exhausted or expired
     */
    public function checkAndComplete()
    {
        if ($this->status === 'active' && ($this->isBudgetExhausted() || $this->isExpired())) {
            $this->status = 'completed';
            $this->save();
        }
    }
}