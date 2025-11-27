<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'order_id',
        'reviewer_id',
        'rating',
        'content'
    ];

    public $timestamps = false; // Không có updated_at

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
