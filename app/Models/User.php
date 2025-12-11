<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasFactory;

    protected $fillable = [
        'email',
        'password',
        'full_name',
        'password_hash',
        'phone',
        'avatar_url',
        'role', // buyer, seller, admin
        'status',
        'is_verified',
        'is_active',
        'provider',
        'provider_id',
        'last_login_at',
        'subscription_plan_id',
        'subscription_expires_at',
    ];

    // Role constants
    const ROLE_BUYER = 'buyer';
    const ROLE_SELLER = 'seller';
    const ROLE_ADMIN = 'admin';

    // Helper methods for roles
    public function isBuyer()
    {
        return $this->role === self::ROLE_BUYER;
    }

    public function isSeller()
    {
        return $this->role === self::ROLE_SELLER;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canCreateListing()
    {
        return in_array($this->role, [self::ROLE_SELLER, self::ROLE_ADMIN]);
    }

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
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

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->latest();
    }

    /**
     * Get user's commission rate based on subscription plan
     * Default 10% for free users
     */
    public function getCommissionRate(): float
    {
        if ($this->subscription_plan_id && $this->subscription_expires_at && $this->subscription_expires_at->isFuture()) {
            return $this->subscriptionPlan->commission_rate ?? 10.00;
        }
        return 10.00; // Default 10% for free users
    }

    /**
     * Get user's search boost based on subscription plan
     */
    public function getSearchBoost(): int
    {
        if ($this->subscription_plan_id && $this->subscription_expires_at && $this->subscription_expires_at->isFuture()) {
            return $this->subscriptionPlan->search_boost ?? 0;
        }
        return 0;
    }

    /**
     * Check if user has active premium subscription
     */
    public function hasPremiumSubscription(): bool
    {
        return $this->subscription_plan_id 
            && $this->subscription_expires_at 
            && $this->subscription_expires_at->isFuture()
            && $this->subscriptionPlan 
            && $this->subscriptionPlan->price > 0;
    }

    /**
     * Get user's subscription badge
     */
    public function getSubscriptionBadge(): ?string
    {
        if ($this->hasPremiumSubscription()) {
            return $this->subscriptionPlan->badge;
        }
        return null;
    }
}
