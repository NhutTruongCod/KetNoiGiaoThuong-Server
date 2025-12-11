<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'frozen_balance',
        'currency',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
    ];

    protected $appends = ['available_balance'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function depositRequests()
    {
        return $this->hasMany(DepositRequest::class);
    }

    public function withdrawRequests()
    {
        return $this->hasMany(WithdrawRequest::class);
    }

    // Accessors
    public function getAvailableBalanceAttribute()
    {
        return $this->balance - $this->frozen_balance;
    }

    // Business Logic
    public function canWithdraw($amount): bool
    {
        return $this->status === 'active' && $this->available_balance >= $amount;
    }

    public function canFreeze($amount): bool
    {
        return $this->status === 'active' && $this->available_balance >= $amount;
    }

    public function freeze($amount, $referenceType = null, $referenceId = null, $description = null)
    {
        if (!$this->canFreeze($amount)) {
            throw new \Exception('Số dư khả dụng không đủ để đóng băng');
        }

        $balanceBefore = $this->balance;
        $this->frozen_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_code' => 'FRZ' . time() . rand(1000, 9999),
            'type' => 'freeze',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Đóng băng số dư',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }

    public function unfreeze($amount, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($this->frozen_balance < $amount) {
            throw new \Exception('Số dư đóng băng không đủ');
        }

        $balanceBefore = $this->balance;
        $this->frozen_balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_code' => 'UFZ' . time() . rand(1000, 9999),
            'type' => 'unfreeze',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Giải phóng số dư đóng băng',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }

    public function deduct($amount, $type, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Số dư không đủ');
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_code' => 'TXN' . time() . rand(1000, 9999),
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }

    public function credit($amount, $type, $referenceType = null, $referenceId = null, $description = null)
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_code' => 'TXN' . time() . rand(1000, 9999),
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }

    // Thanh toán từ số dư đóng băng (cho auction)
    public function payFromFrozen($amount, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($this->frozen_balance < $amount) {
            throw new \Exception('Số dư đóng băng không đủ');
        }

        $balanceBefore = $this->balance;
        $this->frozen_balance -= $amount;
        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'transaction_code' => 'PAY' . time() . rand(1000, 9999),
            'type' => 'auction_win',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Thanh toán đấu giá thắng',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }
}
