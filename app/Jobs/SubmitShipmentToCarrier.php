<?php

namespace App\Jobs;

use App\Carriers\CarrierFactory;
use App\Carriers\CarrierErrorTranslator;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\Exceptions\CarrierException;
use App\Helpers\CarrierStatus;
use App\Helpers\ShipmentStatus;
use App\Mail\ShipmentTrackingReady;
use App\Models\Ship;
use App\Models\CarrierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Async job to submit shipment to carrier API
 * 
 * Dispatched after successful payment in ShipController::checkout()
 * Will not retry automatically - admin must manually retry failed submissions
 */
class SubmitShipmentToCarrier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     * Set to 1 since admin should manually retry failures
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    public function __construct(
        public Ship $ship
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('carrier')->info('Starting carrier submission job', [
            'ship_id' => $this->ship->id,
            'option_id' => $this->ship->international_shipping_option_id,
        ]);

        try {
            // Load packages for the shipment early
            $this->ship->load('packages.items', 'customerAddress', 'customer', 'carrierService');

            // Get the carrier - prefer new carrier_service_id, fallback to legacy shipping_option_id
            $carrier = null;
            $serviceType = null;

            if ($this->ship->carrier_service_id && $this->ship->carrierService) {
                // New: Use database-driven carrier service
                $carrierService = $this->ship->carrierService;
                $carrier = CarrierFactory::fromCarrierService($carrierService);
                $serviceType = $carrierService->getApiServiceCode();

                Log::channel('carrier')->debug('Using CarrierService', [
                    'ship_id' => $this->ship->id,
                    'carrier_service_id' => $carrierService->id,
                    'carrier_code' => $carrierService->carrier_code,
                    'service_code' => $serviceType,
                ]);
            } else {
                // Legacy: Use international_shipping_option_id
                $optionId = $this->ship->international_shipping_option_id;
                $carrier = CarrierFactory::fromShippingOption($optionId);

                Log::channel('carrier')->debug('Using legacy shipping option', [
                    'ship_id' => $this->ship->id,
                    'option_id' => $optionId,
                ]);
            }

            $packages = $this->ship->packages;

            // Build the shipment request DTO
            $request = ShipmentRequest::fromShip($this->ship, $packages->all());

            // Submit to carrier API
            $response = $carrier->createShipment($request);

            if ($response->success) {
                // Update ship with carrier response
                $this->ship->update([
                    'carrier_tracking_number' => $response->trackingNumber,
                    'carrier_name' => $carrier->getName(),
                    'carrier_service_type' => $request->serviceType ?? $serviceType,
                    'label_url' => $response->labelUrl,
                    'label_data' => $response->labelData,
                    'status' => ShipmentStatus::LABEL_READY,
                    'carrier_status' => CarrierStatus::SUBMITTED,
                    'submitted_to_carrier_at' => now(),
                    'carrier_response' => $response->rawResponse,
                    'carrier_errors' => null,
                    'rate_source' => 'live_api',
                ]);

                Log::channel('carrier')->info('Carrier submission successful', [
                    'ship_id' => $this->ship->id,
                    'carrier' => $carrier->getName(),
                    'tracking' => $response->trackingNumber,
                ]);

                // Send email notification to customer
                $this->sendTrackingEmail();

            } else {
                // Mark as failed with error details
                $this->markAsFailed(
                    $response->errorMessage,
                    $response->errors,
                    'api_rejection'
                );
            }

        } catch (CarrierException $e) {
            $this->markAsFailed(
                $e->getMessage(),
                $e->getErrors(),
                $this->categorizeCarrierError($e)
            );

        } catch (\Exception $e) {
            $this->markAsFailed(
                $e->getMessage(),
                [],
                'system_error'
            );
        }
    }

    /**
     * Mark the shipment as failed with error categorization
     */
    private function markAsFailed(string $message, array $errors = [], string $errorType = 'unknown'): void
    {
        Log::channel('carrier')->error('Carrier submission failed', [
            'ship_id' => $this->ship->id,
            'error' => $message,
            'errors' => $errors,
            'error_type' => $errorType,
        ]);

        // Translate raw error to operator-friendly message
        $friendlyMessage = CarrierErrorTranslator::translate($message);
        $errorCategory = CarrierErrorTranslator::getErrorCategory($message);

        $this->ship->update([
            'status' => ShipmentStatus::PAID,  // Keep as paid so operator can retry
            'carrier_status' => CarrierStatus::FAILED,
            'carrier_errors' => [
                'friendly_message' => $friendlyMessage,
                'raw_message' => $message,
                'error_type' => $errorType,
                'error_category' => $errorCategory,
                'details' => $errors,
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
    private function sendTrackingEmail(): void
    {
        try {
            $this->ship->load('customer');

            if ($this->ship->customer && $this->ship->customer->email) {
                Mail::to($this->ship->customer->email)
                    ->send(new ShipmentTrackingReady($this->ship));

                Log::channel('carrier')->info('Tracking email sent', [
                    'ship_id' => $this->ship->id,
                    'email' => $this->ship->customer->email,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the job if email fails
            Log::channel('carrier')->warning('Failed to send tracking email', [
                'ship_id' => $this->ship->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::channel('carrier')->error('Carrier job failed completely', [
            'ship_id' => $this->ship->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->ship->update([
            'status' => ShipmentStatus::PAID,  // Keep as paid so operator can retry
            'carrier_status' => CarrierStatus::FAILED,
            'carrier_errors' => [
                'message' => $exception?->getMessage() ?? 'Unknown error',
                'error_type' => 'system_error',
                'failed_at' => now()->toIso8601String(),
                'can_retry' => true,
            ],
        ]);
    }
}
