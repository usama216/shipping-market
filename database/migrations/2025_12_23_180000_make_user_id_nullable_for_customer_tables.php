<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to make user_id nullable in tables that can be owned by customers.
 * 
 * Background: The system now has separate tables for system users (users) and 
 * customers (customers). Tables like ships, transactions, etc. can be owned by
 * customers who use customer_id instead of user_id. Making user_id nullable
 * allows these records to exist without a system user reference.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite doesn't support modifying columns or dropping foreign keys in the same way
        // On SQLite, columns are already flexible and foreign keys are handled differently
        if ($driver === 'sqlite') {
            // SQLite: Foreign key constraints are not enforced by default
            // and columns can accept NULL without explicit modification
            // No action needed - the schema already supports nullable in SQLite
            return;
        }

        // MySQL/PostgreSQL: Make user_id nullable in ships table
        Schema::table('ships', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['user_id']);

            // Make the column nullable
            $table->foreignId('user_id')->nullable()->change();

            // Re-add foreign key as nullable
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Make user_id nullable in transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Make user_id nullable in user_addresses table
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Make user_id nullable in loyalty_transactions table
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Make user_id nullable in package_change_requests table
        Schema::table('package_change_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Reverting this will fail if there are records with NULL user_id
        // Those records should be deleted or updated first

        Schema::table('ships', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('package_change_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
