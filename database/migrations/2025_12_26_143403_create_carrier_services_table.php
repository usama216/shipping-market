<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates carrier_services table to replace hardcoded InternationalShippingOptions constants.
 * This enables database-driven carrier service configuration with API code mapping.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('carrier_services', function (Blueprint $table) {
            $table->id();

            // Carrier identification
            $table->string('carrier_code', 20); // fedex, dhl, ups
            $table->string('service_code', 100); // API service type code (e.g., FEDEX_INTERNATIONAL_PRIORITY)

            // Display information
            $table->string('display_name', 100); // User-facing name
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();

            // Service type flags
            $table->boolean('is_international')->default(true);
            $table->boolean('is_domestic')->default(false);

            // Transit and weight limits
            $table->integer('base_transit_days')->nullable();
            $table->integer('max_transit_days')->nullable();
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->decimal('max_weight_lb', 8, 2)->nullable();

            // Geographic availability
            $table->json('supported_countries')->nullable(); // Array of ISO codes, null = all
            $table->json('excluded_countries')->nullable(); // Array of ISO codes to exclude
            $table->json('supported_origin_countries')->nullable(); // Where can ship FROM

            // Pricing configuration (fallback when API unavailable)
            $table->json('fallback_pricing_rules')->nullable(); // { "weight_based": [...], "flat_rates": {...} }

            // Status and ordering
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Auto-select when user doesn't choose
            $table->integer('sort_order')->default(0);

            // Metadata
            $table->json('carrier_specific_options')->nullable(); // Carrier-specific config

            $table->timestamps();

            // Indexes
            $table->index('carrier_code');
            $table->index('is_active');
            $table->index('is_international');
            $table->unique(['carrier_code', 'service_code']);
        });

        // Seed initial carrier services based on existing constants
        $this->seedCarrierServices();
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_services');
    }

    private function seedCarrierServices(): void
    {
        $services = [
            // FedEx International Services
            [
                'carrier_code' => 'fedex',
                'service_code' => 'FEDEX_INTERNATIONAL_PRIORITY',
                'display_name' => 'FedEx International Priority',
                'description' => 'Fast international shipping with customs clearance',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 2,
                'max_transit_days' => 5,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'carrier_code' => 'fedex',
                'service_code' => 'FEDEX_INTERNATIONAL_ECONOMY',
                'display_name' => 'FedEx International Economy',
                'description' => 'Economical international shipping',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 5,
                'max_transit_days' => 8,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'carrier_code' => 'fedex',
                'service_code' => 'FEDEX_GROUND',
                'display_name' => 'FedEx Ground',
                'description' => 'Reliable ground shipping within the US',
                'is_international' => false,
                'is_domestic' => true,
                'base_transit_days' => 3,
                'max_transit_days' => 7,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 10,
            ],
            // DHL Services
            [
                'carrier_code' => 'dhl',
                'service_code' => 'EXPRESS_WORLDWIDE',
                'display_name' => 'DHL Express Worldwide',
                'description' => 'Premium international express service',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 2,
                'max_transit_days' => 4,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'carrier_code' => 'dhl',
                'service_code' => 'EXPRESS_ECONOMY',
                'display_name' => 'DHL Economy Select',
                'description' => 'Cost-effective international shipping',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 5,
                'max_transit_days' => 10,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
            ],
            // UPS Services
            [
                'carrier_code' => 'ups',
                'service_code' => 'EXPRESS',
                'display_name' => 'UPS Worldwide Express',
                'description' => 'Fast worldwide delivery',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 2,
                'max_transit_days' => 4,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 5,
            ],
            [
                'carrier_code' => 'ups',
                'service_code' => 'SAVER',
                'display_name' => 'UPS Worldwide Saver',
                'description' => 'End-of-day delivery worldwide',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 3,
                'max_transit_days' => 5,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 6,
            ],
            // Non-carrier services (manual handling)
            [
                'carrier_code' => 'sea_freight',
                'service_code' => 'SEA_FREIGHT_STANDARD',
                'display_name' => 'Sea Freight',
                'description' => 'Economical ocean shipping for large cargo',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 30,
                'max_transit_days' => 60,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 20,
            ],
            [
                'carrier_code' => 'air_cargo',
                'service_code' => 'AIR_CARGO_STANDARD',
                'display_name' => 'Air Cargo',
                'description' => 'Air freight for larger shipments',
                'is_international' => true,
                'is_domestic' => false,
                'base_transit_days' => 7,
                'max_transit_days' => 14,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 15,
            ],
        ];

        foreach ($services as $service) {
            \DB::table('carrier_services')->insert(array_merge($service, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
