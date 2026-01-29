<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhances ships table for consolidated shipment management.
 * Adds addon tracking, declared value, and customs fields.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('ships', function (Blueprint $table) use ($driver) {
            // Reference to new carrier_services table
            if ($driver === 'sqlite') {
                // SQLite: Add column without foreign key constraint
                $table->unsignedBigInteger('carrier_service_id')->nullable()->after('international_shipping_option_id');
            } else {
                // MySQL/PostgreSQL: Add with foreign key constraint
                $table->foreignId('carrier_service_id')
                    ->nullable()
                    ->after('international_shipping_option_id')
                    ->constrained('carrier_services')
                    ->nullOnDelete();
            }

            // Selected addons and their charges
            $table->json('selected_addon_ids')->nullable()->after('carrier_service_id');
            $table->decimal('addon_charges', 10, 2)->default(0)->after('selected_addon_ids');

            // Declared value for customs and insurance
            $table->decimal('declared_value', 10, 2)->nullable()->after('addon_charges');
            $table->string('declared_value_currency', 3)->default('USD')->after('declared_value');

            // Enhanced status tracking
            $table->string('customs_status', 50)->nullable()->after('carrier_status');
            $table->timestamp('customs_cleared_at')->nullable()->after('customs_status');

            // Shipment type (for future multi-package consolidation)
            $table->string('shipment_type', 20)->default('standard')->after('customs_cleared_at');

            // Rate source tracking (for debugging/audit)
            $table->string('rate_source', 20)->default('live_api')->after('shipment_type');

            // Indexes
            $table->index('carrier_service_id');
            $table->index('customs_status');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('ships', function (Blueprint $table) use ($driver) {
            // Only drop foreign key on non-SQLite databases
            if ($driver !== 'sqlite') {
                $table->dropForeign(['carrier_service_id']);
            }

            // Drop indexes first
            $table->dropIndex(['carrier_service_id']);
            $table->dropIndex(['customs_status']);

            $table->dropColumn([
                'carrier_service_id',
                'selected_addon_ids',
                'addon_charges',
                'declared_value',
                'declared_value_currency',
                'customs_status',
                'customs_cleared_at',
                'shipment_type',
                'rate_source',
            ]);
        });
    }
};
