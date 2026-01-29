<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CarrierService - Database-driven carrier service configuration
 * 
 * Replaces hardcoded InternationalShippingOptions constants with 
 * flexible, API-friendly service definitions.
 */
class CarrierService extends Model
{
    protected $fillable = [
        'carrier_code',
        'service_code',
        'display_name',
        'description',
        'logo_url',
        'is_international',
        'is_domestic',
        'transit_time_min',
        'transit_time_max',
        'max_weight_kg',
        'max_weight_lb',
        // Dimension limits (new)
        'max_length_in',
        'max_length_cm',
        'max_girth_in',
        'max_girth_cm',
        'max_declared_value',
        // Shipping restrictions (new)
        'accepts_dangerous_goods',
        'accepts_lithium_batteries',
        'accepts_fragile',
        // Freight identification (new)
        'is_freight',
        'min_weight_lb',
        'min_weight_kg',
        // Geographic
        'supported_countries',
        'excluded_countries',
        'supported_origin_countries',
        'fallback_base_rate',
        'fallback_per_lb_rate',
        'fallback_pricing_rules',
        'is_active',
        'is_default',
        'sort_order',
        'carrier_specific_options',
    ];

    protected $casts = [
        'is_international' => 'boolean',
        'is_domestic' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_freight' => 'boolean',
        'accepts_dangerous_goods' => 'boolean',
        'accepts_lithium_batteries' => 'boolean',
        'accepts_fragile' => 'boolean',
        'supported_countries' => 'array',
        'excluded_countries' => 'array',
        'supported_origin_countries' => 'array',
        'fallback_pricing_rules' => 'array',
        'carrier_specific_options' => 'array',
        'max_weight_kg' => 'decimal:2',
        'max_weight_lb' => 'decimal:2',
        'min_weight_lb' => 'decimal:2',
        'min_weight_kg' => 'decimal:2',
        'max_length_in' => 'decimal:2',
        'max_length_cm' => 'decimal:2',
        'max_girth_in' => 'decimal:2',
        'max_girth_cm' => 'decimal:2',
        'max_declared_value' => 'decimal:2',
        'fallback_base_rate' => 'decimal:2',
        'fallback_per_lb_rate' => 'decimal:2',
    ];


    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Only active services
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Only international services
     */
    public function scopeInternational($query)
    {
        return $query->where('is_international', true);
    }

    /**
     * Only domestic services
     */
    public function scopeDomestic($query)
    {
        return $query->where('is_domestic', true);
    }

    /**
     * Filter by carrier
     */
    public function scopeByCarrier($query, string $carrierCode)
    {
        return $query->where('carrier_code', $carrierCode);
    }

    /**
     * Get default service(s)
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    /**
     * Only freight services
     */
    public function scopeFreight($query)
    {
        return $query->where('is_freight', true);
    }

    /**
     * Only parcel (non-freight) services
     */
    public function scopeParcel($query)
    {
        return $query->where('is_freight', false);
    }

