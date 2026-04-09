<?php

use App\Http\Controllers\AuthController;
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

    // --- NEW: PIN Management ---
    // This is the separate process to set/update the PIN after login
    Route::post('/auth/set-pin', [AuthController::class, 'updatePin']);

    // Application Routes
    Route::get('/dashboard', [AuthController::class, 'dashboard']);
    
    // Session Management
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});