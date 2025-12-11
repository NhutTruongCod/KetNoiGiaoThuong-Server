<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Bookmark;
use App\Models\Listing;

/**
 * BA 3.4 — API Bookmarks
 * 
 * - GET    /api/bookmarks
 * - POST   /api/bookmarks
 * - DELETE /api/bookmarks/{listing_id}
 */
class BookmarkController extends BaseApiController
{
    /**
     * GET /api/bookmarks
     * Danh sách tin đăng đã đánh dấu
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);

        $bookmarks = Bookmark::where('user_id', $user->id)
            ->with('listing')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginate($bookmarks);
    }

    /**
     * POST /api/bookmarks
     * body: { listing_id: int }
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $v = $request->validate([
            'listing_id' => 'required|integer|exists:listings,id',
        ]);

        $bookmark = Bookmark::firstOrCreate([
            'user_id'    => $user->id,
            'listing_id' => $v['listing_id'],
        ]);

        return $this->created($bookmark->load('listing'));
    }

    /**
     * DELETE /api/bookmarks/{listing_id}
     */
    public function destroy(Request $request, int $listingId)
    {
        $user = $request->user();
        $bookmark = Bookmark::where('listing_id', $listingId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $bookmark->delete();

        return $this->noContent();
    }
}
