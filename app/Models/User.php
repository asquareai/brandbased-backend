<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Added for API authentication standard

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * * We include 'pin' here so it can be saved during the final signup step.
     */
    protected $fillable = [
        'email',
        'password',
        'pin',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * * Sensitive data like password and pin should never be 
     * visible in API responses or logs.
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed', // PIN should also be hashed for security
            'last_login_at' => 'datetime',
        ];
    }
}