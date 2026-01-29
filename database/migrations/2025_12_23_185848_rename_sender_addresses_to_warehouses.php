<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename sender_addresses to warehouses and enhance for warehouse management.
 * Warehouses are origin addresses for shipments - assigned to customers and packages.
 */
return new class extends Migration {
    public function up(): void
    {
        // Rename table
        Schema::rename('sender_addresses', 'warehouses');

        Schema::table('warehouses', function (Blueprint $table) {
            // Add warehouse-specific fields
            $table->string('name')->after('id'); // e.g., "Miami Warehouse", "UK Hub"
            $table->string('code', 10)->after('name')->unique(); // e.g., "MIA", "UK1"
            $table->boolean('is_active')->default(true)->after('is_default');

            // Remove user_id - warehouses are global, not per-user
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->dropColumn(['name', 'code', 'is_active']);
        });

        Schema::rename('warehouses', 'sender_addresses');
    }
};
