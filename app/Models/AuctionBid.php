<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuctionBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'user_id',
        'amount_cents',
        'is_winning',
        'is_auto_bid',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'is_winning' => 'boolean',
        'is_auto_bid' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Methods
     */
    public function markAsWinning()
    {
        // Mark all other bids as not winning
        self::where('auction_id', $this->auction_id)
            ->where('id', '!=', $this->id)
            ->update(['is_winning' => false]);

        // Mark this bid as winning
        $this->is_winning = true;
        $this->save();
    }

    public function markAsLosing()
    {
        $this->is_winning = false;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeWinning($query)
    {
        return $query->where('is_winning', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
