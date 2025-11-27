<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Auction;
use App\Models\AuctionBid;

/**
 * BA 3.6 — API Auctions
 *
 * - GET  /api/auctions?status=active|upcoming|ended
 * - GET  /api/auctions/{id}
 * - POST /api/auctions/{id}/bids    (auth:sanctum)
 */
class AuctionController extends BaseApiController
{
    public function index(Request $request)
    {
        $v = $request->validate([
            'status'   => 'nullable|in:active,upcoming,ended',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $now = now();
        $q = Auction::query()->with('listing');

        $status = $v['status'] ?? 'active';
        if ($status === 'active') {
            $q->where('starts_at', '<=', $now)->where('ends_at', '>=', $now);
        } elseif ($status === 'upcoming') {
            $q->where('starts_at', '>', $now);
        } elseif ($status === 'ended') {
            $q->where('ends_at', '<', $now);
        }

        $items = $q->orderBy('starts_at', 'desc')
            ->paginate($v['per_page'] ?? 20);

        return $this->paginate($items);
    }

    public function show(int $id)
    {
        $auction = Auction::with(['listing', 'bids.user'])
            ->findOrFail($id);

        return $this->ok($auction);
    }

    public function bid(Request $request, int $id)
    {
        $user = $request->user();
        $auction = Auction::findOrFail($id);

        $v = $request->validate([
            'amount_cents' => 'required|integer|min:1',
        ]);

        if (!$auction->isActive()) {
            return $this->fail(['message' => 'Phiên đấu giá đã kết thúc hoặc chưa bắt đầu'], 422);
        }

        if ($v['amount_cents'] <= $auction->current_price_cents) {
            return $this->fail(['message' => 'Giá bid phải lớn hơn giá hiện tại'], 422);
        }

        $bid = AuctionBid::create([
            'auction_id'   => $auction->id,
            'user_id'      => $user->id,
            'amount_cents' => $v['amount_cents'],
        ]);

        $auction->current_price_cents = $v['amount_cents'];
        $auction->save();

        return $this->created($bid);
    }
}
