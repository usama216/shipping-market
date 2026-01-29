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
        Schema::create('hs_code_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // Category (apparel, electronics, etc.)
            $table->json('keywords')->nullable(); // Keywords that trigger this rule
            $table->json('materials')->nullable(); // Material keywords
            $table->json('usage_terms')->nullable(); // Usage keywords
            $table->string('gender', 20)->nullable(); // male, female, unisex, children
            $table->string('suggested_hs_code', 20)->nullable(); // Suggested HS code
            $table->decimal('confidence_score', 3, 2)->default(0.50); // Confidence 0.00-1.00
            $table->integer('priority')->default(0); // Higher priority = checked first
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['category', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hs_code_rules');
    }
};
