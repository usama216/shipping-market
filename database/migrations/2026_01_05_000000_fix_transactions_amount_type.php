<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Changes the transactions.amount column from VARCHAR to DECIMAL
 * to allow SUM() and other numeric operations in PostgreSQL.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Use ALTER COLUMN with USING clause for type conversion
            DB::statement('ALTER TABLE transactions ALTER COLUMN amount TYPE DECIMAL(12,2) USING amount::numeric');
        } elseif ($driver === 'mysql') {
            // MySQL: Use MODIFY COLUMN
            DB::statement('ALTER TABLE transactions MODIFY COLUMN amount DECIMAL(12,2) NULL');
        }
        // SQLite: Cannot easily alter column types, skip for testing environments
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transactions ALTER COLUMN amount TYPE VARCHAR(255)');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN amount VARCHAR(255) NULL');
        }
    }
};
