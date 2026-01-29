<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Expands the ships.status column from ENUM to VARCHAR(50)
 * to support all ShipmentStatus values including 'paid', 'processing', etc.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: string columns are already flexible, just ensure column exists
            // SQLite doesn't enforce varchar length or enum constraints
            // The column will accept any string value
            if (!Schema::hasColumn('ships', 'status')) {
                Schema::table('ships', function (Blueprint $table) {
                    $table->string('status', 50)->default('pending');
                });
            }
            // SQLite columns are already flexible - no action needed for existing column
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: use ALTER COLUMN TYPE syntax
            DB::statement("ALTER TABLE ships ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE ships ALTER COLUMN status SET DEFAULT 'pending'");
        } else {
            // MySQL: use raw SQL to convert ENUM to VARCHAR
            DB::statement("ALTER TABLE ships MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: no action needed (can't easily revert column type)
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: revert to VARCHAR with limited values (note: PostgreSQL doesn't have ENUM like MySQL)
            // We can't easily revert to ENUM in PostgreSQL, so we leave it as VARCHAR
            // Alternatively, you could create a custom type, but that's complex for a rollback
        } else {
            // MySQL: revert to original ENUM (note: this may fail if unsupported values exist)
            DB::statement("ALTER TABLE ships MODIFY COLUMN status ENUM('pending', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");
        }
    }
};
