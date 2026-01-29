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
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            // Add name if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'name')) {
                $table->string('name')->after('id');
            }

            // Add slug if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }

            // Add min_lifetime_spend if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'min_lifetime_spend')) {
                $table->decimal('min_lifetime_spend', 10, 2)->default(0)->after('slug');
            }

            // Add earn_multiplier if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'earn_multiplier')) {
                $table->decimal('earn_multiplier', 4, 2)->default(1.00)->after('min_lifetime_spend');
            }

            // Add color if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'color')) {
                $table->string('color', 7)->default('#CD7F32')->after('earn_multiplier');
            }

            // Add icon if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'icon')) {
                $table->string('icon')->nullable()->after('color');
            }

            // Add sort_order if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('icon');
            }

            // Add is_active if it doesn't exist
            if (!Schema::hasColumn('loyalty_tiers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns in down - they may have been there originally
    }
};
