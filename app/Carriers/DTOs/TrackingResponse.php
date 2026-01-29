<?php

namespace App\Carriers\DTOs;

/**
 * TrackingResponse DTO - Shipment tracking information
 */
class TrackingResponse
{
    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $status,
        public readonly string $statusDescription,
        public readonly ?string $estimatedDelivery = null,
        public readonly ?string $actualDelivery = null,
        public readonly ?string $signedBy = null,
        public readonly array $events = [], // TrackingEvent[]
        public readonly ?string $currentLocation = null,
        public readonly array $rawResponse = [],
    ) {
    }

    /**
     * Common status normalization
     */
    public static function normalizeStatus(string $carrierStatus): string
    {
        $statusMap = [
            // FedEx statuses
            'PU' => 'picked_up',
            'IT' => 'in_transit',
            'OD' => 'out_for_delivery',
            'DL' => 'delivered',
            'DE' => 'exception',
            'CA' => 'cancelled',

            // DHL statuses
            'picked up' => 'picked_up',
            'in transit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'exception' => 'exception',

            // Generic
            'PICKED_UP' => 'picked_up',
            'IN_TRANSIT' => 'in_transit',
            'OUT_FOR_DELIVERY' => 'out_for_delivery',
            'DELIVERED' => 'delivered',
        ];

        return $statusMap[strtoupper($carrierStatus)] ?? $statusMap[strtolower($carrierStatus)] ?? 'unknown';
    }

    /**
     * Check if package has been delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered' || $this->actualDelivery !== null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status,
            'status_description' => $this->statusDescription,
            'estimated_delivery' => $this->estimatedDelivery,
            'actual_delivery' => $this->actualDelivery,
            'signed_by' => $this->signedBy,
            'current_location' => $this->currentLocation,
            'events' => $this->events,
        ];
    }
}
