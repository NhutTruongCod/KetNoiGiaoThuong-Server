<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIdentity extends Model
{
    use HasFactory;

    protected $table = 'user_identities';

    protected $fillable = [
        'user_id',
        'identity_type',
        'full_name',
        'date_of_birth',
        'business_name',
        'business_license',
        'address',
        'phone',
        'identity_status',
        'verified_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Quan hệ: một identity thuộc về một user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
