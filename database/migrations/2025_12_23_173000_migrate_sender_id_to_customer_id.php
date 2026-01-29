<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Migrates sender_id data to customer_id for packages where customer_id is null.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Skip for SQLite - no data to migrate in fresh database
        if ($driver === 'sqlite') {
            return;
        }

        // First, migrate any packages that have sender_id but no customer_id
        // This maps old user IDs to new customer IDs via email matching
        if ($driver === 'pgsql') {
            // PostgreSQL syntax: UPDATE...SET...FROM...WHERE
            DB::statement("
                UPDATE packages
                SET customer_id = c.id
                FROM users u, customers c
                WHERE packages.sender_id = u.id
                AND u.email = c.email
                AND packages.customer_id IS NULL
                AND packages.sender_id IS NOT NULL
            ");
        } else {
            // MySQL syntax: UPDATE...INNER JOIN...SET
            DB::statement("
                UPDATE packages p
                INNER JOIN users u ON p.sender_id = u.id
                INNER JOIN customers c ON u.email = c.email
                SET p.customer_id = c.id
                WHERE p.customer_id IS NULL AND p.sender_id IS NOT NULL
            ");
        }

        // Log any packages that couldn't be migrated (sender_id exists but no matching customer)
        $unmigrated = DB::table('packages')
            ->whereNull('customer_id')
            ->whereNotNull('sender_id')
            ->count();

        if ($unmigrated > 0) {
            \Log::warning("Migration: {$unmigrated} packages have sender_id but no matching customer record.");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration only copies data, it doesn't delete anything
        // Rolling back would require clearing customer_id values that were set,
        // but we can't know which ones were set by this migration vs. already set
        // So we leave this as a no-op for safety
    }
};

