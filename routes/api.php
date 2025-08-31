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
});