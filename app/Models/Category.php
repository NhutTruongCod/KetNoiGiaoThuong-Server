<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Auto-generate slug from name if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            
            // Auto set user_id from auth
            if (empty($category->user_id) && auth()->check()) {
                $category->user_id = auth()->id();
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Relationship với Shop
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Relationship với User (creator)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship với Listings
     */
    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * Relationship với parent category
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relationship với child categories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Scope active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by shop
     */
    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope search by name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
    }

    /**
     * Kiểm tra xem category có listings không
     */
    public function hasListings()
    {
        return $this->listings()->exists();
    }

    /**
     * Get route key for URL
     */
    public function getRouteKeyName()
    {
        return 'id'; // Dùng id thay vì slug vì slug chỉ unique trong shop
    }
}
