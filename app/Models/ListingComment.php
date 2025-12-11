<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'user_id',
        'parent_id',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent comment (for replies)
     */
    public function parent()
    {
        return $this->belongsTo(ListingComment::class, 'parent_id');
    }

    /**
     * Child comments (replies)
     */
    public function replies()
    {
        return $this->hasMany(ListingComment::class, 'parent_id')->with('user')->orderBy('created_at', 'asc');
    }

    /**
     * Check if this is a reply
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }
}
