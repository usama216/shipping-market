<?php

namespace App\Services;

use App\Models\Ship;
use App\Models\ShipmentEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    /**
     * Record a tracking event for a shipment
     */
    public function recordEvent(
        Ship $ship,
        string $status,
        ?string $description = null,
        ?string $location = null,
        string $source = 'system',
        ?\DateTime $eventTime = null,
        ?array $rawData = null
    ): ShipmentEvent {
        return ShipmentEvent::create([
            'ship_id' => $ship->id,
            'status' => $status,
            'description' => $description,
            'location' => $location,
            'source' => $source,
            'event_time' => $eventTime ?? now(),
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Get tracking timeline for a shipment
     */
    public function getTrackingTimeline(Ship $ship): array
    {
        $events = $ship->trackingEvents()
            ->orderBy('event_time', 'desc')
            ->get();

        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'status' => $event->status,
                'status_label' => $event->status_label,
                'description' => $event->description,
                'location' => $event->location,
                'source' => $event->source,
                'event_time' => $event->event_time?->format('Y-m-d H:i:s'),
                'event_time_formatted' => $event->event_time?->format('M d, Y h:i A'),
            ];
        })->toArray();
    }

    /**
     * Fetch live tracking status from carrier API
     */
    public function fetchLiveStatus(Ship $ship): ?array
    {
        if (!$ship->carrier_tracking_number || !$ship->carrier_name) {
            return null;
        }

        try {
            switch ($ship->carrier_name) {
                case 'fedex':
                    return $this->fetchFedExStatus($ship);
                case 'dhl':
                    return $this->fetchDHLStatus($ship);
                case 'ups':
                    return $this->fetchUPSStatus($ship);
                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Tracking fetch failed', [
                'ship_id' => $ship->id,
                'carrier' => $ship->carrier_name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fetch FedEx tracking status
     */
    protected function fetchFedExStatus(Ship $ship): ?array
    {
        $config = config('carriers.fedex');

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            Log::warning('FedEx API credentials not configured');
            return null;
        }

        try {
            // Get OAuth token
            $tokenResponse = Http::asForm()->post($config['base_url'] . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('FedEx OAuth failed', ['response' => $tokenResponse->body()]);
                return null;
            }

            $token = $tokenResponse->json('access_token');

            // Fetch tracking
            $trackingResponse = Http::withToken($token)
                ->withHeaders(['X-locale' => 'en_US'])
                ->post($config['base_url'] . '/track/v1/trackingnumbers', [
                    'trackingInfo' => [
                        [
                            'trackingNumberInfo' => [
                                'trackingNumber' => $ship->carrier_tracking_number
                            ]
                        ]
                    ],
                    'includeDetailedScans' => true
                ]);

            if ($trackingResponse->successful()) {
                $data = $trackingResponse->json();
                $this->processFedExEvents($ship, $data);
                return $data;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FedEx tracking error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Process FedEx tracking events and store them
     */
    protected function processFedExEvents(Ship $ship, array $data): void
    {
        $trackResults = $data['output']['completeTrackResults'][0]['trackResults'][0] ?? null;

        if (!$trackResults) {
            return;
        }

        $scanEvents = $trackResults['scanEvents'] ?? [];

        foreach ($scanEvents as $event) {
            $eventTime = isset($event['date'])
                ? \Carbon\Carbon::parse($event['date'])
                : now();

            $location = '';
            if (isset($event['scanLocation'])) {
                $loc = $event['scanLocation'];
                $location = implode(', ', array_filter([
                    $loc['city'] ?? '',
                    $loc['stateOrProvinceCode'] ?? '',
                    $loc['countryCode'] ?? ''
                ]));
            }

            // Check if event already exists
            $exists = ShipmentEvent::where('ship_id', $ship->id)
                ->where('source', 'fedex')
                ->where('event_time', $eventTime)
                ->exists();

            if (!$exists) {
                $this->recordEvent(
                    $ship,
                    $this->mapFedExStatus($event['eventType'] ?? ''),
                    $event['eventDescription'] ?? '',
                    $location,
                    'fedex',
                    $eventTime,
                    $event
                );
            }
        }
    }

    /**
     * Map FedEx status to our status
     */
    protected function mapFedExStatus(string $fedexStatus): string
    {
        $mapping = [
            'PU' => ShipmentEvent::STATUS_PICKED_UP,
            'IT' => ShipmentEvent::STATUS_IN_TRANSIT,
            'OD' => ShipmentEvent::STATUS_OUT_FOR_DELIVERY,
            'DL' => ShipmentEvent::STATUS_DELIVERED,
            'DE' => ShipmentEvent::STATUS_EXCEPTION,
        ];

        return $mapping[$fedexStatus] ?? ShipmentEvent::STATUS_IN_TRANSIT;
    }

    /**
     * Fetch DHL tracking status
     */
    protected function fetchDHLStatus(Ship $ship): ?array
    {
        $config = config('carriers.dhl');

        if (empty($config['api_key']) || empty($config['api_secret'])) {
            Log::warning('DHL API credentials not configured');
            return null;
        }

        try {
            $response = Http::withBasicAuth($config['api_key'], $config['api_secret'])
                ->get($config['base_url'] . '/shipments/' . $ship->carrier_tracking_number . '/tracking');

            if ($response->successful()) {
                $data = $response->json();
                $this->processDHLEvents($ship, $data);
                return $data;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('DHL tracking error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Process DHL tracking events
     */
    protected function processDHLEvents(Ship $ship, array $data): void
    {
        $events = $data['shipments'][0]['events'] ?? [];

        foreach ($events as $event) {
            $eventTime = isset($event['timestamp'])
                ? \Carbon\Carbon::parse($event['timestamp'])
                : now();

            $location = $event['location']['address']['addressLocality'] ?? '';

            $exists = ShipmentEvent::where('ship_id', $ship->id)
                ->where('source', 'dhl')
                ->where('event_time', $eventTime)
                ->exists();

            if (!$exists) {
                $this->recordEvent(
                    $ship,
                    $this->mapDHLStatus($event['statusCode'] ?? ''),
                    $event['description'] ?? '',
                    $location,
                    'dhl',
                    $eventTime,
                    $event
                );
            }
        }
    }

    /**
     * Map DHL status to our status
     */
    protected function mapDHLStatus(string $dhlStatus): string
    {
        $mapping = [
            'pre-transit' => ShipmentEvent::STATUS_LABEL_CREATED,
            'transit' => ShipmentEvent::STATUS_IN_TRANSIT,
            'out-for-delivery' => ShipmentEvent::STATUS_OUT_FOR_DELIVERY,
            'delivered' => ShipmentEvent::STATUS_DELIVERED,
            'failure' => ShipmentEvent::STATUS_EXCEPTION,
        ];

        return $mapping[strtolower($dhlStatus)] ?? ShipmentEvent::STATUS_IN_TRANSIT;
    }

    /**
     * Fetch UPS tracking status
     */
    protected function fetchUPSStatus(Ship $ship): ?array
    {
        $config = config('carriers.ups');

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            Log::warning('UPS API credentials not configured');
            return null;
        }

        try {
            // Get OAuth token
            $tokenResponse = Http::asForm()
                ->withBasicAuth($config['client_id'], $config['client_secret'])
                ->post($config['base_url'] . '/security/v1/oauth/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$tokenResponse->successful()) {
                return null;
            }

            $token = $tokenResponse->json('access_token');

            // Fetch tracking
            $trackingResponse = Http::withToken($token)
                ->get($config['base_url'] . '/api/track/v1/details/' . $ship->carrier_tracking_number);

            if ($trackingResponse->successful()) {
                $data = $trackingResponse->json();
                $this->processUPSEvents($ship, $data);
                return $data;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('UPS tracking error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Process UPS tracking events
     */
    protected function processUPSEvents(Ship $ship, array $data): void
    {
        $package = $data['trackResponse']['shipment'][0]['package'][0] ?? null;

        if (!$package) {
            return;
        }

        $activities = $package['activity'] ?? [];

        foreach ($activities as $activity) {
            $eventTime = isset($activity['date']) && isset($activity['time'])
                ? \Carbon\Carbon::createFromFormat('Ymd His', $activity['date'] . ' ' . $activity['time'])
                : now();

            $location = '';
            if (isset($activity['location']['address'])) {
                $addr = $activity['location']['address'];
                $location = implode(', ', array_filter([
                    $addr['city'] ?? '',
                    $addr['stateProvince'] ?? '',
                    $addr['country'] ?? ''
                ]));
            }

            $exists = ShipmentEvent::where('ship_id', $ship->id)
                ->where('source', 'ups')
                ->where('event_time', $eventTime)
                ->exists();

            if (!$exists) {
                $this->recordEvent(
                    $ship,
                    $this->mapUPSStatus($activity['status']['type'] ?? ''),
                    $activity['status']['description'] ?? '',
                    $location,
                    'ups',
                    $eventTime,
                    $activity
                );
            }
        }
    }

    /**
     * Map UPS status to our status
     */
    protected function mapUPSStatus(string $upsStatus): string
    {
        $mapping = [
            'M' => ShipmentEvent::STATUS_LABEL_CREATED,
            'P' => ShipmentEvent::STATUS_PICKED_UP,
            'I' => ShipmentEvent::STATUS_IN_TRANSIT,
            'O' => ShipmentEvent::STATUS_OUT_FOR_DELIVERY,
            'D' => ShipmentEvent::STATUS_DELIVERED,
            'X' => ShipmentEvent::STATUS_EXCEPTION,
        ];

        return $mapping[$upsStatus] ?? ShipmentEvent::STATUS_IN_TRANSIT;
    }

    /**
     * Get carrier tracking URL
     */
    public function getCarrierTrackingUrl(Ship $ship): ?string
    {
        if (!$ship->carrier_tracking_number) {
            return null;
        }

        $tracking = $ship->carrier_tracking_number;

        switch ($ship->carrier_name) {
            case 'fedex':
                return "https://www.fedex.com/fedextrack/?trknbr={$tracking}";
            case 'dhl':
                return "https://www.dhl.com/en/express/tracking.html?AWB={$tracking}";
            case 'ups':
                return "https://www.ups.com/track?tracknum={$tracking}";
            default:
                return null;
        }
    }

    /**
     * Search shipments by tracking number (internal or carrier)
     */
    public function searchByTrackingNumber(string $trackingNumber, ?int $customerId = null): ?Ship
    {
        $query = Ship::query()
            ->where(function ($q) use ($trackingNumber) {
                $q->where('tracking_number', $trackingNumber)
                    ->orWhere('carrier_tracking_number', $trackingNumber);
            });

        // If customerId is provided, only search that customer's shipments
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return $query->with(['customer', 'customerAddress', 'trackingEvents'])->first();
    }
}
