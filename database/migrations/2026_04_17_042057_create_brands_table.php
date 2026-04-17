<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('brand_name');
            $table->string('slug')->unique()->index(); // <--- ADD THIS LINE
            
            $table->string('website_url');
            $table->string('logo_light_url')->nullable();
            $table->string('logo_dark_url')->nullable();

            // Verification Stages
            $table->enum('identity_status', ['pending','inprogress','verified', 'rejected'])->default('pending');
            $table->integer('identity_progress')->default(0); 
            $table->enum('meta_status', ['waiting', 'pending', 'verified', 'failed'])->default('waiting');
            $table->string('meta_verification_code')->nullable();

            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
