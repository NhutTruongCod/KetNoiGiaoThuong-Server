<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Listing;
use App\Models\Shop;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\DepositRequest;
use App\Models\WithdrawRequest;
use App\Models\AuctionPayment;
use App\Models\Auction;
use App\Models\ModerationReport;
use App\Models\Review;
use App\Models\LoginHistory;
use App\Models\PlatformRevenue;
use Illuminate\Support\Facades\DB;

class AdminController extends BaseApiController
{
    public function dashboard(Request $request)
    {
        try {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();
            
            // Tính doanh thu platform
            $monthRevenue = PlatformRevenue::where('created_at', '>=', $thisMonth)->sum('amount');
            $monthVat = PlatformRevenue::where('created_at', '>=', $thisMonth)->sum('vat_amount');
            $monthNetRevenue = PlatformRevenue::where('created_at', '>=', $thisMonth)->sum('net_amount');
            
            // Phí từ các nguồn trong tháng
            $withdrawFees = PlatformRevenue::where('created_at', '>=', $thisMonth)
                ->where('type', 'withdraw_fee')->sum('amount');
            $auctionFees = PlatformRevenue::where('created_at', '>=', $thisMonth)
                ->where('type', 'auction_fee')->sum('amount');
            $subscriptionFees = PlatformRevenue::where('created_at', '>=', $thisMonth)
                ->where('type', 'subscription_fee')->sum('amount');
            $promotionFees = Promotion::where('created_at', '>=', $thisMonth)
                ->where('status', 'active')->sum('spent');
            
            return $this->ok([
                'users' => [
                    'total' => User::count(),
                    'new_today' => User::where('created_at', '>=', $today)->count(),
                    'new_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                ],
                'listings' => [
                    'total' => Listing::count(),
                    'active' => Listing::where('status', 'active')->count(),
                    'pending' => Listing::where('status', 'pending')->count(),
                ],
                'shops' => [
                    'total' => Shop::count(),
                    'verified' => Shop::where('is_verified', true)->count(),
                ],
                'orders' => [
                    'total' => Order::count(),
                    'pending' => Order::where('status', 'pending')->count(),
                ],
                // Doanh thu platform (KHÔNG phải tổng số dư ví của users)
                'platform_revenue' => [
                    'month_total' => $monthRevenue,
                    'month_vat' => $monthVat,
                    'month_net' => $monthNetRevenue,
                    'all_time' => PlatformRevenue::sum('amount'),
                    'breakdown' => [
                        'withdraw_fees' => $withdrawFees,
                        'auction_fees' => $auctionFees,
                        'subscription_fees' => $subscriptionFees,
                        'promotion_fees' => $promotionFees,
                    ],
                ],
                // Thống kê ví (chỉ để quản lý, không phải doanh thu)
                'wallet_stats' => [
                    'pending_deposits' => DepositRequest::whereIn('status', ['pending', 'processing'])->count(),
                    'pending_withdraws' => WithdrawRequest::where('status', 'pending')->count(),
                ],
                'reports' => [
                    'total' => ModerationReport::count(),
                    'pending' => ModerationReport::where('status', 'pending')->count(),
                ],
                'auctions' => [
                    'total' => Auction::count(),
                    'active' => Auction::where('status', 'active')->where('ends_at', '>', now())->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function users(Request $request)
    {
        try {
            $query = User::query();
            
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function userDetail(User $user)
    {
        try {
            return $this->ok(['user' => $user->load(['wallet', 'shop'])]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateUserStatus(Request $request, User $user)
    {
        try {
            $user->update(['status' => $request->status]);
            return $this->ok(['message' => 'Cap nhat thanh cong', 'user' => $user->fresh()]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
    public function listings(Request $request)
    {
        try {
            $query = Listing::with(['shop:id,name', 'user:id,full_name']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $query->where('title', 'like', "%{$request->search}%");
            }
            
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateListingStatus(Request $request, Listing $listing)
    {
        try {
            $listing->update(['status' => $request->status]);
            return $this->ok(['message' => 'Cap nhat thanh cong', 'listing' => $listing->fresh()]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function promotions(Request $request)
    {
        try {
            $query = Promotion::with(['listing:id,title,images', 'shop:id,name,owner_user_id']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('listing', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            }
            
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function promotionStats(Request $request)
    {
        try {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();
            
            // Thống kê theo status
            $byStatus = [
                'total' => Promotion::count(),
                'active' => Promotion::where('status', 'active')->count(),
                'pending' => Promotion::where('status', 'pending')->count(),
                'completed' => Promotion::where('status', 'completed')->count(),
                'cancelled' => Promotion::where('status', 'cancelled')->count(),
            ];
            
            // Thống kê theo type
            $byType = Promotion::select('type')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(budget) as total_budget')
                ->selectRaw('SUM(spent) as total_spent')
                ->selectRaw('SUM(impressions) as total_impressions')
                ->selectRaw('SUM(clicks) as total_clicks')
                ->groupBy('type')
                ->get();
            
            // Tổng doanh thu từ quảng cáo
            $totalRevenue = Promotion::sum('spent');
            $monthRevenue = Promotion::where('created_at', '>=', $thisMonth)->sum('spent');
            
            // Performance metrics
            $totalImpressions = Promotion::sum('impressions');
            $totalClicks = Promotion::sum('clicks');
            $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
            
            // Top performing promotions
            $topPromotions = Promotion::with(['listing:id,title'])
                ->where('status', 'active')
                ->orderBy('clicks', 'desc')
                ->limit(5)
                ->get(['id', 'listing_id', 'type', 'impressions', 'clicks', 'spent', 'ctr']);
            
            return $this->ok([
                'by_status' => $byStatus,
                'by_type' => $byType,
                'revenue' => [
                    'total' => $totalRevenue,
                    'this_month' => $monthRevenue,
                ],
                'performance' => [
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'avg_ctr' => round($avgCtr, 2),
                ],
                'top_promotions' => $topPromotions,
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function transactions(Request $request)
    {
        try {
            $query = WalletTransaction::with(['user:id,full_name,email']);
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
            }
            
            $query->orderBy('created_at', 'desc');
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function transactionStats(Request $request)
    {
        try {
            $thisMonth = now()->startOfMonth();
            $transactions = WalletTransaction::where('created_at', '>=', $thisMonth)->where('status', 'completed')->get();
            
            return $this->ok([
                'total_transactions' => WalletTransaction::count(),
                'month_transactions' => $transactions->count(),
                'total_deposit' => $transactions->where('type', 'deposit')->sum('amount'),
                'total_withdraw' => $transactions->where('type', 'withdraw')->sum('amount'),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
    public function reports(Request $request)
    {
        try {
            $query = ModerationReport::with(['reporter:id,full_name,email']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }
            
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function reportStats(Request $request)
    {
        try {
            return $this->ok([
                'total' => ModerationReport::count(),
                'pending' => ModerationReport::where('status', 'pending')->count(),
                'resolved' => ModerationReport::where('status', 'resolved')->count(),
                'dismissed' => ModerationReport::where('status', 'dismissed')->count(),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function resolveReport(Request $request, ModerationReport $report)
    {
        try {
            $report->update([
                'status' => $request->status,
                'resolution_note' => $request->resolution_note,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);
            return $this->ok(['message' => 'Da xu ly bao cao', 'report' => $report->fresh()]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/orders
     * Quản lý đơn hàng
     */
    public function orders(Request $request)
    {
        try {
            $query = Order::with([
                'buyer:id,full_name,email',
                'seller:id,full_name,email',
                'shop:id,name',
                'listing:id,title,images'
            ]);
            
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->has('payment_status') && $request->payment_status !== 'all') {
                $query->where('payment_status', $request->payment_status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('buyer', function($q2) use ($search) {
                          $q2->where('full_name', 'like', "%{$search}%");
                      });
                });
            }
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
            }
            
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/orders/stats
     * Thống kê đơn hàng
     */
    public function orderStats(Request $request)
    {
        try {
            $thisMonth = now()->startOfMonth();
            
            return $this->ok([
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'confirmed' => Order::where('status', 'confirmed')->count(),
                'processing' => Order::where('status', 'processing')->count(),
                'shipped' => Order::where('status', 'shipped')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
                'refund_requested' => Order::whereNotNull('refund_requested_at')->whereNull('refund_processed_at')->count(),
                'month_orders' => Order::where('created_at', '>=', $thisMonth)->count(),
                'month_revenue' => Order::where('created_at', '>=', $thisMonth)
                    ->where('payment_status', 'paid')
                    ->sum('final_amount'),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function auctions(Request $request)
    {
        try {
            $query = Auction::with(['listing:id,title', 'createdBy:id,full_name', 'winner:id,full_name']);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function auctionPayments(Request $request)
    {
        try {
            $query = AuctionPayment::with(['auction.listing:id,title', 'winner:id,full_name', 'seller:id,full_name']);
            
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            $query->orderBy('created_at', 'desc');
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/auctions/stats
     * Thống kê đấu giá
     */
    public function auctionStats(Request $request)
    {
        try {
            $thisMonth = now()->startOfMonth();
            
            return $this->ok([
                'total' => Auction::count(),
                'upcoming' => Auction::where('status', 'upcoming')->count(),
                'active' => Auction::where('status', 'active')->count(),
                'ended' => Auction::where('status', 'ended')->count(),
                'cancelled' => Auction::where('status', 'cancelled')->count(),
                'with_winner' => Auction::whereNotNull('winner_id')->count(),
                'payments' => [
                    'total' => AuctionPayment::count(),
                    'pending' => AuctionPayment::where('status', 'pending')->count(),
                    'paid' => AuctionPayment::where('status', 'paid')->count(),
                    'transferred' => AuctionPayment::where('status', 'transferred')->count(),
                    'expired' => AuctionPayment::where('status', 'expired')->count(),
                ],
                'month_auctions' => Auction::where('created_at', '>=', $thisMonth)->count(),
                'total_bids' => Auction::sum('total_bids'),
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function shops(Request $request)
    {
        try {
            $query = Shop::with(['user:id,full_name,email'])->withCount('listings');
            
            if ($request->has('is_verified')) {
                $query->where('is_verified', $request->is_verified === 'true');
            }
            if ($request->has('search')) {
                $query->where('name', 'like', "%{$request->search}%");
            }
            
            $query->orderBy('created_at', 'desc');
            
            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    public function verifyShop(Request $request, Shop $shop)
    {
        try {
            $shop->update(['is_verified' => true]);
            return $this->ok(['message' => 'Da xac minh shop', 'shop' => $shop->fresh()]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/revenue
     * Xem chi tiết doanh thu platform
     */
    public function revenue(Request $request)
    {
        try {
            $query = PlatformRevenue::with(['user:id,full_name,email']);

            // Filter by type
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
            }

            $query->orderBy('created_at', 'desc');

            return $this->paginate($query->paginate($request->get('per_page', 20)));
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/revenue/stats
     * Thống kê doanh thu platform
     */
    public function revenueStats(Request $request)
    {
        try {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();

            // Doanh thu theo thời gian
            $todayRevenue = PlatformRevenue::where('created_at', '>=', $today)->sum('amount');
            $weekRevenue = PlatformRevenue::where('created_at', '>=', $thisWeek)->sum('amount');
            $monthRevenue = PlatformRevenue::where('created_at', '>=', $thisMonth)->sum('amount');
            $lastMonthRevenue = PlatformRevenue::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->sum('amount');
            $allTimeRevenue = PlatformRevenue::sum('amount');

            // Doanh thu theo loại
            $byType = PlatformRevenue::select('type')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(vat_amount) as total_vat')
                ->selectRaw('SUM(net_amount) as total_net')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('type')
                ->get();

            // Doanh thu 7 ngày gần nhất
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayRevenue = PlatformRevenue::whereDate('created_at', $date)->sum('amount');
                $last7Days[] = [
                    'date' => $date,
                    'amount' => $dayRevenue,
                ];
            }

            // Doanh thu 12 tháng gần nhất
            $last12Months = [];
            for ($i = 11; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthEnd = now()->subMonths($i)->endOfMonth();
                $monthLabel = $monthStart->format('Y-m');
                $monthAmount = PlatformRevenue::whereBetween('created_at', [$monthStart, $monthEnd])->sum('amount');
                $last12Months[] = [
                    'month' => $monthLabel,
                    'amount' => $monthAmount,
                ];
            }

            return $this->ok([
                'summary' => [
                    'today' => $todayRevenue,
                    'this_week' => $weekRevenue,
                    'this_month' => $monthRevenue,
                    'last_month' => $lastMonthRevenue,
                    'all_time' => $allTimeRevenue,
                    'growth_percent' => $lastMonthRevenue > 0
                        ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
                        : 0,
                ],
                'by_type' => $byType,
                'chart_data' => [
                    'last_7_days' => $last7Days,
                    'last_12_months' => $last12Months,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->fail(['message' => 'Loi server', 'error' => $e->getMessage()], 500);
        }
    }
}