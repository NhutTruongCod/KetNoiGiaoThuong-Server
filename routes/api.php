<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Events\UserRegistered;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Tracking\TrackController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\AdminIdentityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\WalletController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// User Profile & Avatar Routes
Route::prefix('user')->middleware('auth:api')->group(function () {
    Route::get('profile', [UserProfileController::class, 'getProfile']);
    Route::put('profile', [UserProfileController::class, 'updateProfile']);
    Route::post('avatar', [UserProfileController::class, 'uploadAvatar']);
    Route::post('avatar/base64', [UserProfileController::class, 'uploadAvatarBase64']); // Easier for frontend
    Route::delete('avatar', [UserProfileController::class, 'deleteAvatar']);
});


// Public subscription plans
Route::get('plans', [PlanController::class, 'index']);
Route::get('plans/{id}', [PlanController::class, 'show'])->whereNumber('id');

Route::prefix('auth')->group(function () {
    // Rate limit: 5 attempts per minute for sensitive endpoints
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('resend-verification-otp', [AuthController::class, 'resendVerificationOtp']);
    });

    // No rate limit for refresh & logout
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

// Login history - user
Route::middleware('auth:api')->get('login-history', [LoginHistoryController::class, 'myHistory']);

// Login history - admin
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    Route::get('login-history', [LoginHistoryController::class, 'adminIndex']);
    Route::get('users/{userId}/login-history', [LoginHistoryController::class, 'adminUserHistory']);
});

