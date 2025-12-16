<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Listing;
use App\Models\Bookmark;
use App\Models\Shop;
use App\Models\User;
use App\Models\Promotion;

/**
 * BA 3.4 — Discovery/Search + Public Listings + Nearby
 *
 * Các API chính cho màn hình khám phá:
 * - GET /api/discovery/search      (text search + filter cho listings)
 * - GET /api/discovery/search-all  (search cả shops và listings)
 * - GET /api/discovery/shops       (search shops/companies)
 * - GET /api/discovery/nearby      (tìm tin gần vị trí lat/lng)
 * - GET /api/discovery/bookmarks   (danh sách tin đã lưu của user)
 * - GET /api/discovery/featured    (danh sách tin nổi bật/quảng cáo)
 */
class DiscoveryController extends BaseApiController
{
    /**
     * GET /api/discovery/search
     * query, category, min_price_cents, max_price_cents, sort=latest|price_asc|price_desc
     * 
     * Ưu tiên hiển thị:
     * 1. Listings có promotion type='top_search' và status='active' (lên top)
     * 2. Listings có subscription search_boost cao
     * 3. Listings thường
     */
    public function search(Request $request)
    {
        $v = $request->validate([
            'query'            => 'nullable|string|max:255',
            'category'         => 'nullable|string|max:100',
            'shop_id'          => 'nullable|integer|exists:shops,id',
            'min_price_cents'  => 'nullable|integer|min:0',
            'max_price_cents'  => 'nullable|integer|min:0',
            'sort'             => 'nullable|in:latest,price_asc,price_desc',
            'page'             => 'nullable|integer|min:1',
            'per_page'         => 'nullable|integer|min:1|max:100',
        ]);

        $q = Listing::query()
            ->with(['shop:id,name,slug,logo,is_verified,rating'])
            ->where('listings.is_public', true)
            ->where('listings.status', 'published');

        if (!empty($v['query'])) {
            $term = $v['query'];
            $q->where(function ($sub) use ($term) {
                $sub->where('listings.title', 'like', "%{$term}%")
                    ->orWhere('listings.description', 'like', "%{$term}%")
                    ->orWhere('listings.location_text', 'like', "%{$term}%");
            });
        }

        if (!empty($v['category'])) {
            $q->where('listings.category', $v['category']);
        }

        // Filter by shop_id
        if (!empty($v['shop_id'])) {
            $q->where('listings.shop_id', $v['shop_id']);
        }

        if (isset($v['min_price_cents'])) {
            $q->where('listings.price_cents', '>=', $v['min_price_cents']);
        }
        if (isset($v['max_price_cents'])) {
            $q->where('listings.price_cents', '<=', $v['max_price_cents']);
        }

        // Join với promotions để lấy thông tin quảng cáo (top_search HOẶC featured)
        // Join với users và subscription_plans để lấy search_boost
        $q->leftJoin('promotions', function ($join) {
              $join->on('listings.id', '=', 'promotions.listing_id')
                   ->where('promotions.status', '=', 'active')
                   ->whereIn('promotions.type', ['top_search', 'featured']) // Ưu tiên cả featured
                   ->whereRaw('promotions.end_date >= CURDATE()');
          })
          ->leftJoin('users', 'listings.user_id', '=', 'users.id')
          ->leftJoin('subscription_plans', function ($join) {
              $join->on('users.subscription_plan_id', '=', 'subscription_plans.id')
                   ->whereRaw('users.subscription_expires_at > NOW()');
          })
          ->select('listings.*')
          ->selectRaw('CASE WHEN promotions.id IS NOT NULL THEN 1 ELSE 0 END as has_promotion')
          ->selectRaw('COALESCE(promotions.featured_position, 999) as promo_position')
          ->selectRaw('COALESCE(subscription_plans.search_boost, 0) as search_boost')
          ->selectRaw('promotions.id as promotion_id')
          ->selectRaw('promotions.type as promotion_type');

        $sort = $v['sort'] ?? 'latest';
        
        // Luôn ưu tiên: có quảng cáo (top_search hoặc featured) > search_boost > sort thường
        if ($sort === 'price_asc') {
            $q->orderByDesc('has_promotion')
              ->orderBy('promo_position')
              ->orderByDesc('search_boost')
              ->orderBy('listings.price_cents', 'asc');
        } elseif ($sort === 'price_desc') {
            $q->orderByDesc('has_promotion')
              ->orderBy('promo_position')
              ->orderByDesc('search_boost')
              ->orderBy('listings.price_cents', 'desc');
        } else {
            $q->orderByDesc('has_promotion')
              ->orderBy('promo_position')
              ->orderByDesc('search_boost')
              ->orderBy('listings.created_at', 'desc');
        }

        $perPage = $v['per_page'] ?? 20;
        $items   = $q->paginate($perPage);

        // Track impressions cho các listings có promotion
        $this->trackPromotionImpressions($items->items());

        return $this->paginate($items);
    }

