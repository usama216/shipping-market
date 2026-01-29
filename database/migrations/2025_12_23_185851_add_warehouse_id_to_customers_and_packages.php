<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add warehouse_id foreign key to customers and packages tables.
 * - Customers get assigned a warehouse on registration
 * - Packages use warehouse as shipping origin for carrier APIs
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Add warehouse_id to customers
        Schema::table('customers', function (Blueprint $table) use ($driver) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('suite');
            if ($driver !== 'sqlite') {
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            }
        });

        // Add warehouse_id to packages
        Schema::table('packages', function (Blueprint $table) use ($driver) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('customer_id');
            if ($driver !== 'sqlite') {
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            }
        });

        // Assign default warehouse to existing customers and packages
        $defaultWarehouseId = DB::table('warehouses')->where('is_default', true)->value('id');

        if ($defaultWarehouseId) {
            DB::table('customers')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
            DB::table('packages')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('packages', function (Blueprint $table) use ($driver) {
            if ($driver !== 'sqlite') {
                $table->dropForeign(['warehouse_id']);
            }
            $table->dropColumn('warehouse_id');
        });

        Schema::table('customers', function (Blueprint $table) use ($driver) {
            if ($driver !== 'sqlite') {
                $table->dropForeign(['warehouse_id']);
            }
            $table->dropColumn('warehouse_id');
        });
    }
};
