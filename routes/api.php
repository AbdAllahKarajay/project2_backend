<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\Admin\WalletManagementController;
use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
    Route::get('/profile/stats', [ProfileController::class, 'stats']);
    
    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/topup', [WalletController::class, 'topup']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/stats', [WalletController::class, 'stats']);
    
    // Loyalty routes
    Route::get('/loyalty/points', [LoyaltyController::class, 'points']);
    Route::get('/loyalty/rewards', [LoyaltyController::class, 'rewards']);
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeem']);
    Route::get('/loyalty/redemptions', [LoyaltyController::class, 'redemptions']);
    Route::get('/loyalty/stats', [LoyaltyController::class, 'stats']);
    
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    
    // Reviews
    Route::get('/services/{serviceId}/reviews', [ReviewController::class, 'index']);
    Route::get('/services/{serviceId}/reviews/summary', [ReviewController::class, 'summary']);
    Route::post('/services/{serviceId}/reviews', [ReviewController::class, 'store']);
    Route::post('/reviews/{reviewId}', [ReviewController::class, 'update']);
    Route::post('/reviews/{reviewId}/delete', [ReviewController::class, 'destroy']);
    
    // Service Request (Bookings) routes
    Route::post('/bookings', [ServiceRequestController::class, 'store']);
    Route::get('/bookings', [ServiceRequestController::class, 'index']);
    Route::get('/bookings/{id}', [ServiceRequestController::class, 'show']);
    Route::put('/bookings/{id}', [ServiceRequestController::class, 'update']);
    Route::delete('/bookings/{id}', [ServiceRequestController::class, 'destroy']);
    Route::put('/bookings/{id}/status', [ServiceRequestController::class, 'updateStatus']);
    
    // Payment routes
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);
    
    Route::post('/ratings', [RatingController::class, 'store']);

    Route::post('/coupons/apply', [CouponController::class, 'apply']);
    
    // Location routes
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::get('/locations/{location}', [LocationController::class, 'show']);
    Route::put('/locations/{location}', [LocationController::class, 'update']);
    Route::delete('/locations/{location}', [LocationController::class, 'destroy']);
    
    // Notification routes
    Route::post('/notifications/fcm-token', [NotificationController::class, 'updateFcmToken']);
    Route::delete('/notifications/fcm-token', [NotificationController::class, 'clearFcmToken']);
    Route::get('/notifications/fcm-token/status', [NotificationController::class, 'getFcmTokenStatus']);
    Route::post('/notifications/test', [NotificationController::class, 'sendTestNotification']);
    
    // User Analytics routes
    Route::get('/analytics/personal', [AnalyticsController::class, 'getUserAnalytics']);
    Route::get('/analytics/bookings', [AnalyticsController::class, 'getBookingHistory']);
    Route::get('/analytics/loyalty', [AnalyticsController::class, 'getLoyaltyAnalytics']);
    Route::get('/analytics/wallet', [AnalyticsController::class, 'getWalletAnalytics']);
    Route::get('/analytics/spending-trends', [AnalyticsController::class, 'getSpendingTrends']);
    Route::get('/analytics/service-preferences', [AnalyticsController::class, 'getServicePreferences']);
    
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Admin routes (require admin authentication)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Wallet management routes
    Route::get('/wallets', [WalletManagementController::class, 'index']);
    Route::get('/wallets/{user}', [WalletManagementController::class, 'show']);
    Route::post('/wallets/{user}/refill', [WalletManagementController::class, 'refill']);
    Route::post('/wallets/{user}/deduct', [WalletManagementController::class, 'deduct']);
    Route::get('/wallets/stats/overview', [WalletManagementController::class, 'stats']);
    
    // Admin notification routes
    Route::post('/notifications/send-all', [NotificationController::class, 'sendToAllUsers']);
    Route::post('/notifications/send-by-role', [NotificationController::class, 'sendToUsersByRole']);
    Route::post('/notifications/send-specific', [NotificationController::class, 'sendToSpecificUsers']);
    
    // Admin Analytics routes
    Route::get('/analytics/dashboard', [AdminAnalyticsController::class, 'getDashboardOverview']);
    Route::get('/analytics/revenue', [AdminAnalyticsController::class, 'getRevenueAnalytics']);
    Route::get('/analytics/services', [AdminAnalyticsController::class, 'getServiceUsageAnalytics']);
    Route::get('/analytics/customers', [AdminAnalyticsController::class, 'getCustomerAnalytics']);
    Route::get('/analytics/loyalty', [AdminAnalyticsController::class, 'getLoyaltyAnalytics']);
    Route::get('/analytics/coupons', [AdminAnalyticsController::class, 'getCouponAnalytics']);
    Route::get('/analytics/comprehensive', [AdminAnalyticsController::class, 'getComprehensiveReport']);
    Route::get('/analytics/date-range', [AdminAnalyticsController::class, 'getDateRangeAnalytics']);
    Route::post('/analytics/export', [AdminAnalyticsController::class, 'exportAnalytics']);
});