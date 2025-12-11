<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Listing;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Auction Controller - Hệ thống đấu giá
 * 
 * Nghiệp vụ 3.6: Hệ thống Đấu giá Sản phẩm/Dịch vụ
 * - Người bán đăng sản phẩm đấu giá với mức giá khởi điểm
 * - Người mua tham gia đặt giá thầu trực tuyến
 * - Hệ thống tự động cập nhật giá thầu cao nhất
 * - Thông báo người chiến thắng khi kết thúc phiên đấu giá
 */
class AuctionController extends BaseApiController
{
    /**
     * GET /api/auctions
     * Danh sách đấu giá
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:upcoming,active,ended,cancelled',
                'category_id' => 'nullable|integer|exists:categories,id',
                'shop_id' => 'nullable|integer|exists:shops,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'sort' => 'nullable|in:ending_soon,most_bids,highest_price,newest',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 400);
            }

            $query = Auction::with(['listing', 'shop']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                // Default: show active auctions
                $query->active();
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->whereHas('listing', function($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            // Filter by shop
            if ($request->has('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }

            // Filter by price range
            if ($request->has('min_price')) {
                $query->where('current_price_cents', '>=', $request->min_price * 100);
            }

            if ($request->has('max_price')) {
                $query->where('current_price_cents', '<=', $request->max_price * 100);
            }

            // Sorting
            switch ($request->get('sort', 'ending_soon')) {
                case 'ending_soon':
                    $query->orderBy('ends_at', 'asc');
                    break;
                case 'most_bids':
                    $query->orderBy('total_bids', 'desc');
                    break;
                case 'highest_price':
                    $query->orderBy('current_price_cents', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $perPage = $request->get('per_page', 20);
            $auctions = $query->paginate($perPage);

            return $this->paginate($auctions);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/auctions/{auction}
     * Chi tiết đấu giá
     */
    public function show(Auction $auction)
    {
        try {
            $auction->load([
                'listing',
                'shop',
                'bids' => function($query) {
                    $query->with('user:id,full_name')->orderBy('amount_cents', 'desc')->limit(10);
                },
                'createdBy:id,full_name',
                'winner:id,full_name'
            ]);

            // Add computed fields
            $auction->highest_bidder = $auction->highestBid ? $auction->highestBid->user : null;
            $auction->minimum_bid = $auction->getMinimumBid();
            $auction->has_reached_reserve = $auction->hasReachedReservePrice();

            return $this->ok($auction);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/auctions
     * Tạo phiên đấu giá (chỉ seller)
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ seller và admin
            if (!$user->canCreateListing()) {
                return $this->fail(['message' => 'Chỉ người bán (seller) mới có quyền tạo đấu giá'], 403);
            }

            $validator = Validator::make($request->all(), [
                'listing_id' => 'required|integer|exists:listings,id',
                'starting_price' => 'required|numeric|min:0',
                'reserve_price' => 'nullable|numeric|min:0',
                'bid_increment' => 'required|numeric|min:1000',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'auto_extend' => 'nullable|boolean',
                'extend_minutes' => 'nullable|integer|min:1|max:60',
                'max_bids_per_user' => 'nullable|integer|min:0',
            ], [
                'listing_id.required' => 'Vui lòng chọn sản phẩm đấu giá',
                'listing_id.exists' => 'Sản phẩm không tồn tại',
                'starting_price.required' => 'Vui lòng nhập giá khởi điểm',
                'starting_price.min' => 'Giá khởi điểm phải >= 0',
                'bid_increment.required' => 'Vui lòng chọn bước giá',
                'bid_increment.min' => 'Bước giá phải >= 1,000 VND',
                'start_time.required' => 'Vui lòng chọn thời gian bắt đầu',
                'start_time.date' => 'Thời gian bắt đầu không hợp lệ',
                'end_time.required' => 'Vui lòng chọn thời gian kết thúc',
                'end_time.date' => 'Thời gian kết thúc không hợp lệ',
                'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu',
            ]);

            if ($validator->fails()) {
                return $this->fail([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Kiểm tra start_time phải trong tương lai (cho phép 5 phút trước)
            $startTime = \Carbon\Carbon::parse($request->start_time);
            if ($startTime->lt(now()->subMinutes(5))) {
                return $this->fail([
                    'message' => 'Thời gian bắt đầu phải trong tương lai',
                    'errors' => ['start_time' => ['Thời gian bắt đầu phải trong tương lai']]
                ], 422);
            }

            // Kiểm tra listing thuộc về user
            $listing = Listing::findOrFail($request->listing_id);
            if ($listing->user_id !== $user->id && !$user->isAdmin()) {
                return $this->fail(['message' => 'Bạn không có quyền tạo đấu giá cho tin đăng này'], 403);
            }

            // Kiểm tra listing đã có auction active chưa
            if ($listing->auction()->where('status', '!=', 'ended')->where('status', '!=', 'cancelled')->exists()) {
                return $this->fail(['message' => 'Tin đăng này đã có phiên đấu giá đang hoạt động'], 422);
            }

            // Validate reserve price >= starting price
            if ($request->has('reserve_price') && $request->reserve_price < $request->starting_price) {
                return $this->fail(['message' => 'Giá dự trữ phải lớn hơn hoặc bằng giá khởi điểm'], 422);
            }

            DB::beginTransaction();

            $auction = Auction::create([
                'listing_id' => $request->listing_id,
                'shop_id' => $listing->shop_id,
                'starting_price_cents' => $request->starting_price * 100,
                'current_price_cents' => $request->starting_price * 100,
                'reserve_price_cents' => $request->has('reserve_price') ? $request->reserve_price * 100 : null,
                'bid_increment_cents' => $request->bid_increment * 100,
                'starts_at' => $request->start_time,
                'ends_at' => $request->end_time,
                'auto_extend' => $request->get('auto_extend', true),
                'extend_minutes' => $request->get('extend_minutes', 5),
                'max_bids_per_user' => $request->get('max_bids_per_user', 0),
                'status' => now() >= $request->start_time ? 'active' : 'upcoming',
                'created_by' => $user->id,
            ]);

            DB::commit();

            return $this->created($auction->load('listing'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/auctions/{auction}
     * Cập nhật đấu giá (chỉ khi chưa có bid)
     */
    public function update(Request $request, Auction $auction)
    {
        try {
            $user = $request->user();

            // Chỉ người tạo mới được cập nhật
            if ($auction->created_by !== $user->id && !$user->isAdmin()) {
                return $this->fail(['message' => 'Bạn không có quyền cập nhật phiên đấu giá này'], 403);
            }

            // Không cho cập nhật nếu đã có bid
            if (!$auction->canUpdate()) {
                return $this->fail(['message' => 'Không thể cập nhật phiên đấu giá đã có người đặt giá hoặc đã kết thúc'], 422);
            }

            $validator = Validator::make($request->all(), [
                'reserve_price' => 'nullable|numeric|min:0',
                'end_time' => 'nullable|date|after:start_time',
                'auto_extend' => 'nullable|boolean',
                'extend_minutes' => 'nullable|integer|min:1|max:60',
            ]);

            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 400);
            }

            $updateData = [];

            if ($request->has('reserve_price')) {
                $updateData['reserve_price_cents'] = $request->reserve_price * 100;
            }

            if ($request->has('end_time')) {
                $updateData['ends_at'] = $request->end_time;
            }

            if ($request->has('auto_extend')) {
                $updateData['auto_extend'] = $request->auto_extend;
            }

            if ($request->has('extend_minutes')) {
                $updateData['extend_minutes'] = $request->extend_minutes;
            }

            $auction->update($updateData);

            return $this->ok($auction->load('listing'));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/auctions/{auction}
     * Xóa đấu giá (chỉ khi chưa có bid)
     */
    public function destroy(Auction $auction)
    {
        try {
            $user = request()->user();

            if ($auction->created_by !== $user->id && !$user->isAdmin()) {
                return $this->fail(['message' => 'Bạn không có quyền xóa phiên đấu giá này'], 403);
            }

            // Chỉ xóa được nếu chưa có bid
            if ($auction->total_bids > 0) {
                return $this->fail(['message' => 'Không thể xóa phiên đấu giá đã có người đặt giá'], 422);
            }

            $auction->delete();

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/auctions/{auction}/bids
     * Đặt giá trong phiên đấu giá
     * 
     * @param amount - Số tiền VND (không phải cents)
     *                 VD: 100000000 = 100 triệu VND
     */
    public function placeBid(Request $request, Auction $auction)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1000',
            ], [
                'amount.required' => 'Vui lòng nhập số tiền đặt giá',
                'amount.numeric' => 'Số tiền phải là số',
                'amount.min' => 'Số tiền tối thiểu là 1,000 VND',
            ]);

            if ($validator->fails()) {
                return $this->fail([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // amount là VND, chuyển sang cents
            $amountCents = (int) round($request->amount * 100);

            // Debug info
            \Log::info('PlaceBid Debug', [
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'input_amount' => $request->amount,
                'amount_cents' => $amountCents,
                'current_price_cents' => $auction->current_price_cents,
                'bid_increment_cents' => $auction->bid_increment_cents,
                'minimum_bid_cents' => $auction->getMinimumBid(),
            ]);

            // Kiểm tra không cho seller tự đặt giá auction mình
            if ($auction->created_by === $user->id) {
                return $this->fail([
                    'message' => 'Bạn không thể đặt giá cho phiên đấu giá của chính mình'
                ], 403);
            }

            // Kiểm tra trạng thái
            if (!$auction->canBid()) {
                return $this->fail([
                    'message' => 'Phiên đấu giá không hoạt động hoặc đã kết thúc',
                    'status' => $auction->status,
                    'starts_at' => $auction->starts_at,
                    'ends_at' => $auction->ends_at,
                ], 422);
            }

            // Kiểm tra giá tối thiểu
            $minimumBid = $auction->getMinimumBid();
            if ($amountCents < $minimumBid) {
                return $this->fail([
                    'message' => 'Giá đặt phải ít nhất ' . number_format($minimumBid / 100, 0, ',', '.') . ' VND',
                    'your_bid' => $request->amount,
                    'your_bid_cents' => $amountCents,
                    'current_price' => $auction->current_price_cents / 100,
                    'bid_increment' => $auction->bid_increment_cents / 100,
                    'minimum_bid' => $minimumBid / 100,
                    'hint' => 'Gửi amount là số VND (không phải cents). VD: 100000000 = 100 triệu VND'
                ], 422);
            }

            // Kiểm tra max bids per user
            if ($auction->max_bids_per_user > 0) {
                $userBidsCount = AuctionBid::where('auction_id', $auction->id)
                    ->where('user_id', $user->id)
                    ->count();

                if ($userBidsCount >= $auction->max_bids_per_user) {
                    return $this->fail([
                        'message' => 'Bạn đã đạt giới hạn số lần đặt giá cho phiên đấu giá này',
                        'max_bids' => $auction->max_bids_per_user,
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Tạo bid mới
            $bid = AuctionBid::create([
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'amount_cents' => $amountCents,
                'is_winning' => true,
                'is_auto_bid' => false,
            ]);

            // Mark bid as winning
            $bid->markAsWinning();

            // Update auction current price
            $auction->updateCurrentPrice($amountCents);

            // Check and extend if needed
            $extended = $auction->extendIfNeeded($bid);

            // Send notifications (wrap in try-catch to not fail the bid)
            try {
                // Load listing relationship if not loaded
                if (!$auction->relationLoaded('listing')) {
                    $auction->load('listing');
                }
                
                $listingTitle = $auction->listing ? $auction->listing->title : 'Sản phẩm đấu giá';
                
                // 1. Notify previous highest bidder (if exists)
                $previousHighestBid = AuctionBid::where('auction_id', $auction->id)
                    ->where('user_id', '!=', $user->id)
                    ->where('is_winning', false)
                    ->orderBy('amount_cents', 'desc')
                    ->first();

                if ($previousHighestBid) {
                    Notification::create([
                        'user_id' => $previousHighestBid->user_id,
                        'title' => 'Bạn đã bị vượt giá',
                        'message' => "Giá đặt của bạn trong phiên đấu giá \"{$listingTitle}\" đã bị vượt qua.",
                        'type' => 'auction',
                    ]);
                }

                // 2. Notify seller
                Notification::create([
                    'user_id' => $auction->created_by,
                    'title' => 'Có giá mới trong đấu giá',
                    'message' => "Có người đặt giá " . number_format($amountCents / 100, 0, ',', '.') . " VND cho phiên đấu giá \"{$listingTitle}\".",
                    'type' => 'auction',
                ]);
            } catch (\Exception $notifyError) {
                // Log notification error but don't fail the bid
                \Log::warning('Failed to send auction notification', [
                    'auction_id' => $auction->id,
                    'error' => $notifyError->getMessage()
                ]);
            }

            DB::commit();

            return $this->created([
                'bid' => $bid->load('user'),
                'auction' => [
                    'current_price' => $auction->current_price_cents / 100,
                    'total_bids' => $auction->total_bids,
                    'time_remaining' => $auction->time_remaining,
                    'extended' => $extended,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/auctions/{auction}/bids
     * Danh sách giá đã đặt
     */
    public function getBids(Auction $auction)
    {
        try {
            $bids = $auction->bids()
                ->with('user:id,full_name')
                ->orderBy('amount_cents', 'desc')
                ->paginate(20);

            $meta = [
                'total_bids' => $auction->total_bids,
                'highest_bid' => $auction->current_price_cents / 100,
                'lowest_bid' => $auction->starting_price_cents / 100,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $bids->items(),
                'meta' => array_merge([
                    'current_page' => $bids->currentPage(),
                    'per_page' => $bids->perPage(),
                    'total' => $bids->total(),
                    'last_page' => $bids->lastPage(),
                ], $meta),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/auctions/my-bids
     * Xem các phiên đấu giá mình đã tham gia
     */
    public function myBids(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:active,ended',
                'is_winning' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->fail(['errors' => $validator->errors()], 400);
            }

            // Get auctions where user has placed bids
            $query = Auction::whereHas('bids', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with(['listing', 'shop']);

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } else {
                    $query->ended();
                }
            }

            // Filter by winning status
            if ($request->has('is_winning')) {
                $query->whereHas('bids', function($q) use ($user, $request) {
                    $q->where('user_id', $user->id)
                      ->where('is_winning', $request->is_winning);
                });
            }

            $perPage = $request->get('per_page', 20);
            $auctions = $query->orderBy('ends_at', 'desc')->paginate($perPage);

            // Add user's bid info to each auction
            $auctions->getCollection()->transform(function($auction) use ($user) {
                $userBids = $auction->bids()->where('user_id', $user->id)->get();
                $auction->my_highest_bid = $userBids->max('amount_cents') / 100;
                $auction->is_winning = $userBids->where('is_winning', true)->count() > 0;
                $auction->total_my_bids = $userBids->count();
                $auction->last_bid_at = $userBids->max('created_at');
                return $auction;
            });

            return $this->paginate($auctions);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }
}
