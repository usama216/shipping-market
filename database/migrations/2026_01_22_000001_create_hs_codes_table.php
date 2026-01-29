<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->index(); // HS Code (e.g., 6204.44.00)
            $table->text('description'); // Full description
            $table->string('chapter', 10)->nullable()->index(); // Chapter number
            $table->string('heading', 10)->nullable(); // Heading number
            $table->string('subheading', 10)->nullable(); // Subheading number
            $table->string('category', 50)->nullable()->index(); // Category (apparel, electronics, etc.)
            $table->year('active_year')->default(2024); // Year this code is active
            $table->boolean('is_active')->default(true)->index();
            $table->json('keywords')->nullable(); // Searchable keywords
            $table->json('materials')->nullable(); // Common materials
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            
            // Indexes for faster searching
            $table->index(['category', 'is_active']);
            $table->index(['active_year', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hs_codes');
    }
};
