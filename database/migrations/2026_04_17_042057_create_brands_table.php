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
            $table->string('identity_status')->default('pending');
            $table->text('identity_verification_notes')->nullable();
            $table->text('identity_progress')->default(0);

            $table->string('meta_status')->default('pending');
            $table->text('meta_verification_notes')->nullable();
            $table->text('meta_progress')->default(0);

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
