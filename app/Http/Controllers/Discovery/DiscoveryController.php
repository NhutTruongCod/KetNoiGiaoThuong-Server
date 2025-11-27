<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Listing;
use App\Models\Bookmark;

/**
 * BA 3.4 — Discovery/Search + Public Listings + Nearby
 *
 * Các API chính cho màn hình khám phá:
 * - GET /api/discovery/search      (text search + filter)
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
            'min_price_cents'  => 'nullable|integer|min:0',
            'max_price_cents'  => 'nullable|integer|min:0',
            'sort'             => 'nullable|in:latest,price_asc,price_desc',
            'page'             => 'nullable|integer|min:1',
            'per_page'         => 'nullable|integer|min:1|max:100',
        ]);

        $q = Listing::query()->where('is_public', true)->where('status', 'published');

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

        if (isset($v['min_price_cents'])) {
            $q->where('price_cents', '>=', $v['min_price_cents']);
        }
        if (isset($v['max_price_cents'])) {
            $q->where('price_cents', '<=', $v['max_price_cents']);
        }

        $sort = $v['sort'] ?? 'latest';
        if ($sort === 'price_asc') {
            $q->orderBy('price_cents', 'asc');
        } elseif ($sort === 'price_desc') {
            $q->orderBy('price_cents', 'desc');
        } else {
            $q->orderBy('created_at', 'desc');
        }

        $perPage = $v['per_page'] ?? 20;
        $items   = $q->paginate($perPage);

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
