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
        'starts_at',
        'ends_at',
        'starting_price_cents',
        'current_price_cents',
        'status',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function bids()
    {
        return $this->hasMany(AuctionBid::class);
    }

    public function isActive(): bool
    {
        $now = now();
        return $this->starts_at <= $now && $this->ends_at >= $now && $this->status === 'active';
    }
}