// Notifications (require auth)
Route::prefix('notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('{id}', [NotificationController::class, 'show'])->whereNumber('id');
    Route::put('{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('delete-all', [NotificationController::class, 'destroyAll']);
    Route::delete('{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
});

// Subscriptions
Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']); // Public - xem danh sách gói

Route::prefix('subscriptions')->middleware('auth:api')->group(function () {
    Route::post('/', [SubscriptionController::class, 'subscribe']);
    Route::put('{id}/renew', [SubscriptionController::class, 'renew'])->whereNumber('id');
    Route::post('{id}/confirm-transfer', [SubscriptionController::class, 'confirmTransfer'])->whereNumber('id');
    Route::get('current', [SubscriptionController::class, 'current']);
    Route::get('history', [SubscriptionController::class, 'history']);
    Route::delete('{id}/cancel', [SubscriptionController::class, 'cancel'])->whereNumber('id');
});

// Data export (require auth)
Route::prefix('data/export')->middleware('auth:api')->group(function () {
    Route::post('request', [DataExportController::class, 'requestExport']);
    Route::get('status/{id}', [DataExportController::class, 'status'])->whereNumber('id');
    Route::get('download/{id}', [DataExportController::class, 'download'])->whereNumber('id');
    Route::delete('cancel/{id}', [DataExportController::class, 'cancel'])->whereNumber('id');
    Route::get('history', [DataExportController::class, 'history']);
});

// Identity Routes (require auth)
Route::prefix('identity')->middleware('auth:api')->group(function () {
    // User endpoints
    Route::get('profile', [IdentityController::class, 'getProfile']);
    Route::put('profile', [IdentityController::class, 'updateProfile']);
    Route::post('verify-request', [IdentityController::class, 'submitVerifyRequest']);
    Route::get('verify-history', [IdentityController::class, 'getVerifyHistory']);

    // Admin endpoints (require admin role)
    Route::middleware('admin')->group(function () {
        Route::get('verify-requests', [AdminIdentityController::class, 'getVerifyRequests']);
        Route::get('verify-requests/{id}', [AdminIdentityController::class, 'getVerifyRequest']);
        Route::put('verify-request/{id}/approve', [IdentityController::class, 'approveVerifyRequest']);
        Route::put('verify-request/{id}/reject', [IdentityController::class, 'rejectVerifyRequest']);
    });
});

// Moderation Routes (require auth)
Route::prefix('moderation')->middleware('auth:api')->group(function () {
    // User endpoints
    Route::post('report', [ModerationController::class, 'report']);
    Route::get('my-reports', [ModerationController::class, 'myReports']);

    // Admin endpoints (require admin role)
    Route::middleware('admin')->group(function () {
        Route::get('reports', [ModerationController::class, 'getReports']);
        Route::get('reports/{id}', [ModerationController::class, 'getReport']);
        Route::put('reports/{id}/resolve', [ModerationController::class, 'resolveReport']);
        Route::delete('reports/{id}', [ModerationController::class, 'deleteReport']);
    });
});
// Orders API - Quản lý đơn hàng (require auth)
Route::prefix('orders')->middleware('auth:api')->group(function () {
    // Đặt routes cụ thể TRƯỚC routes có parameter {id}
    Route::post('/preview', [OrderController::class, 'preview']);  // Preview checkout
    Route::get('/my-purchases', [OrderController::class, 'myPurchases']); // Đơn mình mua
    Route::get('/my-sales', [OrderController::class, 'mySales']);         // Đơn mình bán
    Route::get('/stats', [OrderController::class, 'stats']);              // Thống kê đơn hàng
    
    Route::get('/', [OrderController::class, 'index']);           // Danh sách đơn hàng
    Route::post('/', [OrderController::class, 'store']);          // Tạo đơn hàng mới
    Route::get('/{id}', [OrderController::class, 'show']);        // Chi tiết đơn hàng
    Route::post('/{id}/pay', [OrderController::class, 'payOrder']); // Thanh toán đơn hàng bằng ví
    Route::post('/{id}/confirm-received', [OrderController::class, 'confirmReceived']); // Xác nhận nhận hàng
    Route::post('/{id}/request-refund', [OrderController::class, 'requestRefund']); // Yêu cầu hoàn tiền
    Route::put('/{id}', [OrderController::class, 'update']);      // Cập nhật đơn hàng (seller)
    Route::delete('/{id}', [OrderController::class, 'destroy']);  // Hủy đơn hàng
});

// Admin - Xử lý hoàn tiền
Route::post('/admin/orders/{id}/process-refund', [OrderController::class, 'processRefund'])
    ->middleware(['auth:api', 'admin']);



// Reviews API - Hệ thống đánh giá
Route::prefix('reviews')->group(function () {
    // Public routes - không cần auth
    Route::get('/', [ReviewController::class, 'index']);           // Danh sách đánh giá (filter, sort, pagination)
    Route::get('/summary', [ReviewController::class, 'getSummary']); // Thống kê rating
    
    // Protected routes - cần auth (đặt TRƯỚC /{id} để tránh conflict)
    Route::middleware('auth:api')->group(function () {
        Route::get('/my-reviews', [ReviewController::class, 'myReviews']); // Đánh giá của tôi
        Route::post('/', [ReviewController::class, 'store']);          // Tạo đánh giá mới
        Route::put('/{id}', [ReviewController::class, 'update']);      // Cập nhật đánh giá
        Route::delete('/{id}', [ReviewController::class, 'destroy']);  // Xóa đánh giá
        
        // Mark as helpful
        Route::post('/{id}/helpful', [ReviewController::class, 'markAsHelpful']);
        Route::delete('/{id}/helpful', [ReviewController::class, 'unmarkAsHelpful']);
        
        // Seller reply
        Route::post('/{id}/reply', [ReviewController::class, 'addSellerReply']);
    });
    
    // Public - Chi tiết đánh giá (đặt SAU /my-reviews để tránh conflict)
    Route::get('/{id}', [ReviewController::class, 'show']);
});



// Payments API - Thanh toán
Route::prefix('payments')->group(function () {
    // Public routes - Callbacks từ payment gateways
    Route::post('/vnpay/callback', [PaymentController::class, 'vnpayCallback']);
    Route::post('/momo/callback', [PaymentController::class, 'momoCallback']);
    Route::post('/zalopay/callback', [PaymentController::class, 'zalopayCallback']);
    
    // Protected routes - Cần authentication
    Route::middleware('auth:api')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/my-payments', [PaymentController::class, 'myPayments']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::post('/{id}/refund', [PaymentController::class, 'refund']);
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
    });
});

