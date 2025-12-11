<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\ListingLike;
use App\Models\ListingComment;
use App\Models\Listing;
use App\Models\Notification;

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

        // Tạo thông báo cho chủ sản phẩm
        $this->createNotification($listingId, $user, 'like');

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

    /**
     * GET /api/listings/{listing}/comments
     * Trả về comments với nested replies
     */
    public function getComments(Request $request, int $listingId)
    {
        $v = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Chỉ lấy comments gốc (không có parent_id), kèm theo replies
        $comments = ListingComment::with(['user', 'replies.user'])
            ->where('listing_id', $listingId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate($v['per_page'] ?? 20);

        return $this->paginate($comments);
    }

    /**
     * POST /api/listings/{listing}/comments
     * Hỗ trợ cả comment mới và reply (nested comment)
     */
    public function comment(Request $request, int $listingId)
    {
        $user = $request->user();
        $v = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:listing_comments,id',
        ]);

        // Nếu là reply, kiểm tra parent comment thuộc cùng listing
        if (!empty($v['parent_id'])) {
            $parentComment = ListingComment::find($v['parent_id']);
            if (!$parentComment || $parentComment->listing_id != $listingId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parent comment không hợp lệ'
                ], 400);
            }
        }

        $comment = ListingComment::create([
            'listing_id' => $listingId,
            'user_id'    => $user->id,
            'parent_id'  => $v['parent_id'] ?? null,
            'body'       => $v['content'],
        ]);

        // Tạo thông báo
        if (!empty($v['parent_id'])) {
            // Reply - thông báo cho người viết comment gốc
            $this->createReplyNotification($comment, $user);
        } else {
            // Comment mới - thông báo cho chủ sản phẩm
            $this->createNotification($listingId, $user, 'comment', $v['content']);
        }

        return $this->created($comment->load('user'));
    }

    /**
     * Tạo thông báo khi có reply
     */
    private function createReplyNotification(ListingComment $reply, $fromUser): void
    {
        try {
            $parentComment = $reply->parent;
            if (!$parentComment) return;

            // Không gửi thông báo cho chính mình
            if ($parentComment->user_id === $fromUser->id) return;

            $listing = Listing::find($reply->listing_id);
            $listingTitle = $listing ? $listing->title : 'sản phẩm';

            Notification::create([
                'user_id' => $parentComment->user_id,
                'type' => 'listing',
                'title' => 'Có người phản hồi bình luận của bạn',
                'message' => "{$fromUser->full_name} đã phản hồi: \"" . mb_substr($reply->body, 0, 100) . "...\"",
                'data' => [
                    'listing_id' => $reply->listing_id,
                    'listing_title' => $listingTitle,
                    'comment_id' => $reply->id,
                    'parent_comment_id' => $parentComment->id,
                    'from_user_id' => $fromUser->id,
                    'from_user_name' => $fromUser->full_name,
                    'action' => 'reply',
                    'content' => $reply->body,
                ],
                'action_url' => '/product/' . $reply->listing_id,
                'is_read' => false,
                'priority' => 'normal',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create reply notification: ' . $e->getMessage());
        }
    }

    /**
     * Tạo thông báo cho chủ sản phẩm khi có tương tác mới
     */
    private function createNotification(int $listingId, $fromUser, string $type, ?string $content = null): void
    {
        try {
            $listing = Listing::find($listingId);
            if (!$listing) return;

            // Không gửi thông báo cho chính mình
            if ($listing->user_id === $fromUser->id) return;

            $title = '';
            $message = '';

            if ($type === 'like') {
                $title = 'Có người thích sản phẩm của bạn';
                $message = "{$fromUser->full_name} đã thích sản phẩm \"{$listing->title}\"";
            } elseif ($type === 'comment') {
                $title = 'Có bình luận mới trên sản phẩm của bạn';
                $message = "{$fromUser->full_name} đã bình luận: \"" . mb_substr($content, 0, 100) . "...\"";
            }

            Notification::create([
                'user_id' => $listing->user_id,
                'type' => 'listing', // Sử dụng type 'listing' cho tương tác sản phẩm
                'title' => $title,
                'message' => $message,
                'data' => [
                    'listing_id' => $listingId,
                    'listing_title' => $listing->title,
                    'from_user_id' => $fromUser->id,
                    'from_user_name' => $fromUser->full_name,
                    'action' => $type,
                    'content' => $content,
                ],
                'action_url' => '/product/' . $listingId,
                'is_read' => false,
                'priority' => 'normal',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create notification: ' . $e->getMessage());
        }
    }
}
