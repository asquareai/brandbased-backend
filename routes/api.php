<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController; // Ensure this matches your controller path
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/
// Registration Flow
Route::post('/auth/signup/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/signup/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/signup/complete', [AuthController::class, 'completeSignup']);

// Login Flow
Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::post('/brands', [BrandController::class, 'store']);
});

Route::post('/brands/{id}/status-update', [BrandController::class, 'updateStatus']);


// Health Check / Debug
Route::post('/test-post', function() {
    return response()->json(['message' => 'Post working!']);
});


/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- NEW: Brand Management ---
    Route::post('/brands/store', [BrandController::class, 'store']);

    // --- PIN Management ---
    Route::post('/auth/set-pin', [AuthController::class, 'updatePin']);

    // Application Routes
    Route::get('/dashboard', [AuthController::class, 'dashboard']);
    
    // Session Management
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user/dashboard-status', [BrandController::class, 'getDashboardStatus']);
    Route::get('/user/brands', [BrandController::class, 'index']);
});