// Shops API - Quản lý gian hàng
Route::prefix('shops')->group(function () {
    Route::get('/', [\App\Http\Controllers\ShopController::class, 'index']);
    Route::get('/{shop}', [\App\Http\Controllers\ShopController::class, 'show']);
    
    // Listings của shop (public)
    Route::get('/{shop}/listings', [\App\Http\Controllers\ShopController::class, 'listings']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [\App\Http\Controllers\ShopController::class, 'store']);
        Route::put('/{shop}', [\App\Http\Controllers\ShopController::class, 'update']);
        Route::delete('/{shop}', [\App\Http\Controllers\ShopController::class, 'destroy']);
    });
    
    // Categories của shop (nested routes)
    Route::prefix('{shop}/categories')->group(function () {
        // Public - xem categories của shop
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/simple-list', [CategoryController::class, 'simpleList']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        
        // Seller - tạo/sửa/xóa categories của shop mình
        Route::middleware('auth:api')->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{category}', [CategoryController::class, 'update']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
        });
    });
});

// My Shop - Seller lấy shop của mình
Route::middleware('auth:api')->get('my-shop', [\App\Http\Controllers\ShopController::class, 'myShop']);

// Global Categories - Xem tất cả categories từ mọi shops (cho trang chủ, search)
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'allCategories']); // Tất cả categories
    Route::get('/simple-list', [CategoryController::class, 'allCategoriesSimple']); // Dropdown tất cả
});

Route::prefix('listings')->group(function () {
    // Public routes
    Route::get('/', [ListingController::class, 'index']);      // Danh sách (có lọc, tìm kiếm, phân trang)
    
    // Protected routes - cần đăng nhập (đặt trước /{listing} để tránh conflict)
    Route::middleware('auth:api')->group(function () {
        Route::get('/my', [ListingController::class, 'myListings']); // Sản phẩm của seller
        Route::get('/subscription-benefits', [ListingController::class, 'checkSubscriptionBenefits']); // Kiểm tra quyền lợi gói tháng
        Route::post('/', [ListingController::class, 'store']);     // Thêm mới
        Route::put('/{listing}', [ListingController::class, 'update']); // Cập nhật
        Route::delete('/{listing}', [ListingController::class, 'destroy']); // Xóa
        
        // Upload & quản lý ảnh
        Route::post('/{listing}/images', [ListingController::class, 'uploadImages']); // Upload ảnh
        Route::delete('/{listing}/images/{imageId}', [ListingController::class, 'deleteImage']); // Xóa ảnh
        Route::put('/{listing}/images/reorder', [ListingController::class, 'reorderImages']); // Sắp xếp ảnh
    });
    
    // Public - xem chi tiết (đặt sau để không conflict với /my)
    Route::get('/{listing}', [ListingController::class, 'show']); // Xem chi tiết
});

Route::prefix('promotion')->middleware('auth:api')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);
    Route::get('/active', [PromotionController::class, 'activePromotions']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/{id}', [PromotionController::class, 'show']);
    Route::put('/{id}', [PromotionController::class, 'update']);
    Route::patch('/{id}/featured', [PromotionController::class, 'updateFeatured']);
    Route::delete('/{id}', [PromotionController::class, 'destroy']);
});

// Discovery & Social Features
Route::prefix('discovery')->group(function () {
    Route::get('search', [\App\Http\Controllers\Discovery\DiscoveryController::class, 'search']);
    Route::get('search-all', [\App\Http\Controllers\Discovery\DiscoveryController::class, 'searchAll']);
    Route::get('shops', [\App\Http\Controllers\Discovery\DiscoveryController::class, 'shops']);
});

// Bookmarks (require auth)
Route::prefix('bookmarks')->middleware('auth:api')->group(function () {
    Route::get('/', [\App\Http\Controllers\Discovery\BookmarkController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Discovery\BookmarkController::class, 'store']);
    Route::delete('/{listing_id}', [\App\Http\Controllers\Discovery\BookmarkController::class, 'destroy']);
});

// Listing Social Features (require auth)
Route::prefix('listings')->middleware('auth:api')->group(function () {
    Route::post('/{listing}/like', [\App\Http\Controllers\Discovery\SocialController::class, 'like']);
    Route::delete('/{listing}/like', [\App\Http\Controllers\Discovery\SocialController::class, 'unlike']);
    Route::post('/{listing}/comments', [\App\Http\Controllers\Discovery\SocialController::class, 'comment']);
    Route::get('/{listing}/comments', [\App\Http\Controllers\Discovery\SocialController::class, 'getComments']);
});

