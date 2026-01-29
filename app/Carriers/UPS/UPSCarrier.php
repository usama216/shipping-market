<?php

namespace App\Carriers\UPS;

use App\Carriers\AbstractCarrier;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\DTOs\ShipmentResponse;
use App\Carriers\DTOs\RateResponse;
use App\Carriers\DTOs\TrackingResponse;
use App\Carriers\DTOs\LabelResponse;
use App\Carriers\Exceptions\CarrierException;
use App\Carriers\Exceptions\CarrierAuthException;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UPSCarrier - UPS Developer API Implementation
 * 
 * Uses UPS OAuth 2.0 API (legacy XML deprecated)
 * Docs: https://developer.ups.com/api
 */
class UPSCarrier extends AbstractCarrier
{
    /**
     * UPS service code to name mapping
     */
    private const SERVICE_NAMES = [
        '01' => 'UPS Next Day Air',
        '02' => 'UPS 2nd Day Air',
        '03' => 'UPS Ground',
        '07' => 'UPS Worldwide Express',
        '08' => 'UPS Worldwide Expedited',
        '11' => 'UPS Standard',
        '12' => 'UPS 3 Day Select',
        '13' => 'UPS Next Day Air Saver',
        '14' => 'UPS Next Day Air Early',
        '54' => 'UPS Worldwide Express Plus',
        '59' => 'UPS 2nd Day Air A.M.',
        '65' => 'UPS Worldwide Saver',
        '82' => 'UPS Today Standard',
        '83' => 'UPS Today Dedicated Courier',
        '84' => 'UPS Today Intercity',
        '85' => 'UPS Today Express',
        '86' => 'UPS Today Express Saver',
        '96' => 'UPS Worldwide Express Freight',
    ];

    public function getName(): string
    {
        return 'ups';
    }

    /**
     * Authenticate with UPS OAuth 2.0
     * POST /security/v1/oauth/token
     */
    public function authenticate(): bool
    {
        $clientId = $this->config['client_id'] ?? '';
        $clientSecret = $this->config['client_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new CarrierAuthException('UPS', 'Missing client_id or client_secret - UPS integration not configured');
        }

        try {
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/security/v1/oauth/token", [
                        'grant_type' => 'client_credentials',
                    ]);

            if ($response->failed()) {
                throw new CarrierAuthException('UPS', 'OAuth token request failed', $response->json() ?? []);
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'] ?? null;
            $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);

