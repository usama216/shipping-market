<?php

namespace App\Carriers\DHL;

use App\Carriers\AbstractCarrier;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\DTOs\ShipmentResponse;
use App\Carriers\DTOs\RateResponse;
use App\Carriers\DTOs\TrackingResponse;
use App\Carriers\DTOs\LabelResponse;
use App\Carriers\Exceptions\CarrierException;
use App\Carriers\Exceptions\CarrierAuthException;
use App\Models\Package;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * DHLCarrier - DHL MyDHL API Implementation
 * 
 * Uses MyDHL Express API (legacy XML deprecated late 2024)
 * Docs: https://developer.dhl.com/api-reference/dhl-express-mydhl-api
 */
class DHLCarrier extends AbstractCarrier
{
    public function getName(): string
    {
        return 'dhl';
    }

    /**
     * DHL uses Basic Auth, not OAuth
     * Credentials are sent with each request
     */
    public function authenticate(): bool
    {
        $apiKey = $this->config['api_key'] ?? '';
        $apiSecret = $this->config['api_secret'] ?? '';

        if (empty($apiKey) || empty($apiSecret)) {
            throw new CarrierAuthException('DHL', 'Missing api_key or api_secret');
        }

        // DHL validates credentials on first API call
        // Set token to base64 encoded credentials for Basic Auth
        $this->accessToken = base64_encode("{$apiKey}:{$apiSecret}");
        $this->tokenExpiresAt = null; // No expiry for Basic Auth

        return true;
    }

