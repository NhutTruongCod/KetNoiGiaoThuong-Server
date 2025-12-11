<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\SupportTicket;
use App\Models\SupportMessage;

/**
 * BA 3.8 — Tickets / FAQ / Chatbot đơn giản
 *
 * - GET  /api/support/faqs
 * - POST /api/support/tickets
 * - GET  /api/support/tickets         (auth)
 * - GET  /api/support/tickets/{id}    (auth)
 * - POST /api/support/tickets/{id}/reply (auth)
 */
class SupportController extends BaseApiController
{
    public function faqs(Request $request)
    {
        $faqs = Faq::query()
            ->where('is_public', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->ok($faqs);
    }

    public function createTicket(Request $request)
    {
        $user = $request->user();
        $v = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
            'channel' => 'nullable|string|max:50', // web/app/email
        ]);

        $ticket = SupportTicket::create([
            'user_id'   => $user?->id,
            'subject'   => $v['subject'],
            'status'    => 'open',
            'priority'  => 'normal',
            'channel'   => $v['channel'] ?? 'web',
        ]);

        SupportMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'user',
            'body'        => $v['message'],
        ]);

        return $this->created($ticket->load('messages'));
    }

    /**
     * GET /api/support/tickets
     */
    public function tickets(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status');

        $query = SupportTicket::with('messages')
            ->where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        $tickets = $query->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 20));

        return $this->paginate($tickets);
    }

    /**
     * GET /api/support/tickets/{ticket}
     */
    public function showTicket(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();

        if ($ticket->user_id !== $user->id) {
            return $this->fail(['message' => 'Không có quyền truy cập'], 403);
        }

        $ticket->load('messages');

        return $this->ok($ticket);
    }

    /**
     * POST /api/support/tickets/{ticket}/messages
     */
    public function replyTicket(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();
        
        if ($ticket->user_id !== $user->id) {
            return $this->fail(['message' => 'Không có quyền truy cập'], 403);
        }

        $v = $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        $msg = SupportMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'user',
            'body'        => $v['message'],
        ]);

        $ticket->touch(); // update updated_at

        return $this->created($msg);
    }

    /**
     * PUT /api/support/tickets/{ticket}/close
     */
    public function closeTicket(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();
        
        if ($ticket->user_id !== $user->id) {
            return $this->fail(['message' => 'Không có quyền truy cập'], 403);
        }

        $ticket->update(['status' => 'closed']);

        return $this->ok($ticket);
    }
}