            Log::channel('carrier')->info('UPS authentication successful');
            return true;

        } catch (\Exception $e) {
            if ($e instanceof CarrierAuthException) {
                throw $e;
            }
            throw new CarrierAuthException('UPS', $e->getMessage());
        }
    }

    /**
     * Get shipping rates
     * POST /api/rating/v2409/Shop
     * 
     * Implements automatic retry: if UPS rejects due to address errors,
     * retries with stripped address fields (industry-standard Caribbean fallback).
     */
    public function getRates(ShipmentRequest $request): array
    {
        $logId = uniqid('rate_', true);
        
        // Log comprehensive rate request details
        Log::channel('carrier')->info("UPS Rate Request - START", [
            'log_id' => $logId,
            'carrier' => 'UPS',
            'service_type' => $request->serviceType,
            'origin' => [
                'street1' => $request->senderAddress->street1 ?? '',
                'street2' => $request->senderAddress->street2 ?? '',
                'city' => $request->senderAddress->city ?? '',
                'state' => $request->senderAddress->state ?? '',
                'postal_code' => $request->senderAddress->postalCode ?? '',
                'country' => $request->senderAddress->countryCode ?? '',
            ],
            'destination' => [
                'street1' => $request->recipientAddress->street1 ?? '',
                'street2' => $request->recipientAddress->street2 ?? '',
                'city' => $request->recipientAddress->city ?? '',
                'state' => $request->recipientAddress->state ?? '',
                'postal_code' => $request->recipientAddress->postalCode ?? '',
                'country' => $request->recipientAddress->countryCode ?? '',
            ],
            'packages' => array_map(function($pkg) {
                return [
                    'weight' => $pkg->weight ?? 0,
                    'weight_unit' => $pkg->weightUnit ?? 'LB',
                    'length' => $pkg->length ?? 0,
                    'width' => $pkg->width ?? 0,
                    'height' => $pkg->height ?? 0,
                    'dimension_unit' => $pkg->dimensionUnit ?? 'IN',
                    'total_weight_lbs' => ($pkg->weightUnit === 'LB') ? $pkg->weight : ($pkg->weight * 2.20462),
                ];
            }, $request->packages),
            'total_packages' => count($request->packages),
            'declared_value' => $request->declaredValue ?? 0,
            'currency' => $request->currency ?? 'USD',
        ]);
        
        try {
            $payload = $this->buildRatePayload($request);
            
            // Log the payload being sent to UPS
            Log::channel('carrier')->info("UPS Rate Request - Payload", [
                'log_id' => $logId,
                'payload' => $payload,
            ]);
            
            // UPS Rating API v2409 - use /Shop endpoint for all available services
            $response = $this->post('/api/rating/v2409/Shop', $payload);
            
            // Log raw response
            Log::channel('carrier')->info("UPS Rate Request - Raw Response", [
                'log_id' => $logId,
                'response' => $response,
                'rated_shipments_count' => count($response['RateResponse']['RatedShipment'] ?? []),
            ]);

            $rates = $this->parseRateResponse($response, $logId);
            
            Log::channel('carrier')->info("UPS Rate Request - COMPLETE", [
                'log_id' => $logId,
                'rates_count' => count($rates),
                'rates_summary' => array_map(fn($r) => [
                    'service' => $r->serviceName,
                    'total' => $r->totalCharge,
                ], $rates),
            ]);

            return $rates;
        } catch (\Exception $e) {
            // Log the exception details
            Log::channel('carrier')->error("UPS Rate Request - EXCEPTION", [
                'log_id' => $logId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Check if this is an address-related error that can be retried
            if ($this->isAddressRelatedError($e)) {
                Log::channel('carrier')->info('UPS address error detected, retrying with stripped fields', [
                    'log_id' => $logId,
                    'error' => $e->getMessage(),
                ]);

                return $this->getRatesWithStrippedAddress($request, $logId);
            }

            throw $e;
        }
    }

    /**
     * Parse rate response into RateResponse DTOs
     * 
     * UPS returns different shapes:
     * - Shop mode: Array of RatedShipment objects
     * - Rate mode: Single RatedShipment object
     * - Edge case: Single-element array
     */
    private function parseRateResponse(array $response, ?string $logId = null): array
    {
        $rates = [];
        $ratedShipment = $response['RateResponse']['RatedShipment'] ?? [];

        // Ensure it's an array of shipments
        // Check for numeric key [0] to detect if it's already an array
        if (!empty($ratedShipment) && !isset($ratedShipment[0])) {
            $ratedShipment = [$ratedShipment];
        }

        foreach ($ratedShipment as $rate) {
            $rateResponse = RateResponse::fromUPS($rate);

            // Guard: Skip rates with empty, null, or zero charges
            if (empty($rateResponse->totalCharge) || $rateResponse->totalCharge <= 0) {
                Log::channel('carrier')->warning('UPS: Skipping rate with invalid charge', [
                    'log_id' => $logId,
                    'service' => $rateResponse->serviceType ?? 'unknown',
                    'charge' => $rateResponse->totalCharge,
                ]);
                continue;
            }

            // Log each rate with full breakdown
            Log::channel('carrier')->info("UPS Rate Response - Service Breakdown", [
                'log_id' => $logId,
                'service_type' => $rateResponse->serviceType,
                'service_name' => $rateResponse->serviceName,
                'total_charge' => $rateResponse->totalCharge,
                'currency' => $rateResponse->currency,
                'base_charge' => $rateResponse->baseCharge,
                'surcharges' => $rateResponse->surcharges,
                'surcharge_breakdown' => $rateResponse->surchargeBreakdown,
                'taxes' => $rateResponse->taxes,
                'estimated_delivery' => $rateResponse->estimatedDelivery,
                'transit_days' => $rateResponse->transitDays,
                'raw_response' => $rateResponse->rawResponse,
            ]);

            $rates[] = $rateResponse;
        }

        // Log if no valid rates found
        if (empty($rates) && !empty($ratedShipment)) {
            Log::channel('carrier')->warning('UPS: All rates filtered out (zero or empty charges)', [
                'log_id' => $logId,
                'original_count' => count($ratedShipment),
            ]);
        }

        return $rates;
    }

    /**
     * Retry rate request with stripped state and postal code (Caribbean fallback)
     */
    private function getRatesWithStrippedAddress(ShipmentRequest $request, ?string $logId = null): array
    {
        $payload = $this->buildRatePayload($request);

        // Strip state and postal code from destination address
        if (isset($payload['RateRequest']['Shipment']['ShipTo']['Address'])) {
            unset($payload['RateRequest']['Shipment']['ShipTo']['Address']['StateProvinceCode']);
            $payload['RateRequest']['Shipment']['ShipTo']['Address']['PostalCode'] = '';
        }

        Log::channel('carrier')->info('UPS retry with stripped address', [
            'log_id' => $logId,
            'destination_country' => $payload['RateRequest']['Shipment']['ShipTo']['Address']['CountryCode'] ?? 'unknown',
        ]);

        $response = $this->post('/api/rating/v2409/Shop', $payload);
        return $this->parseRateResponse($response, $logId);
    }

    /**
     * Check if the exception is related to address validation
     */
    private function isAddressRelatedError(\Exception $e): bool
    {
        $message = strtoupper($e->getMessage());

        $addressErrorPatterns = [
            'STATE',
            'PROVINCE',
            'POSTAL',
            'ZIPCODE',
            'ZIP CODE',
            'ADDRESS',
            'INVALID CITY',
            'DESTINATION',
        ];

        foreach ($addressErrorPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create shipment and generate label
     * POST /api/shipments/v2205/ship
     */
    public function createShipment(ShipmentRequest $request, ?array $packageModels = null): ShipmentResponse
    {
        try {
            $payload = $this->buildShipmentPayload($request);

            // Debug: Log the shipment payload
            Log::channel('carrier')->info('UPS Shipment Payload', [
                'packages' => json_encode($payload['ShipmentRequest']['Shipment']['Package'] ?? 'MISSING'),
            ]);

            // UPS Shipment API v2409 (matches rating API version)
            $response = $this->post('/api/shipments/v2409/ship', $payload);

            // Extract tracking number and label
            $shipmentResults = $response['ShipmentResponse']['ShipmentResults'] ?? [];
            $packageResults = $shipmentResults['PackageResults'] ?? [];

            // Handle single package (comes as object, not array)
            // Use numeric key check for safer array detection
            if (!empty($packageResults) && !isset($packageResults[0])) {
                $packageResults = [$packageResults];
            }

            $trackingNumber = $packageResults[0]['TrackingNumber'] ??
                $shipmentResults['ShipmentIdentificationNumber'] ?? null;

            // Get label data (base64 encoded)
            $labelData = null;
            $labelUrl = null;

            if (isset($packageResults[0]['ShippingLabel']['GraphicImage'])) {
                $labelData = $packageResults[0]['ShippingLabel']['GraphicImage'];
            }

            if (!$trackingNumber) {
                return ShipmentResponse::failure('No tracking number received', [], $response);
            }

            Log::channel('carrier')->info('UPS shipment created', [
                'tracking' => $trackingNumber,
            ]);

            // Extract total charge
            $totalCharge = null;
            if (isset($shipmentResults['ShipmentCharges']['TotalCharges']['MonetaryValue'])) {
                $totalCharge = (float) $shipmentResults['ShipmentCharges']['TotalCharges']['MonetaryValue'];
            }

            return ShipmentResponse::success(
                trackingNumber: $trackingNumber,
                labelUrl: $labelUrl,
                labelData: $labelData,
                rawResponse: $response,
                totalCharge: $totalCharge,
            );

        } catch (CarrierException $e) {
            Log::channel('carrier')->error('UPS shipment failed', [
                'error' => $e->getMessage(),
            ]);
            return ShipmentResponse::failure($e->getMessage(), $e->getErrors(), $e->getRawResponse());
        }
    }

    /**
     * Get shipping label for existing shipment
     */
    public function getLabel(string $trackingNumber): LabelResponse
    {
        // UPS labels are returned on shipment creation
        // For re-printing, you'd use the Label Recovery API
        try {
            $payload = [
                'LabelRecoveryRequest' => [
                    'LabelSpecification' => [
                        'LabelImageFormat' => [
                            'Code' => config('carriers.label_format', 'PDF'),
                        ],
                    ],
                    'TrackingNumber' => $trackingNumber,
                ],
            ];

            $response = $this->post('/api/labels/v1/recovery', $payload);

            $labelResults = $response['LabelRecoveryResponse']['LabelResults'] ?? [];
            $labelData = $labelResults['LabelImage']['GraphicImage'] ?? null;

            if ($labelData) {
                return LabelResponse::success(
                    labelData: $labelData,
                    format: config('carriers.label_format', 'PDF'),
                );
            }

            return LabelResponse::failure('Label not found');
        } catch (\Exception $e) {
            return LabelResponse::failure($e->getMessage());
        }
    }

    /**
     * Track a shipment
     * GET /api/track/v1/details/{trackingNumber}
     */
    public function track(string $trackingNumber): TrackingResponse
    {
        $logId = uniqid('track_', true);
        
        Log::channel('carrier')->info("UPS Tracking Request - START", [
            'log_id' => $logId,
            'carrier' => 'UPS',
            'tracking_number' => $trackingNumber,
        ]);
        
        try {
            // UPS Tracking API uses query parameters
            $response = $this->get("/api/track/v1/details/{$trackingNumber}", [
                'locale' => 'en_US',
                'returnSignature' => 'true',
            ]);
            
            // Log raw response
            Log::channel('carrier')->info("UPS Tracking Request - Raw Response", [
                'log_id' => $logId,
                'response' => $response,
            ]);

            $trackResult = $response['trackResponse']['shipment'][0]['package'][0] ?? [];

            $activity = $trackResult['activity'] ?? [];
            $latestActivity = $activity[0] ?? [];
            $status = $latestActivity['status'] ?? [];

            $events = [];
            foreach ($activity as $event) {
                $location = $event['location']['address'] ?? [];
                $events[] = [
                    'timestamp' => $event['date'] ?? null,
                    'status' => $event['status']['type'] ?? '',
                    'description' => $event['status']['description'] ?? '',
                    'location' => isset($location['city'])
                        ? "{$location['city']}, {$location['stateProvince']}"
                        : null,
                ];
            }

            // Get delivery info
            $deliveryDate = $trackResult['deliveryDate'][0]['date'] ?? null;
            $deliveryTime = $trackResult['deliveryTime']['endTime'] ?? null;
            $estimatedDelivery = $deliveryDate ? "{$deliveryDate} {$deliveryTime}" : null;

            $trackingResponse = new TrackingResponse(
                trackingNumber: $trackingNumber,
                status: TrackingResponse::normalizeStatus($status['type'] ?? 'unknown'),
                statusDescription: $status['description'] ?? '',
                estimatedDelivery: $estimatedDelivery,
                actualDelivery: $trackResult['deliveryDate'][0]['date'] ?? null,
                signedBy: $trackResult['deliveryInformation']['receivedBy'] ?? null,
                events: $events,
                currentLocation: $latestActivity['location']['address']['city'] ?? null,
                rawResponse: $response,
            );
            
            Log::channel('carrier')->info("UPS Tracking Request - COMPLETE", [
                'log_id' => $logId,
                'status' => $trackingResponse->status,
                'events_count' => count($events),
                'estimated_delivery' => $estimatedDelivery,
            ]);
            
            return $trackingResponse;
        } catch (\Exception $e) {
            Log::channel('carrier')->error("UPS Tracking Request - EXCEPTION", [
                'log_id' => $logId,
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Cancel/void a shipment
     * DELETE /api/shipments/v1/void/cancel/{trackingNumber}
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $this->delete("/api/shipments/v1/void/cancel/{$trackingNumber}");

            Log::channel('carrier')->info('UPS shipment cancelled', [
                'tracking' => $trackingNumber,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('carrier')->error('UPS cancel failed', [
                'tracking' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build rate request payload
     * 
     * Always uses 'Shop' mode to return all available service rates.
     * UPS requires NO Service block when using Shop mode.
     */
    private function buildRatePayload(ShipmentRequest $request): array
    {
        return [
            'RateRequest' => [
                'Request' => [
                    'RequestOption' => 'Shop',
                    'TransactionReference' => [
                        'CustomerContext' => 'Rate Request',
                    ],
                ],
                'Shipment' => [
                    'Shipper' => $this->formatAddress(
                        $request->senderName,
                        $request->senderPhone,
                        $request->senderAddress,
                        true // isShipper
                    ),
                    'ShipTo' => $this->formatAddress(
                        $request->recipientName,
                        $request->recipientPhone,
                        $request->recipientAddress,
                        false
                    ),
                    'ShipFrom' => $this->formatAddress(
                        $request->senderName,
                        $request->senderPhone,
                        $request->senderAddress,
                        false
                    ),
                    'PaymentDetails' => [
                        'ShipmentCharge' => [
                            [
                                'Type' => '01', // Transportation
                                'BillShipper' => [
                                    'AccountNumber' => $this->getAccountNumber(),
                                ],
                            ],
                        ],
                    ],
                    'NumOfPieces' => (string) count($request->packages),
                    'Package' => $this->formatPackages($request->packages),
                ],
            ],
        ];
    }

    /**
     * Map service type string to UPS numeric code
     * Handles both full format (UPS_WORLDWIDE_SAVER) and short format (SAVER) from database
     */
    private function mapServiceToCode(string $serviceType): string
    {
        $mapping = [
            // Full format (UPS_*)
            'UPS_GROUND' => '03',
            'UPS_NEXT_DAY_AIR' => '01',
            'UPS_NEXT_DAY_AIR_SAVER' => '13',
            'UPS_NEXT_DAY_AIR_EARLY' => '14',
            'UPS_2ND_DAY_AIR' => '02',
            'UPS_2ND_DAY_AIR_AM' => '59',
            'UPS_3_DAY_SELECT' => '12',
            'UPS_WORLDWIDE_EXPRESS' => '07',
            'UPS_WORLDWIDE_EXPEDITED' => '08',
            'UPS_STANDARD' => '11',
            'UPS_WORLDWIDE_EXPRESS_PLUS' => '54',
            'UPS_WORLDWIDE_SAVER' => '65',
            // Short format from legacy database entries
            'EXPRESS' => '07',
            'SAVER' => '65',
            'EXPEDITED' => '08',
            'GROUND' => '03',
            'STANDARD' => '11',
        ];

        return $mapping[strtoupper($serviceType)] ?? '65'; // Default to Worldwide Saver
    }

    /**
     * Build shipment request payload
     */
    private function buildShipmentPayload(ShipmentRequest $request): array
    {
        $payload = [
            'ShipmentRequest' => [
                'Request' => [
                    'SubVersion' => '2205',
                    'TransactionReference' => [
                        'CustomerContext' => 'Shipment Request',
                    ],
                ],
                'Shipment' => [
                    'Description' => $request->description ?? 'Package',
                    'Shipper' => $this->formatAddress(
                        $request->senderName,
                        $request->senderPhone,
                        $request->senderAddress,
                        true
                    ),
                    'ShipTo' => $this->formatAddress(
                        $request->recipientName,
                        $request->recipientPhone,
                        $request->recipientAddress,
                        false
                    ),
                    'ShipFrom' => $this->formatAddress(
                        $request->senderName,
                        $request->senderPhone,
                        $request->senderAddress,
                        false
                    ),
                    'PaymentInformation' => [
                        'ShipmentCharge' => [
                            [
                                'Type' => '01',
                                'BillShipper' => [
                                    'AccountNumber' => $this->getAccountNumber(),
                                ],
                            ],
                        ],
                    ],
                    'Service' => [
                        'Code' => $this->mapServiceToCode($request->serviceType ?? 'UPS_WORLDWIDE_SAVER'),
                        'Description' => self::SERVICE_NAMES[$this->mapServiceToCode($request->serviceType ?? 'UPS_WORLDWIDE_SAVER')] ?? 'UPS Service',
                    ],
                    'Package' => $this->formatPackages($request->packages, true), // true = for Shipment API
                ],
                'LabelSpecification' => [
                    'LabelImageFormat' => [
                        'Code' => config('carriers.label_format', 'PDF'),
                        'Description' => 'Label',
                    ],
                    'LabelStockSize' => [
                        'Height' => '6',
                        'Width' => '4',
                    ],
                ],
            ],
        ];

        // Add customs for international shipments
        if ($request->commodities && count($request->commodities) > 0) {
            $payload['ShipmentRequest']['Shipment']['ShipmentServiceOptions'] = [
                'InternationalForms' => [
                    'FormType' => '01', // Invoice
                    'InvoiceNumber' => $request->referenceNumber ?? uniqid('INV'),
                    'InvoiceDate' => now()->format('Ymd'),
                    'ReasonForExport' => 'SALE',
                    'CurrencyCode' => 'USD',
                    'Contacts' => [
                        'SoldTo' => [
                            'Name' => $request->recipientName,
                            'AttentionName' => $request->recipientName,
                            'Phone' => [
                                'Number' => $request->recipientPhone ?: '0000000000',
                            ],
                            'Address' => [
                                'AddressLine' => array_filter([$request->recipientAddress->street1, $request->recipientAddress->street2]),
                                'City' => $request->recipientAddress->city,
                                'CountryCode' => strtoupper(substr($request->recipientAddress->countryCode ?? 'US', 0, 2)),
                            ],
                        ],
                    ],
                    'Product' => $this->formatCommodities($request->commodities),
                ],
            ];

            // Add recipient tax ID if available
            if ($request->recipientTaxId) {
                $payload['ShipmentRequest']['Shipment']['ShipTo']['TaxIdentificationNumber'] = $request->recipientTaxId;
            }
        }

        // Add reference
        if ($request->referenceNumber) {
            $payload['ShipmentRequest']['Shipment']['ReferenceNumber'] = [
                'Code' => '01',
                'Value' => $request->referenceNumber,
            ];
        }

        return $payload;
    }

    /**
     * Format contact/address for UPS API
     */
    private function formatAddress(string $name, string $phone, $address, bool $isShipper = false): array
    {
        $countryCode = strtoupper($address->countryCode ?? 'US');

        // Query database for country-specific rules
        $country = Country::where('code', $countryCode)->first();

        // Use carrier_code for API calls (e.g., BQ-BO → BQ for Caribbean Netherlands)
        $carrierCountryCode = $country?->getCarrierCode() ?? substr($countryCode, 0, 2);
        $requiresPostalCode = $country ? $country->has_postal_code : true;
        $acceptsState = $country ? ($country->ups_accepts_state ?? true) : true;

        // Truncate address to 2 lines if too long (UPS limit: 35 chars per line)
        $streetLines = $this->truncateAddressToTwoLines(
            $address->street1 ?? '',
            $address->street2 ?? '',
            35 // UPS max length per address line
        );

        $addressData = [
            'AddressLine' => array_filter($streetLines),
            'City' => $address->city,
            'CountryCode' => $carrierCountryCode,
        ];

        // Add state only if country accepts it (Caribbean islands often don't)
        if (!empty($address->state) && $acceptsState) {
            $addressData['StateProvinceCode'] = $address->state;
        }

        // UPS postal code handling
        if ($requiresPostalCode && !empty($address->postalCode)) {
            $addressData['PostalCode'] = $address->postalCode;
        } elseif (!$requiresPostalCode) {
            // Countries without postal codes - use dummy code
            $addressData['PostalCode'] = '00000';
        }

        $contact = [
            'Name' => $name,
            'AttentionName' => $name, // Required for international shipments
            'Phone' => [
                'Number' => $phone ?: '0000000000', // Fallback phone number
            ],
            'Address' => $addressData,
        ];

        // Shipper requires account number
        if ($isShipper) {
            $contact['ShipperNumber'] = $this->getAccountNumber();
        }

        return $contact;
    }

    /**
     * Truncate address to 2 lines if too long
     * Intelligently splits at word boundaries to avoid cutting words
     * 
     * @param string $street1 First address line
     * @param string $street2 Second address line (optional)
     * @param int $maxLength Maximum characters per line
     * @return array Array with max 2 address lines
     */
    private function truncateAddressToTwoLines(string $street1, string $street2 = '', int $maxLength = 35): array
    {
        // Combine both lines
        $combined = trim($street1 . ' ' . $street2);
        
        // If already within limit and has 2 lines, return as-is
        if (strlen($street1) <= $maxLength && (empty($street2) || strlen($street2) <= $maxLength)) {
            return array_filter([$street1, $street2]);
        }
        
        // If combined length is within one line limit, return as single line
        if (strlen($combined) <= $maxLength) {
            return [$combined];
        }
        
        // Need to split into 2 lines
        // Try to split at word boundary near the middle
        $targetSplit = $maxLength;
        $splitPos = $targetSplit;
        
        // Find last space before maxLength for line 1
        $line1 = substr($combined, 0, $maxLength);
        $lastSpace = strrpos($line1, ' ');
        
        if ($lastSpace !== false && $lastSpace > ($maxLength * 0.6)) {
            // Split at word boundary
            $line1 = trim(substr($combined, 0, $lastSpace));
            $line2 = trim(substr($combined, $lastSpace));
        } else {
            // Force split at maxLength
            $line1 = substr($combined, 0, $maxLength);
            $line2 = trim(substr($combined, $maxLength));
        }
        
        // Truncate line2 if still too long
        if (strlen($line2) > $maxLength) {
            $line2 = substr($line2, 0, $maxLength);
        }
        
        return array_filter([$line1, $line2]);
    }

    /**
     * Format packages for UPS API
     * 
     * @param bool $forShipment - If true, uses 'Packaging' field (Shipment API). If false, uses 'PackagingType' (Rating API)
     */
    private function formatPackages(array $packages, bool $forShipment = false): array
    {
        // Rating API uses 'PackagingType', Shipment API uses 'Packaging'
        $packagingKey = $forShipment ? 'Packaging' : 'PackagingType';

        return array_map(function ($pkg) use ($packagingKey) {
            $package = [
                $packagingKey => [
                    'Code' => '02', // Customer Supplied Package
                    'Description' => 'Package',
                ],
                'PackageWeight' => [
                    'UnitOfMeasurement' => [
                        'Code' => $pkg->weightUnit === 'KG' ? 'KGS' : 'LBS',
                    ],
                    'Weight' => (string) max(0.1, (float) $pkg->weight),
                ],
            ];

            // Only include dimensions if valid
            if ($pkg->length > 0 && $pkg->width > 0 && $pkg->height > 0) {
                $package['Dimensions'] = [
                    'UnitOfMeasurement' => [
                        'Code' => $pkg->dimensionUnit === 'CM' ? 'CM' : 'IN',
                    ],
                    'Length' => (string) ceil($pkg->length),
                    'Width' => (string) ceil($pkg->width),
                    'Height' => (string) ceil($pkg->height),
                ];
            }

            // Add declared value if set
            if ($pkg->declaredValue) {
                $package['PackageServiceOptions'] = [
                    'DeclaredValue' => [
                        'Type' => [
                            'Code' => '01', // Declared value
                        ],
                        'CurrencyCode' => 'USD',
                        'MonetaryValue' => (string) $pkg->declaredValue,
                    ],
                ];
            }

            return $package;
        }, $packages);
    }

    /**
     * Format commodities for customs
     */
    private function formatCommodities(array $commodities): array
    {
        return array_map(fn($item) => [
            'Description' => substr($item->description, 0, 35), // UPS limit
            'Unit' => [
                'Number' => (string) $item->quantity,
                'UnitOfMeasurement' => [
                    'Code' => 'PCS',
                ],
                'Value' => (string) max(0.01, $item->totalValue),
            ],
            'CommodityCode' => $item->hsCode ?? '',
            'OriginCountryCode' => $item->countryOfOrigin ?? 'US',
            'NumberOfPackagesPerCommodity' => '1',
            'ProductWeight' => [
                'UnitOfMeasurement' => [
                    'Code' => strtoupper($item->weightUnit) === 'KG' ? 'KGS' : 'LBS',
                ],
                'Weight' => (string) max(0.1, $item->weight),
            ],
        ], $commodities);
    }
}
