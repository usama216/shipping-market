<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CarrierAddon - Extra services for shipments (insurance, handling, etc.)
 * 
 * Supports both carrier-fetched and admin-defined addons with flexible pricing.
 */
class CarrierAddon extends Model
{
    protected $fillable = [
        'addon_code',
        'carrier_code',
        'display_name',
        'description',
        'icon',
        'price_type',
        'price_value',
        'fallback_price',
        'use_fallback',
        'currency',
        'compatible_services',
        'incompatible_addons',
        'requires_value_declaration',
        'min_declared_value',
        'max_declared_value',
        'source',
        'is_active',
        'sort_order',
        'carrier_api_code',
    ];

    protected $casts = [
        'price_value' => 'decimal:2',
        'fallback_price' => 'decimal:2',
        'use_fallback' => 'boolean',
        'min_declared_value' => 'decimal:2',
        'max_declared_value' => 'decimal:2',
        'compatible_services' => 'array',
        'incompatible_addons' => 'array',
        'requires_value_declaration' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // PRICE TYPE CONSTANTS
    // ==========================================

    const PRICE_TYPE_FIXED = 'fixed';
    const PRICE_TYPE_PERCENTAGE = 'percentage';
    const PRICE_TYPE_CARRIER_RATE = 'carrier_rate';

    // ==========================================
    // SOURCE CONSTANTS
    // ==========================================

    const SOURCE_CARRIER_API = 'carrier_api';
    const SOURCE_ADMIN = 'admin';

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Only active addons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by carrier (includes 'all' carrier addons)
     */
    public function scopeForCarrier($query, string $carrierCode)
    {
        return $query->where(function ($q) use ($carrierCode) {
            $q->where('carrier_code', 'all')
                ->orWhere('carrier_code', $carrierCode);
        });
    }

    /**
     * Filter by compatible service
     */
    public function scopeForService($query, string $serviceCode)
    {
        return $query->where(function ($q) use ($serviceCode) {
            $q->whereNull('compatible_services')
                ->orWhereJsonContains('compatible_services', $serviceCode);
        });
    }

    /**
     * Only admin-defined addons
     */
    public function scopeAdminDefined($query)
    {
        return $query->where('source', self::SOURCE_ADMIN);
    }

    /**
     * Only carrier API addons
     */
    public function scopeCarrierDefined($query)
    {
        return $query->where('source', self::SOURCE_CARRIER_API);
    }

    /**
     * Order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Calculate addon price based on type and base amount
     * Note: For carrier_rate type, live pricing is now required.
     * Fallback prices are deprecated - use calculatePriceWithCarrierData() for live pricing flow.
     */
    public function calculatePrice(float $baseAmount = 0, ?float $declaredValue = null): float
    {
        switch ($this->price_type) {
            case self::PRICE_TYPE_FIXED:
                return (float) $this->price_value;

            case self::PRICE_TYPE_PERCENTAGE:
                // For insurance, use declared value; otherwise use base shipping amount
                $amountToCalculate = $this->requires_value_declaration
                    ? ($declaredValue ?? 0)
                    : $baseAmount;
                return $amountToCalculate * ((float) $this->price_value / 100);

            case self::PRICE_TYPE_CARRIER_RATE:
                // No fallback - live pricing required
                // Use CarrierAddonService::getAddonsWithLivePricing() for live rates
                return 0;

            default:
                return 0;
        }
    }

    /**
     * Calculate price using carrier-provided surcharge data when available
     * This is the preferred method for checkout flow with live pricing.
     */
    public function calculatePriceWithCarrierData(
        float $baseAmount = 0,
        ?float $declaredValue = null,
        ?float $carrierProvidedPrice = null
    ): float {
        // For carrier_rate type, prefer live price
        if ($this->price_type === self::PRICE_TYPE_CARRIER_RATE) {
            return $carrierProvidedPrice ?? 0;
        }

        // Delegate to existing logic for fixed/percentage
        return $this->calculatePrice($baseAmount, $declaredValue);
    }

    /**
     * Check if addon is applicable for a declared value
     */
    public function isApplicableForValue(?float $declaredValue): bool
    {
        if (!$this->requires_value_declaration) {
            return true;
        }

        if ($declaredValue === null) {
            return false;
        }

        // Check minimum
        if ($this->min_declared_value !== null && $declaredValue < $this->min_declared_value) {
            return false;
        }

        // Check maximum
        if ($this->max_declared_value !== null && $declaredValue > $this->max_declared_value) {
            return false;
        }

        return true;
    }

    /**
     * Check if addon is compatible with another addon
     */
    public function isCompatibleWith(CarrierAddon $other): bool
    {
        if (empty($this->incompatible_addons)) {
            return true;
        }

        return !in_array($other->addon_code, $this->incompatible_addons);
    }

    /**
     * Get the carrier API code for inclusion in shipment requests
     */
    public function getApiCode(): ?string
    {
        return $this->carrier_api_code ?? $this->addon_code;
    }

    /**
     * Check if this addon's price needs to be fetched from carrier
     * Returns false if fallback pricing is configured and enabled
     */
    public function needsCarrierPricing(): bool
    {
        if ($this->price_type !== self::PRICE_TYPE_CARRIER_RATE) {
            return false;
        }

        // Has fallback configured - doesn't need carrier pricing
        if ($this->use_fallback && $this->fallback_price !== null) {
            return false;
        }

        return true;
    }

    /**
     * Format for frontend display
     */
    public function toFrontendFormat(?float $baseAmount = null, ?float $declaredValue = null): array
    {
        $calculatedPrice = $this->calculatePrice($baseAmount ?? 0, $declaredValue);
        $isUsingFallback = $this->price_type === self::PRICE_TYPE_CARRIER_RATE
            && $this->use_fallback
            && $this->fallback_price !== null;

        return [
            'id' => $this->id,
            'addon_code' => $this->addon_code,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'icon' => $this->icon,
            'price_type' => $this->price_type,
            'price_value' => $this->price_value,
            'fallback_price' => $this->fallback_price,
            'use_fallback' => $this->use_fallback,
            'is_using_fallback' => $isUsingFallback,
            'price_display' => $this->getPriceDisplay($calculatedPrice),
            'calculated_price' => round($calculatedPrice, 2),
            'currency' => $this->currency,
            'requires_value_declaration' => $this->requires_value_declaration,
            'needs_carrier_pricing' => $this->needsCarrierPricing(),
            'carrier_code' => $this->carrier_code,
            'source' => $this->source,
        ];
    }

    /**
     * Get human-readable price display
     */
    public function getPriceDisplay(float $calculatedPrice = 0): string
    {
        switch ($this->price_type) {
            case self::PRICE_TYPE_FIXED:
                return '$' . number_format((float) $this->price_value, 2);

            case self::PRICE_TYPE_PERCENTAGE:
                return number_format((float) $this->price_value, 1) . '%';

            case self::PRICE_TYPE_CARRIER_RATE:
                return 'Varies';

            default:
                return '$' . number_format($calculatedPrice, 2);
        }
    }

    // ==========================================
    // STATIC HELPERS
    // ==========================================

    /**
     * Get all addons available for a carrier service
     */
    public static function getForService(CarrierService $service): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forCarrier($service->carrier_code)
            ->forService($service->service_code)
            ->ordered()
            ->get();
    }
}
