<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_name', 
        'slug', 
        'website_url', 
        'logo_url',
        'status', 
        'is_active',
        'verification_notes'
    ];
}