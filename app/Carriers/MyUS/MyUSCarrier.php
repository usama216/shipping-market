<?php

namespace App\Carriers\MyUS;

use App\Carriers\AbstractCarrier;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\DTOs\ShipmentResponse;
use App\Carriers\DTOs\RateResponse;
use App\Carriers\DTOs\TrackingResponse;
use App\Carriers\DTOs\LabelResponse;
use App\Carriers\Exceptions\CarrierException;
use App\Carriers\Exceptions\CarrierAuthException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MyUSCarrier - MyUS API Implementation
 * 
 * MyUS is a package forwarding service
 * Base URL: gateway.myus.com
 */
class MyUSCarrier extends AbstractCarrier
{
    public function getName(): string
    {
        return 'myus';
    }

    /**
     * Authenticate with MyUS API
     * Uses Bearer token authentication
     */
    public function authenticate(): bool
    {
        $bearerToken = $this->config['bearer_token'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($bearerToken) && empty($apiKey)) {
            throw new CarrierAuthException('MyUS', 'Missing bearer_token or api_key');
        }

        // MyUS uses Bearer token authentication
        // If bearer_token is provided, use it directly
        // Otherwise, we might need to authenticate with api_key to get a token
        if (!empty($bearerToken)) {
            // Remove "Bearer " prefix if present
            $this->accessToken = str_replace('Bearer ', '', $bearerToken);
            $this->tokenExpiresAt = null; // Bearer tokens typically don't expire, or have long expiry
        } else {
            // If only api_key is provided, we might need to authenticate
            // This would depend on MyUS API documentation
            // For now, use api_key as token
            $this->accessToken = $apiKey;
            $this->tokenExpiresAt = null;
        }

        Log::channel('carrier')->info('MyUS authentication successful');
        return true;
    }

    /**
     * Override request to use MyUS API structure
     * Based on old integration: API key is passed as query parameter, not header
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = [], bool $isRetry = false): array
    {
        // Build URL with API key as query parameter (MyUS API pattern)
        $apiKey = $this->config['api_key'] ?? '';
        $baseEndpoint = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        // Add API key as query parameter (MyUS API pattern from old code)
        $url = $baseEndpoint;
        if (!empty($apiKey)) {
            $separator = str_contains($baseEndpoint, '?') ? '&' : '?';
            $url = $baseEndpoint . $separator . 'api_key=' . urlencode($apiKey);
        }

        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // MyUS may use Bearer token in some cases, but API key in query is primary
        $token = $this->getAccessToken();
        if ($token) {
            $defaultHeaders['Authorization'] = "Bearer {$token}";
        }

        // Add MyUS specific headers (if needed)
        $affiliateId = $this->config['affiliate_id'] ?? '';
        if (!empty($affiliateId)) {
            $defaultHeaders['X-Affiliate-ID'] = $affiliateId;
        }

        $headers = array_merge($defaultHeaders, $headers);

        try {
            $response = Http::withHeaders($headers)
                        ->timeout(10) // Reduced from 30 to 10 seconds
                        ->retry(1, 500) // Reduced retries
                ->{strtolower($method)}($url, $data);

            $statusCode = $response->status();
            $rawBody = $response->body();
            $body = $response->json() ?? [];

            Log::channel('carrier')->info("MyUS API Request", [
                'method' => $method,
                'url' => $url,
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'headers_sent' => array_keys($headers),
            ]);

            if ($response->failed()) {
                Log::channel('carrier')->error("MyUS API Error - Full Details", [
                    'method' => $method,
                    'url' => $url,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'raw_body' => substr($rawBody, 0, 1000), // First 1000 chars
                    'body' => $body,
                    'headers_sent' => $headers,
                ]);

                // For 403 errors, provide more helpful error message
                if ($statusCode === 403) {
                    throw new CarrierException(
                        message: "Access Denied (403). The endpoint '{$endpoint}' may not exist or authentication failed. " .
                                 "Please check: 1) The API endpoint URL is correct, 2) Authentication credentials are valid, " .
                                 "3) Your account has access to this endpoint. Full URL: {$url}",
                        errors: [['code' => '403', 'message' => 'Access Denied']],
                        rawResponse: ['status' => 403, 'body' => substr($rawBody, 0, 500)],
                        code: 403
                    );
                }

                throw CarrierException::fromApiResponse($body, $statusCode);
            }

            return $body;

        } catch (\Exception $e) {
            Log::channel('carrier')->error("MyUS API Error", [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CarrierException) {
                throw $e;
            }

            throw new CarrierException(
                message: "HTTP request failed: {$e->getMessage()}",
                errors: [],
                rawResponse: [],
                code: $e->getCode()
            );
        }
    }

    /**
     * Get shipping rates
     * Note: MyUS API endpoints need to be confirmed from documentation
     */
    public function getRates(ShipmentRequest $request): array
    {
        try {
            // TODO: Implement based on MyUS API documentation
            // This is a placeholder - actual endpoint and payload structure need to be confirmed
            $payload = [
                'origin' => [
                    'address' => $request->senderAddress->street1 ?? '',
                    'city' => $request->senderAddress->city ?? '',
                    'state' => $request->senderAddress->state ?? '',
                    'postal_code' => $request->senderAddress->postalCode ?? '',
                    'country' => $request->senderAddress->countryCode ?? 'US',
                ],
                'destination' => [
                    'address' => $request->recipientAddress->street1 ?? '',
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
                    ];
                }, $request->packages),
            ];

