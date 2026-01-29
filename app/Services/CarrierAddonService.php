<?php

namespace App\Services;

use App\Models\CarrierAddon;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\CarrierCommissionSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * CarrierAddonService - Manages addon pricing with live carrier data
 * 
 * Responsibilities:
 * 1. Match FedEx surcharges to CarrierAddon records using addon_code
 * 2. Determine mandatory addons based on item classifications
 * 3. Enrich addon pricing from live carrier rate responses
 */
class CarrierAddonService
{
    /**
     * Map item classifications to required addon codes
     * When items have these flags, the corresponding addons are mandatory
     */
    private const CLASSIFICATION_TO_ADDON = [
        'is_dangerous' => 'dangerous_goods',
        'is_fragile' => 'extra_handling',      // Maps to SPECIAL_HANDLING surcharge
        'is_oversized' => 'additional_handling', // Maps to ADDITIONAL_HANDLING surcharge
    ];

    /**
     * Get available addons for a carrier with live pricing from rate response
     * 
     * @param string $carrierCode Carrier code (fedex, dhl, ups)
     * @param array $surchargeBreakdown Surcharge breakdown from RateResponse
     * @param float $baseShippingRate Base shipping cost for percentage calculations
     * @param array $packageIds Optional package IDs to determine mandatory addons
     * @return array Enriched addon data for frontend
     */
    public function getAddonsWithLivePricing(
        string $carrierCode,
        array $surchargeBreakdown = [],
        float $baseShippingRate = 0,
        array $packageIds = []
    ): array {
        // Get all active addons for this carrier
        $addons = CarrierAddon::active()
            ->forCarrier($carrierCode)
            ->ordered()
            ->get();

        // Build surcharge lookup by addon_code
        $surchargeMap = $this->buildSurchargeMap($surchargeBreakdown);

        // Determine mandatory addons from item classifications
        $mandatoryAddonCodes = $this->getMandatoryAddonCodes($packageIds);

        Log::channel('carrier')->info('[CarrierAddonService] Processing addons', [
            'carrier' => $carrierCode,
            'addon_count' => $addons->count(),
            'surcharge_count' => count($surchargeBreakdown),
            'mandatory_addons' => $mandatoryAddonCodes,
        ]);

        return $addons->map(function (CarrierAddon $addon) use ($surchargeMap, $baseShippingRate, $mandatoryAddonCodes) {
            $carrierPrice = $surchargeMap[$addon->addon_code] ?? null;
            $isMandatory = in_array($addon->addon_code, $mandatoryAddonCodes);

            // Determine availability
            // For carrier_rate addons: allow even without live pricing (carrier will charge at shipment creation)
            // For other types: always available
            $isCarrierRateType = $addon->price_type === CarrierAddon::PRICE_TYPE_CARRIER_RATE;
            $hasLivePrice = $carrierPrice !== null;
            // Allow carrier_rate addons even without live pricing - carrier will charge when shipment is created
            $isAvailable = !$isCarrierRateType || $hasLivePrice || $isMandatory;

            // Calculate price - prefer carrier price, no fallback
            $calculatedPrice = $this->calculateAddonPrice($addon, $carrierPrice, $baseShippingRate);

            // Determine unavailable reason
            $unavailableReason = null;
            if (!$isAvailable) {
                $unavailableReason = 'Live pricing not available for this service';
            } elseif ($isCarrierRateType && !$hasLivePrice && $isMandatory) {
                // For mandatory carrier_rate addons without live pricing, show note
                $unavailableReason = 'Pricing will be determined by carrier at shipment creation';
            }

            return [
                'id' => $addon->id,
                'addon_code' => $addon->addon_code,
                'display_name' => $addon->display_name,
                'description' => $addon->description,
                'icon' => $addon->icon,
                'price_type' => $addon->price_type,
                'calculated_price' => round($calculatedPrice, 2),
                'price_display' => $isAvailable
                    ? ($calculatedPrice > 0 
                        ? '$' . number_format($calculatedPrice, 2) 
                        : ($isCarrierRateType && !$hasLivePrice && $isMandatory
                            ? 'Carrier pricing'
                            : 'Included'))
                    : 'Unavailable',
                'currency' => $addon->currency ?? 'USD',
                'is_mandatory' => $isMandatory,
                'is_available' => $isAvailable,
                'is_live_price' => $hasLivePrice,
                'unavailable_reason' => $unavailableReason,
                'requires_value_declaration' => $addon->requires_value_declaration,
                'carrier_code' => $addon->carrier_code,
            ];
        })->toArray();
    }

