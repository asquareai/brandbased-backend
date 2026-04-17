<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\PasswordResetOtp;

class AuthController extends Controller
{
    /**
     * STAGE 1: Send OTP to Email
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'Email already registered. Please login.'], 422);
        }

        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        UserTemp::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'otp_expires_at' => $expiresAt,
                'is_verified' => false
            ]
        );

        return response()->json([
            'message' => 'OTP sent successfully.',
            'otp_debug' => $otp 
        ]);
    }

    /**
     * STAGE 2: Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $temp = UserTemp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('otp_expires_at', '>', Carbon::now())
            ->first();

        if (!$temp) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $temp->update(['is_verified' => true]);

        return response()->json(['message' => 'Email verified successfully.']);
    }

    /**
     * STAGE 3: Complete Signup (Password only)
     */
    public function completeSignup(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
            // PIN is no longer required here
        ]);

        $temp = UserTemp::where('email', $request->email)
            ->where('is_verified', true)
            ->first();

        if (!$temp) {
            return response()->json(['message' => 'Verification required.'], 403);
        }

        $user = User::create([
            'email'    => $request->email,
            'password' => $request->password, 
            'status'   => 'active'
        ]);

        // 2. Now generate the token for this specific user
        $token = $user->createToken('auth_token')->plainTextToken;

        $temp->delete();

        // 3. Return the token so React can save it to localStorage
        return response()->json([
            'message' => 'Registration complete.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    /**
     * STAGE 4: Set/Update PIN (Protected Route)
     * This is the separate process done after login
     */
    public function updatePin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4|confirmed',
        ]);

        $user = $request->user();
        $user->update([
            'pin' => $request->pin // Assumes your User model handles the hashing
        ]);

        return response()->json(['message' => 'Security PIN updated successfully.']);
    }

    /**
     * LOGIN: Using Email and Password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // Delete existing tokens if you want to allow only one session at a time (Optional)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            // --- ADD THIS SECTION ---
            'user' => [
                'id'    => $user->id,
                'email' => $user->email,
                'name'  => $user->name, // If you have a name field
            ]
        ]);
    }

    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Welcome to your dashboard',
            'user'    => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }
    /**
     * FORGOT PASSWORD: Send OTP to existin g user
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'If registered, an OTP will be sent.'], 200);
        }

        $otp = rand(100000, 999999);

        // Store in the NEW table
        PasswordResetOtp::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10),
                'is_used' => false
            ]
        );

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * FORGOT PASSWORD: Reset using the new table
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        // Check the NEW table for verification
        $resetData = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_used', false)
            ->first();

        if (!$resetData) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
            
            // Mark as used or delete
            $resetData->update(['is_used' => true]);

            return response()->json(['message' => 'Password reset successfully.']);
        }

        return response()->json(['message' => 'User not found.'], 404);
    }
}