<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    // Quan hệ với Shop
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    // Quan hệ với ảnh
    public function productImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    // Quan hệ với Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}