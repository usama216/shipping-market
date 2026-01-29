<?php

namespace App\Services;

use App\Carriers\CarrierFactory;
use App\Carriers\CarrierErrorTranslator;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\Exceptions\CarrierException;
use App\Helpers\CarrierStatus;
use App\Helpers\ShipmentStatus;
use App\Mail\ShipmentTrackingReady;
use App\Models\Ship;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Synchronous service for submitting shipments to carrier APIs.
 * 
 * Extracted from the SubmitShipmentToCarrier job for shared hosting compatibility.
 * Called directly after successful payment instead of being dispatched to a queue.
 */
class ShipmentSubmissionService
{
    /**
     * Submit a shipment to the carrier API synchronously.
     * 
     * @param Ship $ship The shipment to submit
     * @return array{success: bool, message: string, tracking_number: ?string}
     */
    public function submit(Ship $ship): array
    {
        Log::channel('carrier')->info('Starting carrier submission', [
            'ship_id' => $ship->id,
            'option_id' => $ship->international_shipping_option_id,
        ]);

        try {
            // Load packages for the shipment early
            $ship->load('packages.items', 'customerAddress', 'customer', 'carrierService');

            // Get the carrier - prefer new carrier_service_id, fallback to legacy shipping_option_id
            $carrier = null;
            $serviceType = null;

            if ($ship->carrier_service_id && $ship->carrierService) {
                // New: Use database-driven carrier service
                $carrierService = $ship->carrierService;
                $carrier = CarrierFactory::fromCarrierService($carrierService);
                $serviceType = $carrierService->getApiServiceCode();

                Log::channel('carrier')->debug('Using CarrierService', [
                    'ship_id' => $ship->id,
                    'carrier_service_id' => $carrierService->id,
                    'carrier_code' => $carrierService->carrier_code,
                    'service_code' => $serviceType,
                ]);
            } elseif ($ship->international_shipping_option_id) {
                // Legacy: Use international_shipping_option_id
                $optionId = $ship->international_shipping_option_id;

                if (!$optionId) {
                    throw new \InvalidArgumentException(
                        'No carrier service selected for this shipment. Please select a shipping option before submitting.'
                    );
                }

                $carrier = CarrierFactory::fromShippingOption($optionId);

                Log::channel('carrier')->debug('Using legacy shipping option', [
                    'ship_id' => $ship->id,
                    'option_id' => $optionId,
                ]);
            } else {
                // No carrier service specified
                throw new \Exception(
                    'No carrier service selected for this shipment. ' .
                    'Please select a carrier service (e.g., DHL Express Worldwide) when creating the shipment.'
                );
            }

            $packages = $ship->packages;

            // CRITICAL: Always send ALL packages as ONE shipment request
            // This ensures the carrier treats it as a single shipment and returns a consolidated label
            // Multiple packages in one shipment = ONE label (consolidated)
            Log::channel('carrier')->info('Building shipment request - Single shipment with multiple packages', [
                'ship_id' => $ship->id,
                'package_count' => $packages->count(),
                'packages' => $packages->pluck('id')->toArray(),
            ]);

            // Build the shipment request DTO - ALL packages go into ONE request
            $request = ShipmentRequest::fromShip($ship, $packages->all());

            // Submit to carrier API
            // For DHL, pass Package models for invoice retrieval
            $packageModels = ($carrier->getName() === 'dhl') ? $packages->all() : null;
            $response = $carrier->createShipment($request, $packageModels);

            if ($response->success) {
                // CRITICAL: Ensure we always have a single consolidated label
                // The carrier may return multiple labels (one per package), but we need ONE label per shipment
                $consolidatedLabelData = $response->labelData;
                $packageCount = $packages->count();
                
                // Log label status for debugging
                Log::channel('carrier')->info('Carrier submission - Label received', [
                    'ship_id' => $ship->id,
                    'carrier' => $carrier->getName(),
                    'package_count' => $packageCount,
                    'has_label_data' => !empty($consolidatedLabelData),
                    'label_data_size' => $consolidatedLabelData ? strlen($consolidatedLabelData) : 0,
                ]);
                
                // Verify we have a label (should be consolidated by carrier implementation)
                if (empty($consolidatedLabelData)) {
                    Log::channel('carrier')->error('Carrier submission - NO LABEL DATA received', [
                        'ship_id' => $ship->id,
                        'carrier' => $carrier->getName(),
                        'package_count' => $packageCount,
                        'has_label_url' => !empty($response->labelUrl),
                    ]);
                }
                
                // Update ship with carrier response
                $ship->update([
                    'carrier_tracking_number' => $response->trackingNumber,
                    'carrier_name' => $carrier->getName(),
                    'carrier_service_type' => $request->serviceType ?? $serviceType,
                    'label_url' => $response->labelUrl,
                    'label_data' => $consolidatedLabelData, // Always single consolidated label
                    'status' => ShipmentStatus::LABEL_READY,
                    'carrier_status' => CarrierStatus::SUBMITTED,
                    'submitted_to_carrier_at' => now(),
                    'carrier_response' => $response->rawResponse,
                    'carrier_errors' => null,
                    'rate_source' => 'live_api',
                ]);

                Log::channel('carrier')->info('Carrier submission successful - Single consolidated label stored', [
                    'ship_id' => $ship->id,
                    'carrier' => $carrier->getName(),
                    'tracking' => $response->trackingNumber,
                    'package_count' => $packageCount,
                    'label_stored' => !empty($consolidatedLabelData),
                ]);

                // Send email notification to customer
                $this->sendTrackingEmail($ship);

                return [
                    'success' => true,
                    'message' => 'Shipment submitted successfully',
                    'tracking_number' => $response->trackingNumber,
                ];

            } else {
                // Mark as failed with error details
                $this->markAsFailed(
                    $ship,
                    $response->errorMessage,
                    $response->errors,
                    'api_rejection'
                );

                return [
                    'success' => false,
                    'message' => $response->errorMessage,
                    'tracking_number' => null,
                ];
            }

        } catch (CarrierException $e) {
            // Get full error details including validation errors
            $errorMessage = $e->getMessage();
            $errorDetails = $e->getErrors();
            $rawResponse = $e->getRawResponse();
            
            // If we have validation errors in raw response, extract them
            // Check for DHL format (additionalDetails)
            if (empty($errorDetails) && isset($rawResponse['additionalDetails'])) {
                $errorDetails = [];
                foreach ($rawResponse['additionalDetails'] as $detail) {
                    if (isset($detail['field'])) {
                        $field = $detail['field'];
                        $errorMsg = $detail['message'] ?? $detail['value'] ?? 'Validation error';
                        $errorDetails[] = [
                            'field' => $field,
                            'message' => $errorMsg,
                            'value' => $detail['value'] ?? null,
                        ];
                    }
                }
            }
            
            // Check for FedEx format (errors array)
            if (empty($errorDetails) && isset($rawResponse['errors']) && is_array($rawResponse['errors'])) {
                $errorDetails = [];
                foreach ($rawResponse['errors'] as $error) {
                    if (is_array($error)) {
                        $errorCode = $error['code'] ?? 'UNKNOWN';
                        $errorMsg = $error['message'] ?? 'Validation error';
                        $parameterList = $error['parameterList'] ?? [];
                        
                        // Extract field from parameterList if available
                        $field = 'general';
                        if (!empty($parameterList)) {
                            $firstParam = $parameterList[0];
                            if (is_array($firstParam)) {
                                $field = $firstParam['key'] ?? $firstParam['value'] ?? 'general';
                            } else {
                                $field = $firstParam;
                            }
                        }
                        
                        $errorDetails[] = [
                            'code' => $errorCode,
                            'field' => $field,
                            'message' => $errorMsg,
                            'value' => $parameterList[0]['value'] ?? null,
                            'parameterList' => $parameterList,
                        ];
                    }
                }
            }
            
            $this->markAsFailed(
                $ship,
                $errorMessage,
                $errorDetails,
                $this->categorizeCarrierError($e)
            );

            return [
                'success' => false,
                'message' => $errorMessage,
                'tracking_number' => null,
            ];

        } catch (\Exception $e) {
            $this->markAsFailed(
                $ship,
                $e->getMessage(),
                [],
                'system_error'
            );

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'tracking_number' => null,
            ];
        }
    }

    /**
     * Mark the shipment as failed with error categorization
     */
    private function markAsFailed(Ship $ship, string $message, array $errors = [], string $errorType = 'unknown'): void
    {
        // Log FULL error details (not truncated)
        Log::channel('carrier')->error('Carrier submission failed - FULL DETAILS', [
            'ship_id' => $ship->id,
            'error_message' => $message,
            'error_message_length' => strlen($message),
            'errors_count' => count($errors),
            'errors' => $errors,
            'error_type' => $errorType,
            'errors_is_array' => is_array($errors),
            'errors_empty' => empty($errors),
        ]);

        // Translate raw error to operator-friendly message
        $friendlyMessage = CarrierErrorTranslator::translate($message);
        $errorCategory = CarrierErrorTranslator::getErrorCategory($message);

        // Format validation errors for better display
        // Ensure errors are always in structured format
        $formattedErrors = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                // Already formatted with field/message structure
                if (isset($error['field']) || isset($error['message'])) {
                    $formattedErrors[] = [
                        'field' => $error['field'] ?? 'general',
                        'message' => $error['message'] ?? json_encode($error),
                        'value' => $error['value'] ?? null,
                    ];
                } else {
                    $formattedErrors[] = $error;
                }
            } elseif (is_string($error)) {
                // Try to parse if it's a field:message format
                if (str_contains($error, ':')) {
                    [$field, $errorMsg] = explode(':', $error, 2);
                    $formattedErrors[] = [
                        'field' => trim($field),
                        'message' => trim($errorMsg),
                    ];
                } else {
                    $formattedErrors[] = [
                        'field' => 'general',
                        'message' => $error,
                    ];
                }
            }
        }

        $ship->update([
            'status' => ShipmentStatus::PAID,  // Keep as paid so operator can retry
            'carrier_status' => CarrierStatus::FAILED,
            'carrier_errors' => [
                'friendly_message' => $friendlyMessage,
                'raw_message' => $message,
                'error_type' => $errorType,
                'error_category' => $errorCategory,
                'details' => $formattedErrors,
                'validation_errors' => $formattedErrors, // Add separate field for easier access
                'failed_at' => now()->toIso8601String(),
                'can_retry' => $this->canRetry($errorType),
            ],
        ]);
    }

    /**
     * Categorize carrier exception for better admin visibility
     */
    private function categorizeCarrierError(CarrierException $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'authentication') || str_contains($message, 'credential')) {
            return 'auth_error';
        }

        if (str_contains($message, 'address') || str_contains($message, 'postal') || str_contains($message, 'city')) {
            return 'address_validation';
        }

        if (str_contains($message, 'weight') || str_contains($message, 'dimension')) {
            return 'package_validation';
        }

        if (str_contains($message, 'rate limit') || str_contains($message, 'throttl')) {
            return 'rate_limited';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            return 'network_error';
        }

        if (str_contains($message, 'service') || str_contains($message, 'unavailable')) {
            return 'service_unavailable';
        }

        return 'api_error';
    }

    /**
     * Determine if error type allows automatic retry
     */
    private function canRetry(string $errorType): bool
    {
        return in_array($errorType, [
            'network_error',
            'rate_limited',
            'service_unavailable',
        ]);
    }

    /**
     * Send tracking email to customer
     */
    private function sendTrackingEmail(Ship $ship): void
    {
        try {
            $ship->load('customer');

            if ($ship->customer && $ship->customer->email) {
                Mail::to($ship->customer->email)
                    ->send(new ShipmentTrackingReady($ship));

                Log::channel('carrier')->info('Tracking email sent', [
                    'ship_id' => $ship->id,
                    'email' => $ship->customer->email,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the submission if email fails
            Log::channel('carrier')->warning('Failed to send tracking email', [
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
