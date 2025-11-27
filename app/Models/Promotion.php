<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'status',
        'start_date',
        'end_date',
        'discount_percentage',
        'min_order_amount',
        'max_usage',
        'promo_code',
        'is_featured',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_percentage' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    /**
     * Scope active promotions
     */
    public function scopeActive($query)
    {
        $today = Carbon::today()->toDateString();
        return $query->where('status', 'active')
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        $today = Carbon::today()->toDateString();
        
        switch ($status) {
            case 'active':
                return $query->where('status', 'active')
                            ->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today);
            case 'expired':
                return $query->where(function($q) use ($today) {
                    $q->where('status', 'expired')
                      ->orWhere('end_date', '<', $today);
                });
            case 'upcoming':
                return $query->where('status', 'upcoming')
                            ->where('start_date', '>', $today);
            case 'inactive':
                return $query->where('status', 'inactive');
            default:
                return $query;
        }
    }

    /**
     * Scope featured promotions
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Check if promotion is currently active
     */
    public function getIsCurrentlyActiveAttribute()
    {
        $today = Carbon::today();
        return $this->status === 'active' && 
               $today->between($this->start_date, $this->end_date);
    }

    /**
     * Update status based on dates
     */
    public function updateStatus()
    {
        $today = Carbon::today();
        
        if ($today->gt($this->end_date)) {
            $this->status = 'expired';
        } elseif ($today->between($this->start_date, $this->end_date)) {
            $this->status = 'active';
        } elseif ($today->lt($this->start_date)) {
            $this->status = 'upcoming';
        }
        
        $this->save();
    }

    /**
     * Relationship với Listings (nhiều-nhiều)
     */
    public function listings()
    {
        return $this->belongsToMany(Listing::class, 'promotion_listing')
                    ->withTimestamps();
    }
}