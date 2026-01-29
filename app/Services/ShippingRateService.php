<?php

namespace App\Services;

use App\Carriers\CarrierFactory;
use App\Carriers\Contracts\CarrierInterface;
use App\Carriers\DTOs\Address;
use App\Carriers\DTOs\PackageDetail;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\Exceptions\CarrierException;
use App\Models\CarrierService;
use App\Models\InternationalShippingOptions;
use App\Models\Package;
use App\Models\RateMarkupRule;
use App\Models\CarrierCommissionSetting;
use App\Repositories\ShipRepository;
use App\Services\DTOs\RateResult;
use App\Services\AddressService;
use App\Services\CarrierAddonService;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ShippingRateService - Orchestrates live carrier API rates
 * 
 * No fallback to database rates - API failures show error with refresh option
 * 
 * Usage:
 *   $service = app(ShippingRateService::class);
 *   $result = $service->getRate(carrierId: 2, weight: 5.5, destination: [...]);
 */
class ShippingRateService
{
    private ShipRepository $shipRepository;
    private AddressService $addressService;
    private CarrierAddonService $addonService;
    private int $cacheTtl;
    private int $timeout;

    public function __construct(
        ShipRepository $shipRepository,
        AddressService $addressService,
        CarrierAddonService $addonService
    ) {
        $this->shipRepository = $shipRepository;
        $this->addressService = $addressService;
        $this->addonService = $addonService;
        $this->cacheTtl = config('carriers.rates.cache_ttl', 300);
        $this->timeout = config('carriers.rates.timeout', 5);
    }

