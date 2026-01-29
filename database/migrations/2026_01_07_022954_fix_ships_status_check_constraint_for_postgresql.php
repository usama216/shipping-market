<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes the ships.status CHECK constraint issue in PostgreSQL.
 * 
 * Problem: The original migration created an ENUM type which PostgreSQL
 * implements as a CHECK constraint. The subsequent migration
 * (2026_01_04_000000_expand_ships_status_column) changed the column type
 * to VARCHAR(50), but did NOT drop the CHECK constraint, causing:
 * 
 * ERROR: new row for relation "ships" violates check constraint "ships_status_check"
 * 
 * This migration safely drops the constraint if it exists, allowing the
 * full ShipmentStatus lifecycle values (pending, paid, processing, shipped, etc.)
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // Check if the constraint exists before trying to drop it
            $constraintExists = DB::selectOne("
                SELECT 1 FROM pg_constraint 
                WHERE conname = 'ships_status_check' 
                AND conrelid = 'ships'::regclass
            ");

            if ($constraintExists) {
                DB::statement('ALTER TABLE ships DROP CONSTRAINT ships_status_check');
            }

            // Also check for invoice_status constraint if it exists
            $invoiceConstraintExists = DB::selectOne("
                SELECT 1 FROM pg_constraint 
                WHERE conname = 'ships_invoice_status_check' 
                AND conrelid = 'ships'::regclass
            ");

            if ($invoiceConstraintExists) {
                DB::statement('ALTER TABLE ships DROP CONSTRAINT ships_invoice_status_check');
            }

            // Ensure column types are VARCHAR(50) to support all status values
            DB::statement("ALTER TABLE ships ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE ships ALTER COLUMN invoice_status TYPE VARCHAR(50)");
        }
        // MySQL/SQLite: No action needed - ENUM to VARCHAR conversion 
        // was already handled by the previous migration, and these DBs
        // don't create separate CHECK constraints for ENUM types.
    }

    public function down(): void
    {
        // Intentionally left empty - we don't want to re-add constraints
        // that restrict the status values. The expanded status lifecycle
        // is the intended behavior going forward.
    }
};
