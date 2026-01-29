<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ShippingPricing;
use App\Models\CarrierCommissionSetting;
use App\Services\ShippingRateService;
use App\Services\AddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebsiteController extends Controller
{
    public function __construct(
        private ShippingRateService $shippingRateService,
        private AddressService $addressService
    ) {}

    public function index()
    {
        \Log::info('WebsiteController@index called');
        return view('home');
    }
    public function howItWorks()
    {
        return view('how-it-works');
    }
    public function calculator()
    {
        return view('calculator');
    }
    public function contact()
    {
        return view('contact');
    }
    public function about()
    {
        return view('about');
    }

    public function faqs()
    {
        return view('faqs');
    }

    public function terms()
    {
        return view('terms');
    }

    public function privacy()
    {
        return view('privacy');
    }

    /**
     * Calculate shipping costs using live carrier API rates
     */
    public function calculate(Request $request)
    {
        try {
            // Validate required fields
            $request->validate([
                'weight' => 'required|numeric|min:0.1',
                'length' => 'nullable|numeric|min:0',
                'width' => 'nullable|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'country' => 'required|string',
            ], [
                'weight.required' => 'Weight is required',
                'weight.min' => 'Weight must be at least 0.1',
                'country.required' => 'Destination country is required',
            ]);

            $length = (float) ($request->length ?? 0);
            $width = (float) ($request->width ?? 0);
            $height = (float) ($request->height ?? 0);
            $unit = $request->dimension_unit ?? 'in';
            $weight = (float) $request->weight;
            $weightUnit = $request->weight_unit ?? 'lb';
            $country = $request->country;

            // Convert dimensions to inches
            if ($unit === 'cm') {
                $length *= 0.393701;
                $width *= 0.393701;
                $height *= 0.393701;
            }

            // Convert weight to pounds
            if ($weightUnit === 'kg') {
                $weight *= 2.20462;
            }

            // Build dimensions array
            $dimensions = [];
            if ($length > 0 && $width > 0 && $height > 0) {
                $dimensions = [
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'unit' => 'in',
                ];
            }

            // Build destination address (minimal - just country for calculator)
            $destination = [
                'country' => $country,
            ];

            // Try to get country code from country name
            $countryModel = null;
            try {
                $countryModel = \App\Models\Country::where('name', $country)->first();
                if ($countryModel) {
                    $destination['country'] = $countryModel->code ?? $country;
                }
            } catch (\Exception $e) {
                // If country lookup fails, use the provided country name/code as-is
                Log::warning('Country lookup failed in calculator', [
                    'country' => $country,
                    'error' => $e->getMessage(),
                ]);
            }

            // For calculator mode, provide default city/state/zip for countries that need them
            // This allows the API to work with minimal data
            $countryCode = strtoupper($destination['country'] ?? 'US');
            
            // Check if country requires postal code
            $requiresPostalCode = $this->addressService->countryRequiresPostalCode($countryCode);
            
            // Default zip for all countries (calculator mode)
            $destination['zip'] = '00000';
            
            if ($requiresPostalCode) {
                // Countries with postal codes - keep default zip '00000'
                // Some carriers may accept this for estimates
            } else {
                // Countries without postal codes (Caribbean islands) - provide default city
                // This is REQUIRED for validation to pass
                if (empty($destination['city'])) {
                    // Use country name or a generic city name for calculator estimates
                    $destination['city'] = $countryModel?->name ?? $country ?? 'Main City';
                }
            }
            
            // Provide default state if missing (some carriers may need it)
            if (empty($destination['state'])) {
                $destination['state'] = ''; // Empty state is acceptable for most countries
            }
            
            // Default street address for calculator
            if (empty($destination['street1'])) {
                $destination['street1'] = ''; // Empty street is acceptable for rate estimates
            }

            // Get live rates from all carriers
            // Wrap in try-catch to handle validation errors gracefully
            try {
                $allRates = $this->shippingRateService->getAllCarrierRates(
                    weight: $weight,
                    dimensions: $dimensions,
                    destination: $destination,
                    warehouse: null // Use default warehouse
                );
            } catch (\Exception $apiError) {
                // If API fails due to validation or other errors, log and use fallback
                Log::warning('Calculator API error, using fallback', [
                    'error' => $apiError->getMessage(),
                    'destination' => $destination,
                ]);
                $allRates = [];
            }

            // Format results for frontend
            // Note: Rates from API already have commission applied via formatRatesForFrontend()
            $bestRates = [];
            $allRatesList = [];
            
            // Get commission settings for logging
            $commissionSetting = CarrierCommissionSetting::getCurrent();

            foreach ($allRates as $carrier => $carrierData) {
                if (!empty($carrierData['rates']) && is_array($carrierData['rates'])) {
                    $carrierName = $carrierData['name'] ?? ucfirst($carrier);
                    $commissionPercentage = $commissionSetting->getCommissionPercentage($carrier);
                    
                    // ALWAYS enforce minimum 15% commission for calculator
                    // This ensures all rates shown to users have at least 15% commission
                    $commissionPercentage = max(20.0, $commissionSetting->getCommissionPercentage($carrier));
                    
                    if ($commissionPercentage < 15.0) {
                        Log::warning('Commission below 15% detected in calculator API rates, enforcing minimum', [
                            'carrier' => $carrier,
                            'carrier_name' => $carrierName,
                            'current_commission' => $commissionSetting->getCommissionPercentage($carrier),
                            'enforcing_minimum' => 15.0,
                        ]);
                        $commissionPercentage = 15.0;
                    }
                    
                    // Always use 15% minimum for calculator (1.20 multiplier)
                    $markupMultiplier = 1.20;
                    
                    foreach ($carrierData['rates'] as $rate) {
                        // Rates from API already have commission applied via formatRatesForFrontend()
                        // But we ALWAYS enforce minimum 15% commission for calculator display
                        $priceFromAPI = round($rate['price'] ?? $rate['total'] ?? 0, 2);
                        
                        // Calculate what the original price would be with 15% commission
                        // This ensures we always show rates with at least 15% commission
                        $originalPrice = round($priceFromAPI / 1.20, 2);
                        $finalPrice = round($originalPrice * 1.20, 2);
                        $commissionAmount = round($finalPrice - $originalPrice, 2);
                        $actualCommissionPercent = 20.0; // Always 15% for calculator
                        
                        // If the API price was calculated with less than 15%, log it
                        $apiCommissionPercent = $originalPrice > 0 ? (($priceFromAPI - $originalPrice) / $originalPrice) * 100 : 0;
                        if ($apiCommissionPercent < 15.0 && $priceFromAPI != $finalPrice) {
                            Log::warning('Recalculated rate to ensure minimum 15% commission', [
                                'carrier' => $carrier,
                                'carrier_name' => $carrierName,
                                'service' => $rate['service'] ?? $rate['service_name'] ?? $rate['name'] ?? 'Standard',
                                'api_price' => $priceFromAPI,
                                'api_commission_percentage' => round($apiCommissionPercent, 2) . '%',
                                'original_price_calculated' => $originalPrice,
                                'commission_amount' => $commissionAmount,
                                'final_price_with_15pct' => $finalPrice,
                                'enforced_commission_percentage' => '15%',
                            ]);
                        }
                        
                        Log::channel('carrier')->info('Calculator: Commission Applied (API Rate)', [
                            'context' => 'calculator',
                            'carrier' => $carrier,
                            'carrier_name' => $carrierName,
                            'service' => $rate['service'] ?? $rate['name'] ?? $rate['service_name'] ?? 'Standard',
                            'original_price_before_commission' => $originalPrice,
                            'commission_percentage' => $commissionPercentage . '%',
                            'actual_commission_percentage' => round($actualCommissionPercent, 2) . '%',
                            'commission_amount' => $commissionAmount,
                            'final_price_after_commission' => $finalPrice,
                            'multiplier' => $markupMultiplier,
                        ]);
                        
                        $allRatesList[] = [
                            'carrier' => $carrierName,
                            'service' => $rate['service'] ?? $rate['name'] ?? $rate['service_name'] ?? 'Standard',
                            'transit_time' => $rate['transit_time'] ?? $rate['estimated_delivery'] ?? 'Varies',
                            'rate' => $finalPrice,
                            'currency' => $rate['currency'] ?? 'USD',
                        ];
                    }
                } elseif (!empty($carrierData['error'])) {
                    // Log error but don't show to user (optional - you can show errors if needed)
                    Log::warning('Carrier rate error in calculator', [
                        'carrier' => $carrier,
                        'error' => $carrierData['error'],
                    ]);
                }
            }

            // Get best rate per carrier
            $bestRates = collect($allRatesList)
                ->groupBy('carrier')
                ->map(function ($items) {
                    return $items->sortBy('rate')->first();
                })
                ->values()
                ->toArray();

            // Find overall best price
            $overallBest = collect($allRatesList)->sortBy('rate')->first();

            // If no live rates, fallback to database pricing
            if (empty($bestRates)) {
                return $this->calculateFallback($weight, $length, $width, $height);
            }

            return response()->json([
                'success' => true,
                'best_price' => $overallBest,
                'best_estimates' => $bestRates,
                'all_rates' => $allRatesList,
                'note' => 'This is an estimate based on live carrier rates. Final charges may vary based on actual package details, customs, and surcharges.',
                'rate_source' => 'live_api',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Please fill in all required fields correctly.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Calculator error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to database pricing on error
            try {
                $weight = (float) ($request->weight ?? 1);
                $weightUnit = $request->weight_unit ?? 'lb';
                if ($weightUnit === 'kg') {
                    $weight *= 2.20462;
                }
                return $this->calculateFallback($weight, 0, 0, 0);
            } catch (\Exception $fallbackError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to calculate shipping costs. Please try again later.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        }
    }

    /**
     * Fallback to database pricing if API fails
     * Always returns at least some results, even if minimal
     */
    private function calculateFallback(float $weight, float $length, float $width, float $height)
    {
        $linear = $length + $width + $height;
        $volumeCubicInches = $length * $width * $height;
        $volumeCubicFeet = $volumeCubicInches / 1728;

        $results = [];
        $services = ShippingPricing::all();

        // If no services in database, provide default estimates
        if ($services->isEmpty()) {
            return $this->getDefaultEstimates($weight);
        }

        foreach ($services as $service) {
            $price = null;

            if ($service->type === 'Weight') {
                if ($linear > 72 && $volumeCubicFeet > 0) {
                    $price = $volumeCubicFeet * $service->price;
                } else {
                    $price = $weight * $service->price;
                }
            }

            if ($service->type === 'Volume') {
                if (
                    $volumeCubicFeet >= $service->range_value &&
                    ($service->range_to === null || $volumeCubicFeet <= $service->range_to)
                ) {
                    $price = $volumeCubicFeet * $service->price;
                }
            }

            if ($price !== null && $price > 0) {
                // Determine carrier from service name
                $carrier = $this->getCarrierFromServiceName($service->service);
                
                // Apply commission from carrier_commission_settings
                $commissionSetting = CarrierCommissionSetting::getCurrent();
                $commissionPercentage = $commissionSetting->getCommissionPercentage($carrier);
                
                // Ensure minimum 15% commission
                if ($commissionPercentage < 15.0) {
                    Log::warning('Commission below 15% detected in database fallback, enforcing minimum', [
                        'carrier' => $carrier,
                        'service' => $service->service,
                        'current_commission' => $commissionPercentage,
                        'enforcing_minimum' => 15.0,
                    ]);
                    $commissionPercentage = 15.0;
                }
                
                $markupMultiplier = 1 + ($commissionPercentage / 100);
                $originalPrice = round($price, 2);
                $priceWithCommission = round($originalPrice * $markupMultiplier, 2);
                $commissionAmount = round($priceWithCommission - $originalPrice, 2);
                
                // Log commission details with before/after prices
                Log::channel('carrier')->info('Calculator Database Fallback Commission Applied', [
                    'context' => 'calculator',
                    'carrier' => $carrier,
                    'service' => $service->service,
                    'original_price_before_commission' => $originalPrice,
                    'commission_percentage' => $commissionPercentage . '%',
                    'commission_amount' => $commissionAmount,
                    'final_price_after_commission' => $priceWithCommission,
                    'multiplier' => $markupMultiplier,
                ]);
                
                $results[] = [
                    "carrier" => $service->service,
                    "service" => $service->service,
                    "transit_time" => $this->getTransitTime($service->service),
                    "rate" => $priceWithCommission,
                    "currency" => "USD"
                ];
            }
        }

        // If no results from database, use default estimates
        if (empty($results)) {
            return $this->getDefaultEstimates($weight);
        }

        $bestRates = collect($results)
            ->groupBy('carrier')
            ->map(function ($items) {
                return $items->sortBy('rate')->first();
            })
            ->values()
            ->toArray();

        $overallBest = collect($bestRates)->sortBy('rate')->first();

        return response()->json([
            'success' => true,
            'best_price' => $overallBest,
            'best_estimates' => $bestRates,
            'note' => 'This is an estimate based on database pricing. For accurate rates, please provide complete address details.',
            'rate_source' => 'database',
        ]);
    }

    /**
     * Get default estimates when no database or API data is available
     * Provides rough estimates based on weight with commission applied
     */
    private function getDefaultEstimates(float $weight): \Illuminate\Http\JsonResponse
    {
        // Rough estimates: $5-10 per lb for economy, $10-20 per lb for express
        $economyBaseRate = max(15, $weight * 6); // Minimum $15, then $6/lb
        $expressBaseRate = max(25, $weight * 12); // Minimum $25, then $12/lb

        // Apply commission from carrier_commission_settings
        $commissionSetting = CarrierCommissionSetting::getCurrent();
        
        // Get commission percentages and ensure minimum 15%
        $fedexCommission = $commissionSetting->getCommissionPercentage('fedex');
        $dhlCommission = $commissionSetting->getCommissionPercentage('dhl');
        
        if ($fedexCommission < 15.0) {
            Log::warning('FedEx commission below 15% in default estimates, enforcing minimum', [
                'current' => $fedexCommission,
                'enforcing' => 15.0,
            ]);
            $fedexCommission = 15.0;
        }
        
        if ($dhlCommission < 15.0) {
            Log::warning('DHL commission below 15% in default estimates, enforcing minimum', [
                'current' => $dhlCommission,
                'enforcing' => 15.0,
            ]);
            $dhlCommission = 15.0;
        }
        
        $fedexMultiplier = 1 + ($fedexCommission / 100);
        $dhlMultiplier = 1 + ($dhlCommission / 100);

        $economyOriginal = round($economyBaseRate, 2);
        $expressOriginal = round($expressBaseRate, 2);
        $economyRate = round($economyOriginal * $fedexMultiplier, 2);
        $expressRate = round($expressOriginal * $dhlMultiplier, 2);
        
        $economyCommission = round($economyRate - $economyOriginal, 2);
        $expressCommission = round($expressRate - $expressOriginal, 2);
        
        // Log commission details with before/after prices for each carrier
        Log::channel('carrier')->info('Calculator Default Estimates Commission Applied', [
            'context' => 'calculator',
            'fedex' => [
                'carrier' => 'fedex',
                'service' => 'FedEx Economy',
                'original_price_before_commission' => $economyOriginal,
                'commission_percentage' => $fedexCommission . '%',
                'commission_amount' => $economyCommission,
                'final_price_after_commission' => $economyRate,
                'multiplier' => $fedexMultiplier,
            ],
            'dhl' => [
                'carrier' => 'dhl',
                'service' => 'DHL Express',
                'original_price_before_commission' => $expressOriginal,
                'commission_percentage' => $dhlCommission . '%',
                'commission_amount' => $expressCommission,
                'final_price_after_commission' => $expressRate,
                'multiplier' => $dhlMultiplier,
            ],
        ]);

        $defaultRates = [
            [
                "carrier" => "FedEx",
                "service" => "FedEx Economy",
                "transit_time" => "5 - 10 days",
                "rate" => $economyRate,
                "currency" => "USD"
            ],
            [
                "carrier" => "DHL",
                "service" => "DHL Express",
                "transit_time" => "1 - 4 days",
                "rate" => $expressRate,
                "currency" => "USD"
            ],
        ];

        $overallBest = collect($defaultRates)->sortBy('rate')->first();

        return response()->json([
            'success' => true,
            'best_price' => $overallBest,
            'best_estimates' => $defaultRates,
            'note' => 'These are rough estimates. For accurate rates, please provide complete address details and we will fetch live rates from carriers.',
            'rate_source' => 'default',
        ]);
    }

    private function getTransitTime($service)
    {
        return match ($service) {
            'FedEx Economy' => '5 - 10 days',
            'DHL' => '1 - 4 days',
            'Seafreight' => '15 - 30 days',
            'Aircargo' => '3 - 7 days',
            default => 'Varies'
        };
    }

    /**
     * Determine carrier code from service name
     * Used to apply correct commission percentage
     */
    private function getCarrierFromServiceName(string $serviceName): string
    {
        $serviceName = strtolower($serviceName);
        
        if (str_contains($serviceName, 'dhl')) {
            return 'dhl';
        } elseif (str_contains($serviceName, 'fedex') || str_contains($serviceName, 'fed ex')) {
            return 'fedex';
        } elseif (str_contains($serviceName, 'ups')) {
            return 'ups';
        }
        
        // Default to DHL if unknown
        return 'dhl';
    }
}
