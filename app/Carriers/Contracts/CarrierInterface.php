<?php

namespace App\Carriers\Contracts;

use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\DTOs\ShipmentResponse;
use App\Carriers\DTOs\RateResponse;
use App\Carriers\DTOs\TrackingResponse;
use App\Carriers\DTOs\LabelResponse;

/**
 * CarrierInterface - Strategy Pattern for multi-carrier shipping integration
 * 
 * Implemented by: FedExCarrier, DHLCarrier, UPSCarrier
 */
interface CarrierInterface
{
    /**
     * Get the carrier name/identifier
     */
    public function getName(): string;

    /**
     * Authenticate with the carrier API
     * @return bool True if authentication successful
     * @throws \App\Carriers\Exceptions\CarrierAuthException
     */
    public function authenticate(): bool;

    /**
     * Check if currently authenticated (has valid token)
     */
    public function isAuthenticated(): bool;

    /**
     * Get shipping rates for a shipment request
     * @param ShipmentRequest $request
     * @return RateResponse[]
     * @throws \App\Carriers\Exceptions\CarrierException
     */
    public function getRates(ShipmentRequest $request): array;

    /**
     * Create a shipment and generate labels
     * @param ShipmentRequest $request
     * @param array|null $packageModels Optional array of Package models (for DHL invoice retrieval)
     * @return ShipmentResponse Contains tracking number, label data
     * @throws \App\Carriers\Exceptions\CarrierException
     */
    public function createShipment(ShipmentRequest $request, ?array $packageModels = null): ShipmentResponse;

    /**
     * Get shipping label for an existing shipment
     * @param string $trackingNumber
     * @return LabelResponse
     * @throws \App\Carriers\Exceptions\CarrierException
     */
    public function getLabel(string $trackingNumber): LabelResponse;

    /**
     * Track a shipment by tracking number
     * @param string $trackingNumber
     * @return TrackingResponse
     * @throws \App\Carriers\Exceptions\CarrierException
     */
    public function track(string $trackingNumber): TrackingResponse;

    /**
     * Cancel/void a shipment
     * @param string $trackingNumber
     * @return bool True if cancellation successful
     * @throws \App\Carriers\Exceptions\CarrierException
     */
    public function cancelShipment(string $trackingNumber): bool;

    /**
     * Validate an address with the carrier
     * @param array $address
     * @return array Validated/corrected address
     */
    public function validateAddress(array $address): array;
}
