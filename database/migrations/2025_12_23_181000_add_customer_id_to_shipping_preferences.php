<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add customer_id foreign key constraint to shipping_preferences table.
 * The column was created earlier but without the constraint (customers table didn't exist yet).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Add foreign key constraint (column already exists from create_shipping_preferences migration)
        if ($driver !== 'sqlite') {
            Schema::table('shipping_preferences', function (Blueprint $table) {
                // Add foreign key constraint to existing column
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('shipping_preferences', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }
    }
};

