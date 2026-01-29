<?php

namespace App\Carriers;

use App\Carriers\Contracts\CarrierInterface;
use App\Carriers\FedEx\FedExCarrier;
use App\Carriers\DHL\DHLCarrier;
use App\Carriers\UPS\UPSCarrier;
use App\Carriers\MyUS\MyUSCarrier;
use App\Carriers\Exceptions\UnsupportedCarrierException;
use App\Models\CarrierService;
use App\Models\InternationalShippingOptions;

/**
 * CarrierFactory - Resolves the correct carrier implementation
 * 
 * Usage:
 *   $carrier = CarrierFactory::make('fedex');
 *   $carrier = CarrierFactory::fromCarrierService($carrierService);
 *   $carrier = CarrierFactory::fromServiceCode('FEDEX_INTERNATIONAL_PRIORITY');
 */
class CarrierFactory
{
    /**
     * Carrier name to class mapping
     */
    private static array $carriers = [
        'fedex' => FedExCarrier::class,
        'dhl' => DHLCarrier::class,
        'ups' => UPSCarrier::class,
        'myus' => MyUSCarrier::class,
    ];

    /**
     * Carrier codes that require manual handling (no API integration)
     */
    private static array $manualCarriers = [
        'sea_freight',
        'air_cargo',
    ];

    /**
     * Create carrier instance by name
     * 
     * @param string $name Carrier name: 'fedex', 'dhl', 'ups'
     * @return CarrierInterface
     * @throws UnsupportedCarrierException
     */
    public static function make(string $name): CarrierInterface
    {
        $name = strtolower(trim($name));

        if (!isset(self::$carriers[$name])) {
            throw new UnsupportedCarrierException($name);
        }

        $class = self::$carriers[$name];
        return new $class();
    }

    /**
     * Create carrier from CarrierService model
     * 
     * @param CarrierService $carrierService
     * @return CarrierInterface
     * @throws UnsupportedCarrierException
     */
    public static function fromCarrierService(CarrierService $carrierService): CarrierInterface
    {
        return self::make($carrierService->carrier_code);
    }

    /**
     * Create carrier from carrier_services.id
     * 
     * @param int $carrierServiceId
     * @return CarrierInterface
     * @throws UnsupportedCarrierException
     */
    public static function fromCarrierServiceId(int $carrierServiceId): CarrierInterface
    {
        $carrierService = CarrierService::find($carrierServiceId);

        if (!$carrierService) {
            // Fallback to FedEx for unknown services
            return self::make('fedex');
        }

        return self::fromCarrierService($carrierService);
    }

    /**
     * Create carrier from service code (from carrier_services table or API response)
     * 
     * @param string $serviceCode e.g., 'FEDEX_INTERNATIONAL_PRIORITY', 'EXPRESS_WORLDWIDE'
     * @return CarrierInterface
     * @throws UnsupportedCarrierException
     */
    public static function fromServiceCode(string $serviceCode): CarrierInterface
    {
        // First try to find in our carrier_services table
        $carrierService = CarrierService::where('service_code', $serviceCode)->first();

        if ($carrierService) {
            return self::fromCarrierService($carrierService);
        }

        // Fallback: Try to determine carrier from service code pattern
        $upperCode = strtoupper($serviceCode);

        // Match FedEx service codes
        if (
            str_starts_with($upperCode, 'FEDEX') ||
            str_contains($upperCode, 'FEDEX') ||
            in_array($upperCode, ['GROUND', 'GROUND_HOME_DELIVERY', 'EXPRESS_SAVER', 'STANDARD_OVERNIGHT', 'PRIORITY_OVERNIGHT', 'FIRST_OVERNIGHT'])
        ) {
            return self::make('fedex');
        }

        // Match DHL service codes
        if (
            str_starts_with($upperCode, 'DHL') ||
            str_starts_with($upperCode, 'EXPRESS_') ||
            in_array($upperCode, ['EXPRESS_WORLDWIDE', 'EXPRESS_9_00', 'EXPRESS_12_00', 'ECONOMY_SELECT'])
        ) {
            return self::make('dhl');
        }

        // Match UPS service codes
        if (
            str_starts_with($upperCode, 'UPS') ||
            in_array($upperCode, ['GROUND', 'UPS_GROUND', 'UPS_NEXT_DAY_AIR', 'UPS_2ND_DAY_AIR', 'SAVER', 'EXPRESS'])
        ) {
            return self::make('ups');
        }

        // Default to FedEx
        return self::make('fedex');
    }

    /**
     * Legacy: Create carrier from shipping option ID or service code
     * 
     * @deprecated Use fromCarrierServiceId() or fromServiceCode() instead
     * @param int|string $optionId Can be:
     *   - Database ID from carrier_services or international_shipping_options table
     *   - Carrier service code from live API
     * @return CarrierInterface
     * @throws UnsupportedCarrierException
     */
    public static function fromShippingOption(int|string $optionId): CarrierInterface
    {
        // If it's a string, it's likely a service code
        if (is_string($optionId) && !is_numeric($optionId)) {
            return self::fromServiceCode($optionId);
        }

        // Try new carrier_services table first
        $carrierService = CarrierService::find($optionId);
        if ($carrierService) {
            return self::fromCarrierService($carrierService);
        }

        // Fallback to legacy InternationalShippingOptions
        if (is_numeric($optionId)) {
            $option = InternationalShippingOptions::find($optionId);
            if ($option) {
                $name = strtolower($option->title ?? '');

                foreach (array_keys(self::$carriers) as $carrier) {
                    if (str_contains($name, $carrier)) {
                        return self::make($carrier);
                    }
                }
            }
        }

        // Default to FedEx
        return self::make('fedex');
    }

    /**
     * Get all available carrier codes (with API support)
     */
    public static function availableCarriers(): array
    {
        return array_keys(self::$carriers);
    }

    /**
     * Get all carrier codes including manual handling carriers
     */
    public static function allCarrierCodes(): array
    {
        return array_merge(array_keys(self::$carriers), self::$manualCarriers);
    }

    /**
     * Check if a carrier has API support
     */
    public static function hasApiSupport(string $carrierCode): bool
    {
        return isset(self::$carriers[strtolower($carrierCode)]);
    }

    /**
     * Check if a carrier requires manual handling
     */
    public static function isManualCarrier(string $carrierCode): bool
    {
        return in_array(strtolower($carrierCode), self::$manualCarriers);
    }

    /**
     * Check if a carrier is supported (either API or manual)
     */
    public static function isSupported(string $name): bool
    {
        $name = strtolower($name);
        return isset(self::$carriers[$name]) || in_array($name, self::$manualCarriers);
    }

    /**
     * Register a custom carrier
     */
    public static function register(string $name, string $class): void
    {
        self::$carriers[strtolower($name)] = $class;
    }

    /**
     * Get carrier services from database for a specific carrier
     */
    public static function getServicesForCarrier(string $carrierCode): \Illuminate\Database\Eloquent\Collection
    {
        return CarrierService::active()
            ->byCarrier($carrierCode)
            ->ordered()
            ->get();
    }

    /**
     * Get all active services for international routes
     */
    public static function getInternationalServices(): \Illuminate\Database\Eloquent\Collection
    {
        return CarrierService::active()
            ->international()
            ->ordered()
            ->get();
    }

    /**
     * Get auto-selected service for a route
     */
    public static function autoSelectService(string $originCountry, string $destCountry): ?CarrierService
    {
        return CarrierService::autoSelectForRoute($originCountry, $destCountry);
    }
}
