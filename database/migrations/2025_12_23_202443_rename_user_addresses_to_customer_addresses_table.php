<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Rename the table (works on both MySQL and SQLite)
        Schema::rename('user_addresses', 'customer_addresses');

        if ($driver === 'sqlite') {
            // SQLite: Just rename the column, skip foreign key operations
            Schema::table('ships', function (Blueprint $table) {
                $table->renameColumn('user_address_id', 'customer_address_id');
            });
        } else {
            // MySQL/PostgreSQL: Update foreign key reference in ships table
            Schema::table('ships', function (Blueprint $table) {
                // Drop old foreign key
                $table->dropForeign(['user_address_id']);

                // Rename column
                $table->renameColumn('user_address_id', 'customer_address_id');
            });

            Schema::table('ships', function (Blueprint $table) {
                // Add new foreign key
                $table->foreign('customer_address_id')
                    ->references('id')
                    ->on('customer_addresses')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Just rename the column, skip foreign key operations
            Schema::table('ships', function (Blueprint $table) {
                $table->renameColumn('customer_address_id', 'user_address_id');
            });
        } else {
            // MySQL/PostgreSQL: Rename column back and update FK
            Schema::table('ships', function (Blueprint $table) {
                $table->dropForeign(['customer_address_id']);
                $table->renameColumn('customer_address_id', 'user_address_id');
            });

            Schema::table('ships', function (Blueprint $table) {
                $table->foreign('user_address_id')
                    ->references('id')
                    ->on('user_addresses')
                    ->onDelete('set null');
            });
        }

        // Rename table back
        Schema::rename('customer_addresses', 'user_addresses');
    }
};