    /**
     * GET /api/discovery/featured
     * Lấy danh sách tin nổi bật (có quảng cáo featured hoặc homepage_banner)
     */
    public function featured(Request $request)
    {
        $v = $request->validate([
            'type'     => 'nullable|in:featured,homepage_banner,category_banner,all',
            'category' => 'nullable|string|max:100',
            'limit'    => 'nullable|integer|min:1|max:50',
        ]);

        $type = $v['type'] ?? 'all';
        $limit = $v['limit'] ?? 10;

        $q = Listing::query()
            ->with(['shop:id,name,slug,logo,is_verified,rating'])
            ->join('promotions', 'listings.id', '=', 'promotions.listing_id')
            ->where('listings.is_public', true)
            ->where('listings.status', 'published')
            ->where('promotions.status', 'active')
            ->whereRaw('promotions.end_date >= CURDATE()');

        if ($type !== 'all') {
            $q->where('promotions.type', $type);
        } else {
            $q->whereIn('promotions.type', ['featured', 'homepage_banner']);
        }

        if (!empty($v['category'])) {
            $q->where('listings.category', $v['category']);
        }

        $items = $q->select('listings.*')
            ->selectRaw('promotions.id as promotion_id')
            ->selectRaw('promotions.type as promotion_type')
            ->selectRaw('promotions.is_featured')
            ->selectRaw('promotions.featured_position')
            ->orderBy('promotions.featured_position')
            ->orderByDesc('promotions.created_at')
            ->limit($limit)
            ->get();

        // Track impressions
        $this->trackPromotionImpressions($items->toArray());

        return $this->ok([
            'data' => $items,
            'total' => $items->count(),
        ]);
    }

    /**
     * Track impressions cho các listings có promotion
     */
    private function trackPromotionImpressions($items)
    {
        foreach ($items as $item) {
            $promotionId = is_array($item) ? ($item['promotion_id'] ?? null) : ($item->promotion_id ?? null);
            if ($promotionId) {
                Promotion::where('id', $promotionId)->increment('impressions');
            }
        }
    }

    /**
     * POST /api/discovery/promotions/{id}/click
     * Track click cho promotion (FE gọi khi user click vào listing có quảng cáo)
     */
    public function trackClick(Request $request, $promotionId)
    {
        $promotion = Promotion::find($promotionId);
        
        if (!$promotion || $promotion->status !== 'active') {
            return $this->ok(['tracked' => false]);
        }

        // Tính cost per click (giả sử 500 VND/click)
        $costPerClick = 500;
        
        $promotion->trackClick($costPerClick);
        $promotion->checkAndComplete(); // Auto-complete nếu hết budget

        return $this->ok(['tracked' => true]);
    }

