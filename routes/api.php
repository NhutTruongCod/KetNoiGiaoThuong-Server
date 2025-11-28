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


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
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

// Subscriptions (require auth)
Route::prefix('subscriptions')->middleware('auth:api')->group(function () {
    Route::post('/', [SubscriptionController::class, 'subscribe']);
    Route::put('{id}/renew', [SubscriptionController::class, 'renew'])->whereNumber('id');
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
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);      // Lấy tất cả
    Route::get('/{id}', [OrderController::class, 'show']);   // Lấy theo ID
    Route::post('/', [OrderController::class, 'store']);          // POST create
    Route::put('/{id}', [OrderController::class, 'update']);      // PUT update
    Route::delete('/{id}', [OrderController::class, 'destroy']);  // DELETE cancel
});



Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);           // GET all
    Route::get('/{id}', [ReviewController::class, 'show']);        // GET by id
    Route::post('/', [ReviewController::class, 'store']);          // POST create
    Route::put('/{id}', [ReviewController::class, 'update']);      // PUT update
    Route::delete('/{id}', [ReviewController::class, 'destroy']);  // DELETE
});



Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::post('/', [PaymentController::class, 'store']);
});

Route::fallback(fn () => response()->json(['message' => 'Not Found'], 404));

Route::prefix('stores')->group(function () {
    Route::get('/', [StoreController::class, 'index']);      // Lấy danh sách (có phân trang, search)
    Route::post('/', [StoreController::class, 'store']);     // Tạo mới
    Route::get('/{store}', [StoreController::class, 'show']); // Xem chi tiết 1 cửa hàng
    Route::put('/{store}', [StoreController::class, 'update']); // Cập nhật
    Route::delete('/{store}', [StoreController::class, 'destroy']); // Xóa
});

Route::prefix('categories')->group(function () {
    // API lấy danh sách gọn (cho dropdown chọn danh mục)
    Route::get('/simple-list', [CategoryController::class, 'simpleList']); 
    
    // Các API CRUD chuẩn
    Route::get('/', [CategoryController::class, 'index']);      // Danh sách & Search
    Route::post('/', [CategoryController::class, 'store']);     // Tạo mới
    Route::get('/{category}', [CategoryController::class, 'show']); // Xem chi tiết
    Route::put('/{category}', [CategoryController::class, 'update']); // Cập nhật
    Route::delete('/{category}', [CategoryController::class, 'destroy']); // Xóa
});

Route::prefix('listings')->group(function () {
    Route::get('/', [ListingController::class, 'index']);      // Danh sách (có lọc, tìm kiếm, phân trang)
    Route::post('/', [ListingController::class, 'store']);     // Thêm mới
    Route::get('/{listing}', [ListingController::class, 'show']); // Xem chi tiết
    Route::put('/{listing}', [ListingController::class, 'update']); // Cập nhật
    Route::delete('/{listing}', [ListingController::class, 'destroy']); // Xóa
});

Route::prefix('promotion')->group(function () {
    Route::get('/active', [PromotionController::class, 'activePromotions']);
    Route::patch('/{id}/featured', [PromotionController::class, 'updateFeatured']);
    
    Route::get('/', [PromotionController::class, 'index']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/{id}', [PromotionController::class, 'show']);
    Route::put('/{id}', [PromotionController::class, 'update']);
    Route::delete('/{id}', [PromotionController::class, 'destroy']);
});