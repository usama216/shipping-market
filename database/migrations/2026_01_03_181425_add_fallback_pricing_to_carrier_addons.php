<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add fallback pricing fields to carrier_addons table
 * 
 * For 'carrier_rate' type addons, this allows admin to set a fallback price
 * when carrier API pricing is unavailable. This enables the hybrid pricing
 * approach where some addons use carrier API prices and others use admin-defined prices.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('carrier_addons', function (Blueprint $table) {
            // Fallback price for carrier_rate type addons
            // Used when carrier API pricing is unavailable
            $table->decimal('fallback_price', 10, 2)->nullable()->after('price_value');

            // Whether to show this addon even when carrier pricing is unavailable
            // If true, uses fallback_price; if false, hides the addon
            $table->boolean('use_fallback')->default(true)->after('fallback_price');
        });

        // Update existing carrier_rate addons with sensible default fallback prices
        $this->seedFallbackPrices();
    }

    public function down(): void
    {
        Schema::table('carrier_addons', function (Blueprint $table) {
            $table->dropColumn(['fallback_price', 'use_fallback']);
        });
    }

    /**
     * Seed fallback prices for existing carrier_rate addons
     */
    private function seedFallbackPrices(): void
    {
        $fallbackPrices = [
            'extra_handling' => 18.00,
            'signature_required' => 6.50,
            'dangerous_goods' => 55.00,
            'hold_at_location' => 8.00,
            'saturday_delivery' => 25.00,
        ];

        foreach ($fallbackPrices as $addonCode => $fallbackPrice) {
            \DB::table('carrier_addons')
                ->where('addon_code', $addonCode)
                ->where('price_type', 'carrier_rate')
                ->update([
                        'fallback_price' => $fallbackPrice,
                        'use_fallback' => true,
                    ]);
        }
    }
};
