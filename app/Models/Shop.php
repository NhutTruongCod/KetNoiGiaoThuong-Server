<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    public function owner()
{
    return $this->belongsTo(User::class, 'owner_user_id');
}

public function products()
{
    return $this->hasMany(Product::class, 'shop_id');
}
}
