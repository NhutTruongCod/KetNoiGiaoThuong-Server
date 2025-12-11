<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_code',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Type constants
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_PAYMENT = 'payment';
    const TYPE_RECEIVE = 'receive';
    const TYPE_REFUND = 'refund';
    const TYPE_FREEZE = 'freeze';
    const TYPE_UNFREEZE = 'unfreeze';
    const TYPE_AUCTION_WIN = 'auction_win';
    const TYPE_AUCTION_RECEIVE = 'auction_receive';

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helpers
    public function isCredit(): bool
    {
        return in_array($this->type, [
            self::TYPE_DEPOSIT,
            self::TYPE_RECEIVE,
            self::TYPE_REFUND,
            self::TYPE_AUCTION_RECEIVE,
        ]);
    }

    public function isDebit(): bool
    {
        return in_array($this->type, [
            self::TYPE_WITHDRAW,
            self::TYPE_PAYMENT,
            self::TYPE_AUCTION_WIN,
        ]);
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_DEPOSIT => 'Nạp tiền',
            self::TYPE_WITHDRAW => 'Rút tiền',
            self::TYPE_PAYMENT => 'Thanh toán',
            self::TYPE_RECEIVE => 'Nhận tiền',
            self::TYPE_REFUND => 'Hoàn tiền',
            self::TYPE_FREEZE => 'Đóng băng',
            self::TYPE_UNFREEZE => 'Giải phóng',
            self::TYPE_AUCTION_WIN => 'Thanh toán đấu giá',
            self::TYPE_AUCTION_RECEIVE => 'Nhận tiền đấu giá',
            default => $this->type,
        };
    }
}