            // TODO: Replace with actual MyUS rate endpoint
            $response = $this->post('/api/rates', $payload);

            // TODO: Parse response based on actual MyUS API structure
            $rates = [];
            // Placeholder - needs actual implementation
            return $rates;

        } catch (\Exception $e) {
            Log::channel('carrier')->error('MyUS rate request failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create shipment and generate label
     * Note: MyUS API endpoints need to be confirmed from documentation
     */
    public function createShipment(ShipmentRequest $request, ?array $packageModels = null): ShipmentResponse
    {
        try {
            // TODO: Implement based on MyUS API documentation
            $payload = [
                'member_id' => $this->config['member_id'] ?? '',
                'suite' => $this->config['suite'] ?? '',
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
                'packages' => array_map(function($pkg) {
                    return [
                        'weight' => $pkg->weight ?? 0,
                        'weight_unit' => $pkg->weightUnit ?? 'LB',
                        'length' => $pkg->length ?? 0,
                        'width' => $pkg->width ?? 0,
                        'height' => $pkg->height ?? 0,
                        'dimension_unit' => $pkg->dimensionUnit ?? 'IN',
                        'declared_value' => $pkg->declaredValue ?? 0,
                    ];
                }, $request->packages),
            ];

            // TODO: Replace with actual MyUS shipment endpoint
            $response = $this->post('/api/shipments', $payload);

            // TODO: Parse response based on actual MyUS API structure
            $trackingNumber = $response['tracking_number'] ?? null;
            $labelData = $response['label_data'] ?? null;
            $labelUrl = $response['label_url'] ?? null;

            if (!$trackingNumber) {
                return ShipmentResponse::failure('No tracking number received', [], $response);
            }

            Log::channel('carrier')->info('MyUS shipment created', [
                'tracking' => $trackingNumber,
            ]);

            return ShipmentResponse::success(
                trackingNumber: $trackingNumber,
                labelUrl: $labelUrl,
                labelData: $labelData,
                rawResponse: $response,
            );

        } catch (CarrierException $e) {
            Log::channel('carrier')->error('MyUS shipment failed', [
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);
            
            return ShipmentResponse::failure($e->getMessage(), $e->getErrors(), $e->getRawResponse());
        }
    }

    /**
     * Get shipping label for existing shipment
     */
    public function getLabel(string $trackingNumber): LabelResponse
    {
        try {
            // TODO: Implement based on MyUS API documentation
            $response = $this->get("/api/shipments/{$trackingNumber}/label");

            $labelData = $response['label_data'] ?? null;
            $labelUrl = $response['label_url'] ?? null;

            if (!$labelData && !$labelUrl) {
                return LabelResponse::failure('No label data received');
            }

            return LabelResponse::success(
                labelUrl: $labelUrl,
                labelData: $labelData,
                rawResponse: $response,
            );

        } catch (\Exception $e) {
            return LabelResponse::failure($e->getMessage());
        }
    }

    /**
     * Track a shipment
     */
    public function track(string $trackingNumber): TrackingResponse
    {
        try {
            // TODO: Implement based on MyUS API documentation
            $response = $this->get("/api/shipments/{$trackingNumber}/track");

            $status = $response['status'] ?? 'unknown';
            $statusDescription = $response['status_description'] ?? '';
            $events = $response['events'] ?? [];

            return new TrackingResponse(
                trackingNumber: $trackingNumber,
                status: TrackingResponse::normalizeStatus($status),
                statusDescription: $statusDescription,
                estimatedDelivery: $response['estimated_delivery'] ?? null,
                actualDelivery: $response['actual_delivery'] ?? null,
                signedBy: $response['signed_by'] ?? null,
                events: $events,
                currentLocation: $response['current_location'] ?? null,
                rawResponse: $response,
            );

        } catch (\Exception $e) {
            Log::channel('carrier')->error('MyUS tracking failed', [
                'tracking' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);

            return new TrackingResponse(
                trackingNumber: $trackingNumber,
                status: 'unknown',
                statusDescription: $e->getMessage(),
                estimatedDelivery: null,
                actualDelivery: null,
                signedBy: null,
                events: [],
                currentLocation: null,
                rawResponse: [],
            );
        }
    }

    /**
     * Cancel/void a shipment
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            // TODO: Implement based on MyUS API documentation
            $this->delete("/api/shipments/{$trackingNumber}");

            Log::channel('carrier')->info('MyUS shipment cancelled', [
                'tracking' => $trackingNumber,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('carrier')->error('MyUS cancel failed', [
                'tracking' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate address
     */
    public function validateAddress(array $address): array
    {
        // MyUS may not have address validation
        // Return address as-is for now
        return $address;
    }

    /**
     * Get packages from MyUS for a specific suite/member
     * 
     * @param string|null $suite Suite number (e.g., "5975-441")
     * @param string|null $memberId Member ID
     * @param array $filters Additional filters (status, date_from, date_to, etc.)
     * @return array List of packages
     */
    public function getPackages(?string $suite = null, ?string $memberId = null, array $filters = []): array
    {
        try {
            $memberId = $memberId ?? $this->config['member_id'] ?? '';
            $suite = $suite ?? $this->config['suite'] ?? '';

            $queryParams = [];
            
            if (!empty($memberId)) {
                $queryParams['member_id'] = $memberId;
            }
            
            if (!empty($suite)) {
                $queryParams['suite'] = $suite;
            }

            // Add filters
            if (!empty($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $queryParams['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $queryParams['date_to'] = $filters['date_to'];
            }

            // Try different common endpoint patterns based on old integration structure
            // Old code showed: /Registration/Application/CreateMyUSAccount/
            // So packages might be: /Package/GetPackages/ or similar
            $endpointsToTry = [
                // Pattern 1: Based on old integration structure
                '/Package/GetPackages/',
                '/Package/GetMemberPackages/',
                '/Package/GetSuitePackages/',
                // Pattern 2: Standard REST API
                '/api/packages',
                '/api/v1/packages',
                // Pattern 3: Member-based
                "/api/members/{$memberId}/packages",
                "/api/v1/members/{$memberId}/packages",
                // Pattern 4: Suite-based
                "/api/suite/{$suite}/packages",
                "/api/v1/suite/{$suite}/packages",
                // Pattern 5: Affiliate API pattern
                '/api/affiliate/packages',
                "/api/affiliate/{$this->config['affiliate_id']}/packages",
            ];

            $lastError = null;
            foreach ($endpointsToTry as $endpoint) {
                try {
                    // Build endpoint with query params
                    $fullEndpoint = $endpoint;
                    if (!empty($queryParams)) {
                        $separator = str_contains($endpoint, '?') ? '&' : '?';
                        $fullEndpoint .= $separator . http_build_query($queryParams);
                    }

                    Log::channel('carrier')->info('MyUS trying endpoint', [
                        'endpoint' => $fullEndpoint,
                    ]);

                    $response = $this->get($fullEndpoint);

                    // If we get here, the request succeeded
                    $packages = $response['packages'] ?? $response['data'] ?? $response ?? [];

                    Log::channel('carrier')->info('MyUS packages retrieved successfully', [
                        'endpoint' => $fullEndpoint,
                        'suite' => $suite,
                        'member_id' => $memberId,
                        'count' => count($packages),
                    ]);

                    return is_array($packages) ? $packages : [];

                } catch (\Exception $e) {
                    $lastError = $e;
                    // Continue to next endpoint
                    Log::channel('carrier')->warning('MyUS endpoint failed, trying next', [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // If all endpoints failed, throw the last error with helpful message
            throw new \Exception(
                "All MyUS API endpoints failed. Last error: " . $lastError->getMessage() . 
                ". Please check MyUS API documentation for the correct endpoint structure. " .
                "Tried endpoints: " . implode(', ', $endpointsToTry)
            );

        } catch (\Exception $e) {
            Log::channel('carrier')->error('MyUS get packages failed', [
                'suite' => $suite,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get shipments from MyUS for a specific suite/member
     * 
     * @param string|null $suite Suite number
     * @param string|null $memberId Member ID
     * @param array $filters Additional filters
     * @return array List of shipments
     */
    public function getShipments(?string $suite = null, ?string $memberId = null, array $filters = []): array
    {
        try {
            $memberId = $memberId ?? $this->config['member_id'] ?? '';
            $suite = $suite ?? $this->config['suite'] ?? '';

            $queryParams = [];
            
            if (!empty($memberId)) {
                $queryParams['member_id'] = $memberId;
            }
            
            if (!empty($suite)) {
                $queryParams['suite'] = $suite;
            }

            // Add filters
            if (!empty($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $queryParams['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $queryParams['date_to'] = $filters['date_to'];
            }

            // TODO: Replace with actual MyUS shipments endpoint
            $endpoint = '/api/shipments';
            if (!empty($queryParams)) {
                $endpoint .= '?' . http_build_query($queryParams);
            }

            $response = $this->get($endpoint);

            // TODO: Parse response based on actual MyUS API structure
            $shipments = $response['shipments'] ?? $response['data'] ?? $response ?? [];

            Log::channel('carrier')->info('MyUS shipments retrieved', [
                'suite' => $suite,
                'member_id' => $memberId,
                'count' => count($shipments),
            ]);

            return is_array($shipments) ? $shipments : [];

        } catch (\Exception $e) {
            Log::channel('carrier')->error('MyUS get shipments failed', [
                'suite' => $suite,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get package details by ID
     * 
     * @param string $packageId Package ID from MyUS
     * @return array Package details
     */
    public function getPackageDetails(string $packageId): array
    {
        try {
            // TODO: Replace with actual MyUS package details endpoint
            $response = $this->get("/api/packages/{$packageId}");

            Log::channel('carrier')->info('MyUS package details retrieved', [
                'package_id' => $packageId,
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::channel('carrier')->error('MyUS get package details failed', [
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
