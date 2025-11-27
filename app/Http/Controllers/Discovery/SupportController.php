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

    public function myTickets(Request $request)
    {
        $user = $request->user();

        $tickets = SupportTicket::with('latestMessage')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 20));

        return $this->paginate($tickets);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $ticket = SupportTicket::with('messages')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return $this->ok($ticket);
    }

    public function reply(Request $request, int $id)
    {
        $user = $request->user();
        $v = $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        $ticket = SupportTicket::where('user_id', $user->id)->findOrFail($id);

        $msg = SupportMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'user',
            'body'        => $v['message'],
        ]);

        $ticket->touch(); // update updated_at

        return $this->created($msg);
    }
}
