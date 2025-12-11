<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'duration_months',
        'price',
        'discount_amount',
        'final_amount',
        'payment_method',
        'payment_code',
        'payment_proof',
        'coupon_code',
        'status',
        'start_date',
        'end_date',
        'started_at',
        'expires_at',
        'canceled_at',
        'is_active',
        'auto_renew',
        'admin_note',
        'approved_by',
        'approved_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'duration_months' => 'integer',
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'canceled_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_renew' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function($q) {
                $q->where('status', 'active')
                  ->where('end_date', '<', now()->toDateString());
            });
    }

    /**
     * Accessors
     */
    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $endDate = Carbon::parse($this->end_date);
        $now = Carbon::now();

        if ($endDate->isPast()) {
            return 0;
        }

        return $now->diffInDays($endDate, false);
    }

    public function getUsageAttribute()
    {
        $plan = $this->plan;
        if (!$plan) {
            return null;
        }

        $features = $plan->features ?? [];
        
        // TODO: Get actual usage from listings table
        // For now return mock data
        return [
            'listings_used' => 0,
            'listings_remaining' => $features['max_listings'] ?? 0,
            'featured_listings_used' => 0,
            'featured_listings_remaining' => $features['featured_listings'] ?? 0,
        ];
    }

    /**
     * Methods
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->end_date >= now()->toDateString();
    }

    public function isExpired()
    {
        return $this->status === 'expired' || $this->end_date < now()->toDateString();
    }

    public function markAsActive()
    {
        $this->update([
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired',
            'is_active' => false,
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'is_active' => false,
            'canceled_at' => now(),
        ]);

        return $this->calculateRefund();
    }

    public function calculateRefund()
    {
        $daysUsed = Carbon::parse($this->start_date)->diffInDays(now());
        
        if ($daysUsed <= 7) {
            return [
                'refund_amount' => $this->final_amount,
                'refund_percent' => 100,
                'refund_note' => 'Hoàn 100% vì hủy trong 7 ngày đầu'
            ];
        } elseif ($daysUsed <= 15) {
            $refundAmount = $this->final_amount * 0.5;
            return [
                'refund_amount' => $refundAmount,
                'refund_percent' => 50,
                'refund_note' => 'Hoàn 50% vì hủy trong 15 ngày đầu'
            ];
        } else {
            return [
                'refund_amount' => 0,
                'refund_percent' => 0,
                'refund_note' => 'Không hoàn tiền vì đã sử dụng quá 15 ngày'
            ];
        }
    }
}

