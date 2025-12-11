<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'shop_id',
        'starts_at',
        'ends_at',
        'starting_price_cents',
        'current_price_cents',
        'reserve_price_cents',
        'bid_increment_cents',
        'total_bids',
        'winner_id',
        'auto_extend',
        'extend_minutes',
        'max_bids_per_user',
        'status',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'auto_extend' => 'boolean',
        'starting_price_cents' => 'integer',
        'current_price_cents' => 'integer',
        'reserve_price_cents' => 'integer',
        'bid_increment_cents' => 'integer',
        'total_bids' => 'integer',
        'extend_minutes' => 'integer',
        'max_bids_per_user' => 'integer',
    ];

    protected $appends = ['time_remaining', 'time_remaining_seconds'];

    /**
     * Relationships
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function bids()
    {
        return $this->hasMany(AuctionBid::class)->orderBy('amount_cents', 'desc');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function highestBid()
    {
        return $this->hasOne(AuctionBid::class)->where('is_winning', true);
    }

    public function auctionPayment()
    {
        return $this->hasOne(AuctionPayment::class);
    }

    /**
     * Accessors
     */
    public function getTimeRemainingAttribute()
    {
        if ($this->isEnded()) {
            return 'Đã kết thúc';
        }

        if ($this->isUpcoming()) {
            return 'Chưa bắt đầu';
        }

        $diff = now()->diff($this->ends_at);
        
        if ($diff->days > 0) {
            return $diff->days . ' ngày ' . $diff->h . ' giờ';
        } elseif ($diff->h > 0) {
            return $diff->h . ' giờ ' . $diff->i . ' phút';
        } else {
            return $diff->i . ' phút ' . $diff->s . ' giây';
        }
    }

    public function getTimeRemainingSecondsAttribute()
    {
        if ($this->isEnded() || $this->isUpcoming()) {
            return 0;
        }

        return now()->diffInSeconds($this->ends_at);
    }

    /**
     * Status Check Methods
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->starts_at <= $now && $this->ends_at >= $now && $this->status === 'active';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended' || now() > $this->ends_at;
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming' || now() < $this->starts_at;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBid(): bool
    {
        $now = now();
        
        // Có thể bid nếu:
        // 1. Thời gian hiện tại >= starts_at
        // 2. Thời gian hiện tại <= ends_at
        // 3. Status không phải 'cancelled' hoặc 'ended'
        
        if ($this->status === 'cancelled' || $this->status === 'ended') {
            return false;
        }
        
        if ($now < $this->starts_at) {
            return false; // Chưa bắt đầu
        }
        
        if ($now > $this->ends_at) {
            return false; // Đã kết thúc
        }
        
        // Auto-update status to active if needed
        if ($this->status === 'upcoming' && $now >= $this->starts_at) {
            $this->status = 'active';
            $this->save();
        }
        
        return true;
    }

    public function canUpdate(): bool
    {
        return $this->total_bids === 0 && !$this->isEnded();
    }

    /**
     * Business Logic Methods
     */
    public function getMinimumBid(): int
    {
        return $this->current_price_cents + $this->bid_increment_cents;
    }

    public function hasReachedReservePrice(): bool
    {
        if (!$this->reserve_price_cents) {
            return true;
        }

        return $this->current_price_cents >= $this->reserve_price_cents;
    }

    public function determineWinner()
    {
        if (!$this->isEnded()) {
            return null;
        }

        $highestBid = $this->bids()->orderBy('amount_cents', 'desc')->first();

        if (!$highestBid) {
            return null;
        }

        // Check reserve price
        if (!$this->hasReachedReservePrice()) {
            return null; // No winner if reserve price not met
        }

        $this->winner_id = $highestBid->user_id;
        $this->status = 'ended';
        $this->save();

        return $highestBid->user;
    }

    public function extendIfNeeded(AuctionBid $newBid)
    {
        if (!$this->auto_extend) {
            return false;
        }

        $minutesRemaining = now()->diffInMinutes($this->ends_at);

        // If bid placed in last 5 minutes, extend by extend_minutes
        if ($minutesRemaining <= $this->extend_minutes) {
            $this->ends_at = $this->ends_at->addMinutes($this->extend_minutes);
            $this->save();
            return true;
        }

        return false;
    }

    public function updateCurrentPrice(int $amountCents)
    {
        $this->current_price_cents = $amountCents;
        $this->total_bids++;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
            ->where('starts_at', '>', now());
    }

    public function scopeEnded($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'ended')
              ->orWhere('ends_at', '<', now());
        });
    }

    public function scopeEndingSoon($query, $hours = 24)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addHours($hours))
            ->orderBy('ends_at', 'asc');
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
