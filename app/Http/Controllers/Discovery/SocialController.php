<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\ListingLike;
use App\Models\ListingComment;

/**
 * BA 3.4 — API Social/Interactions (Like/Comment/Share)
 *
 * - POST   /api/social/listings/{id}/like
 * - DELETE /api/social/listings/{id}/like
 * - POST   /api/social/listings/{id}/comments
 * - GET    /api/social/listings/{id}/comments
 *
 * Share: FE có thể chỉ cần log event click share (tracking), 
 *       nên ở đây chỉ hỗ trợ Like + Comment.
 */
class SocialController extends BaseApiController
{
    public function like(Request $request, int $listingId)
    {
        $user = $request->user();

        $like = ListingLike::firstOrCreate([
            'user_id'    => $user->id,
            'listing_id' => $listingId,
        ]);

        return $this->ok($like);
    }

    public function unlike(Request $request, int $listingId)
    {
        $user = $request->user();

        ListingLike::where('user_id', $user->id)
            ->where('listing_id', $listingId)
            ->delete();

        return $this->noContent();
    }

    public function comments(Request $request, int $listingId)
    {
        $v = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $comments = ListingComment::with('user')
            ->where('listing_id', $listingId)
            ->orderBy('created_at', 'asc')
            ->paginate($v['per_page'] ?? 50);

        return $this->paginate($comments);
    }

    public function storeComment(Request $request, int $listingId)
    {
        $user = $request->user();
        $v = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment = ListingComment::create([
            'listing_id' => $listingId,
            'user_id'    => $user->id,
            'body'       => $v['body'],
        ]);

        return $this->created($comment);
    }
}