    /**
     * Apply admin-configured markup rules to a rate
     *
     * @param float $basePrice Base carrier price
     * @param string $carrier Carrier code (fedex, dhl, ups)
     * @param float $weight Shipment weight
     * @param string|null $destinationCountry Destination country code
     * @return array Contains finalPrice, markupAmount, appliedRules
     */
    public function applyMarkups(
        float $basePrice,
        string $carrier,
        float $weight,
        ?string $destinationCountry = null
    ): array {
        $appliedRules = [];
        $totalMarkup = 0.0;

        // Log markup application start
        Log::channel('carrier')->info("Rate Markup Rules - START", [
            'carrier' => $carrier,
            'base_price' => $basePrice,
            'weight' => $weight,
            'destination_country' => $destinationCountry,
        ]);

        // Get active rules matching criteria
        $rules = RateMarkupRule::active()
            ->forCarrier($carrier)
            ->forWeight($weight)
            ->forDestination($destinationCountry)
            ->ordered()
            ->get();

        Log::channel('carrier')->info("Rate Markup Rules - Found Rules", [
            'carrier' => $carrier,
            'rules_count' => $rules->count(),
            'rules' => $rules->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'type' => $r->type,
                'value' => $r->value,
                'carrier' => $r->carrier,
                'min_weight' => $r->min_weight,
                'max_weight' => $r->max_weight,
                'destination_countries' => $r->destination_countries,
            ])->toArray(),
        ]);

        foreach ($rules as $rule) {
            $markup = $rule->applyTo($basePrice);
            $totalMarkup += $markup;
            $appliedRules[] = [
                'id' => $rule->id,
                'name' => $rule->name,
                'type' => $rule->type,
                'value' => $rule->value,
                'markup_amount' => round($markup, 2),
            ];
            
            Log::channel('carrier')->info("Rate Markup Rules - Rule Applied", [
                'carrier' => $carrier,
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'rule_type' => $rule->type,
                'rule_value' => $rule->value,
                'base_price' => $basePrice,
                'markup_amount' => round($markup, 2),
                'running_total_markup' => round($totalMarkup, 2),
            ]);
        }

        $finalPrice = round($basePrice + $totalMarkup, 2);
        
        Log::channel('carrier')->info("Rate Markup Rules - COMPLETE", [
            'carrier' => $carrier,
            'base_price' => round($basePrice, 2),
            'total_markup' => round($totalMarkup, 2),
            'final_price' => $finalPrice,
            'applied_rules_count' => count($appliedRules),
            'applied_rules' => $appliedRules,
        ]);

        return [
            'base_price' => round($basePrice, 2),
            'markup_amount' => round($totalMarkup, 2),
            'final_price' => $finalPrice,
            'applied_rules' => $appliedRules,
        ];
    }

    /**
     * Get shipping rate - tries API first, falls back to database
     *
     * @param int $carrierId InternationalShippingOptions ID
     * @param float $weight Package weight in lbs
     * @param array $dimensions Optional dimensions [length, width, height]
     * @param array $destination Destination address [city, state, zip, country]
     * @return RateResult
     */
    public function getRate(
        int $carrierId,
        float $weight,
        array $dimensions = [],
        array $destination = []
    ): RateResult {
        // Normalize destination address (converts country/state names to codes)
        $destination = $this->addressService->normalizeForCarrier($destination);

        $carrierName = $this->getCarrierName($carrierId);
        $cacheKey = $this->buildCacheKey($carrierId, $weight, $dimensions, $destination);

        Log::channel('carrier')->info('[ShippingRateService] Rate request', [
            'carrier_id' => $carrierId,
            'carrier_name' => $carrierName,
            'weight' => $weight,
        ]);

        // 1. Check cache first
        $cachedRate = $this->getCached($cacheKey);
        if ($cachedRate !== null) {
            Log::channel('carrier')->info('[ShippingRateService] Cache hit', [
                'carrier' => $carrierName,
                'rate' => $cachedRate,
            ]);
            return RateResult::fromCache($cachedRate, $carrierName);
        }

        // 2. Try live API rate
        $shouldAttempt = $this->shouldAttemptLiveRate($carrierId);
        Log::info('[ShippingRateService] Rate request', [
            'carrier_id' => $carrierId,
            'carrier_name' => $carrierName,
            'weight' => $weight,
            'destination' => $destination,
            'should_attempt_live' => $shouldAttempt,
        ]);

        if ($shouldAttempt) {
            try {
                Log::info('[ShippingRateService] Attempting live rate fetch...');
                $liveRate = $this->fetchLiveRate($carrierId, $weight, $dimensions, $destination);

                if ($liveRate !== null) {
                    $this->setCache($cacheKey, $liveRate->price);
                    Log::info('[ShippingRateService] API rate received', [
                        'carrier' => $carrierName,
                        'rate' => $liveRate->price,
                    ]);
                    return $liveRate;
                } else {
                    Log::warning('[ShippingRateService] API returned null rate');
                }
            } catch (\Exception $e) {
                Log::error('[ShippingRateService] API failed, using fallback', [
                    'carrier' => $carrierName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // 3. Fallback to database
        $dbRate = $this->getFallbackRate($carrierId, $weight);

        Log::info('[ShippingRateService] Using database rate', [
            'carrier' => $carrierName,
            'rate' => $dbRate,
        ]);

        return RateResult::fromDatabase($dbRate, $carrierName);
    }

    /**
     * Get all available shipping rates from a carrier
     * Returns all service options (Ground, 2-Day, Overnight, etc.)
     * No fallback - returns empty array with error on failure
     *
     * @param string $carrier Carrier name (fedex, dhl, ups)
     * @param float $weight Package weight in lbs
     * @param array $dimensions Optional dimensions [length, width, height]
     * @param array $destination Destination address
     * @param Warehouse|null $warehouse Origin warehouse for sender address
     * @return array Array of rate options with service details
     * @throws \Exception on API failure (caught by caller)
     */
    public function getAllRates(
        string $carrier,
        float $weight,
        array $dimensions = [],
        array $destination = [],
        ?Warehouse $warehouse = null
    ): array {
        // Normalize destination address
        $destination = $this->addressService->normalizeForCarrier($destination);

        // Validate before making API call
        $validation = $this->validateRateRequestData($weight, $destination);
        if (!$validation['valid']) {
            Log::warning('[ShippingRateService] Skipping API due to invalid data', [
                'carrier' => $carrier,
                'errors' => $validation['errors'],
                'destination' => $destination,
            ]);
            throw new \Exception('Invalid address data: ' . implode(', ', $validation['errors']));
        }

        $cacheKey = $this->buildAllRatesCacheKey($carrier, $weight, $dimensions, $destination);

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info('[ShippingRateService] Using cached rates', ['carrier' => $carrier, 'count' => count($cached)]);
            return $cached;
        }

        // Get carrier instance
        $carrierInstance = CarrierFactory::make($carrier);

        // Build shipment request with warehouse origin
        $request = $this->buildRateRequestForCarrier($carrier, $weight, $dimensions, $destination, $warehouse);

        // Debug: Log the full request being sent to carrier
        Log::info('[ShippingRateService] Sending rate request to carrier', [
            'carrier' => $carrier,
            'sender' => [
                'street1' => $request->senderAddress->street1,
                'city' => $request->senderAddress->city,
                'state' => $request->senderAddress->state,
                'zip' => $request->senderAddress->postalCode,
                'country' => $request->senderAddress->countryCode,
            ],
            'recipient' => [
                'street1' => $request->recipientAddress->street1,
                'city' => $request->recipientAddress->city,
                'state' => $request->recipientAddress->state,
                'zip' => $request->recipientAddress->postalCode,
                'country' => $request->recipientAddress->countryCode,
            ],
            'warehouse_id' => $warehouse?->id,
            'warehouse_name' => $warehouse?->name,
            'weight' => $weight,
        ]);

        // Fetch all rates from API (let exceptions bubble up)
        $rates = $carrierInstance->getRates($request);

        if (empty($rates)) {
            Log::warning('[ShippingRateService] No rates returned', ['carrier' => $carrier]);
            return [];
        }

        // Format rates for frontend
        $formattedRates = $this->formatRatesForFrontend($rates, $carrier);

        // Cache for 5 minutes
        Cache::put($cacheKey, $formattedRates, 300);

        Log::info('[ShippingRateService] Fetched live rates', [
            'carrier' => $carrier,
            'count' => count($formattedRates),
        ]);

        return $formattedRates;
    }

    /**
     * Get rates for all configured carriers
     * No fallback - returns error state for failed carriers so frontend can show refresh button
     */
    public function getAllCarrierRates(
        float $weight,
        array $dimensions = [],
        array $destination = [],
        ?Warehouse $warehouse = null
    ): array {
        $apiCarriers = ['fedex', 'dhl', 'ups'];
        $allRates = [];

        foreach ($apiCarriers as $carrier) {
            // Skip carriers that are not enabled
            if (!$this->isCarrierEnabled($carrier)) {
                $allRates[$carrier] = [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => [],
                    'enabled' => false,
                    'error' => 'Service not configured',
                ];
                continue;
            }

            try {
                $rates = $this->getAllRates($carrier, $weight, $dimensions, $destination, $warehouse);
                $allRates[$carrier] = [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => $rates,
                    'enabled' => true,
                ];
            } catch (\Exception $e) {
                Log::warning('[ShippingRateService] Carrier rate fetch failed', [
                    'carrier' => $carrier,
                    'error' => $e->getMessage(),
                ]);

                // No fallback - just return error state for frontend to show refresh button
                $allRates[$carrier] = [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => [],
                    'enabled' => true,
                    'error' => $e->getMessage() ?: 'Unable to fetch rates. Please try again.',
                ];
            }
        }
        
        // Log side-by-side comparison of rates for debugging price differences
        $comparison = [];
        foreach ($allRates as $carrier => $carrierData) {
            if (!empty($carrierData['rates'])) {
                foreach ($carrierData['rates'] as $rate) {
                    $comparison[] = [
                        'carrier' => $carrier,
                        'service' => $rate['service_name'] ?? 'Unknown',
                        'service_type' => $rate['service_type'] ?? null,
                        'price' => $rate['price'] ?? 0,
                        'base_charge' => $rate['base_charge'] ?? 0,
                        'surcharges' => $rate['total_surcharges'] ?? 0,
                        'surcharge_breakdown' => $rate['surcharge_breakdown'] ?? [],
                    ];
                }
            }
        }
        
        if (!empty($comparison)) {
            Log::channel('carrier')->info("Rate Comparison - All Carriers Side-by-Side", [
                'destination' => $destination,
                'weight' => $weight,
                'dimensions' => $dimensions,
                'warehouse' => $warehouse?->name ?? 'Default',
                'comparison' => $comparison,
                'dhl_rates' => array_filter($comparison, fn($r) => $r['carrier'] === 'dhl'),
                'fedex_rates' => array_filter($comparison, fn($r) => $r['carrier'] === 'fedex'),
                'price_difference' => $this->calculatePriceDifference($comparison),
            ]);
        }

        return $allRates;
    }

    /**
     * Get rates for a single carrier (for refresh button)
     * Returns array formatted with carrier key for frontend consistency
     */
    public function getSingleCarrierRates(
        string $carrier,
        array $packageIds,
        array $destination = []
    ): array {
        if (empty($packageIds)) {
            return [
                $carrier => [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => [],
                    'enabled' => false,
                    'error' => 'No package data',
                ]
            ];
        }

        // Get packages and calculate total weight
        $packages = Package::with('warehouse')->whereIn('id', $packageIds)->get();
        $totalWeight = $packages->sum('billed_weight');
        $dimensions = $this->aggregateDimensions($packages);

        if ($totalWeight <= 0) {
            return [
                $carrier => [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => [],
                    'enabled' => false,
                    'error' => 'Invalid package weight',
                ]
            ];
        }

        if (!$this->isCarrierEnabled($carrier)) {
            return [
                $carrier => [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => [],
                    'enabled' => false,
                    'error' => 'Service not configured',
                ]
            ];
        }

        // Get warehouse from first package (all packages in a shipment should share same warehouse)
        $warehouse = $packages->first()?->warehouse;

        try {
            $rates = $this->getAllRates($carrier, $totalWeight, $dimensions, $destination, $warehouse);

            // Apply markups
            $destinationCountry = $destination['country'] ?? null;
            foreach ($rates as &$rate) {
                if (isset($rate['price'])) {
                    $marked = $this->applyMarkups(
                        $rate['price'],
                        $carrier,
                        $totalWeight,
                        $destinationCountry
                    );
                    $rate['base_price'] = $marked['base_price'];
                    $rate['markup_amount'] = $marked['markup_amount'];
                    $rate['price'] = $marked['final_price'];
                    if (!empty($marked['applied_rules'])) {
                        $rate['has_markup'] = true;
                    }
                }
            }

            return [
                $carrier => [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => $rates,
                    'enabled' => true,
                ]
            ];
        } catch (\Exception $e) {
            Log::warning('[ShippingRateService] Single carrier rate fetch failed', [
                'carrier' => $carrier,
                'error' => $e->getMessage(),
            ]);

            return [
                $carrier => [
                    'name' => $this->getCarrierDisplayName($carrier),
                    'logo' => $this->getCarrierLogo($carrier),
                    'rates' => [],
                    'enabled' => true,
                    'error' => $e->getMessage() ?: 'Unable to fetch rates. Please try again.',
                ]
            ];
        }
    }

    /**
     * Get cached rates for packages, with live API fallback if destination changed
     *
     * @param array $packageIds Array of package IDs
     * @param array $destination Current destination address
     * @return array Rates grouped by carrier
     */
    public function getRatesForPackages(array $packageIds, array $destination = []): array
    {
        if (empty($packageIds)) {
            return $this->getEmptyCarrierRates();
        }

        Log::info('[ShippingRateService] Fetching rates for packages', [
            'package_ids' => $packageIds,
        ]);

        // Get packages and calculate total weight
        $packages = Package::with('warehouse')->whereIn('id', $packageIds)->get();
        $totalWeight = $packages->sum('billed_weight');
        $dimensions = $this->aggregateDimensions($packages);

        if ($totalWeight <= 0) {
            Log::warning('[ShippingRateService] No weight for packages', ['package_ids' => $packageIds]);
            return $this->getEmptyCarrierRates();
        }

        // Get warehouse from first package (all packages in a shipment should share same warehouse)
        $warehouse = $packages->first()?->warehouse;

        // Fetch live rates based on collective weight and warehouse origin
        $rates = $this->getAllCarrierRates($totalWeight, $dimensions, $destination, $warehouse);

        // Apply markups to each rate
        $destinationCountry = $destination['country'] ?? null;
        foreach ($rates as $carrier => &$carrierData) {
            if (!empty($carrierData['rates'])) {
                foreach ($carrierData['rates'] as &$rate) {
                    if (isset($rate['price'])) {
                        $marked = $this->applyMarkups(
                            $rate['price'],
                            $carrier,
                            $totalWeight,
                            $destinationCountry
                        );
                        $rate['base_price'] = $marked['base_price'];
                        $rate['markup_amount'] = $marked['markup_amount'];
                        $rate['price'] = $marked['final_price'];
                        if (!empty($marked['applied_rules'])) {
                            $rate['has_markup'] = true;
                        }
                    }
                }
            }
        }

        return $rates;
    }

    /**
     * Get empty carrier rates structure
     */
    private function getEmptyCarrierRates(): array
    {
        $result = [];
        foreach (['fedex', 'dhl', 'ups'] as $carrier) {
            $result[$carrier] = [
                'name' => $this->getCarrierDisplayName($carrier),
                'logo' => $this->getCarrierLogo($carrier),
                'rates' => [],
                'enabled' => false,
                'error' => 'No package data',
            ];
        }
        return $result;
    }

    /**
     * Aggregate dimensions from packages (defaults since package dims removed)
     * Actual shipping box dimensions determined by warehouse at packing time
     */
    private function aggregateDimensions($packages): array
    {
        // Return default dimensions - actual box size determined at packing
        return [
            'length' => 10,
            'width' => 10,
            'height' => 10,
        ];
    }

    /**
     * Validate rate request data before making API calls
     * Returns early with fallback rates if data is invalid
     */
    private function validateRateRequestData(float $weight, array $destination): array
    {
        $errors = [];

        // Validate weight
        if ($weight <= 0) {
            $errors[] = 'Weight must be greater than 0';
        }

        // Get country code
        $country = strtoupper($destination['country'] ?? 'US');
        $zip = trim($destination['zip'] ?? '');
        $city = trim($destination['city'] ?? '');

        // Check if country requires postal code
        $requiresPostalCode = $this->addressService->countryRequiresPostalCode($country);

        if ($requiresPostalCode) {
            // Countries that require postal codes
            // Accept "00000" as valid for Caribbean islands that don't have postal codes
            if (empty($zip) || (strlen($zip) < 3 && $zip !== '00000')) {
                $errors[] = "Valid postal code required for {$country}";
            }
        } else {
            // Caribbean countries without postal codes
            // Accept empty zip, "00000", or any value - city is what matters
            if (empty($city)) {
                $errors[] = "City required for {$country}";
            }
            // If zip is empty for countries without postal codes, default to "00000"
            // This will be handled in the carrier normalization
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Format rates for frontend display
     * Includes surcharge breakdown for transparent pricing
     */
    private function formatRatesForFrontend(array $rates, string $carrier): array
    {
        return array_map(function ($rate) use ($carrier) {
            // Try to find matching CarrierService by service_code to get the database ID
            $carrierServiceId = null;
            $serviceType = $rate->serviceType ?? null;

            if ($serviceType) {
                // First, try direct match (works for FedEx/DHL)
                $carrierService = CarrierService::where('service_code', $serviceType)
                    ->where('carrier_code', $carrier)
                    ->first();

                // If no match and this is UPS, map numeric code to database service code
                if (!$carrierService && $carrier === 'ups') {
                    $mappedCode = $this->mapUpsCodeToServiceCode($serviceType);
                    if ($mappedCode) {
                        $carrierService = CarrierService::where('service_code', $mappedCode)
                            ->where('carrier_code', 'ups')
                            ->first();
                    }
                }

                $carrierServiceId = $carrierService?->id;
            }

            // Apply commission markup to all carriers
            $originalBasePrice = round($rate->totalCharge, 2);
            $originalBaseCharge = round($rate->baseCharge ?? 0, 2);
            $originalSurcharges = round($rate->surcharges ?? 0, 2);
            
            // Get commission multiplier from settings (carrier-specific)
            $commissionSetting = CarrierCommissionSetting::getCurrent();
            $commissionPercentage = $commissionSetting->getCommissionPercentage($carrier);
            
            // Enforce minimum 15% commission
            if ($commissionPercentage < 15.0) {
                Log::warning('Commission below 15% detected in formatRatesForFrontend, enforcing minimum', [
                    'carrier' => $carrier,
                    'service' => $rate->serviceName,
                    'current_commission' => $commissionPercentage,
                    'enforcing_minimum' => 15.0,
                ]);
                $commissionPercentage = 15.0;
            }
            
            $markupMultiplier = 1 + ($commissionPercentage / 100);
            
            // Log commission application
            Log::channel('carrier')->info("Rate Commission Applied", [
                'carrier' => $carrier,
                'service' => $rate->serviceName,
                'service_type' => $rate->serviceType,
                'original_total' => $originalBasePrice,
                'original_base_charge' => $originalBaseCharge,
                'original_surcharges' => $originalSurcharges,
                'commission_multiplier' => $markupMultiplier,
                'commission_percentage' => $commissionPercentage . '%',
            ]);
            
            // Apply carrier-specific commission (minimum 15%)
            $basePrice = round($originalBasePrice * $markupMultiplier, 2);
            $baseCharge = round($originalBaseCharge * $markupMultiplier, 2);
            $totalSurcharges = round($originalSurcharges * $markupMultiplier, 2);
            
            // Log final prices after commission
            Log::channel('carrier')->info("Rate Final Price After Commission", [
                'carrier' => $carrier,
                'service' => $rate->serviceName,
                'service_type' => $rate->serviceType,
                'final_total' => $basePrice,
                'final_base_charge' => $baseCharge,
                'final_surcharges' => $totalSurcharges,
                'commission_amount' => round($basePrice - $originalBasePrice, 2),
                'commission_on_base' => round($baseCharge - $originalBaseCharge, 2),
                'commission_on_surcharges' => round($totalSurcharges - $originalSurcharges, 2),
            ]);

            return [
                'id' => $rate->serviceType ?? uniqid(),
                'carrier_service_id' => $carrierServiceId,
                'service_name' => $rate->serviceName,
                'service_type' => $rate->serviceType ?? null,
                'price' => $basePrice,
                'base_charge' => $baseCharge,
                'total_surcharges' => $totalSurcharges,
                'surcharge_breakdown' => $rate->surchargeBreakdown ?? [],
                'currency' => $rate->currency ?? 'USD',
                'transit_days' => $rate->transitDays ?? null,
                'delivery_date' => $this->calculateDeliveryDate($rate->transitDays ?? null),
                'is_live_rate' => true,
                'carrier' => $carrier,
            ];
        }, $rates);
    }

    /**
     * Map UPS numeric service codes to database service_code values
     * UPS API returns codes like '65', but database has 'SAVER' or 'UPS_WORLDWIDE_SAVER'
     */
    private function mapUpsCodeToServiceCode(string $numericCode): ?string
    {
        $mapping = [
            '01' => 'UPS_NEXT_DAY_AIR',
            '02' => 'UPS_2ND_DAY_AIR',
            '03' => 'UPS_GROUND',
            '07' => 'EXPRESS',           // Also try UPS_WORLDWIDE_EXPRESS
            '08' => 'UPS_WORLDWIDE_EXPEDITED',
            '11' => 'UPS_STANDARD',
            '12' => 'UPS_3_DAY_SELECT',
            '13' => 'UPS_NEXT_DAY_AIR_SAVER',
            '14' => 'UPS_NEXT_DAY_AIR_EARLY',
            '54' => 'UPS_WORLDWIDE_EXPRESS_PLUS',
            '59' => 'UPS_2ND_DAY_AIR_AM',
            '65' => 'SAVER',              // Also try UPS_WORLDWIDE_SAVER
        ];

        // Try the primary mapping first
        $code = $mapping[$numericCode] ?? null;

        // For 65 and 07, also try the UPS_ prefixed versions
        if (!$code) {
            return null;
        }

        // Check if the primary code exists, if not try UPS_ prefix version
        $exists = CarrierService::where('service_code', $code)
            ->where('carrier_code', 'ups')
            ->exists();

        if (!$exists && in_array($numericCode, ['65', '07'])) {
            // Try full-prefixed version
            $altMapping = [
                '65' => 'UPS_WORLDWIDE_SAVER',
                '07' => 'UPS_WORLDWIDE_EXPRESS',
            ];
            return $altMapping[$numericCode] ?? $code;
        }

        return $code;
    }

    /**
     * Get available addons for a carrier rate with live pricing from surcharge data
     * 
     * @param string $carrierCode Carrier code (fedex, dhl, ups)
     * @param array $surchargeBreakdown Surcharge breakdown from rate response
     * @param float $baseShippingRate Base shipping cost for percentage calculations
     * @param array $packageIds Package IDs to determine mandatory addons
     * @return array Enriched addon data with is_mandatory and calculated_price
     */
    public function getAddonsForRate(
        string $carrierCode,
        array $surchargeBreakdown = [],
        float $baseShippingRate = 0,
        array $packageIds = []
    ): array {
        return $this->addonService->getAddonsWithLivePricing(
            $carrierCode,
            $surchargeBreakdown,
            $baseShippingRate,
            $packageIds
        );
    }

    /**
     * Validate that all mandatory addons are selected for checkout
     */
    public function validateMandatoryAddons(array $selectedAddonIds, array $packageIds): array
    {
        return $this->addonService->validateMandatoryAddons($selectedAddonIds, $packageIds);
    }

    /**
     * Get classification summary for packages (dangerous/fragile/oversized flags)
     */
    public function getClassificationSummary(array $packageIds): array
    {
        return $this->addonService->getClassificationSummary($packageIds);
    }

    /**
     * Validate checkout eligibility - blocks checkout if mandatory addons are unavailable
     * Call this before processing checkout to prevent blocked shipments
     * 
     * @param string $carrierCode Carrier code (fedex, dhl, ups)
     * @param array $surchargeBreakdown Surcharge breakdown from rate response
     * @param array $packageIds Package IDs being shipped
     * @return array ['eligible' => bool, 'errors' => customer-friendly messages, 'blocked_addons' => codes]
     */
    public function validateCheckoutEligibility(
        string $carrierCode,
        array $surchargeBreakdown,
        array $packageIds
    ): array {
        return $this->addonService->validateCheckoutEligibility(
            $carrierCode,
            $surchargeBreakdown,
            $packageIds
        );
    }


    /**
     * Calculate estimated delivery date
     */
    private function calculateDeliveryDate(?int $transitDays): ?string
    {
        if (!$transitDays) {
            return null;
        }

        // Skip weekends for business days calculation
        $date = now();
        $daysAdded = 0;

        while ($daysAdded < $transitDays) {
            $date->addDay();
            if (!$date->isWeekend()) {
                $daysAdded++;
            }
        }

        return $date->format('M j');
    }

    /**
     * Get carrier display name
     */
    private function getCarrierDisplayName(string $carrier): string
    {
        return match (strtolower($carrier)) {
            'fedex' => 'FedEx',
            'dhl' => 'DHL Express',
            'ups' => 'UPS',
            default => ucfirst($carrier),
        };
    }

    /**
     * Get carrier logo path
     */
    private function getCarrierLogo(string $carrier): string
    {
        return match (strtolower($carrier)) {
            'fedex' => '/images/carriers/fedex.svg',
            'dhl' => '/images/carriers/dhl.svg',
            'ups' => '/images/carriers/ups.svg',
            default => '/images/carriers/default.svg',
        };
    }

    /**
     * Check if carrier is enabled (has credentials configured)
     */
    private function isCarrierEnabled(string $carrier): bool
    {
        return match (strtolower($carrier)) {
            'fedex' => !empty(config('carriers.fedex.client_id')),
            'dhl' => !empty(config('carriers.dhl.api_key')),
            'ups' => !empty(config('carriers.ups.client_id')),
            default => false,
        };
    }

    // NOTE: getCarrierFallbackRates and formatCarrierServicesAsFallback methods removed
    // Fallback logic has been replaced with error state + refresh button in frontend

    /**
     * Build cache key for all rates
     */
    private function buildAllRatesCacheKey(string $carrier, float $weight, array $dimensions, array $destination): string
    {
        $destKey = implode('-', [
            $destination['zip'] ?? '',
            $destination['country'] ?? 'US',
        ]);

        $dimKey = implode('x', [
            $dimensions['length'] ?? 0,
            $dimensions['width'] ?? 0,
            $dimensions['height'] ?? 0,
        ]);

        return "all_rates_{$carrier}_{$weight}_{$dimKey}_{$destKey}";
    }

    /**
     * Build rate request for specific carrier (delegates to consolidated method)
     */
    private function buildRateRequestForCarrier(string $carrier, float $weight, array $dimensions, array $destination, ?Warehouse $warehouse = null): ShipmentRequest
    {
        return $this->buildShipmentRequest($weight, $dimensions, $destination, null, $warehouse);
    }

    /**
     * Fetch live rate from carrier API
     */
    private function fetchLiveRate(
        int $carrierId,
        float $weight,
        array $dimensions,
        array $destination
    ): ?RateResult {
        try {
            $carrier = CarrierFactory::fromShippingOption($carrierId);

            // Build minimal shipment request for rate quote
            $request = $this->buildRateRequest($carrierId, $weight, $dimensions, $destination);

            // Log the recipient address for debugging
            Log::info('[ShippingRateService] Calling carrier API', [
                'recipient_state' => $request->recipientAddress->state,
                'recipient_zip' => $request->recipientAddress->postalCode,
                'recipient_country' => $request->recipientAddress->countryCode,
            ]);

            // Set timeout for API call
            $rates = $carrier->getRates($request);

            if (empty($rates)) {
                return null;
            }

            // Get the first/cheapest rate
            $rate = $rates[0];
            
            // Get carrier name from the carrier instance
            $carrierName = strtolower($carrier->getName());
            
            // Apply carrier-specific commission markup
            $commissionSetting = CarrierCommissionSetting::getCurrent();
            $markupMultiplier = $commissionSetting->getMultiplier($carrierName);
            $price = $rate->totalCharge * $markupMultiplier;

            return RateResult::fromApi(
                price: $price,
                serviceType: $rate->serviceType,
                serviceName: $rate->serviceName,
                transitDays: $rate->transitDays,
                estimatedDelivery: $rate->estimatedDelivery,
                carrier: $carrier->getName()
            );
        } catch (CarrierException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel('carrier')->error('[ShippingRateService] Unexpected API error', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get fallback rate from database
     */
    private function getFallbackRate(int $carrierId, float $weight): float
    {
        $serviceName = $this->getServiceName($carrierId);
        $isVolumetric = $this->isVolumetricCarrier($carrierId);

        if ($isVolumetric) {
            $pricing = $this->shipRepository->getShipPriceByVolumeAndService($weight, $serviceName);
        } else {
            $pricing = $this->shipRepository->getShipPriceByWeightAndService($weight, $serviceName);
        }

        return $pricing ? (float) $pricing->price : 0.00;
    }

    /**
     * Build a ShipmentRequest for rate fetching (consolidated method)
     * 
     * @param float $weight Package weight
     * @param array $dimensions Package dimensions
     * @param array $destination Recipient address data
     * @param string|null $serviceType Optional service type (null = get all rates)
     * @param Warehouse|null $warehouse Origin warehouse for sender address
     */
    private function buildShipmentRequest(
        float $weight,
        array $dimensions,
        array $destination,
        ?string $serviceType = null,
        ?Warehouse $warehouse = null
    ): ShipmentRequest {
        // Use warehouse address if provided, otherwise fall back to config defaults
        if ($warehouse) {
            $senderAddress = new Address(
                street1: $warehouse->address ?? config('carriers.default_sender_address', '7900 NW 25th St'),
                street2: $warehouse->address_line_2,
                city: $warehouse->city ?? config('carriers.default_sender_city', 'Miami'),
                state: $warehouse->state ?? config('carriers.default_sender_state', 'FL'),
                postalCode: $warehouse->zip ?? config('carriers.default_sender_zip', '33122'),
                countryCode: $warehouse->country_code ?? config('carriers.default_sender_country', 'US'),
            );
            $senderName = $warehouse->full_name ?? config('carriers.default_sender_name', 'Marketz Warehouse');
            $senderCompany = $warehouse->company_name ?? config('carriers.default_sender_company', 'Marketz LLC');
            $senderPhone = $warehouse->phone_number ?? config('carriers.default_sender_phone', '3051234567');
        } else {
            // Fallback to config defaults when no warehouse provided
            $senderAddress = new Address(
                street1: config('carriers.default_sender_address', '7900 NW 25th St'),
                street2: null,
                city: config('carriers.default_sender_city', 'Miami'),
                state: config('carriers.default_sender_state', 'FL'),
                postalCode: config('carriers.default_sender_zip', '33122'),
                countryCode: config('carriers.default_sender_country', 'US'),
            );
            $senderName = config('carriers.default_sender_name', 'Marketz Warehouse');
            $senderCompany = config('carriers.default_sender_company', 'Marketz LLC');
            $senderPhone = config('carriers.default_sender_phone', '3051234567');
        }

        // Recipient address from destination array or default
        // Note: state defaults to empty, not 'NY', to avoid issues with international addresses
        $recipientAddress = new Address(
            street1: $destination['street1'] ?? '123 Customer St',
            street2: $destination['street2'] ?? null,
            city: $destination['city'] ?? 'New York',
            state: $destination['state'] ?? '', // Don't default to US state for international
            postalCode: $destination['zip'] ?? '10001',
            countryCode: $destination['country'] ?? 'US',
        );

        // Package details
        $package = new PackageDetail(
            weight: $weight,
            weightUnit: 'LB',
            length: (float) ($dimensions['length'] ?? 10),
            width: (float) ($dimensions['width'] ?? 10),
            height: (float) ($dimensions['height'] ?? 10),
            dimensionUnit: 'IN',
            declaredValue: null,
        );

        return new ShipmentRequest(
            senderName: $senderName,
            senderCompany: $senderCompany,
            senderPhone: $senderPhone,
            senderEmail: config('carriers.default_sender_email', 'shipping@marketz.com'),
            senderAddress: $senderAddress,
            recipientName: $destination['name'] ?? 'Customer',
            recipientPhone: $destination['phone'] ?? '0000000000',
            recipientEmail: $destination['email'] ?? '',
            recipientAddress: $recipientAddress,
            packages: [$package],
            serviceType: $serviceType,
        );
    }

    /**
     * Build rate request by carrier ID (uses consolidated method)
     */
    private function buildRateRequest(
        int $carrierId,
        float $weight,
        array $dimensions,
        array $destination
    ): ShipmentRequest {
        return $this->buildShipmentRequest(
            $weight,
            $dimensions,
            $destination,
            $this->getServiceType($carrierId)
        );
    }

    /**
     * Check if we should attempt live rate (credentials configured)
     */
    private function shouldAttemptLiveRate(int $carrierId): bool
    {
        $carrierName = $this->getCarrierNameFromId($carrierId);

        switch ($carrierName) {
            case 'fedex':
                return !empty(config('carriers.fedex.client_id'))
                    && !empty(config('carriers.fedex.client_secret'));
            case 'dhl':
                return !empty(config('carriers.dhl.api_key'))
                    && !empty(config('carriers.dhl.api_secret'));
            case 'ups':
                return !empty(config('carriers.ups.client_id'))
                    && !empty(config('carriers.ups.client_secret'));
            default:
                return false;
        }
    }

    /**
     * Get carrier name from ID
     */
    private function getCarrierName(int $carrierId): string
    {
        return match ($carrierId) {
            InternationalShippingOptions::DHL_EXPRESS => 'DHL Express',
            InternationalShippingOptions::FEDEX_ECONOMY => 'FedEx Economy',
            InternationalShippingOptions::SEA_FREIGHT => 'Sea Freight',
            InternationalShippingOptions::AIR_CARGO => 'Air Cargo',
            default => 'Unknown',
        };
    }

    /**
     * Get carrier identifier from ID
     */
    private function getCarrierNameFromId(int $carrierId): string
    {
        return match ($carrierId) {
            InternationalShippingOptions::DHL_EXPRESS => 'dhl',
            InternationalShippingOptions::FEDEX_ECONOMY => 'fedex',
            InternationalShippingOptions::SEA_FREIGHT => 'dhl', // Sea freight via DHL
            InternationalShippingOptions::AIR_CARGO => 'fedex', // Air cargo via FedEx
            default => 'fedex',
        };
    }

    /**
     * Get service name for database lookup
     */
    private function getServiceName(int $carrierId): string
    {
        return match ($carrierId) {
            InternationalShippingOptions::DHL_EXPRESS => InternationalShippingOptions::DHL_NAME,
            InternationalShippingOptions::FEDEX_ECONOMY => InternationalShippingOptions::FEDEX_NAME,
            InternationalShippingOptions::SEA_FREIGHT => InternationalShippingOptions::SEA_FREIGHT_NAME,
            InternationalShippingOptions::AIR_CARGO => InternationalShippingOptions::AIR_CARGO_NAME,
            default => InternationalShippingOptions::FEDEX_NAME,
        };
    }

    /**
     * Get service type for API request
     */
    private function getServiceType(int $carrierId): string
    {
        return match ($carrierId) {
            InternationalShippingOptions::DHL_EXPRESS => 'EXPRESS_WORLDWIDE',
            InternationalShippingOptions::FEDEX_ECONOMY => 'FEDEX_INTERNATIONAL_ECONOMY',
            InternationalShippingOptions::SEA_FREIGHT => 'ECONOMY_SELECT',
            InternationalShippingOptions::AIR_CARGO => 'FEDEX_INTERNATIONAL_PRIORITY',
            default => 'FEDEX_INTERNATIONAL_ECONOMY',
        };
    }

    /**
     * Check if carrier uses volumetric pricing
     */
    private function isVolumetricCarrier(int $carrierId): bool
    {
        return in_array($carrierId, [
            InternationalShippingOptions::SEA_FREIGHT,
            InternationalShippingOptions::AIR_CARGO,
        ]);
    }

    /**
     * Build cache key for rate lookup
     */
    private function buildCacheKey(
        int $carrierId,
        float $weight,
        array $dimensions,
        array $destination
    ): string {
        $dimKey = implode('x', [
            $dimensions['length'] ?? 0,
            $dimensions['width'] ?? 0,
            $dimensions['height'] ?? 0,
        ]);
        $destKey = implode('-', [
            $destination['zip'] ?? '',
            $destination['country'] ?? 'US',
        ]);

        return "shipping_rate:{$carrierId}:{$weight}:{$dimKey}:{$destKey}";
    }

    /**
     * Get cached rate
     */
    private function getCached(string $key): ?float
    {
        $cached = Cache::get($key);
        return $cached !== null ? (float) $cached : null;
    }

    /**
     * Set cached rate
     */
    private function setCache(string $key, float $rate): void
    {
        Cache::put($key, $rate, $this->cacheTtl);
    }

    /**
     * Calculate price differences between carriers for comparison
     */
    private function calculatePriceDifference(array $comparison): array
    {
        $dhlRates = array_filter($comparison, fn($r) => $r['carrier'] === 'dhl');
        $fedexRates = array_filter($comparison, fn($r) => $r['carrier'] === 'fedex');
        
        $differences = [];
        
        // Compare Express Worldwide services
        $dhlExpress = array_values(array_filter($dhlRates, fn($r) => 
            str_contains(strtolower($r['service'] ?? ''), 'express worldwide') ||
            str_contains(strtolower($r['service'] ?? ''), 'worldwide')
        ))[0] ?? null;
        
        $fedexExpress = array_values(array_filter($fedexRates, fn($r) => 
            str_contains(strtolower($r['service'] ?? ''), 'express') ||
            str_contains(strtolower($r['service'] ?? ''), 'priority')
        ))[0] ?? null;
        
        if ($dhlExpress && $fedexExpress) {
            $priceDiff = $dhlExpress['price'] - $fedexExpress['price'];
            $differences[] = [
                'comparison' => 'DHL Express Worldwide vs FedEx Express',
                'dhl_price' => $dhlExpress['price'],
                'fedex_price' => $fedexExpress['price'],
                'difference' => $priceDiff,
                'difference_percentage' => $fedexExpress['price'] > 0 
                    ? round(($priceDiff / $fedexExpress['price']) * 100, 2) 
                    : 0,
                'dhl_base' => $dhlExpress['base_charge'],
                'fedex_base' => $fedexExpress['base_charge'],
                'dhl_surcharges' => $dhlExpress['surcharges'],
                'fedex_surcharges' => $fedexExpress['surcharges'],
            ];
        }
        
        // Compare cheapest rates
        $dhlCheapest = !empty($dhlRates) ? min(array_column($dhlRates, 'price')) : null;
        $fedexCheapest = !empty($fedexRates) ? min(array_column($fedexRates, 'price')) : null;
        
        if ($dhlCheapest !== null && $fedexCheapest !== null) {
            $cheapestDiff = $dhlCheapest - $fedexCheapest;
            $differences[] = [
                'comparison' => 'Cheapest Rate Comparison',
                'dhl_cheapest' => $dhlCheapest,
                'fedex_cheapest' => $fedexCheapest,
                'difference' => $cheapestDiff,
                'difference_percentage' => $fedexCheapest > 0 
                    ? round(($cheapestDiff / $fedexCheapest) * 100, 2) 
                    : 0,
            ];
        }
        
        return $differences;
    }
}
