<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'title',
        'slug',
        'description',
        'images',
        'category',
        'type',
        'price_cents',
        'stock_qty',
        'total_reviews',
        'rating',
        'currency',
        'location_text',
        'latitude',
        'longitude',
        'status',
        'is_active',
        'is_public',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'images' => 'array',
        'meta' => 'array',
        'total_reviews' => 'integer',
        'rating' => 'decimal:2',
        'price_cents' => 'integer',
    ];

    /**
     * Thêm price vào response JSON để FE dễ sử dụng
     */
    protected $appends = ['price', 'main_image'];

    /**
     * Accessor: Chuyển đổi price_cents sang VND
     * FE có thể dùng listing.price thay vì listing.price_cents / 100
     */
    public function getPriceAttribute()
    {
        return ($this->price_cents ?? 0) / 100;
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
     * Kiểm tra xem listing có đang trong chiến dịch quảng cáo không
     */
    public function hasActivePromotions()
    {
        return $this->promotions()->where('status', 'active')->exists();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function auction()
    {
        return $this->hasOne(Auction::class);
    }

    public function likes()
    {
        return $this->hasMany(ListingLike::class);
    }

    public function comments()
    {
        return $this->hasMany(ListingComment::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function activePromotions()
    {
        return $this->hasMany(Promotion::class)->where('status', 'active');
    }

    public function listingImages()
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    // Helper để lấy ảnh đầu tiên
    public function getMainImageAttribute()
    {
        if ($this->images && count($this->images) > 0) {
            return $this->images[0];
        }
        return $this->listingImages()->first()?->url;
    }
}
