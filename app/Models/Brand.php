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
        'slug',
        'website_url', 
        'logo_light_url', 
        'logo_dark_url', 
        'identity_status', 
        'identity_verification_notes', 
        'identity_progress',
        'meta_status',
        'meta_verification_notes',     
        'meta_progress',
        'status'
    ];
}