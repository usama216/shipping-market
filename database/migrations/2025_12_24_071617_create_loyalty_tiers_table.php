<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('loyalty_tiers')) {
            return; // Table already exists
        }

        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold
            $table->string('slug')->unique(); // bronze, silver, gold
            $table->decimal('min_lifetime_spend', 10, 2)->default(0); // Minimum spend to reach this tier
            $table->decimal('earn_multiplier', 4, 2)->default(1.00); // Point earning multiplier
            $table->string('color', 7)->default('#CD7F32'); // Hex color for display
            $table->string('icon')->nullable(); // Icon name or path
            $table->integer('sort_order')->default(0); // For ordering tiers
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
