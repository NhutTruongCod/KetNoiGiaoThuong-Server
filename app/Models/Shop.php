<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'business_name',
        'business_registration_number',
        'business_type',
        'description',
        'phone',
        'email',
        'website',
        'address',
        'ward',
        'district',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'logo',
        'banner',
        'images',
        'facebook_url',
        'instagram_url',
        'zalo_phone',
        'youtube_url',
        'business_hours',
        'is_active',
        'is_verified',
        'verified_at',
        'total_products',
        'total_orders',
        'total_reviews',
        'rating',
        'followers_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'return_policy',
        'shipping_policy',
        'warranty_policy',
    ];

    protected $casts = [
        'images' => 'array',
        'business_hours' => 'array',
        'meta_keywords' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'total_products' => 'integer',
        'total_orders' => 'integer',
        'total_reviews' => 'integer',
        'followers_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['social_links'];

    /**
     * Boot method - Auto generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shop) {
            if (empty($shop->slug)) {
                $shop->slug = Str::slug($shop->name);
                
                // Ensure unique slug
                $originalSlug = $shop->slug;
                $count = 1;
                while (static::where('slug', $shop->slug)->exists()) {
                    $shop->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });

        static::updating(function ($shop) {
            if ($shop->isDirty('name') && empty($shop->slug)) {
                $shop->slug = Str::slug($shop->name);
            }
        });
    }

    /**
     * Get social links as object
     */
    public function getSocialLinksAttribute()
    {
        return [
            'facebook' => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'zalo' => $this->zalo_phone,
            'youtube' => $this->youtube_url,
        ];
    }

    /**
     * Relationships
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shop_id');
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'shop_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'shop_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('business_name', 'like', "%{$search}%");
        });
    }
}
