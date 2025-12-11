<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformRevenue extends Model
{
    use HasFactory;

    protected $table = 'platform_revenues';

    protected $fillable = [
        'transaction_code',
        'type',
        'amount',
        'vat_amount',
        'net_amount',
        'reference_type',
        'reference_id',
        'user_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Type constants
    const TYPE_WITHDRAW_FEE = 'withdraw_fee';
    const TYPE_AUCTION_FEE = 'auction_fee';
    const TYPE_ORDER_FEE = 'order_fee';
    const TYPE_PROMOTION_FEE = 'promotion_fee';
    const TYPE_SUBSCRIPTION_FEE = 'subscription_fee';
    const TYPE_OTHER = 'other';

    // VAT rate (10%)
    const VAT_RATE = 0.10;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Static method to record revenue
    public static function record($type, $amount, $referenceType = null, $referenceId = null, $userId = null, $description = null, $metadata = null)
    {
        $vatAmount = $amount * self::VAT_RATE;
        $netAmount = $amount - $vatAmount;

        return self::create([
            'transaction_code' => 'REV' . time() . rand(1000, 9999),
            'type' => $type,
            'amount' => $amount,
            'vat_amount' => $vatAmount,
            'net_amount' => $netAmount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => $userId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    // Get type label
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_WITHDRAW_FEE => 'Phí rút tiền',
            self::TYPE_AUCTION_FEE => 'Phí đấu giá',
            self::TYPE_ORDER_FEE => 'Phí giao dịch',
            self::TYPE_PROMOTION_FEE => 'Phí quảng cáo',
            self::TYPE_SUBSCRIPTION_FEE => 'Phí gói đăng ký',
            self::TYPE_OTHER => 'Khác',
            default => $this->type,
        };
    }
}