    /**
     * Only services that accept dangerous goods
     */
    public function scopeAcceptsDangerousGoods($query)
    {
        return $query->where('accepts_dangerous_goods', true);
    }


    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get shipments using this service
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Ship::class, 'carrier_service_id');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get the API service code for carrier integration
     */
    public function getApiServiceCode(): string
    {
        return $this->service_code;
    }

    /**
     * Check if service is available for a destination country
     */
    public function isAvailableFor(string $countryCode): bool
    {
        $countryCode = strtoupper($countryCode);

        // Check if explicitly excluded
        if (!empty($this->excluded_countries)) {
            if (in_array($countryCode, $this->excluded_countries)) {
                return false;
            }
        }

        // If supported_countries is null, service is available everywhere (minus exclusions)
        if (empty($this->supported_countries)) {
            return true;
        }

        // Check if explicitly supported
        return in_array($countryCode, $this->supported_countries);
    }

    /**
     * Check if weight is within service limits
     */
    public function isWeightAllowed(float $weight, string $unit = 'lb'): bool
    {
        $maxWeight = $unit === 'kg' ? $this->max_weight_kg : $this->max_weight_lb;

        if ($maxWeight === null) {
            return true; // No limit set
        }

        return $weight <= $maxWeight;
    }

    /**
     * Get estimated delivery date range
     */
    public function getDeliveryEstimate(): string
    {
        if ($this->transit_time_min && $this->transit_time_max) {
            return "{$this->transit_time_min}-{$this->transit_time_max} business days";
        }

        if ($this->transit_time_min) {
            return "{$this->transit_time_min}+ business days";
        }

        return 'Varies by destination';
    }

    /**
     * Calculate fallback price for a given weight
     * 
     * Uses either simple base + per-lb calculation or complex rules from fallback_pricing_rules
     */
    public function calculateFallbackPrice(float $weight, string $unit = 'lb'): float
    {
        // Convert to lbs if needed
        if ($unit === 'kg') {
            $weight = $weight * 2.20462;
        }

        // Simple pricing: base rate + per lb rate
        if ($this->fallback_base_rate !== null) {
            $baseRate = (float) $this->fallback_base_rate;
            $perLbRate = (float) ($this->fallback_per_lb_rate ?? 0);
            return $baseRate + ($perLbRate * $weight);
        }

        // Complex pricing from rules JSON
        if (!empty($this->fallback_pricing_rules)) {
            $rules = $this->fallback_pricing_rules;

            // Check weight brackets
            if (isset($rules['weight_brackets'])) {
                foreach ($rules['weight_brackets'] as $bracket) {
                    $min = $bracket['min_weight'] ?? 0;
                    $max = $bracket['max_weight'] ?? PHP_FLOAT_MAX;

                    if ($weight >= $min && $weight <= $max) {
                        $base = $bracket['base_rate'] ?? 0;
                        $perLb = $bracket['per_lb_rate'] ?? 0;
                        return $base + ($perLb * $weight);
                    }
                }
            }

            // Simple rules format
            if (isset($rules['base_rate'])) {
                $base = (float) $rules['base_rate'];
                $perLb = (float) ($rules['per_lb_rate'] ?? 0);
                return $base + ($perLb * $weight);
            }
        }

        // Default fallback based on carrier
        return match ($this->carrier_code) {
            'fedex' => 25.00 + ($weight * 3.50),
            'dhl' => 30.00 + ($weight * 4.00),
            'ups' => 28.00 + ($weight * 3.75),
            default => 20.00 + ($weight * 3.00),
        };
    }

    /**
     * Get compatible addons for this service
     */
    public function getCompatibleAddons()
    {
        return CarrierAddon::active()
            ->where(function ($query) {
                $query->where('carrier_code', 'all')
                    ->orWhere('carrier_code', $this->carrier_code);
            })
            ->where(function ($query) {
                $query->whereNull('compatible_services')
                    ->orWhereJsonContains('compatible_services', $this->service_code);
            })
            ->ordered()
            ->get();
    }

    /**
     * Format for frontend display
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'carrier_code' => $this->carrier_code,
            'service_code' => $this->service_code,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'delivery_estimate' => $this->getDeliveryEstimate(),
            'is_international' => $this->is_international,
            'is_domestic' => $this->is_domestic,
            'is_default' => $this->is_default,
        ];
    }

    // ==========================================
    // STATIC HELPERS
    // ==========================================

    /**
     * Get auto-selected service for a route
     */
    public static function autoSelectForRoute(string $originCountry, string $destCountry): ?self
    {
        $isInternational = strtoupper($originCountry) !== strtoupper($destCountry);

        return static::active()
            ->when($isInternational, fn($q) => $q->international())
            ->when(!$isInternational, fn($q) => $q->domestic())
            ->default()
            ->ordered()
            ->first();
    }

    /**
     * Get all services available for a route
     */
    public static function getForRoute(string $originCountry, string $destCountry): \Illuminate\Database\Eloquent\Collection
    {
        $isInternational = strtoupper($originCountry) !== strtoupper($destCountry);

        return static::active()
            ->when($isInternational, fn($q) => $q->international())
            ->when(!$isInternational, fn($q) => $q->domestic())
            ->ordered()
            ->get()
            ->filter(fn($service) => $service->isAvailableFor($destCountry));
    }
}
