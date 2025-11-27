<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    protected $table = 'otp_codes';

    public $timestamps = false; // table has created_at only; handled manually

    protected $fillable = [
        'user_id',
        'otp_code',
        'type',
        'expire_at',
        'is_used',
        'used_at',
        'created_at',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];
}
