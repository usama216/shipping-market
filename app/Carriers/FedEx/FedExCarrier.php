<?php

namespace App\Carriers\FedEx;

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
 * FedExCarrier - FedEx REST API Implementation
 * 
 * Uses FedEx Ship API v1 (Web Services deprecated Aug 2024)
 * Docs: https://developer.fedex.com/api/en-us/catalog.html
 */
class FedExCarrier extends AbstractCarrier
{
    public function getName(): string
    {
        return 'fedex';
    }

    /**
     * Authenticate with FedEx OAuth 2.0
     * POST /oauth/token
     */
    public function authenticate(): bool
    {
        $clientId = $this->config['client_id'] ?? '';
        $clientSecret = $this->config['client_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new CarrierAuthException('FedEx', 'Missing client_id or client_secret');
        }

        try {
            $response = Http::asForm()
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->failed()) {
                throw new CarrierAuthException('FedEx', 'OAuth token request failed', $response->json() ?? []);
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'] ?? null;
            $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);

            Log::channel('carrier')->info('FedEx authentication successful');
            return true;

        } catch (\Exception $e) {
            if ($e instanceof CarrierAuthException) {
                throw $e;
            }
            throw new CarrierAuthException('FedEx', $e->getMessage());
        }
    }

    /**
     * Get shipping rates
     * POST /rate/v1/rates/quotes (note: "quotes" is required per FedEx docs)
     * 
     * Implements automatic retry: if FedEx rejects due to STATE/PROVINCE/POSTAL errors,
     * retries with stripped address fields (industry-standard Caribbean fallback).
     */
    public function getRates(ShipmentRequest $request): array
    {
        $logId = uniqid('rate_', true);
        
        // Log comprehensive rate request details
        Log::channel('carrier')->info("FedEx Rate Request - START", [
            'log_id' => $logId,
            'carrier' => 'FedEx',
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
            
            // Log the payload being sent to FedEx
            Log::channel('carrier')->info("FedEx Rate Request - Payload", [
                'log_id' => $logId,
                'payload' => $payload,
            ]);
            
            $response = $this->post('/rate/v1/rates/quotes', $payload);
            
            // Log raw response
            Log::channel('carrier')->info("FedEx Rate Request - Raw Response", [
                'log_id' => $logId,
                'response' => $response,
                'rate_details_count' => count($response['output']['rateReplyDetails'] ?? []),
            ]);

            $rates = $this->parseRateResponse($response);
            
            // Log each rate with full breakdown
            foreach ($rates as $rate) {
                Log::channel('carrier')->info("FedEx Rate Response - Service Breakdown", [
                    'log_id' => $logId,
                    'service_type' => $rate->serviceType,
                    'service_name' => $rate->serviceName,
                    'total_charge' => $rate->totalCharge,
                    'currency' => $rate->currency,
                    'base_charge' => $rate->baseCharge,
                    'surcharges' => $rate->surcharges,
                    'surcharge_breakdown' => $rate->surchargeBreakdown,
                    'taxes' => $rate->taxes,
                    'estimated_delivery' => $rate->estimatedDelivery,
                    'transit_days' => $rate->transitDays,
                    'raw_response' => $rate->rawResponse,
                ]);
            }
            
            Log::channel('carrier')->info("FedEx Rate Request - COMPLETE", [
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
            Log::channel('carrier')->error("FedEx Rate Request - EXCEPTION", [
                'log_id' => $logId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Check if this is an address-related error that can be retried
            if ($this->isAddressRelatedError($e)) {
                Log::channel('carrier')->info('FedEx address error detected, retrying with stripped fields', [
                    'log_id' => $logId,
                    'error' => $e->getMessage(),
                ]);

                return $this->getRatesWithStrippedAddress($request);
            }

            throw $e;
        }
    }

    /**
     * Parse rate response into RateResponse DTOs
     */
    private function parseRateResponse(array $response): array
    {
        $rates = [];
        foreach ($response['output']['rateReplyDetails'] ?? [] as $rateDetail) {
            $rates[] = RateResponse::fromFedEx($rateDetail);
        }
        return $rates;
    }

    /**
     * Retry rate request with stripped state and postal code (Caribbean fallback)
     */
    private function getRatesWithStrippedAddress(ShipmentRequest $request): array
    {
        $payload = $this->buildRatePayload($request);

        // Strip state and postal code from recipient address
        if (isset($payload['requestedShipment']['recipient']['address'])) {
            $payload['requestedShipment']['recipient']['address']['stateOrProvinceCode'] = '';
            $payload['requestedShipment']['recipient']['address']['postalCode'] = '';
        }

        Log::channel('carrier')->info('FedEx retry with stripped address', [
            'recipient_country' => $payload['requestedShipment']['recipient']['address']['countryCode'] ?? 'unknown',
        ]);

        $response = $this->post('/rate/v1/rates/quotes', $payload);
        return $this->parseRateResponse($response);
    }

    /**
     * Check if the exception is related to address validation (STATE/PROVINCE/POSTAL errors)
     * These errors can be recovered by retrying with stripped address fields.
     */
    private function isAddressRelatedError(\Exception $e): bool
    {
        $message = strtoupper($e->getMessage());

        // FedEx error patterns that indicate address field issues
        $addressErrorPatterns = [
            'STATE',
            'PROVINCE',
            'POSTAL',
            'ZIPCODE',
            'ZIP.CODE',
            'ENTERED.ZIPCODE',
            'INVALID.STATEORPROVINCECD',
            'RECIPIENT.ADDRESS',
            'DESTINATION.ADDRESS',
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
     * POST /ship/v1/shipments
     */
    public function createShipment(ShipmentRequest $request, ?array $packageModels = null): ShipmentResponse
    {
        try {
            $payload = $this->buildShipmentPayload($request);
            
            // Clean up numeric values in payload to ensure proper precision
            $payload = $this->cleanNumericValues($payload);
            
            // Double-check: Normalize all float values to prevent JSON encoding precision issues
            $payload = $this->normalizeAllFloats($payload);
            
            // Final pass: Ensure all numeric values are properly formatted (max 2 decimals)
            $payload = $this->finalNumericCleanup($payload);
            
            // Log the full payload for debugging (after cleaning)
            Log::channel('carrier')->info('FedEx Shipment Request - Payload', [
                'payload' => $payload,
                'recipient' => [
                    'name' => $request->recipientName,
                    'phone' => $request->recipientPhone,
                    'address' => [
                        'street1' => $request->recipientAddress->street1,
                        'street2' => $request->recipientAddress->street2,
                        'city' => $request->recipientAddress->city,
                        'state' => $request->recipientAddress->state,
                        'postal_code' => $request->recipientAddress->postalCode,
                        'country' => $request->recipientAddress->countryCode,
                    ],
                ],
                'service_type' => $request->serviceType,
                'packaging_type' => $request->packagingType,
            ]);
            
            // Fix float precision in JSON before sending
            // Laravel's HTTP client uses json_encode() which can introduce precision errors
            $response = $this->postWithFixedPrecision('/ship/v1/shipments', $payload);

            // Extract tracking number and label
            $output = $response['output'] ?? [];
            $transaction = $output['transactionShipments'][0] ?? [];

            $trackingNumber = $transaction['masterTrackingNumber'] ??
                $transaction['pieceResponses'][0]['trackingNumber'] ?? null;

            // Get label data (base64 encoded PDF)
            $labelData = null;
            $labelUrl = null;

            if (isset($transaction['pieceResponses'][0]['packageDocuments'])) {
                foreach ($transaction['pieceResponses'][0]['packageDocuments'] as $doc) {
                    // FedEx returns docType: 'PDF' and contentType: 'LABEL'
                    // Check contentType for label documents
                    if (($doc['contentType'] ?? '') === 'LABEL' || ($doc['docType'] ?? '') === 'LABEL') {
                        $labelData = $doc['encodedLabel'] ?? null;
                        $labelUrl = $doc['url'] ?? null;
                        break;
                    }
                }
            }

            if (!$trackingNumber) {
                return ShipmentResponse::failure('No tracking number received', [], $response);
            }

            Log::channel('carrier')->info('FedEx shipment created', [
                'tracking' => $trackingNumber,
            ]);

            return ShipmentResponse::success(
                trackingNumber: $trackingNumber,
                labelUrl: $labelUrl,
                labelData: $labelData,
                rawResponse: $response,
                totalCharge: $transaction['completedShipmentDetail']['shipmentRating']['shipmentRateDetails'][0]['totalNetCharge'] ?? null,
            );

        } catch (CarrierException $e) {
            Log::channel('carrier')->error('FedEx shipment failed', [
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);
            
            // Check if this is an address-related error that can be retried
            if ($this->isAddressRelatedError($e)) {
                Log::channel('carrier')->info('FedEx address error detected in shipment, retrying with stripped fields', [
                    'error' => $e->getMessage(),
                ]);
                
                try {
                    // Retry with stripped address fields
                    $retryPayload = $this->buildShipmentPayload($request);
                    
                    // Strip state and postal code from recipient address for Caribbean territories
                    if (isset($retryPayload['requestedShipment']['recipients'][0]['address'])) {
                        $address = &$retryPayload['requestedShipment']['recipients'][0]['address'];
                        $countryCode = $address['countryCode'] ?? '';
                        
                        // For countries that don't accept state/province, remove the field entirely
                        // Empty string might still cause validation errors
                        if (in_array($countryCode, ['SX', 'BQ', 'VG', 'KY', 'CW', 'AW'])) {
                            unset($address['stateOrProvinceCode']);
                        } else {
                            unset($address['stateOrProvinceCode']);
                        }
                        
                        // For countries without postal codes, ensure postalCode is empty or removed
                        if (empty($address['postalCode']) || $address['postalCode'] === '00000') {
                            unset($address['postalCode']);
                        }
                    }
                    
                    Log::channel('carrier')->info('FedEx retry shipment with stripped address', [
                        'recipient_country' => $retryPayload['requestedShipment']['recipients'][0]['address']['countryCode'] ?? 'unknown',
                    ]);
                    
                    $response = $this->post('/ship/v1/shipments', $retryPayload);
                    
                    // Extract tracking number and label (same as success path)
                    $output = $response['output'] ?? [];
                    $transaction = $output['transactionShipments'][0] ?? [];
                    
                    $trackingNumber = $transaction['masterTrackingNumber'] ??
                        $transaction['pieceResponses'][0]['trackingNumber'] ?? null;
                    
                    $labelData = null;
                    $labelUrl = null;
                    
                    if (isset($transaction['pieceResponses'][0]['packageDocuments'])) {
                        foreach ($transaction['pieceResponses'][0]['packageDocuments'] as $doc) {
                            if (($doc['contentType'] ?? '') === 'LABEL' || ($doc['docType'] ?? '') === 'LABEL') {
                                $labelData = $doc['encodedLabel'] ?? null;
                                $labelUrl = $doc['url'] ?? null;
                                break;
                            }
                        }
                    }
                    
                    if (!$trackingNumber) {
                        return ShipmentResponse::failure('No tracking number received after retry', [], $response);
                    }
                    
                    Log::channel('carrier')->info('FedEx shipment created after address retry', [
                        'tracking' => $trackingNumber,
                    ]);
                    
                    return ShipmentResponse::success(
                        trackingNumber: $trackingNumber,
                        labelUrl: $labelUrl,
                        labelData: $labelData,
                        rawResponse: $response,
                        totalCharge: $transaction['completedShipmentDetail']['shipmentRating']['shipmentRateDetails'][0]['totalNetCharge'] ?? null,
                    );
                } catch (\Exception $retryException) {
                    Log::channel('carrier')->error('FedEx shipment retry also failed', [
                        'error' => $retryException->getMessage(),
                    ]);
                    // Fall through to return original error
                }
            }
            
            return ShipmentResponse::failure($e->getMessage(), $e->getErrors(), $e->getRawResponse());
        }
    }

    /**
     * Get shipping label for existing shipment
     */
    public function getLabel(string $trackingNumber): LabelResponse
    {
        // FedEx doesn't have a separate label endpoint - labels are returned on shipment creation
        // For re-printing, you'd use the document retrieval endpoint
        try {
            // This would typically call a document retrieval API
            // For now, return a placeholder
            return LabelResponse::failure('Label retrieval not implemented - use createShipment');
        } catch (\Exception $e) {
            return LabelResponse::failure($e->getMessage());
        }
    }

    /**
     * Track a shipment
     * POST /track/v1/trackingnumbers
     */
    public function track(string $trackingNumber): TrackingResponse
    {
        $payload = [
            'includeDetailedScans' => true,
            'trackingInfo' => [
                [
                    'trackingNumberInfo' => [
                        'trackingNumber' => $trackingNumber,
                    ],
                ],
            ],
        ];

        $response = $this->post('/track/v1/trackingnumbers', $payload);

        $trackResult = $response['output']['completeTrackResults'][0]['trackResults'][0] ?? [];

        $latestStatus = $trackResult['latestStatusDetail'] ?? [];
        $events = [];

        foreach ($trackResult['scanEvents'] ?? [] as $event) {
            $events[] = [
                'timestamp' => $event['date'] ?? null,
                'status' => $event['eventType'] ?? '',
                'description' => $event['eventDescription'] ?? '',
                'location' => isset($event['scanLocation'])
                    ? "{$event['scanLocation']['city']}, {$event['scanLocation']['stateOrProvinceCode']}"
                    : null,
            ];
        }

        return new TrackingResponse(
            trackingNumber: $trackingNumber,
            status: TrackingResponse::normalizeStatus($latestStatus['code'] ?? 'unknown'),
            statusDescription: $latestStatus['description'] ?? '',
            estimatedDelivery: $trackResult['estimatedDeliveryTimeWindow']['window']['ends'] ?? null,
            actualDelivery: $trackResult['actualDeliveryTime'] ?? null,
            signedBy: $trackResult['deliveryDetails']['receivedByName'] ?? null,
            events: $events,
            currentLocation: $latestStatus['scanLocation']['city'] ?? null,
            rawResponse: $response,
        );
    }

    /**
     * Cancel/void a shipment
     * PUT /ship/v1/shipments/cancel
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $payload = [
                'accountNumber' => ['value' => (string) $this->getAccountNumber()], // Ensure string type
                'trackingNumber' => $trackingNumber,
            ];

            $this->request('PUT', '/ship/v1/shipments/cancel', $payload);

            Log::channel('carrier')->info('FedEx shipment cancelled', [
                'tracking' => $trackingNumber,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('carrier')->error('FedEx cancel failed', [
                'tracking' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate address
     * POST /address/v1/addresses/resolve
     */
    public function validateAddress(array $address): array
    {
        try {
            $payload = [
                'addressesToValidate' => [
                    [
                        'address' => [
                            'streetLines' => array_filter([$address['street1'] ?? '', $address['street2'] ?? '']),
                            'city' => $address['city'] ?? '',
                            'stateOrProvinceCode' => $address['state'] ?? '',
                            'postalCode' => $address['postal_code'] ?? '',
                            'countryCode' => $address['country_code'] ?? 'US',
                        ],
                    ],
                ],
            ];

            $response = $this->post('/address/v1/addresses/resolve', $payload);
            $resolved = $response['output']['resolvedAddresses'][0] ?? null;

            if ($resolved) {
                return [
                    'street1' => $resolved['streetLinesToken'][0] ?? $address['street1'],
                    'street2' => $resolved['streetLinesToken'][1] ?? null,
                    'city' => $resolved['city'] ?? $address['city'],
                    'state' => $resolved['stateOrProvinceCode'] ?? $address['state'],
                    'postal_code' => $resolved['postalCode'] ?? $address['postal_code'],
                    'country_code' => $resolved['countryCode'] ?? $address['country_code'],
                    'residential' => $resolved['classification'] === 'RESIDENTIAL',
                ];
            }

            return $address;
        } catch (\Exception $e) {
            // If validation fails, return original address
            return $address;
        }
    }

    /**
     * Build rate request payload
     */
    private function buildRatePayload(ShipmentRequest $request): array
    {
        $payload = [
            'accountNumber' => ['value' => (string) $this->getAccountNumber()], // Ensure string type
            'returnTransitTimes' => true, // Request transit time and delivery date
            'requestedShipment' => [
                'shipper' => $this->formatContact($request->senderName, $request->senderPhone, $request->senderAddress),
                'recipient' => $this->formatContact($request->recipientName, $request->recipientPhone, $request->recipientAddress),
                'shipDateStamp' => $request->shipDate ?? now()->format('Y-m-d'),
                'pickupType' => 'USE_SCHEDULED_PICKUP', // Changed from USE_SCHEDULED_PICKUP (requires active pickup)
                'rateRequestType' => ['ACCOUNT', 'LIST'],
                'requestedPackageLineItems' => $this->formatPackages($request->packages),
            ],
        ];

        // Add special services to get surcharge pricing for available addons
        // FedEx returns itemized surcharges for these services in the rate response
        if (!empty($request->requestedServices)) {
            $payload['requestedShipment']['shipmentSpecialServices'] = [
                'specialServiceTypes' => $request->requestedServices,
            ];
        }

        return $payload;
    }

    /**
     * Build shipment request payload
     */
    private function buildShipmentPayload(ShipmentRequest $request): array
    {
        $payload = [
            'labelResponseOptions' => 'LABEL', // Returns base64 encoded label - URL_ONLY requires re-authentication
            'requestedShipment' => [
                'shipper' => $this->formatContact($request->senderName, $request->senderPhone, $request->senderAddress),
                'recipients' => [
                    $this->formatContact($request->recipientName, $request->recipientPhone, $request->recipientAddress),
                ],
                'shipDateStamp' => $request->shipDate ?? now()->format('Y-m-d'), // Fixed: capital S in DateStamp
                'serviceType' => $request->serviceType,
                'packagingType' => $request->packagingType,
                'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION', // Changed from USE_SCHEDULED_PICKUP (requires active pickup)
                'blockInsightVisibility' => false,
                'shippingChargesPayment' => [
                    'paymentType' => 'SENDER',
                    'payor' => [
                        'responsibleParty' => [
                            'accountNumber' => ['value' => (string) $this->getAccountNumber()], // Ensure string type
                        ],
                    ],
                ],
                'labelSpecification' => [
                    'imageType' => 'PDF', // Valid: PDF, PNG, EPL2, ZPLII
                    'labelStockType' => 'STOCK_4X6', // Fixed: Changed from PAPER_4X6 (invalid) to STOCK_4X6
                ],
                'requestedPackageLineItems' => $this->formatPackages($request->packages),
            ],
            'accountNumber' => ['value' => (string) $this->getAccountNumber()], // Ensure string type
        ];

        // Add customs for international
        if ($request->commodities && count($request->commodities) > 0) {
            // Calculate total customs value from all commodities (rounded to 2 decimals)
            // Use direct rounding to avoid precision issues
            $totalCustomsValue = round(array_reduce(
                $request->commodities,
                fn($carry, $item) => $carry + ($item->totalValue ?? 0),
                0
            ), 2);

            $payload['requestedShipment']['customsClearanceDetail'] = [
                'dutiesPayment' => [
                    'paymentType' => $request->dutiesPayor ?? 'SENDER',
                ],
                'isDocumentOnly' => false,
                // Required: Purpose of shipment - FedEx PRODUCT.INFO.REQUIRED error fix
                'commercialInvoice' => [
                    'purpose' => 'SOLD', // Valid: SOLD, GIFT, SAMPLE, NOT_SOLD, PERSONAL_EFFECTS, REPAIR_AND_RETURN, RETURN, OTHER
                    // 'termsOfSale' => 'DAP', // Changed from DDU (FedEx no longer accepts DDU, DAP is the replacement)
                ],
                'commodities' => $this->formatCommodities($request->commodities),
            ];

            // Add export detail with proper documentation
            // Try without exportComplianceStatement first - it might be causing the enum error
            // FedEx may not require this field, or it might need a different format
            // $payload['requestedShipment']['customsClearanceDetail']['exportDetail'] = [
            //     'exportComplianceStatement' => '30.37(a)',
            // ];

            // Add recipient tax ID for export documents if available
            // FedEx valid types: PASSPORT, NATIONAL_ID, TAX_ID, PERSONAL_STATE, PERSONAL_COUNTRY, COMPANY
            if ($request->recipientTaxId) {
                $payload['requestedShipment']['customsClearanceDetail']['recipientCustomsId'] = [
                    'type' => 'PASSPORT', // Changed from PERSONAL_COUNTRY (more reliable)
                    'value' => (string) $request->recipientTaxId, // Ensure string type
                ];
            }
        }

        // Add reference
        if ($request->referenceNumber) {
            $payload['requestedShipment']['requestedPackageLineItems'][0]['customerReferences'] = [
                [
                    'customerReferenceType' => 'CUSTOMER_REFERENCE',
                    'value' => (string) $request->referenceNumber, // Ensure string type
                ],
            ];
        }

        return $payload;
    }

    /**
     * Format contact/address for FedEx API
     * Handles countries without postal codes and Caribbean state quirks using database Country model
     */
    private function formatContact(string $name, string $phone, $address): array
    {
        $countryCode = strtoupper($address->countryCode ?? 'US');

        // Query database for country-specific rules
        $country = Country::where('code', $countryCode)->first();

        // Use carrier_code for API calls (e.g., BQ-BO → BQ)
        $carrierCountryCode = $country?->getCarrierCode() ?? substr($countryCode, 0, 2);

        $requiresPostalCode = $country ? $country->has_postal_code : true;
        $acceptsState = $country ? ($country->fedex_accepts_state ?? true) : true;

        // Truncate address to 2 lines if too long (FedEx limit: 35 chars per line)
        $streetLines = $this->truncateAddressToTwoLines(
            $address->street1 ?? '',
            $address->street2 ?? '',
            35 // FedEx max length per street line
        );

        $addressData = [
            'streetLines' => array_filter($streetLines),
            'city' => $address->city,
            'countryCode' => $carrierCountryCode,
        ];

        // FedEx state/province handling:
        // Only add state if the country's FedEx integration accepts it
        // Many Caribbean territories reject state values (VG, KY, SX, etc.)
        if (!empty($address->state) && $acceptsState) {
            $addressData['stateOrProvinceCode'] = $address->state;
        }

        // FedEx API postal code handling:
        // FedEx REQUIRES postalCode field for most countries, even if the country doesn't use postal codes
        // Always include postalCode to avoid "postalCode can not be null" errors
        // Use provided postal code if available, otherwise use "00000" as fallback
        $postalCode = !empty($address->postalCode) ? trim($address->postalCode) : '00000';
        $addressData['postalCode'] = $postalCode;
        
        // Special handling for Sint Maarten (SX) - FedEx requires stateOrProvinceCode
        if ($carrierCountryCode === 'SX') {
            unset($addressData['stateOrProvinceCode']);
        }


        return [
            'contact' => [
                'personName' => $name,
                'phoneNumber' => $phone,
            ],
            'address' => $addressData,
        ];
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
     * Format packages for FedEx API
     */
    private function formatPackages(array $packages): array
    {
        return array_map(function ($pkg) {
            // Round weight to max 2 decimal places
            // Use direct rounding to avoid precision issues
            $weight = round(max(1, (float) $pkg->weight), 2);
            
            $package = [
                'weight' => [
                    'value' => $weight,
                    'units' => $pkg->weightUnit === 'KG' ? 'KG' : 'LB',
                ],
            ];

            // Only include dimensions if they are valid (all > 0)
            // FedEx requires integer dimensions, and rejects 0 dimensions for YOUR_PACKAGING
            if ($pkg->length > 0 && $pkg->width > 0 && $pkg->height > 0) {
                $package['dimensions'] = [
                    'length' => (int) ceil($pkg->length), // Round up to nearest integer
                    'width' => (int) ceil($pkg->width),
                    'height' => (int) ceil($pkg->height),
                    'units' => $pkg->dimensionUnit === 'CM' ? 'CM' : 'IN',
                ];
            }

            if ($pkg->declaredValue) {
                // Round declared value to 2 decimal places (standard currency precision)
                // Use direct rounding to avoid precision issues
                $amount = round((float) $pkg->declaredValue, 2);
                $package['declaredValue'] = [
                    'amount' => $amount,
                    'currency' => 'USD',
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
        return array_map(function ($item) {
            // Round weight to max 2 decimal places - use direct rounding
            $weight = round(max(0.1, (float) $item->weight), 2);
            
            // Round currency amounts to 2 decimal places - use direct rounding
            $unitPrice = round(max(0.01, (float) $item->unitValue), 2);
            $customsValue = round(max(0.01, (float) $item->totalValue), 2);
            

            
            return [
                'description' => substr($item->description, 0, 450),
                'quantity' => (int) $item->quantity,
                'quantityUnits' => 'EA', // Fixed: Changed from PCS (invalid) to EA (most common, valid)
                'weight' => [
                    'value' => $weight,
                    'units' => strtoupper($item->weightUnit) === 'KG' ? 'KG' : 'LB',
                ],
                // FedEx requires BOTH unitPrice AND customsValue for commodities
                'unitPrice' => [
                    'amount' => $unitPrice,
                    'currency' => 'USD',
                ],
                'customsValue' => [
                    'amount' => $customsValue,
                    'currency' => 'USD',
                ],
                'countryOfManufacture' => $item->countryOfOrigin ?? 'US',
                'harmonizedCode' => $this->normalizeHarmonizedCode($item->hsCode ?? '853110'), // Ensure 6+ digits
            ];
        }, $commodities);
    }

    /**
     * Clean numeric values in payload to ensure proper precision
     * Recursively processes arrays to round weights and currency amounts
     * Also ensures account numbers and reference values are strings
     */
    private function cleanNumericValues(array $data, ?string $parentKey = null): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively clean nested arrays first
                $cleaned = $this->cleanNumericValues($value, $key);
                
                // Check if this is a weight object (has 'value' and 'units' keys)
                if (isset($cleaned['value']) && isset($cleaned['units']) && in_array($cleaned['units'], ['LB', 'KG'])) {
                    // Round weight to max 2 decimal places - use direct rounding to avoid precision issues
                    $cleaned['value'] = round((float) $cleaned['value'], 2);
                }
                // Check if this is a currency object (has 'amount' and 'currency' keys)
                elseif (isset($cleaned['amount']) && isset($cleaned['currency'])) {
                    // Round currency to 2 decimal places - use direct rounding
                    $cleaned['amount'] = round((float) $cleaned['amount'], 2);
                }
                // Check if this is an accountNumber object (key is 'accountNumber' or parent is 'accountNumber' or 'responsibleParty')
                elseif (($key === 'accountNumber' || $parentKey === 'accountNumber' || $parentKey === 'responsibleParty') && isset($cleaned['value'])) {
                    // Ensure account number is a string
                    $cleaned['value'] = (string) $cleaned['value'];
                }
                // Check if this is a customerReferences array item
                elseif (isset($cleaned['customerReferenceType']) && isset($cleaned['value'])) {
                    // Ensure reference value is a string
                    $cleaned['value'] = (string) $cleaned['value'];
                }
                // Check if this is a recipientCustomsId object
                elseif (isset($cleaned['type']) && isset($cleaned['value'])) {
                    // Ensure customs ID value is a string
                    $cleaned['value'] = (string) $cleaned['value'];
                }
                
                $data[$key] = $cleaned;
            } elseif (is_float($value) || (is_numeric($value) && !is_string($value))) {
                // Handle numeric values based on context
                if ($key === 'value') {
                    // Check parent context to determine if it should be string or float
                    if ($parentKey === 'accountNumber' || $parentKey === 'responsibleParty' ||
                        (isset($data['customerReferenceType']) || isset($data['type']))) {
                        // This is an account number or reference/customs ID - should be string
                        $data[$key] = (string) $value;
                    } else {
                        // This might be a weight value - round to max 2 decimals
                        $data[$key] = round((float) $value, 2);
                    }
                } elseif ($key === 'amount') {
                    // Currency amount - round to 2 decimals
                    $data[$key] = round((float) $value, 2);
                }
            }
        }
        return $data;
    }

    /**
     * Normalize all float values in payload to prevent JSON encoding precision issues
     * This is a more aggressive cleaning that handles all float values
     */
    private function normalizeAllFloats(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned = $this->normalizeAllFloats($value);
                
                // After cleaning nested array, check if it's a weight or currency object
                if (isset($cleaned['value']) && isset($cleaned['units']) && in_array($cleaned['units'], ['LB', 'KG'])) {
                    // Weight object - round value to max 2 decimals
                    $cleaned['value'] = round((float) $cleaned['value'], 2);
                } elseif (isset($cleaned['amount']) && isset($cleaned['currency'])) {
                    // Currency object - round amount to 2 decimals
                    $cleaned['amount'] = round((float) $cleaned['amount'], 2);
                }
                
                $data[$key] = $cleaned;
            } elseif (is_float($value)) {
                // Handle standalone float values
                if ($key === 'value') {
                    // Round to max 2 decimals (likely weight)
                    $data[$key] = round($value, 2);
                } elseif ($key === 'amount') {
                    // Round to 2 decimals (currency)
                    $data[$key] = round($value, 2);
                } else {
                    // Other float - round to 2 decimals
                    $data[$key] = round($value, 2);
                }
            }
        }
        return $data;
    }

    /**
     * Normalize harmonized code to ensure it's at least 6 digits
     * FedEx requires 6-10 digit HS codes
     */
    private function normalizeHarmonizedCode(?string $hsCode): string
    {
        if (empty($hsCode)) {
            return '853110'; // Default for signaling/alarm equipment
        }
        
        // Remove any non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $hsCode);
        
        // Pad to at least 6 digits if shorter
        if (strlen($cleaned) < 6) {
            $cleaned = str_pad($cleaned, 6, '0', STR_PAD_RIGHT);
        }
        
        // Limit to 10 digits max
        if (strlen($cleaned) > 10) {
            $cleaned = substr($cleaned, 0, 10);
        }
        
        return $cleaned;
    }

    /**
     * Final numeric cleanup - ensures all float values are properly rounded to max 2 decimals
     * This is a final pass to catch any values that might have been missed
     */
    private function finalNumericCleanup(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->finalNumericCleanup($value);
            } elseif (is_float($value)) {
                // Round all floats to max 2 decimals
                $data[$key] = round($value, 2);
            }
        }
        return $data;
    }

    /**
     * Post request with fixed float precision in JSON
     * This ensures all numeric values are properly rounded in the JSON string before sending
     */
    private function postWithFixedPrecision(string $endpoint, array $data): array
    {
        // Encode to JSON
        $jsonString = json_encode($data, JSON_UNESCAPED_SLASHES);
        
        // Fix float precision in JSON string
        $jsonString = $this->fixFloatPrecisionInJson($jsonString);
        
        // Decode back to array for Laravel HTTP client
        $fixedData = json_decode($jsonString, true);
        
        // Send the request with fixed data
        return $this->post($endpoint, $fixedData);
    }

    /**
     * Fix float precision errors in JSON string
     * This method finds all numeric values in the JSON and ensures they are properly rounded
     */
    private function fixFloatPrecisionInJson(string $json): string
    {
        // Fix weight values (max 2 decimals)
        $json = preg_replace_callback(
            '/"value":\s*([0-9]+\.[0-9]+)/',
            function($matches) {
                $value = (float)$matches[1];
                $rounded = round($value, 2);
                return '"value": ' . number_format($rounded, 2, '.', '');
            },
            $json
        );
        
        // Fix amount values (max 2 decimals)
        $json = preg_replace_callback(
            '/"amount":\s*([0-9]+\.[0-9]+)/',
            function($matches) {
                $value = (float)$matches[1];
                $rounded = round($value, 2);
                return '"amount": ' . number_format($rounded, 2, '.', '');
            },
            $json
        );
        
        return $json;
    }
}
