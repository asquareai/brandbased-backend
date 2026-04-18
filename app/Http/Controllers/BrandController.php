<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{

    public function index(Request $request)
    {
        try {
            // Fetch brands belonging only to the logged-in user
            $brands = Brand::where('user_id', $request->user()->id)->get();

            return response()->json([
                'status' => 'success',
                'brands' => $brands
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve brands.',
                'debug' => $e->getMessage()
            ], 500);    
        }
    }
    /**
     * Store a new brand with a logo uploaded to S3.
     */
   public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User session not found.'], 401);
        }

        $request->validate([
            'brand_name'  => 'required|string|max:255',
            'website_url' => 'nullable|url',
            'light_logo'  => 'required|file|mimes:svg|max:5120', // 5MB limit
            'dark_logo'   => 'required|file|mimes:svg|max:5120',
        ]);

        try {
            $timestamp = time();
            $lightUrl = null;
            $darkUrl = null;

            // 1. Generate a Unique Slug
            $baseSlug = \Illuminate\Support\Str::slug($request->brand_name);
            $slug = $baseSlug;
            $count = 1;
            
            // Check if slug exists and append number if necessary
            while (\App\Models\Brand::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            // 2. Upload Files to S3
            if ($request->hasFile('light_logo')) {
                $lightPath = "brands/logos/{$user->id}/light_{$timestamp}.svg";
                \Illuminate\Support\Facades\Storage::disk('s3')->put($lightPath, file_get_contents($request->file('light_logo')), [
                    'visibility' => 'public',
                    'ContentType' => 'image/svg+xml'
                ]);
                $lightUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($lightPath);
            }

            if ($request->hasFile('dark_logo')) {
                $darkPath = "brands/logos/{$user->id}/dark_{$timestamp}.svg";
                \Illuminate\Support\Facades\Storage::disk('s3')->put($darkPath, file_get_contents($request->file('dark_logo')), [
                    'visibility' => 'public',
                    'ContentType' => 'image/svg+xml'
                ]);
                $darkUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($darkPath);
            }

           // 3. Save to Database
            $brand = \App\Models\Brand::create([
                'user_id'                => $user->id,
                'brand_name'             => $request->brand_name,
                'slug'                   => $slug,
                'website_url'            => $request->website_url,
                'logo_light_url'         => $lightUrl,
                'logo_dark_url'          => $darkUrl,
                
                // --- Stage 1: Identity ---
                'identity_status'        => 'pending',
                'identity_progress'      => 55, // Starting value for the red bar
                
                // --- Stage 2: Meta ---
                'meta_status'            => 'pending',
                'meta_verification_code' => \Illuminate\Support\Str::random(32), // Generate the string they must copy
                
                // Global Status
                'status'                 => 'pending', 
            ]);

            return response()->json([
                'success' => true, 
                'brand'   => $brand, // This now includes the identity progress and meta code
                'message' => 'Brand created. Identity verification in progress.'
            ], 201);

        } catch (\Exception $e) {
            // Optional: Delete uploaded files from S3 if DB save fails
            // if ($lightPath) \Storage::disk('s3')->delete($lightPath);

            return response()->json([
                'error_type'   => get_class($e),
                'main_message' => 'An error occurred while saving the brand.',
                'debug'        => $e->getMessage(), // Remove 'debug' in production
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
            'identity_status'             => $request->status,
            'verification_notes' => $request->notes
        ]);

        return response()->json(['success' => true]);
    }
    public function getPendingBrand(Request $request) 
    {
        $userId = $request->user()->id;
        
        // Statuses that the Python processor will eventually flip to 'verified'
        $working = ['pending', 'inprogress', 'under review'];

        $pendingBrand = Brand::where('user_id', $userId)
            ->where(function ($query) use ($working) {
                // Check if Step 1 (Identity) is still processing
                $query->whereIn('identity_status', $working)
                // OR Step 2 (Meta) is still processing
                    ->orWhereIn('meta_status', $working);
            })
            // We only want the latest one they were working on
            ->latest() 
            ->first();

        if (!$pendingBrand) {
            return response()->json(['brand' => null], 200);
        }

        return response()->json(['brand' => $pendingBrand], 200);
    }

    public function getDashboardStatus(Request $request)
    {
        $user = $request->user();

        // 1. Count total brands owned by user
        $brandCount = \App\Models\Brand::where('user_id', $user->id)->count();

        // 2. Check if any brand is currently 'pending'
        $hasPending = \App\Models\Brand::where('user_id', $user->id)
                                    ->where('status', 'pending')
                                    ->exists();

        // 3. Determine Plan (For now, we can check a column or default to 'free')
        // If you don't have a 'plan' column yet, we'll default to 'free'
        $plan = $user->plan ?? 'free'; 

        return response()->json([
            'plan' => $plan,
            'brand_count' => $brandCount,
            'has_pending' => $hasPending,
            'max_limit' => 12
        ]);
    }
    public function getBrandById(Request $request, $id) 
    {
        $userId = $request->user()->id;
        $brand = Brand::where('user_id', $userId)->where('id', $id)->first();

        if (!$brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        return response()->json(['brand' => $brand], 200);
    }
    public function getBrandStatus($id)
    {
        $brand = Brand::findOrFail($id);

        return response()->json([
            'brand_name' => $brand->brand_name,
            'identity' => [
                'status' => $brand->identity_status,
                'progress' => $brand->identity_progress
            ],
            'meta' => [
                'status' => $brand->meta_status,
                'code' => $brand->meta_verification_code
            ],
            'overall_status' => $brand->status
        ]);
    }
}