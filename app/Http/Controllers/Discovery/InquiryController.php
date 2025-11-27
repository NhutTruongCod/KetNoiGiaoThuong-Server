<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Inquiry;

/**
 * BA 3.4 — API Inquiry (Yêu cầu liên hệ)
 *
 * - POST /api/inquiries
 */
class InquiryController extends BaseApiController
{
    public function store(Request $request)
    {
        $v = $request->validate([
            'listing_id' => 'required|integer',
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
}