// Chat (require auth)
Route::prefix('chat')->middleware('auth:api')->group(function () {
    Route::get('conversations', [\App\Http\Controllers\Discovery\ChatController::class, 'conversations']);
    Route::get('messages/{user_id}', [\App\Http\Controllers\Discovery\ChatController::class, 'messages']);
    Route::post('messages', [\App\Http\Controllers\Discovery\ChatController::class, 'send']);
    Route::put('messages/{user_id}/read', [\App\Http\Controllers\Discovery\ChatController::class, 'markAsRead']);
});

// Inquiries
Route::post('inquiries', [\App\Http\Controllers\Discovery\InquiryController::class, 'store']);
Route::get('inquiries', [\App\Http\Controllers\Discovery\InquiryController::class, 'index'])->middleware('auth:api');

// Auctions
Route::prefix('auctions')->group(function () {
    // Public routes
    Route::get('/', [\App\Http\Controllers\Discovery\AuctionController::class, 'index']);
    
    // Protected routes - đặt my-bids TRƯỚC /{auction} để tránh conflict
    Route::middleware('auth:api')->group(function () {
        Route::get('/my-bids', [\App\Http\Controllers\Discovery\AuctionController::class, 'myBids']);
        Route::post('/', [\App\Http\Controllers\Discovery\AuctionController::class, 'store']);
        Route::put('/{auction}', [\App\Http\Controllers\Discovery\AuctionController::class, 'update']);
        Route::delete('/{auction}', [\App\Http\Controllers\Discovery\AuctionController::class, 'destroy']);
        Route::post('/{auction}/bids', [\App\Http\Controllers\Discovery\AuctionController::class, 'placeBid']);
        Route::get('/{auction}/bids', [\App\Http\Controllers\Discovery\AuctionController::class, 'getBids']);
    });
    
    // Public - xem chi tiết (đặt sau để không conflict với /my-bids)
    Route::get('/{auction}', [\App\Http\Controllers\Discovery\AuctionController::class, 'show']);
});

// Support & FAQ
Route::prefix('faqs')->group(function () {
    Route::get('/', [\App\Http\Controllers\Discovery\SupportController::class, 'faqs']);
});

Route::prefix('support')->middleware('auth:api')->group(function () {
    Route::get('tickets', [\App\Http\Controllers\Discovery\SupportController::class, 'tickets']);
    Route::post('tickets', [\App\Http\Controllers\Discovery\SupportController::class, 'createTicket']);
    Route::get('tickets/{ticket}', [\App\Http\Controllers\Discovery\SupportController::class, 'showTicket']);
    Route::post('tickets/{ticket}/messages', [\App\Http\Controllers\Discovery\SupportController::class, 'replyTicket']);
    Route::put('tickets/{ticket}/close', [\App\Http\Controllers\Discovery\SupportController::class, 'closeTicket']);
});

