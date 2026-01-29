<?php

namespace App\Carriers\DTOs;

/**
 * PackageDetail DTO - Individual package dimensions and weight
 */
class PackageDetail
{
    public function __construct(
        public readonly float $weight,
        public readonly string $weightUnit = 'LB', // LB, KG
        public readonly float $length = 0,
        public readonly float $width = 0,
        public readonly float $height = 0,
        public readonly string $dimensionUnit = 'IN', // IN, CM
        public readonly ?float $declaredValue = null,
        public readonly ?string $reference = null,
        public readonly ?string $typeCode = '2BP', // DHL package type: YP=Your Packaging, 2BP=Small Box, 2BC=Box
    ) {
    }

    /**
     * Calculate volumetric weight using carrier formula
     * FedEx/UPS: L×W×H / 139 (inches) or / 5000 (cm)
     * DHL: L×W×H / 5000 (cm)
     */
    public function getVolumetricWeight(): float
    {
        if ($this->length <= 0 || $this->width <= 0 || $this->height <= 0) {
            return 0;
        }

        $cubicSize = $this->length * $this->width * $this->height;
        $divisor = $this->dimensionUnit === 'IN' ? 139 : 5000;

        return round($cubicSize / $divisor, 2);
    }

    /**
     * Get billable weight (greater of actual or volumetric)
     */
    public function getBillableWeight(): float
    {
        return max($this->weight, $this->getVolumetricWeight());
    }

    /**
     * Convert to array for API requests
     */
    public function toArray(): array
    {
        return [
            'weight' => $this->weight,
            'weight_unit' => $this->weightUnit,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'dimension_unit' => $this->dimensionUnit,
            'declared_value' => $this->declaredValue,
        ];
    }
}
