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

        Schema::table('loyalty_transactions', function (Blueprint $table) use ($driver) {
            // Add customer_id column if it doesn't exist
            if (!Schema::hasColumn('loyalty_transactions', 'customer_id')) {
                if ($driver === 'sqlite') {
                    // SQLite: Add without foreign key constraint
                    $table->unsignedBigInteger('customer_id')->nullable()->after('user_id');
                } else {
                    // MySQL/PostgreSQL: Add with foreign key constraint
                    $table->foreignId('customer_id')->nullable()->after('user_id')
                        ->constrained('customers')->nullOnDelete();
                }

                // Add index for customer lookups
                $table->index(['customer_id', 'type']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('loyalty_transactions', function (Blueprint $table) use ($driver) {
            if (Schema::hasColumn('loyalty_transactions', 'customer_id')) {
                if ($driver !== 'sqlite') {
                    $table->dropForeign(['customer_id']);
                }
                $table->dropIndex(['customer_id', 'type']);
                $table->dropColumn('customer_id');
            }
        });
    }
};
