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
 * - GET  /api/chat/messages?with_user_id=xx[&listing_id=yy]
 * - POST /api/chat/messages
 */
class ChatController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $v = $request->validate([
            'with_user_id' => 'required|integer|different:me',
            'listing_id'   => 'nullable|integer',
            'per_page'     => 'nullable|integer|min:1|max:100',
        ]);

        $otherId  = $v['with_user_id'];
        $perPage  = $v['per_page'] ?? 50;

        $q = ChatMessage::query()
            ->where(function ($q) use ($user, $otherId) {
                $q->where('from_user_id', $user->id)
                  ->where('to_user_id', $otherId);
            })->orWhere(function ($q) use ($user, $otherId) {
                $q->where('from_user_id', $otherId)
                  ->where('to_user_id', $user->id);
            });

        if (!empty($v['listing_id'])) {
            $q->where('listing_id', $v['listing_id']);
        }

        $messages = $q->orderBy('created_at', 'asc')->paginate($perPage);

        return $this->paginate($messages);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $v = $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'listing_id' => 'nullable|integer',
            'body'       => 'required|string|max:2000',
        ]);

        $message = ChatMessage::create([
            'from_user_id' => $user->id,
            'to_user_id'   => $v['to_user_id'],
            'listing_id'   => $v['listing_id'] ?? null,
            'body'         => $v['body'],
        ]);

        return $this->created($message);
    }
}
