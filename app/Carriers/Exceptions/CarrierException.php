<?php

namespace App\Carriers\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Base exception for all carrier-related errors
 */
class CarrierException extends Exception
{
    protected array $errors = [];
    protected array $rawResponse = [];

    public function __construct(
        string $message,
        array $errors = [],
        array $rawResponse = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->rawResponse = $rawResponse;
    }

    /**
     * Get detailed error array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get raw API response for debugging
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }

    /**
     * Create from API error response
     */
    public static function fromApiResponse(array $response, int $httpCode = 0): self
    {
        // Log the full response for debugging
        Log::channel('carrier')->debug('CarrierException::fromApiResponse - Processing error', [
            'response_keys' => array_keys($response),
            'has_additional_details' => isset($response['additionalDetails']),
            'additional_details_type' => isset($response['additionalDetails']) ? gettype($response['additionalDetails']) : 'none',
            'additional_details_count' => isset($response['additionalDetails']) && is_array($response['additionalDetails']) ? count($response['additionalDetails']) : 0,
            'response_sample' => json_encode(array_slice($response, 0, 3, true)),
        ]);
        
        // DHL API error format
        $message = $response['detail'] ?? $response['title'] ?? null;
        $errors = [];
        
        // CRITICAL: Extract validation errors FIRST before creating message
        // This ensures we have the errors even if message gets truncated
        if (isset($response['additionalDetails']) && is_array($response['additionalDetails'])) {
            $validationErrors = [];
            $validationMessages = [];
            
            Log::channel('carrier')->debug('Processing additionalDetails', [
                'count' => count($response['additionalDetails']),
                'first_item' => $response['additionalDetails'][0] ?? null,
            ]);
            
            foreach ($response['additionalDetails'] as $index => $detail) {
                if (isset($detail['field'])) {
                    $field = $detail['field'];
                    $errorMsg = $detail['message'] ?? $detail['invalidValue'] ?? $detail['value'] ?? 'Validation error';
                    $validationMessages[] = "{$field}: {$errorMsg}";
                    $validationErrors[] = [
                        'field' => $field,
                        'message' => $errorMsg,
                        'value' => $detail['value'] ?? $detail['invalidValue'] ?? null,
                    ];
                } else {
                    $errorMsg = $detail['message'] ?? $detail['invalidValue'] ?? json_encode($detail);
                    $validationMessages[] = $errorMsg;
                    $validationErrors[] = [
                        'field' => 'general',
                        'message' => $errorMsg,
                        'value' => $detail['value'] ?? $detail['invalidValue'] ?? null,
                    ];
                }
            }
            
            if (!empty($validationErrors)) {
                $validationText = implode("\n", $validationMessages);
                $message = ($message ? $message . "\n\nValidation Errors:\n" . $validationText : $validationText);
                $errors = $validationErrors; // Store as structured array with field/message/value
                
                Log::channel('carrier')->info('Extracted validation errors', [
                    'count' => count($validationErrors),
                    'errors' => $validationErrors,
                ]);
            } else {
                Log::channel('carrier')->warning('additionalDetails found but no errors extracted', [
                    'additionalDetails' => $response['additionalDetails'],
                ]);
            }
        } else {
            Log::channel('carrier')->warning('No additionalDetails in response', [
                'response_keys' => array_keys($response),
                'full_response' => $response, // Log full response to see what we're getting
            ]);
        }
        
        // Extract FedEx errors FIRST (before DHL fallback) - FedEx uses errors array
        if (isset($response['errors']) && is_array($response['errors']) && !empty($response['errors'])) {
            $fedexErrors = [];
            $fedexMessages = [];
            
            foreach ($response['errors'] as $error) {
                if (is_array($error)) {
                    // FedEx error format: {code, message, parameterList}
                    $errorCode = $error['code'] ?? 'UNKNOWN';
                    $errorMessage = $error['message'] ?? json_encode($error);
                    $parameterList = $error['parameterList'] ?? [];
                    
                    // Build detailed error message with full details
                    $detailedMessage = $errorMessage;
                    if (!empty($parameterList)) {
                        $params = [];
                        foreach ($parameterList as $param) {
                            if (is_array($param)) {
                                $params[] = $param['value'] ?? $param['key'] ?? json_encode($param);
                            } else {
                                $params[] = $param;
                            }
                        }
                        if (!empty($params)) {
                            $detailedMessage .= ' (Parameters: ' . implode(', ', $params) . ')';
                        }
                    }
                    
                    $fedexErrors[] = [
                        'code' => $errorCode,
                        'field' => $parameterList[0]['value'] ?? $parameterList[0]['key'] ?? 'general',
                        'message' => $detailedMessage,
                        'value' => $parameterList[0]['value'] ?? null,
                    ];
                    
                    $fedexMessages[] = $detailedMessage;
                } else {
                    $fedexErrors[] = ['field' => 'general', 'message' => (string)$error];
                    $fedexMessages[] = (string)$error;
                }
            }
            
            if (!empty($fedexErrors)) {
                $errors = $fedexErrors;
                $message = implode("\n", $fedexMessages);
                
                Log::channel('carrier')->info('Extracted FedEx errors', [
                    'count' => count($fedexErrors),
                    'errors' => $fedexErrors,
                    'message' => $message,
                ]);
            }
        }
        
        // Fallback to standard error format if we still don't have a message
        if (!$message) {
            $message = $response['error']['message']
                ?? $response['message']
                ?? 'Unknown carrier API error';
        }

        Log::channel('carrier')->debug('CarrierException created', [
            'message_length' => strlen($message),
            'errors_count' => count($errors),
            'errors' => $errors,
        ]);

        return new self($message, $errors, $response, $httpCode);
    }
}