    /**
     * Build a lookup map from surcharge breakdown by addon_code
     */
    private function buildSurchargeMap(array $surchargeBreakdown): array
    {
        $map = [];
        foreach ($surchargeBreakdown as $surcharge) {
            $addonCode = $surcharge['addon_code'] ?? null;
            if ($addonCode) {
                // If same addon appears multiple times, sum the amounts
                $map[$addonCode] = ($map[$addonCode] ?? 0) + ($surcharge['amount'] ?? 0);
            }
        }
        return $map;
    }

    /**
     * Determine which addons are mandatory based on package item classifications
     * 
     * @param array $packageIds Package IDs to check
     * @return array List of addon_code strings that must be selected
     */
    public function getMandatoryAddonCodes(array $packageIds): array
    {
        if (empty($packageIds)) {
            return [];
        }

        // Get all items from the specified packages
        $items = PackageItem::whereIn('package_id', $packageIds)->get();

        $mandatoryAddons = [];

        // Check each classification type
        foreach (self::CLASSIFICATION_TO_ADDON as $flag => $addonCode) {
            $hasFlag = $items->contains(fn($item) => $item->{$flag} === true);
            if ($hasFlag) {
                $mandatoryAddons[] = $addonCode;
            }
        }

        return array_unique($mandatoryAddons);
    }

    /**
     * Calculate addon price using live carrier data when available
     * No fallback - returns 0 if carrier rate unavailable
     */
    private function calculateAddonPrice(
        CarrierAddon $addon,
        ?float $carrierPrice,
        float $baseShippingRate
    ): float {
        switch ($addon->price_type) {
            case CarrierAddon::PRICE_TYPE_FIXED:
                return (float) $addon->price_value;

            case CarrierAddon::PRICE_TYPE_PERCENTAGE:
                return $baseShippingRate * ((float) $addon->price_value / 100);

            case CarrierAddon::PRICE_TYPE_CARRIER_RATE:
                // Use live carrier price - no fallback
                return $carrierPrice ?? 0;

            default:
                return 0;
        }
    }

    /**
     * Validate that all mandatory addons are selected
     * 
     * @param array $selectedAddonIds IDs of addons selected by user
     * @param array $packageIds Package IDs being shipped
     * @return array Validation result with 'valid' boolean and 'missing' addon codes
     */
    public function validateMandatoryAddons(array $selectedAddonIds, array $packageIds): array
    {
        $mandatoryAddonCodes = $this->getMandatoryAddonCodes($packageIds);

        if (empty($mandatoryAddonCodes)) {
            return ['valid' => true, 'missing' => []];
        }

        // Get the addon codes for selected addons
        $selectedAddons = CarrierAddon::whereIn('id', $selectedAddonIds)->pluck('addon_code')->toArray();

        $missing = array_diff($mandatoryAddonCodes, $selectedAddons);

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Validate checkout eligibility - checks if mandatory addons are available
     * Use this before allowing checkout to prevent blocked shipments
     * 
     * @param string $carrierCode Carrier code (fedex, dhl, ups)
     * @param array $surchargeBreakdown Surcharge breakdown from carrier rate response
     * @param array $packageIds Package IDs being shipped
     * @return array ['eligible' => bool, 'errors' => string[], 'blocked_addons' => array]
     */
    public function validateCheckoutEligibility(
        string $carrierCode,
        array $surchargeBreakdown,
        array $packageIds
    ): array {
        $mandatoryAddonCodes = $this->getMandatoryAddonCodes($packageIds);

        if (empty($mandatoryAddonCodes)) {
            return ['eligible' => true, 'errors' => [], 'blocked_addons' => []];
        }

        // Build surcharge lookup
        $surchargeMap = $this->buildSurchargeMap($surchargeBreakdown);

        // Get mandatory addons and check their availability
        $mandatoryAddons = CarrierAddon::active()
            ->forCarrier($carrierCode)
            ->whereIn('addon_code', $mandatoryAddonCodes)
            ->get();

        $errors = [];
        $blockedAddons = [];

        // Human-readable names for classifications
        $classificationNames = [
            'dangerous_goods' => 'dangerous goods',
            'extra_handling' => 'fragile items',
            'additional_handling' => 'oversized items',
        ];

        foreach ($mandatoryAddons as $addon) {
            $isCarrierRateType = $addon->price_type === CarrierAddon::PRICE_TYPE_CARRIER_RATE;
            $hasLivePrice = isset($surchargeMap[$addon->addon_code]);

            // If carrier_rate type without live pricing, allow shipment to proceed
            // The carrier will charge for these addons when the shipment is created
            // This is common for FedEx/DHL which don't always return surcharge pricing
            // in rate responses, but will charge for them during shipment creation
            if ($isCarrierRateType && !$hasLivePrice) {
                Log::info('Mandatory addon will use carrier pricing at shipment creation', [
                    'carrier' => $carrierCode,
                    'addon_code' => $addon->addon_code,
                    'addon_id' => $addon->id,
                    'addon_name' => $addon->display_name,
                    'note' => 'Carrier will charge for this addon when shipment is created',
                ]);
                
                // Don't block the shipment - allow it to proceed
                // The addon will be included in the shipment request and carrier will charge accordingly
            }
        }

        return [
            'eligible' => empty($errors),
            'errors' => $errors,
            'blocked_addons' => $blockedAddons,
        ];
    }

    /**
     * Get carrier display name for friendly messages
     */
    private function getCarrierDisplayName(string $carrierCode): string
    {
        return match (strtolower($carrierCode)) {
            'fedex' => 'FedEx',
            'dhl' => 'DHL Express',
            'ups' => 'UPS',
            default => ucfirst($carrierCode),
        };
    }

    /**
     * Auto-select mandatory addons and merge with user selections
     * 
     * @param array $userSelectedIds User-selected addon IDs
     * @param array $packageIds Package IDs
     * @param string $carrierCode Carrier code
     * @return array Complete list of addon IDs (user + mandatory)
     */
    public function mergeWithMandatoryAddons(
        array $userSelectedIds,
        array $packageIds,
        string $carrierCode
    ): array {
        $mandatoryAddonCodes = $this->getMandatoryAddonCodes($packageIds);

        if (empty($mandatoryAddonCodes)) {
            return $userSelectedIds;
        }

        // Get IDs for mandatory addons
        $mandatoryAddonIds = CarrierAddon::active()
            ->forCarrier($carrierCode)
            ->whereIn('addon_code', $mandatoryAddonCodes)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($userSelectedIds, $mandatoryAddonIds));
    }

