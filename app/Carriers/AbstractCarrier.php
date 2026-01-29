<?php

namespace App\Carriers;

use App\Carriers\Contracts\CarrierInterface;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\DTOs\ShipmentResponse;
use App\Carriers\DTOs\RateResponse;
use App\Carriers\DTOs\TrackingResponse;
use App\Carriers\DTOs\LabelResponse;
use App\Carriers\Exceptions\CarrierException;
use App\Carriers\Exceptions\CarrierAuthException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AbstractCarrier - Base class for all carrier implementations
 * Provides common HTTP client, caching, logging functionality
 */
abstract class AbstractCarrier implements CarrierInterface
{
    protected string $baseUrl;
    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null;
    protected array $config = [];

    public function __construct()
    {
        $this->config = config("carriers.{$this->getName()}", []);
        $this->baseUrl = $this->config['base_url'] ?? '';
    }

    /**
     * Get cached access token or authenticate
     */
    protected function getAccessToken(): string
    {
        $cacheKey = "carrier_{$this->getName()}_token";

        // Try to get from cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Authenticate and cache token
        $this->authenticate();

        if ($this->accessToken) {
            // tokenExpiresAt is a Unix timestamp, calculate seconds until expiry
            // Default to 55 minutes (3300 seconds) if not set
            $secondsUntilExpiry = $this->tokenExpiresAt
                ? max($this->tokenExpiresAt - time() - 60, 60) // 60 seconds buffer
                : 3300;

            Cache::put($cacheKey, $this->accessToken, $secondsUntilExpiry);
        }

        return $this->accessToken ?? '';
    }

