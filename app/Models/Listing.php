<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',
        'category_id',
        'store_id',
        'is_active',
        'user_id',
        'slug',
        'category',
        'price_cents',
        'currency',
        'location_text',
        'latitude',
        'longitude',
        'status',
        'is_public',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'meta'      => 'array',
    ];

    /**
     * Relationship với Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship với Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope active listings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope search by title
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
    }

    /**
     * Scope filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope filter by store
     */
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Kiểm tra xem listing có đang trong chiến dịch quảng cáo không
     * (Sẽ tích hợp sau khi có bảng promotions)
     */
    public function hasActivePromotions()
    {
        // TODO: Implement after promotions table
        return false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }
}