// Wallet API - Ví điện tử
Route::prefix('wallet')->middleware('auth:api')->group(function () {
    Route::get('/', [WalletController::class, 'getWallet']);
    Route::get('/transactions', [WalletController::class, 'getTransactions']);
    
    // Nap tien - QR code flow
    Route::post('/deposit', [WalletController::class, 'deposit']);
    Route::post('/deposit/{depositRequest}/confirm', [WalletController::class, 'confirmTransfer']);
    Route::get('/deposit/{depositRequest}/status', [WalletController::class, 'checkDepositStatus']);
    
    // Rut tien
    Route::post('/withdraw/calculate-fee', [WalletController::class, 'calculateWithdrawFee']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
    Route::get('/deposit-requests', [WalletController::class, 'depositRequests']);
    Route::get('/withdraw-requests', [WalletController::class, 'withdrawRequests']);
    Route::get('/auction-payments', [WalletController::class, 'auctionPayments']);
    Route::get('/auction-payment/{auctionPayment}', [WalletController::class, 'auctionPaymentDetail']);
    Route::post('/auction-payment/{auctionPayment}', [WalletController::class, 'payAuction']);
    Route::post('/auction-payment/{auctionPayment}/bank-transfer', [WalletController::class, 'payAuctionByBankTransfer']);
});

// Statistics (require auth)
Route::prefix('stats')->middleware('auth:api')->group(function () {
    Route::get('overview', [\App\Http\Controllers\Api\ReportController::class, 'overview']);
    Route::get('views', [\App\Http\Controllers\Api\ReportController::class, 'views']);
    Route::get('revenue', [\App\Http\Controllers\Api\ReportController::class, 'revenue']);
    Route::get('promotions', [\App\Http\Controllers\Api\ReportController::class, 'promotions']);
});

// Admin routes
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard']);
    
    // Users Management
    Route::get('users', [\App\Http\Controllers\AdminController::class, 'users']);
    Route::get('users/{user}', [\App\Http\Controllers\AdminController::class, 'userDetail']);
    Route::put('users/{user}/status', [\App\Http\Controllers\AdminController::class, 'updateUserStatus']);
    
    // Listings Management
    Route::get('listings', [\App\Http\Controllers\AdminController::class, 'listings']);
    Route::put('listings/{listing}/status', [\App\Http\Controllers\AdminController::class, 'updateListingStatus']);
    
    // Promotions/Ads Management
    Route::get('promotions', [\App\Http\Controllers\AdminController::class, 'promotions']);
    Route::get('promotions/stats', [\App\Http\Controllers\AdminController::class, 'promotionStats']);
    
    // Transactions Management
    Route::get('transactions', [\App\Http\Controllers\AdminController::class, 'transactions']);
    Route::get('transactions/stats', [\App\Http\Controllers\AdminController::class, 'transactionStats']);
    
    // Reports Management
    Route::get('reports', [\App\Http\Controllers\AdminController::class, 'reports']);
    Route::get('reports/stats', [\App\Http\Controllers\AdminController::class, 'reportStats']);
    Route::put('reports/{report}/resolve', [\App\Http\Controllers\AdminController::class, 'resolveReport']);
    
    // Orders Management
    Route::get('orders', [\App\Http\Controllers\AdminController::class, 'orders']);
    Route::get('orders/stats', [\App\Http\Controllers\AdminController::class, 'orderStats']);
    
    // Auctions Management
    Route::get('auctions', [\App\Http\Controllers\AdminController::class, 'auctions']);
    Route::get('auctions/stats', [\App\Http\Controllers\AdminController::class, 'auctionStats']);
    Route::get('auction-payments', [\App\Http\Controllers\AdminController::class, 'auctionPayments']);
    Route::put('auction-payment/{auctionPayment}/confirm-bank', [WalletController::class, 'adminConfirmAuctionBankTransfer']);
    Route::put('auction-payment/{auctionPayment}/reject-bank', [WalletController::class, 'adminRejectAuctionBankTransfer']);
    
    // Shops Management
    Route::get('shops', [\App\Http\Controllers\AdminController::class, 'shops']);
    Route::put('shops/{shop}/verify', [\App\Http\Controllers\AdminController::class, 'verifyShop']);
    
    // Revenue Management (Doanh thu platform)
    Route::get('revenue', [\App\Http\Controllers\AdminController::class, 'revenue']);
    Route::get('revenue/stats', [\App\Http\Controllers\AdminController::class, 'revenueStats']);
    
    // Wallet Management
    Route::prefix('wallet')->group(function () {
        Route::get('/deposits', [WalletController::class, 'adminDepositRequests']);
        Route::put('/deposit/{depositRequest}/approve', [WalletController::class, 'confirmDeposit']);
        Route::put('/deposit/{depositRequest}/reject', [WalletController::class, 'rejectDeposit']);
        Route::get('/withdraws', [WalletController::class, 'adminWithdrawRequests']);
        Route::put('/withdraw/{withdrawRequest}/process', [WalletController::class, 'processWithdraw']);
        Route::put('/withdraw/{withdrawRequest}/reject', [WalletController::class, 'rejectWithdraw']);
    });

    // Subscription Management
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'adminList']);
        Route::get('/stats', [SubscriptionController::class, 'adminStats']);
        Route::put('/{id}/approve', [SubscriptionController::class, 'adminApprove'])->whereNumber('id');
        Route::put('/{id}/reject', [SubscriptionController::class, 'adminReject'])->whereNumber('id');
    });
});

// Fallback route - phải đặt cuối cùng
Route::fallback(fn () => response()->json(['message' => 'Not Found'], 404));
