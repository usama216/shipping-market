<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add carrier_code column to countries table.
 * 
 * This allows internal country codes (e.g., 'BQ-BO' for Bonaire) while keeping
 * the actual ISO code for carrier API calls (e.g., 'BQ').
 * 
 * Use cases:
 * - Bonaire (BQ-BO) → carrier_code = 'BQ'
 * - Saba (BQ-SA) → carrier_code = 'BQ'
 * - Sint Eustatius (BQ-SE) → carrier_code = 'BQ'
 */
return new class extends Migration {
    public function up(): void
    {
        // Check if column already exists before adding it
        if (!Schema::hasColumn('countries', 'carrier_code')) {
            Schema::table('countries', function (Blueprint $table) {
                // ISO 2-letter country code for carrier APIs (FedEx, DHL, UPS)
                // If null, use the main 'code' column
                $table->string('carrier_code', 2)->nullable()->after('code');
            });

            // Update existing countries to have carrier_code same as code
            // (optional, since null means "use code")
        }
    }

    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('countries', 'carrier_code')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('carrier_code');
            });
        }
    }
};