    /**
     * Override request to use Basic Auth instead of Bearer
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = [], bool $isRetry = false): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $apiKey = $this->config['api_key'] ?? '';
        $apiSecret = $this->config['api_secret'] ?? '';

        try {
            Log::channel('carrier')->debug("DHL API Request - Making HTTP Call", [
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $url,
                'data_size' => strlen(json_encode($data)),
            ]);
            
            // CRITICAL: Normalize float values in data before JSON encoding to prevent precision errors
            // Laravel's HTTP client uses json_encode() internally, which can introduce floating-point precision errors
            // We need to ensure all weight values are properly normalized before encoding
            if (is_array($data)) {
                $data = $this->normalizeFloatValues($data);
            }
            
            // CRITICAL: Use a custom JSON encoder to fix float precision issues
            // Laravel's HTTP client will use json_encode() which can introduce precision errors
            // We'll encode manually and fix the precision, then send as raw body
            $jsonString = json_encode($data, JSON_UNESCAPED_SLASHES);
            
            // Fix weight precision errors in JSON string
            $jsonString = $this->fixWeightPrecisionInJson($jsonString);
            
            // CRITICAL: Use throw(false) to prevent automatic exception throwing on HTTP errors
            // This allows us to capture the full error response
            try {
                $response = Http::withBasicAuth($apiKey, $apiSecret)
                            ->withHeaders([
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                            ])
                            ->timeout(30)
                            ->retry(2, 1000)
                            ->withBody($jsonString, 'application/json')
                    ->{strtolower($method)}($url);
            } catch (\Illuminate\Http\Client\RequestException $e) {
                // If Laravel throws an exception, try to get the response from it
                Log::channel('carrier')->error("DHL API Request - RequestException Caught", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'has_response' => $e->response !== null,
                ]);
                
                if ($e->response) {
                    $response = $e->response;
                } else {
                    // Re-throw if we can't get the response
                    throw $e;
                }
            }
            
            // CRITICAL: Get status code immediately - don't let exceptions prevent us from logging
            $statusCode = $response->status();
            
            Log::channel('carrier')->debug("DHL API Request - HTTP Call Complete", [
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_type' => get_class($response),
            ]);
            
            // CRITICAL: Log response status immediately
            Log::channel('carrier')->debug("DHL API Request - Response Status", [
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_successful' => $response->successful(),
                'response_failed' => $response->failed(),
                'response_client_error' => $response->clientError(),
                'response_server_error' => $response->serverError(),
            ]);
            
            // Get raw body FIRST - important for error responses
            $rawBodyString = $response->body();
            
            Log::channel('carrier')->debug("DHL API Request - Raw Body Retrieved", [
                'method' => $method,
                'endpoint' => $endpoint,
                'raw_body_type' => gettype($rawBodyString),
                'raw_body_length' => is_string($rawBodyString) ? strlen($rawBodyString) : 0,
                'raw_body_preview' => is_string($rawBodyString) ? substr($rawBodyString, 0, 200) : 'not_string',
            ]);
            
            // Try to parse JSON response
            $body = $response->json() ?? [];
            
            // If json() parsing failed or returned empty, try parsing raw body
            if (empty($body) && is_string($rawBodyString) && !empty($rawBodyString)) {
                $parsedBody = json_decode($rawBodyString, true);
                if ($parsedBody && is_array($parsedBody)) {
                    $body = $parsedBody;
                    Log::channel('carrier')->debug("DHL API Request - Parsed from Raw Body", [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'parsed_keys' => array_keys($body),
                    ]);
                }
            }
            
            Log::channel('carrier')->debug("DHL API Request - Body Parsed", [
                'method' => $method,
                'endpoint' => $endpoint,
                'body_keys' => array_keys($body),
                'body_size' => strlen(json_encode($body)),
            ]);

            // Log complete request payload (COMMENTED - too heavy)
            // Log::channel('carrier')->info("DHL API Request - Complete Payload", [
            //     'method' => $method,
            //     'endpoint' => $endpoint,
            //     'payload' => $data, // Full payload
            // ]);
            
            // Save request to file for detailed review
            file_put_contents(
                storage_path('logs/dhl-request-' . date('Y-m-d-H-i-s') . '.json'),
                json_encode([
                    'timestamp' => now()->toIso8601String(),
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'payload' => $data,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Log complete response (COMMENTED - too heavy)
            // Log::channel('carrier')->info("DHL API Response - Complete Response", [
            //     'method' => $method,
            //     'endpoint' => $endpoint,
            //     'status' => $statusCode,
            //     'response' => $body, // Full response
            // ]);
            
            // Save response to file for detailed review - ALWAYS save, even for errors
            $responseFile = storage_path('logs/dhl-response-' . date('Y-m-d-H-i-s') . '.json');
            file_put_contents(
                $responseFile,
                json_encode([
                    'timestamp' => now()->toIso8601String(),
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'response_successful' => $response->successful(),
                    'response_failed' => $response->failed(),
                    'response' => $body,
                    'raw_body_string' => $rawBodyString, // Include full raw body
                    'raw_body_length' => is_string($rawBodyString) ? strlen($rawBodyString) : 0,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            
            Log::channel('carrier')->debug("DHL API Request - Response File Saved", [
                'method' => $method,
                'endpoint' => $endpoint,
                'response_file' => $responseFile,
                'file_exists' => file_exists($responseFile),
            ]);

            // Check if response failed - use multiple methods to ensure we catch it
            // CRITICAL: Check status code directly, as response->failed() might not work for all cases
            $isFailed = $statusCode >= 400 || $response->failed();
            
            Log::channel('carrier')->debug("DHL API Request - Failure Check", [
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_successful' => $response->successful(),
                'response_failed' => $response->failed(),
                'response_client_error' => $response->clientError(),
                'response_server_error' => $response->serverError(),
                'status_code_400_plus' => $statusCode >= 400,
                'is_failed' => $isFailed,
            ]);
            
            // ALWAYS process errors if status >= 400, regardless of response->failed()
            if ($isFailed || $statusCode >= 400) {
                // CRITICAL: Log immediately that we detected a failed response
                Log::channel('carrier')->error("DHL API Request - FAILED RESPONSE DETECTED", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'raw_body_type' => gettype($rawBodyString),
                    'raw_body_is_string' => is_string($rawBodyString),
                    'raw_body_length' => is_string($rawBodyString) ? strlen($rawBodyString) : 0,
                ]);
                
                // For errors, ALWAYS use the raw body string to ensure we get complete response
                $fullBody = is_string($rawBodyString) ? $rawBodyString : (string)$rawBodyString;
                
                // Log the raw body immediately to ensure we capture it
                Log::channel('carrier')->error("DHL API Request - RAW BODY CAPTURED", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'full_body_length' => strlen($fullBody),
                    'full_body_preview' => substr($fullBody, 0, 500),
                ]);
                
                // Parse the full body fresh - don't trust response->json() for errors
                $errorBody = json_decode($fullBody, true);
                if ($errorBody && is_array($errorBody)) {
                    $body = $errorBody; // Use the freshly parsed error body
                } elseif (empty($body) || !isset($body['additionalDetails'])) {
                    // If json() didn't parse correctly or missing additionalDetails, try raw body
                    if (is_string($fullBody) && !empty($fullBody)) {
                        $errorBody = json_decode($fullBody, true);
                        if ($errorBody && is_array($errorBody)) {
                            $body = $errorBody;
                        } else {
                            // Last resort: create structure with raw body
                            $body = [
                                'detail' => 'Multiple problems found, see Additional Details',
                                'title' => 'Validation error',
                                'instance' => '/expressapi/shipments',
                                'raw_body' => $fullBody,
                            ];
                        }
                    }
                }
                
                // Extract validation errors for better error messages
                $validationErrors = [];
                if (isset($body['additionalDetails']) && is_array($body['additionalDetails'])) {
                    foreach ($body['additionalDetails'] as $detail) {
                        if (isset($detail['field'])) {
                            $field = $detail['field'];
                            $errorMsg = $detail['message'] ?? $detail['invalidValue'] ?? $detail['value'] ?? 'Validation error';
                            $validationErrors[] = [
                                'field' => $field,
                                'message' => $errorMsg,
                                'value' => $detail['value'] ?? $detail['invalidValue'] ?? null,
                            ];
                        } else {
                            // Some errors don't have a field, just a message
                            $validationErrors[] = [
                                'field' => 'general',
                                'message' => $detail['message'] ?? $detail['invalidValue'] ?? json_encode($detail),
                                'value' => $detail['value'] ?? $detail['invalidValue'] ?? null,
                            ];
                        }
                    }
                }
                
                // Log FULL error to Laravel log (not truncated)
                // Log the raw body string length and first 1000 chars to verify we have full response
                Log::channel('carrier')->error("DHL API Error - FULL DETAILS", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'full_body_string_length' => strlen($fullBody),
                    'full_body_string_preview' => substr($fullBody, 0, 1000),
                    'full_body_string' => $fullBody, // Complete body - may be large
                    'parsed_body_keys' => array_keys($body),
                    'parsed_body' => $body,
                    'validation_errors_count' => count($validationErrors),
                    'validation_errors' => $validationErrors,
                    'has_additional_details' => isset($body['additionalDetails']),
                    'additional_details_type' => isset($body['additionalDetails']) ? gettype($body['additionalDetails']) : 'none',
                    'additional_details_count' => isset($body['additionalDetails']) && is_array($body['additionalDetails']) ? count($body['additionalDetails']) : 0,
                    'additional_details_sample' => isset($body['additionalDetails']) && is_array($body['additionalDetails']) ? ($body['additionalDetails'][0] ?? null) : null,
                ]);
                
                // Also write to a separate file for easier access
                $errorLogData = [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'request_payload' => $data,
                    'response_body_raw' => $fullBody,
                    'response_body_length' => strlen($fullBody),
                    'response_json' => $body,
                    'validation_errors' => $validationErrors,
                    'has_additional_details' => isset($body['additionalDetails']),
                    'additional_details_count' => isset($body['additionalDetails']) && is_array($body['additionalDetails']) ? count($body['additionalDetails']) : 0,
                ];
                
                // Write error file - ensure directory exists and file is writable
                $errorLogPath = storage_path('logs/dhl-error-' . date('Y-m-d-H-i-s') . '.json');
                $errorLogDir = dirname($errorLogPath);
                if (!is_dir($errorLogDir)) {
                    @mkdir($errorLogDir, 0755, true);
                }
                $errorFileWritten = @file_put_contents($errorLogPath, json_encode($errorLogData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                // Log that we created the error file
                Log::channel('carrier')->info('DHL error file created yes', [
                    'file' => $errorLogPath,
                    'file_exists' => file_exists($errorLogPath),
                    'file_size' => file_exists($errorLogPath) ? filesize($errorLogPath) : 0,
                    'bytes_written' => $errorFileWritten,
                    'writable' => is_writable($errorLogDir),
                ]);
                
                // CRITICAL: Also log the error file path in the exception message context
                // This ensures we can find it even if exception is truncated
                if (file_exists($errorLogPath)) {
                    Log::channel('carrier')->error('DHL ERROR FILE LOCATION', [
                        'error_file' => $errorLogPath,
                        'error_file_size' => filesize($errorLogPath),
                        'can_read' => is_readable($errorLogPath),
                    ]);
                }
                
                // CRITICAL: Log the full body string to BOTH carrier and default log
                // This ensures we capture it even if exception truncates the message
                Log::channel('carrier')->error("DHL API Error - RAW RESPONSE BODY", [
                    'full_body_string' => $fullBody,
                    'full_body_length' => strlen($fullBody),
                    'parsed_body_keys' => array_keys($body),
                    'additional_details_exists' => isset($body['additionalDetails']),
                ]);
                
                // Also log to default channel to ensure it's visible
                Log::error("DHL API Error - RAW RESPONSE BODY (Default Log)", [
                    'full_body_string' => $fullBody,
                    'full_body_length' => strlen($fullBody),
                    'parsed_body_keys' => array_keys($body),
                    'additional_details_exists' => isset($body['additionalDetails']),
                    'validation_errors_extracted' => $validationErrors,
                ]);
                
                // CRITICAL: Ensure the full body is in the body array before passing to exception
                // This ensures the exception has access to the complete response
                if (!isset($body['_raw_body_string'])) {
                    $body['_raw_body_string'] = $fullBody;
                }
                if (!isset($body['_raw_body_length'])) {
                    $body['_raw_body_length'] = strlen($fullBody);
                }
                
                throw CarrierException::fromApiResponse($body, $statusCode);
            }

            return $body;

        } catch (\Exception $e) {
            // Log::channel('carrier')->error("DHL API Error", [
            //     'method' => $method,
            //     'endpoint' => $endpoint,
            //     'error' => $e->getMessage(),
            // ]);

            if ($e instanceof CarrierException) {
                throw $e;
            }

            throw new CarrierException("HTTP request failed: {$e->getMessage()}");
        }
    }

    /**
     * Get shipping rates
     * POST /rates
     */
    public function getRates(ShipmentRequest $request): array
    {
        $logId = uniqid('rate_', true);
        
        // Log comprehensive rate request details
        Log::channel('carrier')->info("DHL Rate Request - START", [
            'log_id' => $logId,
            'carrier' => 'DHL',
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
        
        $payload = $this->buildRatePayload($request);
        
        // Log the payload being sent to DHL
        Log::channel('carrier')->info("DHL Rate Request - Payload", [
            'log_id' => $logId,
            'payload' => $payload,
        ]);
        
        $response = $this->post('/rates', $payload);

        // Log raw response
        Log::channel('carrier')->info("DHL Rate Request - Raw Response", [
            'log_id' => $logId,
            'response' => $response,
            'products_count' => count($response['products'] ?? []),
        ]);

        $rates = [];
        foreach ($response['products'] ?? [] as $product) {
            $rate = RateResponse::fromDHL($product);
            $rates[] = $rate;
            
            // Log each rate with full breakdown
            Log::channel('carrier')->info("DHL Rate Response - Service Breakdown", [
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
        
        Log::channel('carrier')->info("DHL Rate Request - COMPLETE", [
            'log_id' => $logId,
            'rates_count' => count($rates),
            'rates_summary' => array_map(fn($r) => [
                'service' => $r->serviceName,
                'total' => $r->totalCharge,
            ], $rates),
        ]);

        return $rates;
    }

    /**
     * Create shipment and generate label
     * POST /shipments
     * 
     * @param ShipmentRequest $request
     * @param array|null $packageModels Optional array of Package models for invoice retrieval
     */
    public function createShipment(ShipmentRequest $request, ?array $packageModels = null): ShipmentResponse
    {
        $logId = uniqid('ship_', true);
        $logFile = storage_path('logs/dhl-shipment-' . date('Y-m-d-H-i-s') . '-' . $logId . '.json');
        
        try {
            Log::channel('carrier')->info("DHL Create Shipment - START", [
                'log_id' => $logId,
                'log_file' => $logFile,
                'service_type' => $request->serviceType,
                'package_count' => count($request->packages),
                'commodity_count' => count($request->commodities ?? []),
            ]);
            
            // Step 1: Build payload
            Log::channel('carrier')->info("DHL Create Shipment - Step 1: Building Payload", ['log_id' => $logId]);
            $payload = $this->buildShipmentPayload($request, $packageModels);
            
            // CRITICAL: Normalize weight values to ensure they are exactly multiples of 0.001
            // This prevents floating-point precision issues when JSON encoding
            $payload = $this->normalizePayloadWeights($payload);
            
            // Log complete payload to file
            file_put_contents($logFile, json_encode([
                'step' => 'payload_built',
                'timestamp' => now()->toIso8601String(),
                'log_id' => $logId,
                'payload' => $payload,
                'payload_size' => strlen(json_encode($payload)),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            Log::channel('carrier')->info("DHL Create Shipment - Step 2: Payload Built", [
                'log_id' => $logId,
                'payload_keys' => array_keys($payload),
                'has_customer_details' => isset($payload['customerDetails']),
                'has_packages' => isset($payload['packages']),
                'has_export_declaration' => isset($payload['exportDeclaration']),
                'package_count' => isset($payload['packages']) ? count($payload['packages']) : 0,
            ]);
            
            // Step 2: Send request
            Log::channel('carrier')->info("DHL Create Shipment - Step 3: Sending Request", ['log_id' => $logId]);
            
            $response = null;
            $retriedWithoutHDP = false;
            
            try {
                $response = $this->post('/shipments', $payload);
                
                // Log complete response to file
                $logData = json_decode(file_get_contents($logFile), true) ?? [];
                $logData['step'] = 'response_received';
                $logData['response_timestamp'] = now()->toIso8601String();
                $logData['response'] = $response;
                $logData['response_size'] = strlen(json_encode($response));
                file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } catch (CarrierException $e) {
                // Check if error is HDP not available (error 7008)
                // If so, retry without HDP
                $rawResponse = $e->getRawResponse();
                $errorDetail = $rawResponse['detail'] ?? '';
                
                $isHDPError = strpos($errorDetail, '7008') !== false && 
                             strpos($errorDetail, 'HDP') !== false &&
                             !empty($payload['valueAddedServices']);
                
                if ($isHDPError) {
                    // Check if HDP was in the services
                    $hasHDP = false;
                    foreach ($payload['valueAddedServices'] as $service) {
                        if (($service['serviceCode'] ?? '') === 'HDP') {
                            $hasHDP = true;
                            break;
                        }
                    }
                    
                    if ($hasHDP) {
                        Log::channel('carrier')->warning("DHL Create Shipment - HDP not available for this route, retrying without HDP", [
                            'log_id' => $logId,
                            'error' => $errorDetail,
                            'origin' => $payload['customerDetails']['shipperDetails']['postalAddress']['countryCode'] ?? 'unknown',
                            'destination' => $payload['customerDetails']['receiverDetails']['postalAddress']['countryCode'] ?? 'unknown',
                        ]);
                        
                        // Remove HDP from valueAddedServices
                        $payload['valueAddedServices'] = array_filter(
                            $payload['valueAddedServices'],
                            fn($service) => ($service['serviceCode'] ?? '') !== 'HDP'
                        );
                        $payload['valueAddedServices'] = array_values($payload['valueAddedServices']); // Re-index
                        
                        // Retry without HDP
                        try {
                            $response = $this->post('/shipments', $payload);
                            $retriedWithoutHDP = true;
                            
                            Log::channel('carrier')->info("DHL Create Shipment - Retry without HDP successful", [
                                'log_id' => $logId,
                            ]);
                            
                            // Log complete response to file
                            $logData = json_decode(file_get_contents($logFile), true) ?? [];
                            $logData['step'] = 'response_received_retry';
                            $logData['retry_timestamp'] = now()->toIso8601String();
                            $logData['retry_payload'] = $payload;
                            $logData['retry_without_hdp'] = true;
                            $logData['response'] = $response;
                            $logData['response_size'] = strlen(json_encode($response));
                            file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        } catch (CarrierException $retryException) {
                            // If retry also fails, log and re-throw the original exception
                            Log::channel('carrier')->error("DHL Create Shipment - Retry without HDP also failed", [
                                'log_id' => $logId,
                                'retry_error' => $retryException->getMessage(),
                            ]);
                            throw $e; // Re-throw original exception
                        }
                    } else {
                        // HDP wasn't in the services, so this is a different error
                        throw $e;
                    }
                } else {
                    // Not an HDP error, re-throw
                    throw $e;
                }
            }
            
            // If we get here without a response, something went wrong
            if (!$response) {
                throw new CarrierException('Failed to create shipment: No response received');
            }
            
            // Continue with response processing
            Log::channel('carrier')->info("DHL Create Shipment - Step 4: Response Received", [
                'log_id' => $logId,
                'response_keys' => array_keys($response),
                'has_tracking_number' => isset($response['shipmentTrackingNumber']),
                'has_documents' => isset($response['documents']),
            ]);

            // Extract tracking number (waybill number in DHL terms)
            $trackingNumber = $response['shipmentTrackingNumber'] ?? null;

            // Get label data
            $labelData = null;
            $labelUrl = null;

            if (isset($response['documents'])) {
                Log::channel('carrier')->info("DHL Create Shipment - Step 6: Processing Documents", [
                    'log_id' => $logId,
                    'document_count' => count($response['documents']),
                    'document_types' => array_column($response['documents'], 'typeCode'),
                ]);
                
                foreach ($response['documents'] as $doc) {
                    if (in_array($doc['typeCode'] ?? '', ['waybill', 'label'])) {
                        $labelData = $doc['content'] ?? null;
                        $labelUrl = $doc['url'] ?? null;
                        Log::channel('carrier')->info("DHL Create Shipment - Label Found", [
                            'log_id' => $logId,
                            'type' => $doc['typeCode'],
                            'has_content' => !empty($labelData),
                            'has_url' => !empty($labelUrl),
                        ]);
                        break;
                    }
                }
            }

            if (!$trackingNumber) {
                Log::channel('carrier')->error("DHL Create Shipment - FAILED: No Tracking Number", [
                    'log_id' => $logId,
                    'response_keys' => array_keys($response),
                    'response_sample' => json_encode(array_slice($response, 0, 5, true)),
                ]);
                
                // Update log file
                $logData = json_decode(file_get_contents($logFile), true) ?? [];
                $logData['step'] = 'failed_no_tracking';
                $logData['error'] = 'No tracking number received';
                $logData['response'] = $response;
                file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                return ShipmentResponse::failure('No tracking number received', [], $response);
            }

            Log::channel('carrier')->info("DHL Create Shipment - SUCCESS", [
                'log_id' => $logId,
                'tracking_number' => $trackingNumber,
                'has_label' => !empty($labelData),
            ]);
            
            // Update log file with success
            $logData = json_decode(file_get_contents($logFile), true) ?? [];
            $logData['step'] = 'success';
            $logData['tracking_number'] = $trackingNumber;
            $logData['has_label'] = !empty($labelData);
            file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Extract total charge from response (DHL API structure may vary)
            $totalCharge = null;
            if (isset($response['totalPrice']) && is_array($response['totalPrice']) && !empty($response['totalPrice'])) {
                $totalCharge = $response['totalPrice'][0]['price'] ?? null;
            } elseif (isset($response['prices']) && is_array($response['prices']) && !empty($response['prices'])) {
                $totalCharge = $response['prices'][0]['price'] ?? null;
            }

            // Extract estimated delivery date if available
            $estimatedDelivery = $response['estimatedDeliveryDate'] ?? null;

            return ShipmentResponse::success(
                trackingNumber: $trackingNumber,
                labelUrl: $labelUrl,
                labelData: $labelData,
                rawResponse: $response,
                totalCharge: $totalCharge,
                estimatedDelivery: $estimatedDelivery,
            );

        } catch (CarrierException $e) {
            Log::channel('carrier')->error("DHL Create Shipment - FAILED: CarrierException", [
                'log_id' => $logId,
                'error' => $e->getMessage(),
                'error_length' => strlen($e->getMessage()),
                'errors_count' => count($e->getErrors()),
                'errors' => $e->getErrors(),
                'raw_response' => $e->getRawResponse(),
            ]);
            
            // Update log file with error
            if (file_exists($logFile)) {
                $logData = json_decode(file_get_contents($logFile), true) ?? [];
            } else {
                $logData = ['log_id' => $logId];
            }
            $logData['step'] = 'failed_exception';
            $logData['error'] = $e->getMessage();
            $logData['errors'] = $e->getErrors();
            $logData['raw_response'] = $e->getRawResponse();
            file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            return ShipmentResponse::failure($e->getMessage(), $e->getErrors(), $e->getRawResponse());
        } catch (\Exception $e) {
            Log::channel('carrier')->error("DHL Create Shipment - FAILED: General Exception", [
                'log_id' => $logId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update log file with error
            if (file_exists($logFile)) {
                $logData = json_decode(file_get_contents($logFile), true) ?? [];
            } else {
                $logData = ['log_id' => $logId];
            }
            $logData['step'] = 'failed_general_exception';
            $logData['error'] = $e->getMessage();
            $logData['error_class'] = get_class($e);
            file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            return ShipmentResponse::failure($e->getMessage(), [], []);
        }
    }

    /**
     * Get shipping label
     * GET /shipments/{shipmentTrackingNumber}/get-image
     */
    public function getLabel(string $trackingNumber): LabelResponse
    {
        try {
            $response = $this->get("/shipments/{$trackingNumber}/get-image", [
                'typeCode' => 'label',
            ]);

            $labelData = $response['documents'][0]['content'] ?? null;

            if ($labelData) {
                return LabelResponse::success($labelData, 'PDF', null, $trackingNumber);
            }

            return LabelResponse::failure('No label data in response', $response);
        } catch (\Exception $e) {
            return LabelResponse::failure($e->getMessage());
        }
    }


    /**
     * Track a shipment
     * GET /tracking
     */
    public function track(string $trackingNumber): TrackingResponse
    {
        $response = $this->get('/tracking', [
            'shipmentTrackingNumber' => $trackingNumber,
        ]);

        $shipment = $response['shipments'][0] ?? [];
        $events = [];

        foreach ($shipment['events'] ?? [] as $event) {
            $events[] = [
                'timestamp' => $event['timestamp'] ?? null,
                'status' => $event['typeCode'] ?? '',
                'description' => $event['description'] ?? '',
                'location' => isset($event['serviceArea'])
                    ? $event['serviceArea']['description']
                    : null,
            ];
        }

        $latestEvent = $shipment['events'][0] ?? [];

        return new TrackingResponse(
            trackingNumber: $trackingNumber,
            status: TrackingResponse::normalizeStatus($shipment['status'] ?? 'unknown'),
            statusDescription: $latestEvent['description'] ?? '',
            estimatedDelivery: $shipment['estimatedDeliveryDate'] ?? null,
            actualDelivery: $shipment['events'][0]['timestamp'] ?? null,
            signedBy: $shipment['receivedBy'] ?? null,
            events: $events,
            currentLocation: $latestEvent['serviceArea']['description'] ?? null,
            rawResponse: $response,
        );
    }

    /**
     * Cancel shipment
     * DELETE /shipments/{shipmentTrackingNumber}
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $this->delete("/shipments/{$trackingNumber}");

            // Log::channel('carrier')->info('DHL shipment cancelled', [
            //     'tracking' => $trackingNumber,
            // ]);

            return true;
        } catch (\Exception $e) {
            // Log::channel('carrier')->error('DHL cancel failed', [
            //     'tracking' => $trackingNumber,
            //     'error' => $e->getMessage(),
            // ]);
            return false;
        }
    }

    /**
     * Build rate request payload
     * Per DHL requirements: imperial units, payer account, proper address format
     */
    private function buildRatePayload(ShipmentRequest $request): array
    {
        $accountNumber = $this->getAccountNumber();
        
        return [
            'customerDetails' => [
                'shipperDetails' => $this->formatAddressForRate($request->senderAddress),
                'receiverDetails' => $this->formatAddressForRate($request->recipientAddress),
            ],
            'accounts' => [
                [
                    'typeCode' => 'shipper',
                    'number' => $accountNumber,
                ],
                [
                    'typeCode' => 'payer',
                    'number' => $accountNumber,
                ],
            ],
            // Use next business day (Monday-Friday) to ensure it's a valid pickup date
            // DHL doesn't operate on weekends
            'plannedShippingDateAndTime' => $this->getNextBusinessDay()->format('Y-m-d') . 'T10:00:00',
            'unitOfMeasurement' => 'imperial', // Changed from 'metric' per requirements
            'isCustomsDeclarable' => true,
            'packages' => $this->formatPackagesForRate($request->packages),
        ];
    }

    /**
     * Build shipment request payload
     * Per DHL requirements: disable invoice generation, enable Paperless Trade (HDP), attach Base64 invoice, complete export declaration
     * 
     * @param ShipmentRequest $request
     * @param array|null $packageModels Optional Package models for invoice retrieval
     */
    private function buildShipmentPayload(ShipmentRequest $request, ?array $packageModels = null): array
    {
        Log::channel('carrier')->debug('DHL buildShipmentPayload - START', [
            'service_type' => $request->serviceType,
            'package_count' => count($request->packages),
            'commodity_count' => count($request->commodities ?? []),
            'declared_value' => $request->declaredValue,
            'currency' => $request->currency,
        ]);
        
        $accountNumber = $this->getAccountNumber();
        
        Log::channel('carrier')->debug('DHL buildShipmentPayload - Account Number', [
            'account_number' => $accountNumber,
        ]);
        
        $payload = [
            'plannedShippingDateAndTime' => ($request->shipDate ?? now()->format('Y-m-d')) . 'T10:00:00 GMT+00:00',
            'pickup' => [
                'isRequested' => false, // Dropoff
            ],
            'productCode' => $this->mapServiceType($request->serviceType),
            'accounts' => [
                [
                    'typeCode' => 'shipper',
                    'number' => $accountNumber,
                ],
            ],
            'customerDetails' => [
                'shipperDetails' => [
                    'postalAddress' => $this->formatAddressForShipment($request->senderAddress),
                    'contactInformation' => [
                        'companyName' => $request->senderCompany ?? $request->senderName,
                        'fullName' => $request->senderName,
                        'phone' => $request->senderPhone,
                        'email' => $request->senderEmail ?? '',
                    ],
                    'registrationNumbers' => $this->getShipperRegistrationNumbers(),
                ],
                'receiverDetails' => [
                    'postalAddress' => $this->formatAddressForShipment($request->recipientAddress),
                    'contactInformation' => [
                        'companyName' => $request->recipientName,
                        'fullName' => $request->recipientName,
                        'phone' => $request->recipientPhone,
                        'email' => !empty($request->recipientEmail) ? $request->recipientEmail : 'noreply@example.com', // DHL requires non-empty email
                    ],
                ],
            ],
            'content' => [
                'packages' => $this->formatPackagesForShipment($request->packages),
                'isCustomsDeclarable' => true,
                'declaredValue' => $request->declaredValue ?? 0,
                'declaredValueCurrency' => $request->currency ?? 'USD',
                'incoterm' => 'DAP',
                'description' => 'Shipment contents', // Required by DHL
                'unitOfMeasurement' => 'imperial', // Required by DHL
            ],
            // Disable DHL invoice generation (MANDATORY per requirements)
            'outputImageProperties' => [
                'printerDPI' => 300,
                'encodingFormat' => 'pdf',
                'imageOptions' => [
                    [
                        'typeCode' => 'label',
                        'templateName' => 'ECOM26_84_001',
                    ],
                    [
                        'typeCode' => 'invoice',
                        'invoiceType' => 'commercial',
                        'isRequested' => false, // Disable DHL invoice generation
                    ],
                ],
            ],
        ];

        // Enable Paperless Trade (HDP) - MANDATORY FOR PRODUCTION
        // Note: HDP may not be available in test environment, so we conditionally add it
        // Default to production mode (false) if sandbox is not explicitly set
        $isSandbox = $this->config['sandbox'] ?? false;
        $valueAddedServices = $isSandbox
            ? [] // Skip HDP in test/sandbox environment
            : [
                [
                    'serviceCode' => 'HDP', // Paperless Trade
                ],
            ];
        
        // Add Dangerous Goods service (HE) if any items are marked as dangerous
        $dangerousGoods = $this->getDangerousGoods($packageModels);
        if (!empty($dangerousGoods)) {
            $valueAddedServices[] = [
                'serviceCode' => 'HE',
                'dangerousGoods' => $dangerousGoods,
            ];
        }
        
        $payload['valueAddedServices'] = $valueAddedServices;
        
        Log::channel('carrier')->debug('DHL buildShipmentPayload - Value Added Services', [
            'services' => $valueAddedServices,
            'service_count' => count($valueAddedServices),
        ]);

        // Attach Base64 commercial invoice (MANDATORY per requirements)
        Log::channel('carrier')->debug('DHL buildShipmentPayload - Getting Invoice', [
            'has_package_models' => !empty($packageModels),
            'package_model_count' => count($packageModels ?? []),
        ]);
        
        // Add export declaration with complete structure (REQUIRED) - must be inside content
        if ($request->commodities && count($request->commodities) > 0) {
            Log::channel('carrier')->debug('DHL buildShipmentPayload - Building Export Declaration', [
                'commodity_count' => count($request->commodities),
            ]);
            
            $exportContents = $this->formatCommoditiesForExport($request->commodities);
            
            $payload['content']['exportDeclaration'] = [
                'invoice' => [
                    'number' => $request->referenceNumber ?? 'INV-' . time(),
                    'date' => now()->format('Y-m-d'),
                ],
                'lineItems' => $exportContents, // DHL requires lineItems, not contents
            ];

            // Add recipient tax ID for export documents if available
            if ($request->recipientTaxId) {
                $payload['content']['exportDeclaration']['recipientReference'] = $request->recipientTaxId;
            }
            
            Log::channel('carrier')->debug('DHL buildShipmentPayload - Export Declaration Built', [
                'contents_count' => count($exportContents),
                'has_tax_id' => !empty($request->recipientTaxId),
            ]);
        } else {
            Log::channel('carrier')->warning('DHL buildShipmentPayload - No Commodities', [
                'warning' => 'No commodities provided for export declaration',
            ]);
        }
        
        // Attach Base64 commercial invoice (MANDATORY per requirements) - must be at top level, NOT inside content
        $invoiceBase64 = $this->getCommercialInvoiceBase64($packageModels);
        if ($invoiceBase64 && !empty(trim($invoiceBase64))) {
            $payload['documentImages'] = [
                [
                    'typeCode' => 'INV',
                    'imageFormat' => 'PDF',
                    'content' => $invoiceBase64,
                ],
            ];
            Log::channel('carrier')->debug('DHL buildShipmentPayload - Invoice Attached', [
                'invoice_length' => strlen($invoiceBase64),
                'invoice_preview' => substr($invoiceBase64, 0, 50) . '...',
            ]);
        } else {
            Log::channel('carrier')->warning('DHL buildShipmentPayload - No Invoice Available', [
                'warning' => 'Proceeding without commercial invoice',
                'invoice_base64_received' => !empty($invoiceBase64),
                'invoice_base64_empty' => empty(trim($invoiceBase64 ?? '')),
            ]);
        }

        // Add reference
        if ($request->referenceNumber) {
            $payload['customerReferences'] = [
                [
                    'value' => $request->referenceNumber,
                    'typeCode' => 'CU',
                ],
            ];
            Log::channel('carrier')->debug('DHL buildShipmentPayload - Reference Added', [
                'reference' => $request->referenceNumber,
            ]);
        }

        // Validate payload structure before returning
        $this->validateDhlPayloadStructure($payload);

        Log::channel('carrier')->debug('DHL buildShipmentPayload - COMPLETE', [
            'payload_keys' => array_keys($payload),
            'payload_size' => strlen(json_encode($payload)),
            'has_customer_details' => isset($payload['customerDetails']),
            'has_shipper_contact' => isset($payload['customerDetails']['shipperDetails']['contactInformation']),
            'has_receiver_contact' => isset($payload['customerDetails']['receiverDetails']['contactInformation']),
            'has_packages' => isset($payload['packages']),
            'has_export_declaration' => isset($payload['exportDeclaration']),
            'has_document_images' => isset($payload['documentImages']),
        ]);

        return $payload;
    }

    /**
     * Format address for DHL API (shipment requests)
     */
    private function formatAddress($address): array
    {
        // Truncate address to 2 lines if too long (DHL limit: 45 chars per addressLine1)
        $addressLines = $this->truncateAddressToTwoLines(
            $address->street1 ?? '',
            $address->street2 ?? '',
            45 // DHL max length per address line (addressLine1 max: 45)
        );
        
        // Ensure countryCode is exactly 2 characters (DHL requirement)
        $countryCode = $this->normalizeCountryCode($address->countryCode ?? '');
        
        $formatted = [
            'addressLine1' => substr($addressLines[0] ?? '', 0, 45), // Ensure max 45 chars
            'cityName' => $address->city,
            'provinceCode' => $address->state,
            'postalCode' => $address->postalCode,
            'countryCode' => $countryCode, // Max 2 characters
        ];
        
        // Only include addressLine2 if it has content (DHL rejects empty strings)
        if (isset($addressLines[1]) && !empty(trim($addressLines[1]))) {
            $formatted['addressLine2'] = substr(trim($addressLines[1]), 0, 45); // Max 45 chars
        }
        
        return $formatted;
    }

    /**
     * Format address for DHL shipment payload (includes city defaults)
     */
    private function formatAddressForShipment($address): array
    {
        // Truncate address to 2 lines if too long (DHL limit: 45 chars per addressLine1)
        $addressLines = $this->truncateAddressToTwoLines(
            $address->street1 ?? '',
            $address->street2 ?? '',
            45 // DHL max length per address line (addressLine1 max: 45)
        );
        
        // Ensure countryCode is exactly 2 characters (DHL requirement)
        $countryCode = $this->normalizeCountryCode($address->countryCode ?? '');
        
        $formatted = [
            'addressLine1' => substr($addressLines[0] ?? '', 0, 45), // Ensure max 45 chars
            'postalCode' => $address->postalCode,
            'cityName' => !empty(trim($address->city ?? '')) 
                ? trim($address->city) 
                : $this->getDefaultCityForCountry($countryCode),
            'countryCode' => $countryCode, // Max 2 characters
            'provinceCode' => $address->state ?? '',
        ];
        
        // Only include addressLine2 if it has content (DHL rejects empty strings)
        if (isset($addressLines[1]) && !empty(trim($addressLines[1]))) {
            $formatted['addressLine2'] = substr(trim($addressLines[1]), 0, 45); // Max 45 chars
        }
        
        return $formatted;
    }

    /**
     * Normalize country code to exactly 2 characters for DHL API
     * Handles cases where country code might be longer (e.g., "US-FL" -> "US", "BQ-BO" -> "BQ")
     */
    private function normalizeCountryCode(string $countryCode): string
    {
        $countryCode = strtoupper(trim($countryCode));
        
        // If empty, default to US
        if (empty($countryCode)) {
            return 'US';
        }
        
        // Query database for carrier code first (handles BQ-BO, BQ-SA, etc.)
        $country = \App\Models\Country::where('code', $countryCode)->first();
        if ($country) {
            $carrierCode = $country->getCarrierCode();
            if (strlen($carrierCode) === 2) {
                return $carrierCode;
            }
        }
        
        // If contains hyphen (e.g., "US-FL"), take first part
        if (str_contains($countryCode, '-')) {
            $parts = explode('-', $countryCode);
            $countryCode = $parts[0];
        }
        
        // Ensure exactly 2 characters
        $normalized = substr($countryCode, 0, 2);
        
        // Log if truncation occurred
        if (strlen($countryCode) > 2) {
            Log::channel('carrier')->warning('DHL country code truncated', [
                'original' => $countryCode,
                'normalized' => $normalized,
            ]);
        }
        
        return $normalized;
    }

    /**
     * Truncate address to 2 lines if too long
     * Intelligently splits at word boundaries to avoid cutting words
     * 
     * @param string $street1 First address line
     * @param string $street2 Second address line (optional)
     * @param int $maxLength Maximum characters per line (DHL: 45 for addressLine1)
     * @return array Array with max 2 address lines
     */
    private function truncateAddressToTwoLines(string $street1, string $street2 = '', int $maxLength = 45): array
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
     * Get default city name for a country code when city is not provided
     * Uses capital cities for better DHL API acceptance
     */
    private function getDefaultCityForCountry(string $countryCode): string
    {
        $defaultCities = [
            'BB' => 'Bridgetown',      // Barbados
            'JM' => 'Kingston',        // Jamaica
            'TT' => 'Port of Spain',   // Trinidad and Tobago
            'BS' => 'Nassau',          // Bahamas
            'AG' => 'St. John\'s',     // Antigua and Barbuda
            'GD' => 'St. George\'s',   // Grenada
            'LC' => 'Castries',        // Saint Lucia
            'VC' => 'Kingstown',       // Saint Vincent and the Grenadines
            'DM' => 'Roseau',          // Dominica
            'KN' => 'Basseterre',      // Saint Kitts and Nevis
            'BZ' => 'Belmopan',        // Belize
            'GY' => 'Georgetown',      // Guyana
            'SR' => 'Paramaribo',      // Suriname
            'CW' => 'Willemstad',      // Curaçao
            'AW' => 'Oranjestad',      // Aruba
            'SX' => 'Philipsburg',     // Sint Maarten
            'BQ' => 'Kralendijk',      // Bonaire
            'PR' => 'San Juan',        // Puerto Rico
            'VI' => 'Charlotte Amalie', // US Virgin Islands
            'VG' => 'Road Town',       // British Virgin Islands
            'KY' => 'George Town',     // Cayman Islands
            'TC' => 'Cockburn Town',   // Turks and Caicos
            'AI' => 'The Valley',      // Anguilla
            'MS' => 'Plymouth',        // Montserrat
        ];
        
        $countryCode = strtoupper($countryCode);
        return $defaultCities[$countryCode] ?? 'Unknown';
    }

    /**
     * Validate DHL payload structure to catch errors before API call
     */
    private function validateDhlPayloadStructure(array $payload): void
    {
        // Ensure customerDetails structure is correct
        if (isset($payload['customerDetails'])) {
            // Check for extraneous keys at customerDetails level
            $allowedKeys = ['shipperDetails', 'receiverDetails'];
            foreach (array_keys($payload['customerDetails']) as $key) {
                if (!in_array($key, $allowedKeys)) {
                    Log::channel('carrier')->error('DHL payload validation - extraneous key at customerDetails level', [
                        'key' => $key,
                        'allowed_keys' => $allowedKeys,
                    ]);
                }
            }

            // Validate receiverDetails structure
            if (isset($payload['customerDetails']['receiverDetails'])) {
                $receiverDetails = $payload['customerDetails']['receiverDetails'];
                
                // contactInformation is required
                if (!isset($receiverDetails['contactInformation'])) {
                    Log::channel('carrier')->error('DHL payload validation - contactInformation missing from receiverDetails');
                }

                // Check for extraneous keys (address fields should be in postalAddress)
                $allowedReceiverKeys = ['postalAddress', 'contactInformation'];
                foreach (array_keys($receiverDetails) as $key) {
                    if (!in_array($key, $allowedReceiverKeys)) {
                        Log::channel('carrier')->error('DHL payload validation - extraneous key in receiverDetails', [
                            'key' => $key,
                            'allowed_keys' => $allowedReceiverKeys,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Format address for DHL Rate API (simplified format per requirements)
     * Rate API requires: postalCode, cityName, countryCode, provinceCode
     * Note: DHL API requires cityName to have minLength: 1, so we provide a default if empty
     */
    private function formatAddressForRate($address): array
    {
        // Ensure countryCode is exactly 2 characters (DHL requirement)
        $countryCode = $this->normalizeCountryCode($address->countryCode ?? '');
        
        // DHL API requires cityName to have minLength: 1
        // If city is empty, use country-specific default city for better API acceptance
        $cityName = !empty(trim($address->city ?? '')) 
            ? trim($address->city) 
            : $this->getDefaultCityForCountry($countryCode);
        
        $formatted = [
            'postalCode' => $address->postalCode,
            'cityName' => $cityName,
            'countryCode' => $countryCode, // Max 2 characters
        ];
        
        // Only include provinceCode if state is provided (some countries don't have states)
        if (!empty($address->state)) {
            $formatted['provinceCode'] = $address->state;
        }
        
        return $formatted;
    }

    /**
     * Format packages for rate request (imperial units)
     */
    private function formatPackagesForRate(array $packages): array
    {
        return array_map(fn($pkg) => [
            // Keep weight in pounds (imperial)
            'weight' => $pkg->weightUnit === 'LB' ? $pkg->weight : $pkg->weight * 2.20462,
            // Keep dimensions in inches (imperial)
            'dimensions' => [
                'length' => $pkg->dimensionUnit === 'IN' ? $pkg->length : $pkg->length * 0.393701,
                'width' => $pkg->dimensionUnit === 'IN' ? $pkg->width : $pkg->width * 0.393701,
                'height' => $pkg->dimensionUnit === 'IN' ? $pkg->height : $pkg->height * 0.393701,
            ],
        ], $packages);
    }

    /**
     * Format packages for shipment request
     */
    private function formatPackagesForShipment(array $packages): array
    {
        return array_map(function($pkg, $i) {
            // Keep weight in lbs (imperial) - DHL requires weight to be a multiple of 0.001
            $weight = $pkg->weightUnit === 'LB' ? $pkg->weight : ($pkg->weight * 2.20462);
            
            // CRITICAL: Ensure weight is exactly a multiple of 0.001 to avoid floating-point precision issues
            // Method: Multiply by 1000, round to nearest integer using floor/ceil for exact integer, then divide by 1000
            // Use sprintf to format to exactly 3 decimal places, then parse back
            // This ensures the value is truly a multiple of 0.001 when JSON encoded
            // Example: 4.40924 -> 4409.24 -> 4409 -> 4.409
            $weightMultiplied = $weight * 1000;
            $weightRounded = (int) round($weightMultiplied);
            
            // Format to exactly 3 decimal places using number_format, then parse back
            // number_format is more reliable than sprintf for ensuring exact precision
            // This ensures JSON encoding won't introduce precision errors
            $weightString = number_format($weightRounded / 1000.0, 3, '.', '');
            $weight = (float) $weightString;
            
            // Keep dimensions in inches (imperial) - round to 2 decimal places
            $length = $pkg->dimensionUnit === 'IN' ? $pkg->length : ($pkg->length * 0.393701);
            $width = $pkg->dimensionUnit === 'IN' ? $pkg->width : ($pkg->width * 0.393701);
            $height = $pkg->dimensionUnit === 'IN' ? $pkg->height : ($pkg->height * 0.393701);
            
            return [
                'weight' => $weight,
                'dimensions' => [
                    'length' => round($length, 2),
                    'width' => round($width, 2),
                    'height' => round($height, 2),
                ],
            ];
        }, $packages, array_keys($packages));
    }

    /**
     * Normalize weight values in payload to ensure they are exactly multiples of 0.001
     * This prevents floating-point precision issues when JSON encoding
     * CRITICAL: This must be called right before sending to ensure weights are properly rounded
     */
    private function normalizePayloadWeights(array $payload): array
    {
        // Normalize package weights in content.packages
        if (isset($payload['content']['packages']) && is_array($payload['content']['packages'])) {
            foreach ($payload['content']['packages'] as $index => $package) {
                if (isset($package['weight'])) {
                    // Ensure weight is exactly a multiple of 0.001
                    // Use the same method as formatPackagesForShipment for consistency
                    $weight = $package['weight'];
                    $weightMultiplied = $weight * 1000;
                    $weightRounded = (int) round($weightMultiplied);
                    
                    // Use number_format to ensure exact 3 decimal places, then parse back
                    $weightString = number_format($weightRounded / 1000.0, 3, '.', '');
                    $payload['content']['packages'][$index]['weight'] = (float) $weightString;
                    
                    // Log for debugging
                    Log::channel('carrier')->debug('DHL normalizePayloadWeights - Weight Normalized', [
                        'original' => $weight,
                        'normalized' => $payload['content']['packages'][$index]['weight'],
                        'weight_string' => $weightString,
                    ]);
                }
            }
        }
        
        return $payload;
    }

    /**
     * Normalize float values in data array to prevent JSON encoding precision errors
     * Recursively processes arrays to find and normalize weight values
     */
    private function normalizeFloatValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeFloatValues($value);
            } elseif (is_float($value) && ($key === 'weight' || str_ends_with($key, 'weight'))) {
                // Normalize weight values to be exactly multiples of 0.001
                $weightMultiplied = $value * 1000;
                $weightRounded = (int) round($weightMultiplied);
                $weightString = number_format($weightRounded / 1000.0, 3, '.', '');
                $data[$key] = (float) $weightString;
                
                // Log for debugging
                Log::channel('carrier')->debug('DHL normalizeFloatValues - Weight Normalized', [
                    'key' => $key,
                    'original' => $value,
                    'normalized' => $data[$key],
                    'weight_string' => $weightString,
                    'json_encoded' => json_encode(['weight' => $data[$key]]),
                ]);
            }
        }
        
        return $data;
    }

    /**
     * Fix weight precision errors in JSON string
     * This method finds all weight values in the JSON and ensures they are exactly multiples of 0.001
     * This is necessary because even after normalization, json_encode() can introduce precision errors
     */
    private function fixWeightPrecisionInJson(string $json): string
    {
        // Pattern to match weight values: "weight", "netValue", or "grossValue" followed by a float
        // This will match: "weight": 4.40899999999999980815346134477294981479644775390625
        // and also: "netValue": 1.7999999999999998, "grossValue": 1.2000000000000002
        $patterns = [
            '/"weight"\s*:\s*([0-9]+\.[0-9]+)/' => 'weight',
            '/"netValue"\s*:\s*([0-9]+\.[0-9]+)/' => 'netValue',
            '/"grossValue"\s*:\s*([0-9]+\.[0-9]+)/' => 'grossValue',
        ];
        
        $fixed = $json;
        foreach ($patterns as $pattern => $keyName) {
            $fixed = preg_replace_callback($pattern, function($matches) use ($keyName) {
                $value = (float)$matches[1];
                
                // Normalize to exact multiple of 0.001
                $multiplied = $value * 1000;
                $rounded = (int) round($multiplied);
                $normalized = $rounded / 1000.0;
                
                // Format to exactly 3 decimal places
                $formatted = number_format($normalized, 3, '.', '');
                
                Log::channel('carrier')->debug('DHL fixWeightPrecisionInJson - Weight Fixed', [
                    'key' => $keyName,
                    'original' => $matches[1],
                    'normalized' => $formatted,
                ]);
                
                return '"' . $keyName . '": ' . $formatted;
            }, $fixed);
        }
        
        return $fixed;
    }

    /**
     * Format commodities for export declaration (DHL requires specific structure)
     */
    private function formatCommoditiesForExport(array $commodities): array
    {
        return array_map(function($item, $index) {
            // CRITICAL: Normalize weight to ensure it's exactly a multiple of 0.001
            // Convert to kg if needed (DHL expects weights in kg for export declaration)
            $weightKg = $item->weightUnit === 'LB' ? $item->weight * 0.453592 : $item->weight;
            
            // Normalize to exact multiple of 0.001 using same method as package weights
            $weightMultiplied = $weightKg * 1000;
            $weightRounded = (int) round($weightMultiplied);
            $weightString = number_format($weightRounded / 1000.0, 3, '.', '');
            $normalizedWeight = (float) $weightString;
            
            return [
                'number' => $index + 1, // Required: Line item number (1-based) - must be INTEGER, not string
                'description' => $item->description,
                'quantity' => [
                    'value' => $item->quantity, // DHL requires quantity as object with 'value'
                    'unitOfMeasurement' => 'PCS', // Pieces
                ],
                'price' => $item->totalValue,
                'weight' => [
                    'netValue' => $normalizedWeight, // DHL requires weight as object with 'netValue' - must be multiple of 0.001
                    'grossValue' => $normalizedWeight, // Same as net for simplicity - must be multiple of 0.001
                ],
                'manufacturerCountry' => $item->countryOfOrigin ?? 'US', // Required: Use manufacturerCountry, not originCountry
            ];
        }, $commodities, array_keys($commodities));
    }

    /**
     * Format commodities for customs with complete export declaration structure
     * Per requirements: commodityCodes array, exportReasonType, exportControlClassificationNumber
     */
    private function formatCommodities(array $commodities): array
    {
        return array_map(fn($item, $i) => [
            'number' => $i + 1,
            'description' => $item->description,
            'price' => $item->totalValue,
            'priceCurrency' => 'USD',
            'quantity' => [
                'value' => $item->quantity,
                'unitOfMeasurement' => 'PCS',
            ],
            // Required: commodityCodes array with typeCode and value
            'commodityCodes' => $item->hsCode ? [
                [
                    'typeCode' => 'outbound',
                    'value' => $item->hsCode,
                ],
            ] : [],
            // Required: exportReasonType
            'exportReasonType' => 'permanent',
            'manufacturerCountry' => $item->countryOfOrigin ?? 'US',
            // Required: exportControlClassificationNumber
            'exportControlClassificationNumber' => 'EAR99',
            'weight' => [
                // Round to 3 decimal places (multiple of 0.001) as required by DHL
                'grossValue' => round($item->weightUnit === 'LB' ? $item->weight * 0.453592 : $item->weight, 3),
            ],
        ], $commodities, array_keys($commodities));
    }

    /**
     * Map generic service type to DHL product code
     */
    private function mapServiceType(string $serviceType): string
    {
        $mapping = [
            'EXPRESS_WORLDWIDE' => 'P',
            'EXPRESS_12_00' => 'Y',
            'EXPRESS_9_00' => 'K',
            'ECONOMY_SELECT' => 'H',
            'DHL_EXPRESS' => 'P',
        ];

        return $mapping[$serviceType] ?? 'P'; // Default to Express Worldwide
    }

    /**
     * Get commercial invoice as Base64 string from package invoices
     * 
     * Priority:
     * 1. Auto-generated commercial invoice (from CommercialInvoiceService)
     * 2. Manually uploaded invoice files
     * 
     * @param array|null $packageModels Array of Package models
     * @return string|null Base64 encoded PDF invoice or null if not found
     */
    private function getCommercialInvoiceBase64(?array $packageModels): ?string
    {
        if (!$packageModels || empty($packageModels)) {
            return null;
        }

        try {
            // First, try to get auto-generated commercial invoice (preferred)
            // Check if packages belong to a shipment
            $firstPackage = $packageModels[0];
            if ($firstPackage instanceof Package) {
                $ship = $firstPackage->ships()->first();
                if ($ship) {
                    $invoiceService = app(\App\Services\CommercialInvoiceService::class);
                    $base64 = $invoiceService->getInvoiceBase64($ship);
                    if ($base64) {
                        // Log::channel('carrier')->info('DHL: Using auto-generated commercial invoice', [
                        //     'ship_id' => $ship->id,
                        // ]);
                        return $base64;
                    }
                }
            }

            // Fallback: Try to find manually uploaded invoice from any package
            foreach ($packageModels as $package) {
                if (!$package instanceof Package) {
                    continue;
                }

                // Load invoice relationship
                $package->load('invoices.files');

                // Try to get invoice from PackageInvoice model
                // Prefer auto-generated invoices (type != customer_submitted and invoice_number starts with INV-)
                $invoice = $package->invoices()
                    ->where('type', '!=', 'customer_submitted')
                    ->where(function($query) {
                        $query->where('invoice_number', 'like', 'INV-%')
                              ->orWhereNull('invoice_number');
                    })
                    ->orderByRaw("CASE WHEN invoice_number LIKE 'INV-%' THEN 0 ELSE 1 END")
                    ->first();
                
                if ($invoice) {
                    // Check if invoice has direct image path
                    if ($invoice->image) {
                        $filePath = $this->normalizeInvoicePath($invoice->image);
                        if (Storage::exists($filePath)) {
                            $fileContent = Storage::get($filePath);
                            // Only return PDF files
                            if (str_ends_with(strtolower($invoice->image), '.pdf')) {
                                // Log::channel('carrier')->info('DHL: Using manually uploaded invoice', [
                                //     'package_id' => $package->id,
                                //     'invoice_id' => $invoice->id,
                                // ]);
                                return base64_encode($fileContent);
                            }
                        }
                    }

                    // Check if invoice has files relationship
                    if ($invoice->files && $invoice->files->isNotEmpty()) {
                        foreach ($invoice->files as $file) {
                            $filePath = $this->normalizeInvoicePath($file->file);
                            if (Storage::exists($filePath)) {
                                $fileContent = Storage::get($filePath);
                                // Only return PDF files
                                if ($file->file_type === 'pdf' || str_ends_with(strtolower($file->file), '.pdf')) {
                                    // Log::channel('carrier')->info('DHL: Using invoice file from relationship', [
                                    //     'package_id' => $package->id,
                                    //     'file_id' => $file->id,
                                    // ]);
                                    return base64_encode($fileContent);
                                }
                            }
                        }
                    }
                }

                // Also check PackageInvoiceFile directly
                $invoiceFiles = \App\Models\PackageInvoiceFile::whereHas('invoice', function ($query) use ($package) {
                    $query->where('package_id', $package->id);
                })->where('file_type', 'pdf')->first();

                if ($invoiceFiles) {
                    $filePath = $this->normalizeInvoicePath($invoiceFiles->file);
                    if (Storage::exists($filePath)) {
                        $fileContent = Storage::get($filePath);
                        // Log::channel('carrier')->info('DHL: Using PackageInvoiceFile', [
                        //     'package_id' => $package->id,
                        //     'file_id' => $invoiceFiles->id,
                        // ]);
                        return base64_encode($fileContent);
                    }
                }
            }

            // Log::channel('carrier')->warning('DHL: No commercial invoice found for packages', [
            //     'package_ids' => array_map(fn($p) => $p instanceof Package ? $p->id : null, $packageModels),
            // ]);
            return null;
        } catch (\Exception $e) {
            // Log::channel('carrier')->warning('Failed to retrieve commercial invoice', [
            //     'error' => $e->getMessage(),
            // ]);
            return null;
        }
    }

    /**
     * Normalize invoice file path for storage access
     */
    private function normalizeInvoicePath(string $path): string
    {
        // Remove leading slashes and storage/app/public prefix if present
        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/app/public/#', '', $path);
        $path = preg_replace('#^storage/#', '', $path);
        
        // If path doesn't start with 'public/', add it for Laravel storage
        if (!str_starts_with($path, 'public/')) {
            $path = 'public/' . $path;
        }
        
        return $path;
    }

    /**
     * Get dangerous goods data from package items
     * Returns array of UN codes for items marked as dangerous
     * 
     * @param array|null $packageModels
     * @return array Array of ['unCode' => 'UNXXXX'] format
     */
    private function getDangerousGoods(?array $packageModels): array
    {
        if (!$packageModels || empty($packageModels)) {
            return [];
        }

        $dangerousGoods = [];

        foreach ($packageModels as $package) {
            if (!$package instanceof Package) {
                continue;
            }

            // Load items if not already loaded
            if (!$package->relationLoaded('items')) {
                $package->load('items');
            }

            foreach ($package->items as $item) {
                // Check if item is marked as dangerous and has UN code
                if ($item->is_dangerous && $item->un_code) {
                    $dangerousGoods[] = [
                        'unCode' => $item->un_code,
                    ];
                }
            }
        }

        return $dangerousGoods;
    }

    /**
     * Get shipper registration numbers (EIN/Tax ID) for EEI filing support
     * DHL can manage EEI filings on our behalf when EIN is provided
     * 
     * @return array Array of registration numbers in DHL format
     */
    private function getShipperRegistrationNumbers(): array
    {
        $ein = config('carriers.default_sender_ein');
        
        if (empty($ein)) {
            return [];
        }

        return [
            [
                'typeCode' => 'EIN',
                'number' => $ein,
                'issuerCountryCode' => 'US',
            ],
        ];
    }

    /**
     * Get next business day (Monday-Friday)
     * DHL doesn't operate on weekends
     */
    private function getNextBusinessDay(): \Carbon\Carbon
    {
        $date = now()->addDay();
        
        // Skip weekends - find next Monday-Friday
        while ($date->isWeekend()) {
            $date->addDay();
        }
        
        return $date;
    }
}
