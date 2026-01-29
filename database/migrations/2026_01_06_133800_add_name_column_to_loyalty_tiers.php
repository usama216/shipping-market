<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing 'name' column to loyalty_tiers table.
 * 
 * This fixes a schema gap where the original migration only created
 * id and timestamps, but the seeder expects a 'name' column.
 */
return new class extends Migration {
    public function up(): void
    {
        // Check outside closure for PostgreSQL compatibility
        if (!Schema::hasColumn('loyalty_tiers', 'name')) {
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                $table->string('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loyalty_tiers', 'name')) {
            Schema::table('loyalty_tiers', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
