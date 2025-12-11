<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'note',
        'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
