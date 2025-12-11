<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Listing;
use App\Models\Bookmark;
use App\Models\Shop;
use App\Models\User;

/**
 * BA 3.4 — Discovery/Search + Public Listings + Nearby
 *
 * Các API chính cho màn hình khám phá:
 * - GET /api/discovery/search      (text search + filter cho listings)
 * - GET /api/discovery/search-all  (search cả shops và listings)
 * - GET /api/discovery/shops       (search shops/companies)
 * - GET /api/discovery/nearby      (tìm tin gần vị trí lat/lng)
 * - GET /api/discovery/bookmarks   (danh sách tin đã lưu của user)
 */
class DiscoveryController extends BaseApiController
{
    /**
     * GET /api/discovery/search
     * query, category, min_price_cents, max_price_cents, sort=latest|price_asc|price_desc
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
            ->where('is_public', true)
            ->where('status', 'published');

        if (!empty($v['query'])) {
            $term = $v['query'];
            $q->where(function ($sub) use ($term) {
                $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('location_text', 'like', "%{$term}%");
            });
        }

        if (!empty($v['category'])) {
            $q->where('category', $v['category']);
        }

        // Filter by shop_id
        if (!empty($v['shop_id'])) {
            $q->where('shop_id', $v['shop_id']);
        }

        if (isset($v['min_price_cents'])) {
            $q->where('price_cents', '>=', $v['min_price_cents']);
        }
        if (isset($v['max_price_cents'])) {
            $q->where('price_cents', '<=', $v['max_price_cents']);
        }

        // Apply search boost from subscription plans
        // Join with users and subscription_plans to get search_boost
        $q->leftJoin('users', 'listings.user_id', '=', 'users.id')
          ->leftJoin('subscription_plans', function ($join) {
              $join->on('users.subscription_plan_id', '=', 'subscription_plans.id')
                   ->whereRaw('users.subscription_expires_at > NOW()');
          })
          ->select('listings.*')
          ->selectRaw('COALESCE(subscription_plans.search_boost, 0) as search_boost');

        $sort = $v['sort'] ?? 'latest';
        if ($sort === 'price_asc') {
            $q->orderByDesc('search_boost')->orderBy('price_cents', 'asc');
        } elseif ($sort === 'price_desc') {
            $q->orderByDesc('search_boost')->orderBy('price_cents', 'desc');
        } else {
            $q->orderByDesc('search_boost')->orderBy('listings.created_at', 'desc');
        }

        $perPage = $v['per_page'] ?? 20;
        $items   = $q->paginate($perPage);

        return $this->paginate($items);
    }

    /**
     * GET /api/discovery/search-all
     * Tìm kiếm cả shops (công ty) và listings (sản phẩm)
     * 
     * @param query - từ khóa tìm kiếm
     * @param type - all|shops|listings (default: all)
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

        // Search listings (products) with search_boost from subscription
        if ($type === 'all' || $type === 'listings') {
            $listings = Listing::query()
                ->with(['shop:id,name,slug,logo,is_verified'])
                ->leftJoin('users', 'listings.user_id', '=', 'users.id')
                ->leftJoin('subscription_plans', function ($join) {
                    $join->on('users.subscription_plan_id', '=', 'subscription_plans.id')
                         ->whereRaw('users.subscription_expires_at > NOW()');
                })
                ->select('listings.*')
                ->selectRaw('COALESCE(subscription_plans.search_boost, 0) as search_boost')
                ->where('listings.is_public', true)
                ->where('listings.status', 'published')
                ->where(function ($q) use ($term) {
                    $q->where('listings.title', 'like', "%{$term}%")
                      ->orWhere('listings.description', 'like', "%{$term}%");
                })
                ->orderByDesc('search_boost')
                ->orderByDesc('listings.created_at')
                ->limit($perPage)
                ->get();

            $result['listings'] = $listings->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'slug' => $listing->slug,
                    'price_cents' => $listing->price_cents,
                    'currency' => $listing->currency,
                    'images' => $listing->images,
                    'category' => $listing->category,
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
