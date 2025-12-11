<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuctionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'winner_id',
        'seller_id',
        'payment_code',
        'amount',
        'platform_fee',
        'seller_receive',
        'status',
        'payment_deadline',
        'paid_at',
        'transferred_at',
        'note',
        // Shipping info
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_note',
        // Seller contact
        'seller_phone',
        'seller_email',
        // Bank transfer
        'payment_method',
        'bank_transfer_proof',
        'bank_transfer_confirmed_at',
        'bank_transfer_confirmed_by',
        'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'seller_receive' => 'decimal:2',
        'payment_deadline' => 'datetime',
        'paid_at' => 'datetime',
        'transferred_at' => 'datetime',
        'bank_transfer_confirmed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_EXPIRED = 'expired';

    // Relationships
    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->status === self::STATUS_PENDING && now() > $this->payment_deadline);
    }

    public function canPay(): bool
    {
        return $this->status === self::STATUS_PENDING && now() <= $this->payment_deadline;
    }

    public function getTimeRemaining(): string
    {
        if (!$this->canPay()) {
            return 'Hết hạn';
        }

        $diff = now()->diff($this->payment_deadline);
        
        if ($diff->days > 0) {
            return $diff->days . ' ngày ' . $diff->h . ' giờ';
        } elseif ($diff->h > 0) {
            return $diff->h . ' giờ ' . $diff->i . ' phút';
        } else {
            return $diff->i . ' phút';
        }
    }
}
