<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds location_id columns to customer_addresses for cascading dropdown support.
 * 
 * These columns reference the new countries/states/cities tables.
 * The original text columns (country, state, city) are kept for display
 * and for addresses that predate the location tables.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            // Add nullable foreign keys to location tables
            // These are placed before the text columns for new entries
            $table->foreignId('country_id')->nullable()->after('address_line_2')->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country')->constrained('states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state')->constrained('cities')->nullOnDelete();

            // Make postal_code nullable (many Caribbean islands don't use them)
            $table->string('postal_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);

            $table->dropColumn(['country_id', 'state_id', 'city_id']);
        });
    }
};
