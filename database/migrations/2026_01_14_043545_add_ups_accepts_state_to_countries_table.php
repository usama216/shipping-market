<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists before adding it
        if (!Schema::hasColumn('countries', 'ups_accepts_state')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->boolean('ups_accepts_state')->default(true)->after('dhl_accepts_state');
            });

            // Set ups_accepts_state = false for Caribbean countries that don't accept state codes
            // Mirror the dhl_accepts_state values for consistency
            DB::statement('UPDATE countries SET ups_accepts_state = dhl_accepts_state');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if column exists before dropping it
        if (Schema::hasColumn('countries', 'ups_accepts_state')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('ups_accepts_state');
            });
        }
    }
};
