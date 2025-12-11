<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Inquiry;

/**
 * BA 3.4 — API Inquiry (Yêu cầu liên hệ)
 *
 * - POST /api/inquiries
 * - GET  /api/inquiries (auth - chủ tin)
 */
class InquiryController extends BaseApiController
{
    /**
     * POST /api/inquiries
     * Gửi yêu cầu liên hệ (không cần đăng nhập)
     */
    public function store(Request $request)
    {
        $v = $request->validate([
            'listing_id' => 'required|integer|exists:listings,id',
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'message'    => 'required|string|max:2000',
        ]);

        $inquiry = Inquiry::create([
            'listing_id' => $v['listing_id'],
            'name'       => $v['name'],
            'email'      => $v['email'] ?? null,
            'phone'      => $v['phone'] ?? null,
            'message'    => $v['message'],
            'source_ip'  => $request->ip(),
        ]);

        return $this->created($inquiry);
    }

    /**
     * GET /api/inquiries
     * Danh sách yêu cầu liên hệ (chủ tin đăng)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $listingId = $request->input('listing_id');

        $query = Inquiry::query()->with('listing');

        // Chỉ lấy inquiries của listings thuộc user
        $query->whereHas('listing', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        if ($listingId) {
            $query->where('listing_id', $listingId);
        }

        $inquiries = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->paginate($inquiries);
    }
}
