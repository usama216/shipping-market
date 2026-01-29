<?php

namespace App\Carriers\DTOs;

/**
 * CommodityDetail DTO - Individual item for customs declarations
 */
class CommodityDetail
{
    public function __construct(
        public readonly string $description,
        public readonly int $quantity,
        public readonly float $unitValue,
        public readonly float $totalValue,
        public readonly float $weight,
        public readonly string $weightUnit = 'LB',
        public readonly ?string $hsCode = null,
        public readonly string $countryOfOrigin = 'US',
        public readonly ?string $material = null,
        public readonly ?string $manufacturer = null,
        // Export compliance fields for DHL
        public readonly ?float $netWeight = null,              // Net weight (without packaging)
        public readonly ?string $exportReasonType = 'permanent', // permanent, temporary, return
        public readonly ?string $exportControlNumber = null,   // EAR99, ECCN codes
    ) {
    }

    /**
     * Convert to array for API requests
     */
    public function toArray(): array
    {
        return array_filter([
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_value' => $this->unitValue,
            'total_value' => $this->totalValue,
            'weight' => $this->weight,
            'weight_unit' => $this->weightUnit,
            'hs_code' => $this->hsCode,
            'country_of_origin' => $this->countryOfOrigin,
            'material' => $this->material,
            'manufacturer' => $this->manufacturer,
        ]);
    }
}
