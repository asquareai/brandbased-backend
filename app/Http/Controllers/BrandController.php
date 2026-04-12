<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Store a new brand with a logo uploaded to S3.
     */
   public function store(Request $request)
{
    // 1. Basic Validation
    $request->validate([
        'brand_name' => 'required|string|max:255',
        'logo' => 'required|image|max:2048',
        'website_url' => 'nullable|url',
    ]);

    try {
        if (!$request->hasFile('logo')) {
            return response()->json(['error' => 'No file found in request. Check your HTML form enctype.'], 400);
        }

        $file = $request->file('logo');
        $slug = \Illuminate\Support\Str::slug($request->brand_name);
        $fileName = time() . '_' . $slug . '.' . $file->getClientOriginalExtension();
        $path = "brands/{$slug}/logos";

        // 2. Deep Debug Attempt
        // We are REMOVING 'public' here to ensure ACLs aren't the cause of the failure.
        $disk = \Illuminate\Support\Facades\Storage::disk('s3');
        
        $uploadedPath = $disk->putFileAs($path, $file, $fileName);

        // 3. If successful, proceed
        $s3Url = $disk->url($uploadedPath);

        $brand = Brand::create([
            'brand_name'  => $request->brand_name,
            'slug'        => $slug,
            'website_url' => $request->website_url, // Now storing from the request
            'logo_url'    => $s3Url,
            'status'      => 'pending', // Default status for Python worker to find
            'is_active'   => true
        ]);

        return response()->json(['success' => true, 'url' => $s3Url]);

    } catch (\Throwable $e) {
        // 4. THE DEBUG ENGINE
        // This looks for the "Previous" exception which contains the RAW AWS error
        $previous = $e->getPrevious();
        
        return response()->json([
            'error_type' => get_class($e),
            'main_message' => $e->getMessage(),
            's3_raw_error' => $previous ? $previous->getMessage() : 'No internal driver error reported',
            'debug_info' => [
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'file_size' => $request->file('logo')->getSize(),
                'mime_type' => $request->file('logo')->getMimeType(),
                'line' => $e->getLine()
            ],
            'env_check' => [
                'has_key' => !empty(config('filesystems.disks.s3.key')),
                'has_secret' => !empty(config('filesystems.disks.s3.secret')),
                'region_env' => env('AWS_DEFAULT_REGION'),
            ]
        ], 500);
    }
}
    /**
     * Update brand status (Callback for external services)
     */
    public function updateStatus(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        
        $brand->update([
            'status'             => $request->status,
            'verification_notes' => $request->notes
        ]);

        return response()->json(['success' => true]);
    }
}