<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\CouponController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    
    Route::post('/bookings', [ServiceRequestController::class, 'store']);
    Route::get('/bookings', [ServiceRequestController::class, 'index']);
    Route::get('/bookings/{id}', [ServiceRequestController::class, 'show']);
    
    Route::post('/payments', [PaymentController::class, 'store']);
    
    Route::post('/ratings', [RatingController::class, 'store']);

    Route::post('/coupons/apply', [CouponController::class, 'apply']);
    
    Route::post('/logout', [AuthController::class, 'logout']);
});