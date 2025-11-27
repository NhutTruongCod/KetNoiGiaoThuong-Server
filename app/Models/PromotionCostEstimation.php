<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionCostEstimation extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'listing_id',
        'promotion_type',
        'duration_days',
        'budget',
        'estimated_cost',
        'currency',
        'status',
        'calculation_method',
        'calculation_details',
        'estimated_impressions',
        'estimated_clicks',
        'estimated_conversions',
        'notes',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'estimated_impressions' => 'decimal:0',
        'estimated_clicks' => 'decimal:0',
        'estimated_conversions' => 'decimal:0',
        'duration_days' => 'integer',
    ];

    /**
     * Relationship với Store
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship với Listing
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Scope by store
     */
    public function scopeByStore($query, $storeId)
    {
        if ($storeId) {
            return $query->where('store_id', $storeId);
        }
        return $query;
    }

    /**
     * Scope by listing
     */
    public function scopeByListing($query, $listingId)
    {
        if ($listingId) {
            return $query->where('listing_id', $listingId);
        }
        return $query;
    }

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
     * Scope by promotion type
     */
    public function scopeByPromotionType($query, $promotionType)
    {
        if ($promotionType) {
            return $query->where('promotion_type', $promotionType);
        }
        return $query;
    }

    /**
     * Tính toán chi phí ước tính dựa trên các yếu tố
     */
    public function calculateEstimatedCost()
    {
        $baseCost = $this->budget;
        
        // Hệ số dựa trên loại quảng cáo
        $typeMultipliers = [
            'banner' => 1.0,
            'video_ads' => 1.5,
            'social_media' => 1.2,
            'search_ads' => 1.3,
            'email_marketing' => 0.8,
            'in_app_ads' => 1.4,
        ];

        // Hệ số dựa trên thời lượng
        $durationMultiplier = min(1.0 + ($this->duration_days / 30 * 0.1), 1.5);

        // Tính toán chi phí ước tính
        $estimatedCost = $baseCost * ($typeMultipliers[$this->promotion_type] ?? 1.0) * $durationMultiplier;

        // Làm tròn đến hàng nghìn
        $estimatedCost = round($estimatedCost / 1000) * 1000;

        return $estimatedCost;
    }

    /**
     * Ước tính hiệu suất
     */
    public function estimatePerformance()
    {
        $baseImpressions = $this->budget * 100; // Giả định 100 impression/1k VND
        $baseClicks = $baseImpressions * 0.02; // Giả định CTR 2%
        $baseConversions = $baseClicks * 0.05; // Giả định conversion rate 5%

        // Điều chỉnh dựa trên loại quảng cáo
        $typeFactors = [
            'banner' => ['impressions' => 1.0, 'clicks' => 1.0, 'conversions' => 1.0],
            'video_ads' => ['impressions' => 0.8, 'clicks' => 1.5, 'conversions' => 1.2],
            'social_media' => ['impressions' => 1.2, 'clicks' => 1.8, 'conversions' => 1.5],
            'search_ads' => ['impressions' => 0.9, 'clicks' => 2.0, 'conversions' => 1.8],
            'email_marketing' => ['impressions' => 0.7, 'clicks' => 1.2, 'conversions' => 2.0],
            'in_app_ads' => ['impressions' => 1.1, 'clicks' => 1.3, 'conversions' => 1.1],
        ];

        $factors = $typeFactors[$this->promotion_type] ?? $typeFactors['banner'];

        return [
            'impressions' => round($baseImpressions * $factors['impressions']),
            'clicks' => round($baseClicks * $factors['clicks']),
            'conversions' => round($baseConversions * $factors['conversions']),
        ];
    }

    /**
     * Kiểm tra xem có thể approve không
     */
    public function canBeApproved()
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    /**
     * Mark as approved
     */
    public function markAsApproved()
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }
}