    /**
     * GET /api/discovery/search-all
     * Tìm kiếm cả shops (công ty) và listings (sản phẩm)
     * 
     * @param query - từ khóa tìm kiếm
     * @param type - all|shops|listings (default: all)
     * 
     * Ưu tiên hiển thị listings:
     * 1. Có promotion top_search active
     * 2. Có subscription search_boost cao
     * 3. Listings thường
     */
    public function searchAll(Request $request)
    {
        $v = $request->validate([
            'query'    => 'required|string|max:255',
            'type'     => 'nullable|in:all,shops,listings',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $term = $v['query'];
        $type = $v['type'] ?? 'all';
        $perPage = $v['per_page'] ?? 10;

        $result = [];

        // Search shops (companies)
        if ($type === 'all' || $type === 'shops') {
            $shops = Shop::query()
                ->with(['owner:id,full_name'])
                ->where('is_active', true)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('business_name', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%");
                })
                ->withCount('listings')
                ->orderByDesc('is_verified')
                ->orderByDesc('rating')
                ->limit($perPage)
                ->get();

            $result['shops'] = $shops->map(function ($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'slug' => $shop->slug,
                    'logo' => $shop->logo,
                    'description' => $shop->description,
                    'is_verified' => $shop->is_verified,
                    'rating' => $shop->rating,
                    'listings_count' => $shop->listings_count,
                    'owner' => $shop->owner,
                ];
            });
        }

        // Search listings (products) with promotion boost + subscription boost
        if ($type === 'all' || $type === 'listings') {
            $listings = Listing::query()
                ->with(['shop:id,name,slug,logo,is_verified'])
                ->leftJoin('promotions', function ($join) {
                    $join->on('listings.id', '=', 'promotions.listing_id')
                         ->where('promotions.status', '=', 'active')
                         ->whereIn('promotions.type', ['top_search', 'featured']) // Ưu tiên cả featured
                         ->whereRaw('promotions.end_date >= CURDATE()');
                })
                ->leftJoin('users', 'listings.user_id', '=', 'users.id')
                ->leftJoin('subscription_plans', function ($join) {
                    $join->on('users.subscription_plan_id', '=', 'subscription_plans.id')
                         ->whereRaw('users.subscription_expires_at > NOW()');
                })
                ->select('listings.*')
                ->selectRaw('CASE WHEN promotions.id IS NOT NULL THEN 1 ELSE 0 END as has_promotion')
                ->selectRaw('COALESCE(promotions.featured_position, 999) as promo_position')
                ->selectRaw('COALESCE(subscription_plans.search_boost, 0) as search_boost')
                ->selectRaw('promotions.id as promotion_id')
                ->selectRaw('promotions.type as promotion_type')
                ->where('listings.is_public', true)
                ->where('listings.status', 'published')
                ->where(function ($q) use ($term) {
                    $q->where('listings.title', 'like', "%{$term}%")
                      ->orWhere('listings.description', 'like', "%{$term}%");
                })
                ->orderByDesc('has_promotion')
                ->orderBy('promo_position')
                ->orderByDesc('search_boost')
                ->orderByDesc('listings.created_at')
                ->limit($perPage)
                ->get();

            // Track impressions
            $this->trackPromotionImpressions($listings->toArray());

            $result['listings'] = $listings->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'slug' => $listing->slug,
                    'price_cents' => $listing->price_cents,
                    'price' => $listing->price,
                    'currency' => $listing->currency,
                    'images' => $listing->images,
                    'category' => $listing->category,
                    'has_promotion' => (bool) $listing->promotion_id,
                    'promotion_type' => $listing->promotion_type,
                    'shop' => $listing->shop ? [
                        'id' => $listing->shop->id,
                        'name' => $listing->shop->name,
                        'slug' => $listing->shop->slug,
                        'logo' => $listing->shop->logo,
                        'is_verified' => $listing->shop->is_verified,
                    ] : null,
                ];
            });
        }

        return $this->ok($result);
    }

    /**
     * GET /api/discovery/shops
     * Tìm kiếm và lọc danh sách công ty/shop
     */
    public function shops(Request $request)
    {
        $v = $request->validate([
            'query'     => 'nullable|string|max:255',
            'verified'  => 'nullable|boolean',
            'sort'      => 'nullable|in:latest,rating,products',
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $q = Shop::query()
            ->with(['owner:id,full_name,avatar_url'])
            ->where('is_active', true)
            ->withCount('listings');

        if (!empty($v['query'])) {
            $term = $v['query'];
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('business_name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (isset($v['verified'])) {
            $q->where('is_verified', $v['verified']);
        }

        $sort = $v['sort'] ?? 'latest';
        if ($sort === 'rating') {
            $q->orderByDesc('rating');
        } elseif ($sort === 'products') {
            $q->orderByDesc('listings_count');
        } else {
            $q->orderByDesc('created_at');
        }

        $perPage = $v['per_page'] ?? 20;
        $items = $q->paginate($perPage);

        return $this->paginate($items);
    }

    /**
     * GET /api/discovery/nearby
     * lat, lng, radius_km (default 10km)
     *
     * Sử dụng xấp xỉ Haversine đơn giản trên MySQL.
     */
    public function nearby(Request $request)
    {
        $v = $request->validate([
            'lat'       => 'required|numeric|between:-90,90',
            'lng'       => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:200',
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $lat = $v['lat'];
        $lng = $v['lng'];
        $radius = $v['radius_km'] ?? 10;

        // Công thức Haversine (bán kính Trái đất ~6371km)
        $q = Listing::query()
            ->select('*')
            ->selectRaw('
                6371 * 2 * ASIN(
                    SQRT(
                        POWER(SIN(RADIANS(? - latitude) / 2), 2) +
                        COS(RADIANS(latitude)) * COS(RADIANS(?)) *
                        POWER(SIN(RADIANS(? - longitude) / 2), 2)
                    )
                ) as distance_km
            ', [$lat, $lat, $lng])
            ->where('is_public', true)
            ->where('status', 'published')
            ->having('distance_km', '<=', $radius)
            ->orderBy('distance_km', 'asc');

        $perPage = $v['per_page'] ?? 20;
        $items   = $q->paginate($perPage);

        return $this->paginate($items);
    }

    /**
     * GET /api/discovery/bookmarks
     * Yêu cầu auth:sanctum
     */
    public function bookmarks(Request $request)
    {
        $user = $request->user();

        $bookmarks = Bookmark::with('listing')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->paginate($bookmarks);
    }
}
