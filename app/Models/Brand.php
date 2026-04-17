<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 
        'brand_name', 
        'slug', // <--- ADD THIS LINE
        'website_url', 
        'logo_light_url', 
        'logo_dark_url', 
        'identity_status', 
        'identity_progress',
        'meta_status',
        'meta_verification_code',
        'status'
    ];
}