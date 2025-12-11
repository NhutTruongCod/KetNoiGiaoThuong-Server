<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Models\User;

/**
 * BA 3.4 — API Chat / Liên hệ
 *
 * Đơn giản hoá: chat 1-1 giữa 2 user, có thể gắn với 1 listing.
 *
 * - GET  /api/chat/conversations
 * - GET  /api/chat/messages/{user_id}
 * - POST /api/chat/messages
 * - PUT  /api/chat/messages/{user_id}/read
 */
class ChatController extends BaseApiController
{
    /**
     * GET /api/chat/conversations
     * Lấy danh sách cuộc trò chuyện
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        
        // Lấy danh sách user đã chat
        $conversations = ChatMessage::query()
            ->where(function ($q) use ($user) {
                $q->where('from_user_id', $user->id)
                  ->orWhere('to_user_id', $user->id);
            })
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($user) {
                return $message->from_user_id == $user->id 
                    ? $message->to_user_id 
                    : $message->from_user_id;
            })
            ->map(function ($messages, $otherUserId) use ($user) {
                $lastMessage = $messages->first();
                $otherUser = $lastMessage->from_user_id == $user->id 
                    ? $lastMessage->toUser 
                    : $lastMessage->fromUser;
                
                // Đảm bảo otherUser không null
                if (!$otherUser) {
                    return null;
                }
                
                return [
                    'user_id' => $otherUser->id, // Thêm user_id ở root level
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->full_name ?? $otherUser->name ?? 'Người dùng',
                        'full_name' => $otherUser->full_name,
                        'avatar' => $otherUser->avatar_url ?? null,
                        'avatar_url' => $otherUser->avatar_url ?? null,
                        'email' => $otherUser->email,
                        'role' => $otherUser->role ?? null,
                    ],
                    'last_message' => [
                        'id' => $lastMessage->id,
                        'body' => $lastMessage->body,
                        'from_user_id' => $lastMessage->from_user_id,
                        'to_user_id' => $lastMessage->to_user_id,
                        'is_read' => $lastMessage->is_read,
                        'created_at' => $lastMessage->created_at,
                    ],
                    'unread_count' => $messages->where('to_user_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                    'total_messages' => $messages->count(),
                ];
            })
            ->filter() // Loại bỏ null
            ->values();

        return $this->ok($conversations);
    }

    /**
     * GET /api/chat/messages/{user_id}
     * Lấy lịch sử tin nhắn với một user
     */
    public function messages(Request $request, int $userId)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 50);
        
        // Kiểm tra user tồn tại
        $otherUser = User::find($userId);
        if (!$otherUser) {
            return $this->fail([
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        $messages = ChatMessage::query()
            ->where(function ($q) use ($user, $userId) {
                $q->where('from_user_id', $user->id)
                  ->where('to_user_id', $userId);
            })->orWhere(function ($q) use ($user, $userId) {
                $q->where('from_user_id', $userId)
                  ->where('to_user_id', $user->id);
            })
            ->with([
                'fromUser:id,full_name,email,avatar_url',
                'toUser:id,full_name,email,avatar_url',
                'listing:id,title,images'
            ])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        // Thêm thông tin người chat cùng
        $response = $this->paginate($messages);
        $responseData = $response->getData(true);
        $responseData['other_user'] = [
            'id' => $otherUser->id,
            'full_name' => $otherUser->full_name,
            'email' => $otherUser->email,
            'avatar' => $otherUser->avatar_url,
            'avatar_url' => $otherUser->avatar_url,
        ];
        
        return response()->json($responseData);
    }

    /**
     * POST /api/chat/messages
     * Gửi tin nhắn mới
     * 
     * Hỗ trợ cả 2 format:
     * - to_user_id + body (chuẩn)
     * - receiver_id + message (FE cũ)
     */
    public function send(Request $request)
    {
        $user = $request->user();
        
        // Hỗ trợ cả 2 format field name
        $toUserId = $request->input('to_user_id') ?? $request->input('receiver_id');
        $body = $request->input('body') ?? $request->input('message');
        $listingId = $request->input('listing_id');
        
        // Validate
        if (!$toUserId) {
            return $this->fail([
                'message' => 'Thiếu thông tin người nhận',
                'errors' => ['to_user_id' => ['Vui lòng cung cấp to_user_id hoặc receiver_id']]
            ], 422);
        }
        
        if (!$body || trim($body) === '') {
            return $this->fail([
                'message' => 'Thiếu nội dung tin nhắn',
                'errors' => ['body' => ['Vui lòng cung cấp body hoặc message']]
            ], 422);
        }
        
        // Kiểm tra user tồn tại
        $toUser = User::find($toUserId);
        if (!$toUser) {
            return $this->fail([
                'message' => 'Người nhận không tồn tại',
                'errors' => ['to_user_id' => ['User ID không hợp lệ']]
            ], 422);
        }
        
        // Không cho phép tự nhắn tin cho chính mình
        if ($toUserId == $user->id) {
            return $this->fail([
                'message' => 'Không thể gửi tin nhắn cho chính mình',
            ], 422);
        }
        
        // Kiểm tra listing nếu có
        if ($listingId) {
            $listing = \App\Models\Listing::find($listingId);
            if (!$listing) {
                $listingId = null; // Bỏ qua nếu listing không tồn tại
            }
        }

        $message = ChatMessage::create([
            'from_user_id' => $user->id,
            'to_user_id'   => $toUserId,
            'listing_id'   => $listingId,
            'body'         => trim($body),
            'is_read'      => false,
        ]);

        return $this->created($message->load([
            'fromUser:id,full_name,email,avatar_url',
            'toUser:id,full_name,email,avatar_url'
        ]));
    }

    /**
     * PUT /api/chat/messages/{user_id}/read
     * Đánh dấu tất cả tin nhắn từ user này là đã đọc
     */
    public function markAsRead(Request $request, int $userId)
    {
        $user = $request->user();
        
        ChatMessage::where('from_user_id', $userId)
            ->where('to_user_id', $user->id)
            ->update(['is_read' => true]);

        return $this->ok(['message' => 'Đã đánh dấu đã đọc']);
    }
}