    /**
     * Check if currently authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->accessToken !== null &&
            ($this->tokenExpiresAt === null || $this->tokenExpiresAt > time());
    }

    /**
     * Make authenticated HTTP request to carrier API
     * Automatically retries once on 401 errors after refreshing the token
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = [], bool $isRetry = false): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add auth header if we have a token
        $token = $this->getAccessToken();
        if ($token) {
            $defaultHeaders['Authorization'] = "Bearer {$token}";
        }

        $headers = array_merge($defaultHeaders, $headers);

        try {
            $response = Http::withHeaders($headers)
                        ->timeout(30)
                        ->retry(2, 1000)
                ->{strtolower($method)}($url, $data);

            $statusCode = $response->status();
            
            // Get full body as string first to ensure we capture everything
            $rawBodyString = $response->body();
            $body = $response->json() ?? [];
            
            // If JSON parsing failed but we have a body, try to parse it manually
            if (empty($body) && !empty($rawBodyString)) {
                $decoded = json_decode($rawBodyString, true);
                if ($decoded !== null) {
                    $body = $decoded;
                }
            }

            // Log for debugging
            Log::channel('carrier')->info("{$this->getName()} API Request", [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $statusCode,
            ]);

            // Handle 401 Unauthorized - token might be expired/invalid
            // Clear cache and retry once with fresh token
            if ($statusCode === 401 && !$isRetry) {
                Log::channel('carrier')->warning("{$this->getName()} received 401, clearing token cache and retrying", [
                    'endpoint' => $endpoint,
                ]);

                $this->clearTokenCache();
                return $this->request($method, $endpoint, $data, $headers, true);
            }

            if ($response->failed()) {
                // Log full error response for debugging
                $fullBody = $response->body();
                
                // Parse errors from FedEx response - ensure we get FULL messages
                $errors = [];
                if (isset($body['errors']) && is_array($body['errors'])) {
                    foreach ($body['errors'] as $error) {
                        $errorCode = $error['code'] ?? 'UNKNOWN';
                        $errorMessage = $error['message'] ?? 'No message';
                        $parameterList = $error['parameterList'] ?? [];
                        
                        // Build full error details
                        $errorDetails = [
                            'code' => $errorCode,
                            'message' => $errorMessage, // Full message, not truncated
                            'parameterList' => $parameterList,
                        ];
                        
                        // Add parameter details to message if available
                        if (!empty($parameterList)) {
                            $params = [];
                            foreach ($parameterList as $param) {
                                if (is_array($param)) {
                                    $params[] = ($param['key'] ?? '') . '=' . ($param['value'] ?? json_encode($param));
                                } else {
                                    $params[] = (string)$param;
                                }
                            }
                            if (!empty($params)) {
                                $errorDetails['full_message'] = $errorMessage . ' (Parameters: ' . implode(', ', $params) . ')';
                            }
                        }
                        
                        $errors[] = $errorDetails;
                    }
                }
                
                Log::channel('carrier')->error("{$this->getName()} Full Error Response", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'full_body' => $fullBody,
                    'full_body_length' => strlen($fullBody),
                    'parsed_errors' => $errors,
                    'errors_count' => count($errors),
                    'first_error_code' => $errors[0]['code'] ?? null,
                    'first_error_message' => $errors[0]['message'] ?? null,
                    'first_error_full' => $errors[0]['full_message'] ?? $errors[0]['message'] ?? null,
                ]);
                
                // Also log to main channel for production visibility
                Log::error("{$this->getName()} API Error - Full Details", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'errors' => $errors,
                    'errors_count' => count($errors),
                    'first_error' => $errors[0] ?? null,
                    'full_body_preview' => substr($fullBody, 0, 2000), // Preview for main log
                    'full_body_length' => strlen($fullBody),
                ]);
                
                throw CarrierException::fromApiResponse($body, $statusCode);
            }

            return $body;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Laravel HTTP Client throws RequestException for failed requests
            // Try to get the response from the exception
            $response = $e->response ?? null;
            $responseBody = null;
            $responseStatus = null;
            
            if ($response && method_exists($response, 'body')) {
                $responseBody = $response->body(); // Don't truncate - log full response
                $responseStatus = $response->status();
            } elseif (isset($response) && method_exists($response, 'body')) {
                $responseBody = $response->body();
                $responseStatus = $response->status();
            } else {
                // Fallback: try to extract from exception message
                $responseBody = $e->getMessage();
                $responseStatus = 0;
            }
            
            // Parse errors from response body
            $errorBody = json_decode($responseBody, true) ?? [];
            $parsedErrors = [];
            
            if (isset($errorBody['errors']) && is_array($errorBody['errors'])) {
                foreach ($errorBody['errors'] as $error) {
                    if (is_array($error)) {
                        $parsedErrors[] = [
                            'code' => $error['code'] ?? 'UNKNOWN',
                            'message' => $error['message'] ?? 'No message',
                            'parameterList' => $error['parameterList'] ?? [],
                        ];
                    }
                }
            }
            
            // Handle 401 on exception path as well
            if ($responseStatus === 401 && !$isRetry) {
                Log::channel('carrier')->warning("{$this->getName()} received 401 exception, clearing token cache and retrying", [
                    'endpoint' => $endpoint,
                ]);

                $this->clearTokenCache();
                return $this->request($method, $endpoint, $data, $headers, true);
            }
            
            Log::channel('carrier')->error("{$this->getName()} API Error Full Response (RequestException)", [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $responseStatus,
                'body' => $responseBody,
                'parsed_errors' => $parsedErrors,
                'errors_count' => count($parsedErrors),
            ]);

            // Also log to main channel for production visibility
            Log::error("{$this->getName()} API Error Details (RequestException)", [
                'endpoint' => $endpoint,
                'status' => $responseStatus,
                'errors' => $parsedErrors,
                'errors_count' => count($parsedErrors),
                'response_preview' => substr($responseBody, 0, 1000),
                'full_response_length' => strlen($responseBody),
            ]);
            
            // Build error message - use parsed errors if available
            $errorMessage = "HTTP request failed: {$e->getMessage()}";
            if (!empty($parsedErrors)) {
                $errorMessages = array_column($parsedErrors, 'message');
                $errorMessage .= "\n" . implode("\n", $errorMessages);
            } elseif ($responseBody) {
                // Only include first 500 chars of response body to avoid extremely long messages
                $errorMessage .= "\n" . substr($responseBody, 0, 500);
            }

            throw new CarrierException(
                message: $errorMessage,
                errors: $parsedErrors,
                rawResponse: $errorBody,
                code: $e->getCode()
            );
        } catch (\Exception $e) {
            // Log full response body for debugging if available
            $responseBody = null;
            $responseStatus = null;
            if (isset($response) && method_exists($response, 'body')) {
                $responseBody = $response->body(); // Don't truncate - log full response
                $responseStatus = $response->status();
                
                // Parse errors from response
                $errorBody = json_decode($responseBody, true);
                $errors = [];
                if (isset($errorBody['errors']) && is_array($errorBody['errors'])) {
                    foreach ($errorBody['errors'] as $error) {
                        $errors[] = [
                            'code' => $error['code'] ?? 'UNKNOWN',
                            'message' => $error['message'] ?? 'No message',
                            'parameterList' => $error['parameterList'] ?? [],
                        ];
                    }
                }

                // Handle 401 on exception path as well
                if ($responseStatus === 401 && !$isRetry) {
                    Log::channel('carrier')->warning("{$this->getName()} received 401 exception, clearing token cache and retrying", [
                        'endpoint' => $endpoint,
                    ]);

                    $this->clearTokenCache();
                    return $this->request($method, $endpoint, $data, $headers, true);
                }

                Log::channel('carrier')->error("{$this->getName()} API Error Full Response", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status' => $responseStatus,
                    'body' => $responseBody,
                    'parsed_errors' => $errors,
                    'errors_count' => count($errors),
                ]);

                // Also log to main channel for production visibility
                Log::error("{$this->getName()} API Error Details", [
                    'endpoint' => $endpoint,
                    'status' => $responseStatus,
                    'errors' => $errors,
                    'errors_count' => count($errors),
                    'response_preview' => substr($responseBody, 0, 1000), // Preview for main log
                    'full_response_length' => strlen($responseBody),
                ]);
            }

            Log::channel('carrier')->error("{$this->getName()} API Error", [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CarrierException) {
                throw $e;
            }

            // Try to extract errors from response body before building message
            $parsedErrors = [];
            if ($responseBody) {
                $errorBody = json_decode($responseBody, true);
                if ($errorBody && isset($errorBody['errors']) && is_array($errorBody['errors'])) {
                    foreach ($errorBody['errors'] as $error) {
                        if (is_array($error)) {
                            $parsedErrors[] = [
                                'code' => $error['code'] ?? 'UNKNOWN',
                                'message' => $error['message'] ?? 'No message',
                                'parameterList' => $error['parameterList'] ?? [],
                            ];
                        }
                    }
                }
            }
            
            // Build error message - use parsed errors if available, otherwise include response body
            $errorMessage = "HTTP request failed: {$e->getMessage()}";
            if (!empty($parsedErrors)) {
                $errorMessages = array_column($parsedErrors, 'message');
                $errorMessage .= "\n" . implode("\n", $errorMessages);
            } elseif ($responseBody) {
                // Only include first 500 chars of response body to avoid extremely long messages
                $errorMessage .= "\n" . substr($responseBody, 0, 500);
            }

            throw new CarrierException(
                message: $errorMessage,
                errors: $parsedErrors,
                rawResponse: $errorBody ?? [],
                code: $e->getCode()
            );
        }
    }

    /**
     * Make POST request
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    /**
     * Make GET request
     */
    protected function get(string $endpoint, array $query = [], array $headers = []): array
    {
        $url = $endpoint;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $this->request('GET', $url, [], $headers);
    }

    /**
     * Make DELETE request
     */
    protected function delete(string $endpoint, array $headers = []): array
    {
        return $this->request('DELETE', $endpoint, [], $headers);
    }

    /**
     * Default address validation (can be overridden)
     */
    public function validateAddress(array $address): array
    {
        // Default: return address as-is
        // Carriers with address validation should override this
        return $address;
    }

    /**
     * Check if running in sandbox mode
     */
    protected function isSandbox(): bool
    {
        return $this->config['sandbox'] ?? true;
    }

    /**
     * Get account number
     */
    protected function getAccountNumber(): string
    {
        return $this->config['account_number'] ?? '';
    }

    /**
     * Clear cached token (useful after auth errors)
     */
    protected function clearTokenCache(): void
    {
        Cache::forget("carrier_{$this->getName()}_token");
        $this->accessToken = null;
        $this->tokenExpiresAt = null;
    }
}