    /**
     * Get classification summary for packages
     * Useful for displaying why certain addons are mandatory
     */
    public function getClassificationSummary(array $packageIds): array
    {
        if (empty($packageIds)) {
            return [];
        }

        $items = PackageItem::whereIn('package_id', $packageIds)->get();

        return [
            'has_dangerous' => $items->contains('is_dangerous', true),
            'has_fragile' => $items->contains('is_fragile', true),
            'has_oversized' => $items->contains('is_oversized', true),
            'dangerous_items' => $items->where('is_dangerous', true)->pluck('title')->toArray(),
            'fragile_items' => $items->where('is_fragile', true)->pluck('title')->toArray(),
            'oversized_items' => $items->where('is_oversized', true)->pluck('title')->toArray(),
        ];
    }

    /**
     * Calculate classification charges for packages based on item flags
     * 
     * @param array $packageIds Package IDs to check
     * @return array Contains 'total', 'breakdown', and 'item_counts'
     */
    public function calculateClassificationCharges(array $packageIds): array
    {
        if (empty($packageIds)) {
            return [
                'total' => 0.00,
                'breakdown' => [
                    'dangerous' => 0.00,
                    'fragile' => 0.00,
                    'oversized' => 0.00,
                ],
                'item_counts' => [
                    'dangerous' => 0,
                    'fragile' => 0,
                    'oversized' => 0,
                ],
            ];
        }

        // Get all items from the specified packages
        $items = PackageItem::whereIn('package_id', $packageIds)->get();

        // Get commission settings for charge rates
        $settings = CarrierCommissionSetting::getCurrent();

        // Count items by classification
        $dangerousCount = $items->where('is_dangerous', true)->count();
        $fragileCount = $items->where('is_fragile', true)->count();
        $oversizedCount = $items->where('is_oversized', true)->count();

        // Calculate charges (per item)
        $dangerousCharge = $dangerousCount * (float) $settings->dangerous_goods_charge;
        $fragileCharge = $fragileCount * (float) $settings->fragile_item_charge;
        $oversizedCharge = $oversizedCount * (float) $settings->oversized_item_charge;

        $total = $dangerousCharge + $fragileCharge + $oversizedCharge;

        return [
            'total' => round($total, 2),
            'breakdown' => [
                'dangerous' => round($dangerousCharge, 2),
                'fragile' => round($fragileCharge, 2),
                'oversized' => round($oversizedCharge, 2),
            ],
            'item_counts' => [
                'dangerous' => $dangerousCount,
                'fragile' => $fragileCount,
                'oversized' => $oversizedCount,
            ],
        ];
    }
}
