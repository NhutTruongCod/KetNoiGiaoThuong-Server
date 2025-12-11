<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\Payment;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Statistics & Reports Controller
 * 
 * Thống kê và báo cáo cho người dùng
 */
class ReportController extends BaseApiController
{
    /**
     * GET /api/stats/overview
     * Thống kê tổng quan
     */
    public function overview(Request $request)
    {
        $user = $request->user();

        // Chỉ seller và admin mới có thống kê
        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        // Parse date range
        $dateFrom = $request->input('date_from', Carbon::now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());
        $shopId = $request->input('shop_id');

        // Build base query
        $listingsQuery = Listing::where('user_id', $user->id);
        $ordersQuery = Order::where('seller_id', $user->id);
        
        if ($shopId) {
            $listingsQuery->where('shop_id', $shopId);
            $ordersQuery->where('shop_id', $shopId);
        }

        // Current period stats
        $currentOrders = (clone $ordersQuery)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
        
        $totalListings = $listingsQuery->count();
        $activeListings = (clone $listingsQuery)->where('is_active', true)->count();
        $totalViews = (clone $listingsQuery)->sum('views_count') ?? 0;
        $totalOrders = $currentOrders->count();
        $totalRevenue = (clone $currentOrders)->whereIn('status', ['completed', 'delivered'])->sum('total_amount') ?? 0;
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // Customer stats
        $totalCustomers = (clone $currentOrders)->distinct('buyer_id')->count('buyer_id');
        $repeatCustomers = (clone $ordersQuery)
            ->select('buyer_id')
            ->groupBy('buyer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        // Rating stats
        $avgRating = Review::whereIn('listing_id', $listingsQuery->pluck('id'))
            ->avg('rating') ?? 0;
        $totalReviews = Review::whereIn('listing_id', $listingsQuery->pluck('id'))->count();

        // Previous period for growth calculation
        $periodDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
        $prevDateFrom = Carbon::parse($dateFrom)->subDays($periodDays)->toDateString();
        $prevDateTo = $dateFrom;
        
        $prevOrders = (clone $ordersQuery)
            ->whereBetween('created_at', [$prevDateFrom, $prevDateTo]);
        
        $prevTotalOrders = $prevOrders->count();
        $prevTotalRevenue = (clone $prevOrders)->whereIn('status', ['completed', 'delivered'])->sum('total_amount') ?? 0;
        $prevTotalCustomers = (clone $prevOrders)->distinct('buyer_id')->count('buyer_id');

        // Calculate growth
        $growth = [
            'views' => 0, // Would need historical view tracking
            'orders' => $this->calculateGrowth($totalOrders, $prevTotalOrders),
            'revenue' => $this->calculateGrowth($totalRevenue, $prevTotalRevenue),
            'customers' => $this->calculateGrowth($totalCustomers, $prevTotalCustomers),
        ];

        // Charts data
        $viewsByMonth = $this->getViewsByMonth($listingsQuery, $dateFrom, $dateTo);
        $revenueByMonth = $this->getRevenueByMonth($ordersQuery, $dateFrom, $dateTo);
        $ordersByStatus = $this->getOrdersByStatus($currentOrders);

        // Top listings
        $topListings = $this->getTopListings($listingsQuery, $dateFrom, $dateTo);
        
        // Top categories
        $topCategories = $this->getTopCategories($ordersQuery, $dateFrom, $dateTo);

        return $this->ok([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'summary' => [
                'total_listings' => $totalListings,
                'active_listings' => $activeListings,
                'total_views' => $totalViews,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'avg_order_value' => round($avgOrderValue, 2),
                'total_customers' => $totalCustomers,
                'repeat_customers' => $repeatCustomers,
                'avg_rating' => round($avgRating, 1),
                'total_reviews' => $totalReviews,
            ],
            'growth' => $growth,
            'charts' => [
                'views_by_month' => $viewsByMonth,
                'revenue_by_month' => $revenueByMonth,
                'orders_by_status' => $ordersByStatus,
            ],
            'top_listings' => $topListings,
            'top_categories' => $topCategories,
        ]);
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getViewsByMonth($listingsQuery, $dateFrom, $dateTo)
    {
        // Simplified - would need view tracking table for accurate data
        return [];
    }

    private function getRevenueByMonth($ordersQuery, $dateFrom, $dateTo)
    {
        return (clone $ordersQuery)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed', 'delivered'])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                return [
                    'month' => $item->month,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function getOrdersByStatus($ordersQuery)
    {
        $statusCounts = (clone $ordersQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        return [
            'pending' => $statusCounts['pending'] ?? 0,
            'confirmed' => $statusCounts['confirmed'] ?? 0,
            'shipping' => $statusCounts['shipping'] ?? 0,
            'delivered' => $statusCounts['delivered'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
        ];
    }

    private function getTopListings($listingsQuery, $dateFrom, $dateTo)
    {
        $listingIds = $listingsQuery->pluck('id');
        
        return Order::whereIn('listing_id', $listingIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(
                'listing_id',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('listing_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $listing = Listing::find($item->listing_id);
                return [
                    'id' => $item->listing_id,
                    'title' => $listing->title ?? 'N/A',
                    'views' => $listing->views_count ?? 0,
                    'orders' => $item->orders,
                    'revenue' => (float) $item->revenue,
                    'rating' => $listing->rating ?? 0,
                ];
            })
            ->toArray();
    }

    private function getTopCategories($ordersQuery, $dateFrom, $dateTo)
    {
        return (clone $ordersQuery)
            ->join('listings', 'orders.listing_id', '=', 'listings.id')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->whereNotNull('listings.category')
            ->select(
                'listings.category as category_name',
                DB::raw('COUNT(orders.id) as orders'),
                DB::raw('SUM(orders.total_amount) as revenue')
            )
            ->groupBy('listings.category')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'category_id' => null,
                    'category_name' => $item->category_name,
                    'orders' => $item->orders,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * GET /api/stats/views
     * Thống kê lượt xem
     */
    public function views(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $v = $request->validate([
            'period' => 'nullable|in:day,week,month,year',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'listing_id' => 'nullable|integer|exists:listings,id',
        ]);

        $period = $v['period'] ?? 'month';
        $dateFrom = $v['date_from'] ?? Carbon::now()->subDays(30)->toDateString();
        $dateTo = $v['date_to'] ?? Carbon::now()->toDateString();

        $listingsQuery = Listing::where('user_id', $user->id);

        if (!empty($v['listing_id'])) {
            $listingsQuery->where('id', $v['listing_id']);
        }

        $listings = $listingsQuery->get(['id', 'title', 'views_count']);
        $totalViews = $listings->sum('views_count');
        
        // Calculate metrics
        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $avgViewsPerDay = $days > 0 ? round($totalViews / $days, 2) : 0;
        
        // Views by day (simulated - would need actual tracking)
        $viewsByDay = $this->generateViewsByDay($dateFrom, $dateTo, $totalViews);
        
        // Peak day
        $peakDay = collect($viewsByDay)->sortByDesc('views')->first();
        
        // Views by hour (simulated)
        $viewsByHour = $this->generateViewsByHour($totalViews);
        
        // Views by source (simulated)
        $viewsBySource = [
            'direct' => round($totalViews * 0.33),
            'search' => round($totalViews * 0.40),
            'social' => round($totalViews * 0.18),
            'referral' => round($totalViews * 0.09),
        ];
        
        // Views by device (simulated)
        $viewsByDevice = [
            'desktop' => round($totalViews * 0.44),
            'mobile' => round($totalViews * 0.49),
            'tablet' => round($totalViews * 0.07),
        ];
        
        // Top listings by views
        $topListingsByViews = $listings->sortByDesc('views_count')
            ->take(10)
            ->map(function($listing) {
                return [
                    'listing_id' => $listing->id,
                    'title' => $listing->title,
                    'views' => $listing->views_count ?? 0,
                    'unique_visitors' => round(($listing->views_count ?? 0) * 0.85), // Simulated
                ];
            })
            ->values()
            ->toArray();

        return $this->ok([
            'period' => $period,
            'total_views' => $totalViews,
            'unique_visitors' => round($totalViews * 0.85), // Simulated
            'avg_views_per_day' => $avgViewsPerDay,
            'peak_day' => $peakDay,
            'views_by_day' => $viewsByDay,
            'views_by_hour' => $viewsByHour,
            'views_by_source' => $viewsBySource,
            'views_by_device' => $viewsByDevice,
            'top_listings_by_views' => $topListingsByViews,
        ]);
    }

    private function generateViewsByDay($dateFrom, $dateTo, $totalViews)
    {
        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $avgPerDay = $days > 0 ? $totalViews / $days : 0;
        
        $result = [];
        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);
        
        while ($current <= $end) {
            $variance = rand(-20, 20) / 100;
            $views = round($avgPerDay * (1 + $variance));
            
            $result[] = [
                'date' => $current->toDateString(),
                'views' => max(0, $views),
                'unique' => round($views * 0.85),
            ];
            
            $current->addDay();
        }
        
        return $result;
    }

    private function generateViewsByHour($totalViews)
    {
        $hourlyDistribution = [
            0 => 0.02, 1 => 0.01, 2 => 0.01, 3 => 0.01, 4 => 0.01, 5 => 0.02,
            6 => 0.03, 7 => 0.04, 8 => 0.06, 9 => 0.08, 10 => 0.09, 11 => 0.08,
            12 => 0.07, 13 => 0.06, 14 => 0.07, 15 => 0.08, 16 => 0.07, 17 => 0.06,
            18 => 0.05, 19 => 0.05, 20 => 0.05, 21 => 0.04, 22 => 0.03, 23 => 0.02,
        ];
        
        $result = [];
        foreach ($hourlyDistribution as $hour => $percentage) {
            $result[] = [
                'hour' => $hour,
                'views' => round($totalViews * $percentage),
            ];
        }
        
        return $result;
    }

    /**
     * GET /api/stats/revenue
     * Thống kê doanh thu
     */
    public function revenue(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $v = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'shop_id' => 'nullable|integer',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $dateFrom = $v['date_from'] ?? Carbon::now()->subDays(30)->toDateString();
        $dateTo = $v['date_to'] ?? Carbon::now()->toDateString();
        $groupBy = $v['group_by'] ?? 'month';
        $shopId = $v['shop_id'] ?? null;

        $ordersQuery = Order::where('seller_id', $user->id);
        
        if ($shopId) {
            $ordersQuery->where('shop_id', $shopId);
        }

        $currentOrders = (clone $ordersQuery)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        // Summary calculations
        $totalRevenue = (clone $currentOrders)->whereIn('status', ['completed', 'delivered'])->sum('total_amount') ?? 0;
        $totalOrders = $currentOrders->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // Profit calculation (assuming 30% margin)
        $totalProfit = $totalRevenue * 0.30;
        $profitMargin = 30;
        
        // Refunds
        $totalRefunds = Payment::whereIn('order_id', $currentOrders->pluck('id'))
            ->where('status', 'refunded')
            ->sum('amount') ?? 0;
        $refundRate = $totalRevenue > 0 ? ($totalRefunds / $totalRevenue) * 100 : 0;

        // Revenue by month/week/day
        $revenueByPeriod = $this->getRevenueByPeriod($ordersQuery, $dateFrom, $dateTo, $groupBy);
        
        // Revenue by category
        $revenueByCategory = $this->getRevenueByCategory($ordersQuery, $dateFrom, $dateTo);
        
        // Revenue by payment method
        $revenueByPaymentMethod = $this->getRevenueByPaymentMethod($currentOrders);
        
        // Top customers
        $topCustomers = $this->getTopCustomers($ordersQuery, $dateFrom, $dateTo);

        return $this->ok([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_orders' => $totalOrders,
                'avg_order_value' => round($avgOrderValue, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_margin' => $profitMargin,
                'total_refunds' => round($totalRefunds, 2),
                'refund_rate' => round($refundRate, 2),
            ],
            'revenue_by_' . $groupBy => $revenueByPeriod,
            'revenue_by_category' => $revenueByCategory,
            'revenue_by_payment_method' => $revenueByPaymentMethod,
            'top_customers' => $topCustomers,
        ]);
    }

    private function getRevenueByPeriod($ordersQuery, $dateFrom, $dateTo, $groupBy)
    {
        $format = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m',
        };

        return (clone $ordersQuery)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed', 'delivered'])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$format') as period"),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('AVG(total_amount) as avg_order_value'),
                DB::raw('SUM(total_amount) * 0.30 as profit')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function($item) use ($groupBy) {
                return [
                    $groupBy => $item->period,
                    'revenue' => round((float) $item->revenue, 2),
                    'orders' => $item->orders,
                    'avg_order_value' => round((float) $item->avg_order_value, 2),
                    'profit' => round((float) $item->profit, 2),
                ];
            })
            ->toArray();
    }

    private function getRevenueByCategory($ordersQuery, $dateFrom, $dateTo)
    {
        $data = (clone $ordersQuery)
            ->join('listings', 'orders.listing_id', '=', 'listings.id')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->whereNotNull('listings.category')
            ->select(
                'listings.category as category_name',
                DB::raw('SUM(orders.total_amount) as revenue'),
                DB::raw('COUNT(orders.id) as orders')
            )
            ->groupBy('listings.category')
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $data->sum('revenue');

        return $data->map(function($item) use ($totalRevenue) {
            return [
                'category_id' => null,
                'category_name' => $item->category_name,
                'revenue' => round((float) $item->revenue, 2),
                'orders' => $item->orders,
                'percentage' => $totalRevenue > 0 ? round(($item->revenue / $totalRevenue) * 100, 2) : 0,
            ];
        })->toArray();
    }

    private function getRevenueByPaymentMethod($ordersQuery)
    {
        $payments = Payment::whereIn('order_id', $ordersQuery->pluck('id'))
            ->where('status', 'completed')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        return [
            'cod' => round((float) ($payments['cod'] ?? 0), 2),
            'vnpay' => round((float) ($payments['vnpay'] ?? 0), 2),
            'momo' => round((float) ($payments['momo'] ?? 0), 2),
        ];
    }

    private function getTopCustomers($ordersQuery, $dateFrom, $dateTo)
    {
        return (clone $ordersQuery)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed', 'delivered'])
            ->select(
                'buyer_id as user_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as avg_order_value')
            )
            ->groupBy('buyer_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $user = \App\Models\User::find($item->user_id);
                return [
                    'user_id' => $item->user_id,
                    'name' => $user->name ?? 'N/A',
                    'total_orders' => $item->total_orders,
                    'total_spent' => round((float) $item->total_spent, 2),
                    'avg_order_value' => round((float) $item->avg_order_value, 2),
                ];
            })
            ->toArray();
    }

    /**
     * GET /api/stats/promotions
     * Báo cáo hiệu quả quảng cáo
     */
    public function promotions(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $v = $request->validate([
            'status' => 'nullable|in:pending,active,paused,completed,cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
        ]);

        $dateFrom = $v['date_from'] ?? Carbon::now()->subDays(30)->toDateString();
        $dateTo = $v['date_to'] ?? Carbon::now()->toDateString();
        $status = $v['status'] ?? null;

        $query = Promotion::whereHas('listing', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        if ($status) {
            $query->where('status', $status);
        }

        $query->whereBetween('created_at', [$dateFrom, $dateTo]);

        $promotions = $query->get();

        // Summary calculations
        $totalPromotions = $promotions->count();
        $activePromotions = $promotions->where('status', 'active')->count();
        $totalBudget = $promotions->sum('budget');
        $totalSpent = $promotions->sum('spent');
        $totalImpressions = $promotions->sum('impressions');
        $totalClicks = $promotions->sum('clicks');
        $totalConversions = $promotions->sum('conversions');
        
        $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
        $avgConversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        $avgCostPerClick = $totalClicks > 0 ? $totalSpent / $totalClicks : 0;
        $avgCostPerConversion = $totalConversions > 0 ? $totalSpent / $totalConversions : 0;
        
        // Calculate ROI (assuming revenue from conversions)
        $estimatedRevenue = $totalConversions * 1000000; // Assume 1M VND per conversion
        $roi = $totalSpent > 0 ? (($estimatedRevenue - $totalSpent) / $totalSpent) * 100 : 0;

        // Promotions by type
        $promotionsByType = $this->getPromotionsByType($promotions);
        
        // Performance by day
        $performanceByDay = $this->getPerformanceByDay($promotions, $dateFrom, $dateTo);
        
        // Top performing promotions
        $topPerformingPromotions = $this->getTopPerformingPromotions($promotions);

        return $this->ok([
            'summary' => [
                'total_promotions' => $totalPromotions,
                'active_promotions' => $activePromotions,
                'total_budget' => round($totalBudget, 2),
                'total_spent' => round($totalSpent, 2),
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'avg_ctr' => round($avgCtr, 2),
                'avg_conversion_rate' => round($avgConversionRate, 2),
                'avg_cost_per_click' => round($avgCostPerClick, 2),
                'avg_cost_per_conversion' => round($avgCostPerConversion, 2),
                'roi' => round($roi, 2),
            ],
            'promotions_by_type' => $promotionsByType,
            'performance_by_day' => $performanceByDay,
            'top_performing_promotions' => $topPerformingPromotions,
        ]);
    }

    private function getPromotionsByType($promotions)
    {
        $types = ['featured', 'top_search', 'homepage_banner', 'category_banner'];
        $result = [];

        foreach ($types as $type) {
            $typePromotions = $promotions->where('type', $type);
            $spent = $typePromotions->sum('spent');
            $conversions = $typePromotions->sum('conversions');
            $estimatedRevenue = $conversions * 1000000;
            $roi = $spent > 0 ? (($estimatedRevenue - $spent) / $spent) * 100 : 0;

            $result[] = [
                'type' => $type,
                'count' => $typePromotions->count(),
                'spent' => round($spent, 2),
                'impressions' => $typePromotions->sum('impressions'),
                'clicks' => $typePromotions->sum('clicks'),
                'conversions' => $conversions,
                'roi' => round($roi, 2),
            ];
        }

        return $result;
    }

    private function getPerformanceByDay($promotions, $dateFrom, $dateTo)
    {
        // Simplified - distribute metrics evenly across days
        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        
        if ($days <= 0) return [];

        $totalImpressions = $promotions->sum('impressions');
        $totalClicks = $promotions->sum('clicks');
        $totalConversions = $promotions->sum('conversions');
        $totalSpent = $promotions->sum('spent');

        $avgImpressions = $totalImpressions / $days;
        $avgClicks = $totalClicks / $days;
        $avgConversions = $totalConversions / $days;
        $avgSpent = $totalSpent / $days;

        $result = [];
        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        while ($current <= $end && count($result) < 30) { // Limit to 30 days
            $variance = rand(-15, 15) / 100;
            
            $result[] = [
                'date' => $current->toDateString(),
                'impressions' => round($avgImpressions * (1 + $variance)),
                'clicks' => round($avgClicks * (1 + $variance)),
                'conversions' => round($avgConversions * (1 + $variance)),
                'spent' => round($avgSpent * (1 + $variance), 2),
            ];
            
            $current->addDay();
        }

        return $result;
    }

    private function getTopPerformingPromotions($promotions)
    {
        return $promotions->sortByDesc(function($promo) {
            $revenue = $promo->conversions * 1000000;
            return $promo->spent > 0 ? ($revenue - $promo->spent) / $promo->spent : 0;
        })
        ->take(5)
        ->map(function($promo) {
            $revenue = $promo->conversions * 1000000;
            $roi = $promo->spent > 0 ? (($revenue - $promo->spent) / $promo->spent) * 100 : 0;

            return [
                'promotion_id' => $promo->id,
                'listing_id' => $promo->listing_id,
                'listing_title' => $promo->listing->title ?? 'N/A',
                'type' => $promo->type,
                'impressions' => $promo->impressions,
                'clicks' => $promo->clicks,
                'conversions' => $promo->conversions,
                'spent' => round($promo->spent, 2),
                'revenue' => $revenue,
                'roi' => round($roi, 2),
            ];
        })
        ->values()
        ->toArray();
    }
}
