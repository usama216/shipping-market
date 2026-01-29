<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add dimension limits and shipping restrictions to carrier_services table.
 * 
 * This enables package validation against carrier-specific limits and
 * auto-suggestion of freight services for oversized packages.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('carrier_services', function (Blueprint $table) {
            // Dimension limits
            $table->decimal('max_length_in', 8, 2)->nullable()->after('max_weight_lb');
            $table->decimal('max_length_cm', 8, 2)->nullable()->after('max_length_in');
            $table->decimal('max_girth_in', 8, 2)->nullable()->after('max_length_cm');
            $table->decimal('max_girth_cm', 8, 2)->nullable()->after('max_girth_in');

            // Value limits for insurance/declared value
            $table->decimal('max_declared_value', 12, 2)->nullable()->after('max_girth_cm');

            // Shipping restrictions
            $table->boolean('accepts_dangerous_goods')->default(false)->after('max_declared_value');
            $table->boolean('accepts_lithium_batteries')->default(true)->after('accepts_dangerous_goods');
            $table->boolean('accepts_fragile')->default(true)->after('accepts_lithium_batteries');

            // Freight identification
            $table->boolean('is_freight')->default(false)->after('accepts_fragile');
            $table->decimal('min_weight_lb', 8, 2)->nullable()->after('is_freight');
            $table->decimal('min_weight_kg', 8, 2)->nullable()->after('min_weight_lb');
        });

        // Update existing carriers with actual limit data
        $this->seedCarrierLimits();
    }

    public function down(): void
    {
        Schema::table('carrier_services', function (Blueprint $table) {
            $table->dropColumn([
                'max_length_in',
                'max_length_cm',
                'max_girth_in',
                'max_girth_cm',
                'max_declared_value',
                'accepts_dangerous_goods',
                'accepts_lithium_batteries',
                'accepts_fragile',
                'is_freight',
                'min_weight_lb',
                'min_weight_kg',
            ]);
        });
    }

    /**
     * Seed carrier services with actual carrier limit specifications.
     * 
     * Sources:
     * - FedEx: https://www.fedex.com/en-us/shipping/packaging/size-weight.html
     * - DHL: https://www.dhl.com/en/express/shipping/shipping_advice/express_packaging.html
     * - UPS: https://www.ups.com/us/en/support/shipping-support/shipping-special-care-regulated-items.page
     */
    private function seedCarrierLimits(): void
    {
        // FedEx International Priority
        \DB::table('carrier_services')
            ->where('carrier_code', 'fedex')
            ->where('service_code', 'FEDEX_INTERNATIONAL_PRIORITY')
            ->update([
                    'max_weight_lb' => 150,
                    'max_weight_kg' => 68,
                    'max_length_in' => 119,
                    'max_length_cm' => 302,
                    'max_girth_in' => 165,
                    'max_girth_cm' => 419,
                    'max_declared_value' => 50000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // FedEx International Economy
        \DB::table('carrier_services')
            ->where('carrier_code', 'fedex')
            ->where('service_code', 'FEDEX_INTERNATIONAL_ECONOMY')
            ->update([
                    'max_weight_lb' => 150,
                    'max_weight_kg' => 68,
                    'max_length_in' => 119,
                    'max_length_cm' => 302,
                    'max_girth_in' => 165,
                    'max_girth_cm' => 419,
                    'max_declared_value' => 50000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // FedEx Ground
        \DB::table('carrier_services')
            ->where('carrier_code', 'fedex')
            ->where('service_code', 'FEDEX_GROUND')
            ->update([
                    'max_weight_lb' => 150,
                    'max_weight_kg' => 68,
                    'max_length_in' => 108,
                    'max_length_cm' => 274,
                    'max_girth_in' => 165,
                    'max_girth_cm' => 419,
                    'max_declared_value' => 50000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // DHL Express Worldwide
        \DB::table('carrier_services')
            ->where('carrier_code', 'dhl')
            ->where('service_code', 'EXPRESS_WORLDWIDE')
            ->update([
                    'max_weight_lb' => 154,
                    'max_weight_kg' => 70,
                    'max_length_in' => 118,
                    'max_length_cm' => 300,
                    'max_girth_in' => 157,
                    'max_girth_cm' => 400,
                    'max_declared_value' => 50000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // DHL Economy Select
        \DB::table('carrier_services')
            ->where('carrier_code', 'dhl')
            ->where('service_code', 'EXPRESS_ECONOMY')
            ->update([
                    'max_weight_lb' => 154,
                    'max_weight_kg' => 70,
                    'max_length_in' => 118,
                    'max_length_cm' => 300,
                    'max_girth_in' => 157,
                    'max_girth_cm' => 400,
                    'max_declared_value' => 25000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // UPS Worldwide Express
        \DB::table('carrier_services')
            ->where('carrier_code', 'ups')
            ->where('service_code', 'EXPRESS')
            ->update([
                    'max_weight_lb' => 150,
                    'max_weight_kg' => 68,
                    'max_length_in' => 108,
                    'max_length_cm' => 274,
                    'max_girth_in' => 165,
                    'max_girth_cm' => 419,
                    'max_declared_value' => 50000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // UPS Worldwide Saver
        \DB::table('carrier_services')
            ->where('carrier_code', 'ups')
            ->where('service_code', 'SAVER')
            ->update([
                    'max_weight_lb' => 150,
                    'max_weight_kg' => 68,
                    'max_length_in' => 108,
                    'max_length_cm' => 274,
                    'max_girth_in' => 165,
                    'max_girth_cm' => 419,
                    'max_declared_value' => 50000,
                    'accepts_dangerous_goods' => false,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => false,
                ]);

        // Sea Freight - No size limits, accepts everything
        \DB::table('carrier_services')
            ->where('carrier_code', 'sea_freight')
            ->update([
                    'max_weight_lb' => null, // No limit
                    'max_weight_kg' => null,
                    'max_length_in' => null,
                    'max_length_cm' => null,
                    'max_girth_in' => null,
                    'max_girth_cm' => null,
                    'max_declared_value' => null, // Unlimited
                    'accepts_dangerous_goods' => true,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => true,
                    'min_weight_lb' => 100, // Minimum for freight
                    'min_weight_kg' => 45,
                ]);

        // Air Cargo - High limits, accepts most items
        \DB::table('carrier_services')
            ->where('carrier_code', 'air_cargo')
            ->update([
                    'max_weight_lb' => 2200,
                    'max_weight_kg' => 1000,
                    'max_length_in' => 288, // Pallet size
                    'max_length_cm' => 732,
                    'max_girth_in' => null, // No girth limit
                    'max_girth_cm' => null,
                    'max_declared_value' => 100000,
                    'accepts_dangerous_goods' => true,
                    'accepts_lithium_batteries' => true,
                    'accepts_fragile' => true,
                    'is_freight' => true,
                    'min_weight_lb' => 50,
                    'min_weight_kg' => 23,
                ]);
    }
};
