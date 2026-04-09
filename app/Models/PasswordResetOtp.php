<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'is_used',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Helper scope to check if the OTP is still valid.
     */
    public function scopeIsValid($query, $email, $otp)
    {
        return $query->where('email', $email)
                     ->where('otp', $otp)
                     ->where('is_used', false)
                     ->where('expires_at', '>', now());
    }
}