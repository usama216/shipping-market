<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Adds DHL-specific state acceptance flag to countries table.
     * Similar to fedex_accepts_state - controls whether DHL API accepts state/province for this country.
     */
    public function up(): void
    {
        // Check if column already exists before adding it
        if (!Schema::hasColumn('countries', 'dhl_accepts_state')) {
            Schema::table('countries', function (Blueprint $table) {
                // Add DHL accepts state column (defaults to same as FedEx behavior)
                $table->boolean('dhl_accepts_state')->default(true)->after('fedex_accepts_state');
            });

            // Copy FedEx state rules to DHL (same Caribbean territories don't accept states)
            DB::table('countries')
                ->whereColumn('fedex_accepts_state', '=', DB::raw('false'))
                ->update(['dhl_accepts_state' => false]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('countries', 'dhl_accepts_state')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('dhl_accepts_state');
            });
        }
    }
};
