<?php

namespace App\Carriers\DTOs;

/**
 * ShipmentResponse DTO - Unified response from carrier after shipment creation
 */
class ShipmentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $carrierReference = null,
        public readonly ?string $labelUrl = null,
        public readonly ?string $labelData = null, // Base64 encoded PDF/PNG
        public readonly ?string $labelFormat = 'PDF',
        public readonly ?float $totalCharge = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $serviceType = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly array $rawResponse = [],
        public readonly ?string $errorMessage = null,
        public readonly array $errors = [],
    ) {
    }

    /**
     * Create a success response
     */
    public static function success(
        string $trackingNumber,
        ?string $labelUrl = null,
        ?string $labelData = null,
        array $rawResponse = [],
        ?float $totalCharge = null,
        ?string $estimatedDelivery = null,
    ): self {
        return new self(
            success: true,
            trackingNumber: $trackingNumber,
            labelUrl: $labelUrl,
            labelData: $labelData,
            totalCharge: $totalCharge,
            rawResponse: $rawResponse,
            estimatedDelivery: $estimatedDelivery,
        );
    }

    /**
     * Create a failure response
     */
    public static function failure(string $message, array $errors = [], array $rawResponse = []): self
    {
        return new self(
            success: false,
            errorMessage: $message,
            errors: $errors,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'tracking_number' => $this->trackingNumber,
            'carrier_reference' => $this->carrierReference,
            'label_url' => $this->labelUrl,
            'label_format' => $this->labelFormat,
            'total_charge' => $this->totalCharge,
            'currency' => $this->currency,
            'service_type' => $this->serviceType,
            'estimated_delivery' => $this->estimatedDelivery,
            'error_message' => $this->errorMessage,
            'errors' => $this->errors,
        ];
    }
}
