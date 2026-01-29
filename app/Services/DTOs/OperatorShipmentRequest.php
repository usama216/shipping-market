<?php

namespace App\Services\DTOs;

use Illuminate\Http\Request;

/**
 * DTO for operator-initiated shipment creation
 */
class OperatorShipmentRequest
{
    public function __construct(
        public int $customerId,
        public array $packageIds,
        public int $customerAddressId,
        public int $carrierServiceId, // Required - no longer nullable
        public ?array $selectedAddonIds = null,
        public ?float $declaredValue = null,
        public float $estimatedShippingCharges = 0.0,
        public ?string $eeiCode = null,
        public bool $eeiRequired = false,
        public ?string $eeiExemptionReason = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $carrierServiceId = $request->input('carrier_service_id');
        
        if (!$carrierServiceId) {
            throw new \Exception('Carrier service is required. Please select a carrier service (e.g., DHL Express Worldwide).');
        }

        return new self(
            customerId: (int) $request->input('customer_id'),
            packageIds: $request->input('package_ids', []),
            customerAddressId: (int) $request->input('customer_address_id'),
            carrierServiceId: (int) $carrierServiceId,
            selectedAddonIds: $request->input('selected_addon_ids', []),
            declaredValue: $request->filled('declared_value') ? (float) $request->input('declared_value') : null,
            estimatedShippingCharges: (float) $request->input('estimated_shipping_charges', 0),
            eeiCode: $request->input('eei_code'),
            eeiRequired: $request->boolean('eei_required', false),
            eeiExemptionReason: $request->input('eei_exemption_reason')
        );
    }
}
