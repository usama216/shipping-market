<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates carrier_addons table for extra services like padding, insurance, signature, etc.
 * Addons can be fetched from carrier APIs or added by admin.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('carrier_addons', function (Blueprint $table) {
            $table->id();

            // Addon identification
            $table->string('addon_code', 50); // Internal code (e.g., 'extra_handling', 'signature_required')
            $table->string('carrier_code', 20)->default('all'); // 'all' or specific carrier

            // Display information
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon class or URL

            // Pricing
            $table->enum('price_type', ['fixed', 'percentage', 'carrier_rate'])->default('carrier_rate');
            $table->decimal('price_value', 10, 2)->nullable(); // For fixed/percentage types
            $table->string('currency', 3)->default('USD');

            // Compatibility
            $table->json('compatible_services')->nullable(); // Array of service_codes, null = all
            $table->json('incompatible_addons')->nullable(); // Addons that can't be combined

            // Requirements
            $table->boolean('requires_value_declaration')->default(false);
            $table->decimal('min_declared_value', 10, 2)->nullable();
            $table->decimal('max_declared_value', 10, 2)->nullable();

            // Source and status
            $table->enum('source', ['carrier_api', 'admin'])->default('admin');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Carrier-specific API code (for when source is carrier_api)
            $table->string('carrier_api_code', 100)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('carrier_code');
            $table->index('is_active');
            $table->unique(['addon_code', 'carrier_code']);
        });

        // Seed common addons
        $this->seedCarrierAddons();
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_addons');
    }

    private function seedCarrierAddons(): void
    {
        $addons = [
            // Universal addons (available for all carriers)
            [
                'addon_code' => 'extra_handling',
                'carrier_code' => 'all',
                'display_name' => 'Extra Handling',
                'description' => 'Special handling for oversized or irregularly shaped packages',
                'price_type' => 'carrier_rate',
                'source' => 'admin',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'addon_code' => 'fragile_handling',
                'carrier_code' => 'all',
                'display_name' => 'Fragile Package Protection',
                'description' => 'Special care for fragile items with extra padding',
                'price_type' => 'fixed',
                'price_value' => 15.00,
                'source' => 'admin',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'addon_code' => 'signature_required',
                'carrier_code' => 'all',
                'display_name' => 'Signature Required',
                'description' => 'Recipient signature required upon delivery',
                'price_type' => 'carrier_rate',
                'source' => 'admin',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'addon_code' => 'insurance_basic',
                'carrier_code' => 'all',
                'display_name' => 'Basic Insurance',
                'description' => 'Coverage up to $500 declared value',
                'price_type' => 'percentage',
                'price_value' => 2.5, // 2.5% of declared value
                'requires_value_declaration' => true,
                'max_declared_value' => 500.00,
                'source' => 'admin',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'addon_code' => 'insurance_premium',
                'carrier_code' => 'all',
                'display_name' => 'Premium Insurance',
                'description' => 'Coverage up to $5000 declared value',
                'price_type' => 'percentage',
                'price_value' => 3.5, // 3.5% of declared value
                'requires_value_declaration' => true,
                'min_declared_value' => 500.01,
                'max_declared_value' => 5000.00,
                'source' => 'admin',
                'is_active' => true,
                'sort_order' => 5,
            ],
            // FedEx specific addons
            [
                'addon_code' => 'dangerous_goods',
                'carrier_code' => 'fedex',
                'display_name' => 'Dangerous Goods Handling',
                'description' => 'For shipping hazardous materials (batteries, chemicals)',
                'price_type' => 'carrier_rate',
                'carrier_api_code' => 'DANGEROUS_GOODS',
                'source' => 'carrier_api',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'addon_code' => 'hold_at_location',
                'carrier_code' => 'fedex',
                'display_name' => 'Hold at FedEx Location',
                'description' => 'Package held at nearest FedEx for pickup',
                'price_type' => 'carrier_rate',
                'carrier_api_code' => 'HOLD_AT_LOCATION',
                'source' => 'carrier_api',
                'is_active' => true,
                'sort_order' => 11,
            ],
            // DHL specific addons
            [
                'addon_code' => 'saturday_delivery',
                'carrier_code' => 'dhl',
                'display_name' => 'Saturday Delivery',
                'description' => 'Delivery on Saturday (where available)',
                'price_type' => 'carrier_rate',
                'carrier_api_code' => 'SAT_DELIVERY',
                'source' => 'carrier_api',
                'is_active' => true,
                'sort_order' => 12,
            ],
        ];

        foreach ($addons as $addon) {
            \DB::table('carrier_addons')->insert(array_merge($addon, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
