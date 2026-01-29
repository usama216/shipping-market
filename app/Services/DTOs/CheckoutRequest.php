<?php

namespace App\Services\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for checkout request
 * Encapsulates all data needed for shipment checkout
 * 
 * Enhanced to support carrier services and addons from the consolidated module
 */
readonly class CheckoutRequest
{
    public function __construct(
        public int $shipId,
        public int $cardId,
        public int $customerAddressId,
        public float $estimatedShippingCharges,
        public ?int $internationalShippingOptionId,
        public array $packingOptionIds,
        public array $shippingPreferenceOptionIds,
        public int $loyaltyPointsUsed = 0,
        public float $loyaltyDiscount = 0.0,
        // Coupon fields (optional)
        public ?string $couponCode = null,
        public ?float $couponOrderAmount = null,
        public ?float $couponDiscount = null,
        // New carrier service consolidation fields
        public ?int $carrierServiceId = null,
        public array $selectedAddonIds = [],
        public ?float $addonCharges = null,
        public ?float $declaredValue = null,
        public ?string $declaredValueCurrency = 'USD',
    ) {
    }

    /**
     * Create from HTTP request
     */
    public static function fromRequest(Request $request): self
    {
        // Handle addon charges - calculate from addon IDs if not provided
        $addonCharges = $request->input('addon_charges');
        $selectedAddonIds = (array) $request->input('selected_addon_ids', []);

        // If addon charges not provided but addon IDs are, we'll let the service calculate
        if ($addonCharges === null && !empty($selectedAddonIds)) {
            $addonCharges = 0; // Will be calculated in CheckoutService
        }

        return new self(
            shipId: (int) $request->input('id'),
            cardId: (int) $request->input('card_id'),
            customerAddressId: (int) $request->input('customer_address_id'),
            estimatedShippingCharges: (float) $request->input('estimated_shipping_charges', 0),
            internationalShippingOptionId: $request->input('international_shipping_option_id')
            ? (int) $request->input('international_shipping_option_id')
            : null,
            packingOptionIds: (array) $request->input('packing_option_ids', []),
            shippingPreferenceOptionIds: (array) $request->input('shipping_preference_option_ids', []),
            loyaltyPointsUsed: (int) $request->input('loyalty_points_used', 0),
            loyaltyDiscount: (float) $request->input('loyalty_discount', 0),
            couponCode: $request->filled('coupon_code') ? (string) $request->input('coupon_code') : null,
            couponOrderAmount: $request->filled('coupon_order_amount') ? (float) $request->input('coupon_order_amount') : null,
            couponDiscount: $request->filled('coupon_discount') ? (float) $request->input('coupon_discount') : null,
            // New fields
            carrierServiceId: $request->input('carrier_service_id')
            ? (int) $request->input('carrier_service_id')
            : null,
            selectedAddonIds: $selectedAddonIds,
            addonCharges: $addonCharges !== null ? (float) $addonCharges : null,
            declaredValue: $request->input('declared_value')
            ? (float) $request->input('declared_value')
            : null,
            declaredValueCurrency: $request->input('declared_value_currency', 'USD'),
        );
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shipId: (int) ($data['id'] ?? 0),
            cardId: (int) ($data['card_id'] ?? 0),
            customerAddressId: (int) ($data['customer_address_id'] ?? 0),
            estimatedShippingCharges: (float) ($data['estimated_shipping_charges'] ?? 0),
            internationalShippingOptionId: isset($data['international_shipping_option_id'])
            ? (int) $data['international_shipping_option_id']
            : null,
            packingOptionIds: (array) ($data['packing_option_ids'] ?? []),
            shippingPreferenceOptionIds: (array) ($data['shipping_preference_option_ids'] ?? []),
            loyaltyPointsUsed: (int) ($data['loyalty_points_used'] ?? 0),
            loyaltyDiscount: (float) ($data['loyalty_discount'] ?? 0),
            couponCode: isset($data['coupon_code']) ? (string) $data['coupon_code'] : null,
            couponOrderAmount: isset($data['coupon_order_amount']) ? (float) $data['coupon_order_amount'] : null,
            couponDiscount: isset($data['coupon_discount']) ? (float) $data['coupon_discount'] : null,
            carrierServiceId: isset($data['carrier_service_id'])
            ? (int) $data['carrier_service_id']
            : null,
            selectedAddonIds: (array) ($data['selected_addon_ids'] ?? []),
            addonCharges: isset($data['addon_charges'])
            ? (float) $data['addon_charges']
            : null,
            declaredValue: isset($data['declared_value'])
            ? (float) $data['declared_value']
            : null,
            declaredValueCurrency: $data['declared_value_currency'] ?? 'USD',
        );
    }

    /**
     * Calculate final total after loyalty discount and addons
     */
    public function getFinalTotal(): float
    {
        $total = $this->estimatedShippingCharges + ($this->addonCharges ?? 0);
        return max(0, $total - $this->loyaltyDiscount);
    }

    /**
     * Check if using loyalty discount
     */
    public function hasLoyaltyDiscount(): bool
    {
        return $this->loyaltyPointsUsed > 0 && $this->loyaltyDiscount > 0;
    }

    /**
     * Check if using new carrier service system
     */
    public function hasCarrierService(): bool
    {
        return $this->carrierServiceId !== null;
    }

    /**
     * Check if any addons are selected
     */
    public function hasAddons(): bool
    {
        return !empty($this->selectedAddonIds);
    }

    /**
     * Get effective carrier option ID (prefers new carrier_service_id)
     */
    public function getEffectiveCarrierId(): ?int
    {
        return $this->carrierServiceId ?? $this->internationalShippingOptionId;
    }
}
