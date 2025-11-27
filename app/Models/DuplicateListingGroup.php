<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuplicateListingGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'detected_by',
        'note',
        'status',
        'confidence_score',
        'duplicate_items',
    ];

    protected $casts = [
        'duplicate_items' => 'array',
        'confidence_score' => 'integer',
    ];

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Get listings relationship
     */
    public function listings()
    {
        return Listing::whereIn('id', $this->duplicate_items ?? []);
    }

    /**
     * Get duplicate items with listing details
     */
    public function getDuplicateItemsWithDetailsAttribute()
    {
        $listingIds = $this->duplicate_items ?? [];
        
        if (empty($listingIds)) {
            return [];
        }

        return Listing::whereIn('id', $listingIds)
            ->get(['id', 'title', 'description', 'price', 'store_id', 'category_id'])
            ->map(function ($listing) {
                return [
                    'listing_id' => $listing->id,
                    'title' => $listing->title,
                    'description' => $listing->description,
                    'price' => $listing->price,
                    'store' => $listing->store->name ?? 'N/A',
                    'category' => $listing->category->name ?? 'N/A'
                ];
            })->toArray();
    }

    /**
     * Check if group contains specific listing
     */
    public function containsListing($listingId)
    {
        return in_array($listingId, $this->duplicate_items ?? []);
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved()
    {
        $this->update(['status' => 'resolved']);
    }

    /**
     * Mark as ignored
     */
    public function markAsIgnored()
    {
        $this->update(['status' => 'ignored']);
    }
}