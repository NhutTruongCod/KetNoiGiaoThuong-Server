<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'email',
        'password',
        'full_name',
        'password_hash',
        'phone',
        'avatar_url',
        'role',
        'status',
        'is_verified',
        'is_active',
        'provider',
        'provider_id',
        'last_login_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = ['password_hash'];

    // --- JWT Interface methods ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email' => $this->email,
            'status' => $this->status,
        ];
    }

    // Laravel cần biết cột password là gì
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function tokens()
    {
        return $this->hasMany(UserToken::class);
    }

    public function loginHistory()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
