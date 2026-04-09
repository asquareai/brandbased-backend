<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTemp extends Model
{
    protected $table = 'users_temp';
    
    // Add this line to disable automatic timestamps
    public $timestamps = false; 

    protected $fillable = [
        'email',
        'otp',
        'otp_expires_at',
        'is_verified',
    ];
}