<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'user_id',
        'order_id',
        'payable_type',
        'payable_id',
        'payment_type',
        'payer_id',
        'payee_id',
        'transaction_id',
        'method',
        'payment_gateway',
        'amount',
        'currency',
        'status',
        'payment_gateway_response',
        'return_url',
        'description',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true; // FIX: Đổi từ false sang true

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function payee()
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Polymorphic relationship - QUAN TRỌNG!
     * Có thể là: Order, Subscription, Promotion, Auction
     */
    public function payable()
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPayer($query, $payerId)
    {
        return $query->where('payer_id', $payerId);
    }

    public function scopeByPayee($query, $payeeId)
    {
        return $query->where('payee_id', $payeeId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeForOrders($query)
    {
        return $query->where('payment_type', 'order');
    }

    public function scopeForSubscriptions($query)
    {
        return $query->where('payment_type', 'subscription');
    }

    public function scopeForPromotions($query)
    {
        return $query->where('payment_type', 'promotion');
    }

    public function scopeForAuctions($query)
    {
        return $query->where('payment_type', 'auction');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Methods
     */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Update payable status
        if ($this->payable) {
            if ($this->isForOrder() && $this->payable) {
                $this->payable->update(['payment_status' => 'paid']);
            }
        }

        return $this;
    }

    public function markAsFailed($reason = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;

        $this->update([
            'status' => 'failed',
            'metadata' => $metadata,
        ]);

        return $this;
    }

    public function refund($reason = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['refund_reason'] = $reason;
        $metadata['refunded_at'] = now()->toISOString();

        $this->update([
            'status' => 'refunded',
            'metadata' => $metadata,
        ]);

        // Update payable status
        if ($this->payable && $this->isForOrder()) {
            $this->payable->update(['payment_status' => 'refunded']);
        }

        return $this;
    }

    public function cancel($reason = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['cancel_reason'] = $reason;

        $this->update([
            'status' => 'cancelled',
            'metadata' => $metadata,
        ]);

        return $this;
    }

    /**
     * Check payment type
     */
    public function isForOrder()
    {
        return $this->payment_type === 'order';
    }

    public function isForSubscription()
    {
        return $this->payment_type === 'subscription';
    }

    public function isForPromotion()
    {
        return $this->payment_type === 'promotion';
    }

    public function isForAuction()
    {
        return $this->payment_type === 'auction';
    }

    public function isForFee()
    {
        return $this->payment_type === 'fee';
    }

    /**
     * Accessors
     */
    public function getIsPaidAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    public function getIsRefundedAttribute()
    {
        return $this->status === 'refunded';
    }

    public function getPaymentTypeNameAttribute()
    {
        $types = [
            'order' => 'Thanh toán đơn hàng',
            'subscription' => 'Thanh toán gói VIP',
            'promotion' => 'Thanh toán quảng cáo',
            'auction' => 'Thanh toán đấu giá',
            'fee' => 'Phí giao dịch',
        ];

        return $types[$this->payment_type] ?? 'Khác';
    }

    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'Chờ thanh toán',
            'processing' => 'Đang xử lý',
            'completed' => 'Đã thanh toán',
            'failed' => 'Thất bại',
            'cancelled' => 'Đã hủy',
            'refunded' => 'Đã hoàn tiền',
        ];

        return $statuses[$this->status] ?? 'Không xác định';
    }
}
