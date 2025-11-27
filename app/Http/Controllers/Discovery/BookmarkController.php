<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Bookmark;
use App\Models\Listing;

/**
 * BA 3.4 — API Bookmarks
 * 
 * - GET  /api/bookmarks           (đã cover trong DiscoveryController@bookmarks)
 * - POST /api/bookmarks           (create)
 * - DELETE /api/bookmarks/{id}    (remove)
 */
class BookmarkController extends BaseApiController
{
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

        return $this->ok($bookmark);
    }

    /**
     * DELETE /api/bookmarks/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $bookmark = Bookmark::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $bookmark->delete();

        return $this->noContent();
    }
}
