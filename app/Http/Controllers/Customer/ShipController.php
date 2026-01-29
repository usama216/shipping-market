<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CarrierAddon;
use App\Models\CarrierService;
use App\Models\InternationalShippingOptions;
use App\Models\ShippingPreferences;
use App\Models\CustomerAddress;
use App\Models\Transaction;
use App\Payments\Stripe;
use App\Repositories\PackageRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\ShippingPreferencesRepository;
use App\Repositories\ShipRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Services\ShippingRateService;
use App\Services\LoyaltyService;
use App\Services\CheckoutService;
use App\Services\DTOs\CheckoutRequest;

class ShipController extends Controller
{
    protected $shipRepository, $packageRepository, $paymentMethodRepository, $shippingPreferenceRepository, $stripeClient, $transactionRepository, $shippingRateService, $loyaltyService, $checkoutService;

    public function __construct(
        ShipRepository $shipRepository,
        PackageRepository $packageRepository,
        PaymentMethodRepository $paymentMethodRepository,
        ShippingPreferencesRepository $shippingPreferenceRepository,
        TransactionRepository $transactionRepository,
        ShippingRateService $shippingRateService,
        LoyaltyService $loyaltyService,
        CheckoutService $checkoutService
    ) {
        $this->packageRepository = $packageRepository;
        $this->shipRepository = $shipRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingPreferenceRepository = $shippingPreferenceRepository;
        $this->transactionRepository = $transactionRepository;
        $this->stripeClient = new Stripe();
        $this->shippingRateService = $shippingRateService;
        $this->loyaltyService = $loyaltyService;
        $this->checkoutService = $checkoutService;
    }


    public function index($ship)
    {
        $customer = Auth::guard('customer')->user();
        $shipId = Crypt::decrypt($ship);
        $ship = $this->shipRepository->findById($shipId);

        // Load packages with items, files, and special requests
        $ship->load(['packages.items', 'packages.files']);
        
        // Load special requests for all packages
        $packageIds = $ship->packages->pluck('id')->toArray();
        $allSpecialRequestIds = [];
        foreach ($ship->packages as $package) {
            if (!empty($package->selected_addon_ids)) {
                $ids = is_array($package->selected_addon_ids) 
                    ? $package->selected_addon_ids 
                    : json_decode($package->selected_addon_ids, true) ?? [];
                $allSpecialRequestIds = array_merge($allSpecialRequestIds, $ids);
            }
        }
        $allSpecialRequestIds = array_unique($allSpecialRequestIds);
        
        // Get special requests data
        $specialRequests = \App\Models\SpecialRequest::whereIn('id', $allSpecialRequestIds)->get();
        $specialRequestCost = $specialRequests->sum('price');
        
        $cards = $this->paymentMethodRepository->getCardsByCustomer($customer->id);

        // Get carrier services and addons from new consolidated system
        $carrierServices = CarrierService::active()->ordered()->get()->map->toFrontendFormat();
        $carrierAddons = CarrierAddon::active()->ordered()->get()->map(fn($addon) => $addon->toFrontendFormat());

        return Inertia::render('Customers/Shipment/Create', [
            'ship' => $ship,
            'cards' => $cards,
            'publishableKey' => config('services.stripe.key'),
            'stripeMode' => config('services.stripe.mode'),
            'customerAddresses' => $customer->addresses,
            'internationalShippingMethod' => $this->shippingPreferenceRepository->getInternationalShippingOptions(),
            'userPreferences' => $this->shippingPreferenceRepository->getShippingPreference($customer->id),
            'packingOptions' => $this->shippingPreferenceRepository->getPackingOption(),
            'shippingPreferenceOptions' => $this->shippingPreferenceRepository->shippingPreferenceOptions(),
            // New consolidated carrier data
            'carrierServices' => $carrierServices,
            'carrierAddons' => $carrierAddons,
            // Special requests from packages
            'specialRequests' => $specialRequests->map(fn($sr) => [
                'id' => $sr->id,
                'title' => $sr->title,
                'description' => $sr->description,
                'price' => (float) $sr->price,
            ]),
            'specialRequestCost' => $specialRequestCost,
        ]);
    }

    public function createShipment(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            DB::beginTransaction();
            $totalPackageWeight = 0;
            $totalPackagePrice = 0;
            $packages = $this->packageRepository->getPackageByIds($request->input('package_ids', []));
            if (!$packages->isEmpty()) {
                foreach ($packages as $package) {
                    // Use billed_weight (max of physical or volumetric) - this is what carriers charge on
                    $totalPackageWeight += $package->billed_weight ?? $package->total_weight;
                    $totalPackagePrice += $package->total_value;
                }
            } else {
                return Redirect::back()->withErrors(['message' => 'No packages selected for shipment.']);
            }

            $this->shipRepository->deletePendingShipmentForCustomer($customer->id);

            $ship = $this->shipRepository->create([
                'customer_id' => $customer->id,
                'tracking_number' => rand(00000000, 99999999),
                'total_weight' => $totalPackageWeight,
                'total_price' => $totalPackagePrice,
            ]);

            $ship->packages()->attach($packages->pluck('id'));
            DB::commit();
            return Redirect::route('customer.shipment.index', ['ship' => Crypt::encrypt($ship->id)])->with('success', 'Shipment created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Error creating shipment: ' . $e->getMessage()]);
        }

    }

    public function deletePackageFromShip($id, $packageId)
    {
        try {
            DB::beginTransaction();

            $ship = $this->shipRepository->findById($id);
            if (!$ship) {
                return Redirect::back()->withErrors(['message' => 'Shipment not found.']);
            }

            $package = $this->packageRepository->findById($packageId);
            if (!$package) {
                return Redirect::back()->withErrors(['message' => 'Package not found.']);
            }
            if (!$ship->packages()->wherePivot('package_id', $packageId)->exists()) {
                return Redirect::back()->withErrors(['message' => 'Package is not attached to this shipment.']);
            }


            $ship->packages()->detach($packageId);

            // Use billed_weight (max of physical or volumetric) - this is what carriers charge on
            $packageWeight = $package->billed_weight ?? $package->total_weight;
            $ship->total_weight -= $packageWeight;
            $ship->total_price -= $package->total_value;

            $ship->total_weight = max($ship->total_weight, 0);
            $ship->total_price = max($ship->total_price, 0);

            if ($ship->packages()->count() === 0) {
                $ship->delete();
                DB::commit();
                return Redirect::route('customer.dashboard')->with('alert', 'Shipment deleted as no packages remain.');
            }
            $ship->save();

            DB::commit();
            return Redirect::back()->with('alert', 'Package removed from shipment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Error removing package: ' . $e->getMessage()]);
        }
    }

    public function calculateShippingCost(Request $request)
    {
        $packingOptionAmount = 0.00;
        $shippingPreferenceOptionAmount = 0.00;

        $shipMethod = (int) $request->input('shipMethod');
        $shipWeight = (float) $request->input('shipWeight', 0);

        // Optional: dimensions and destination for more accurate live rates
        $dimensions = $request->input('dimensions', []);
        $destination = $request->input('destination', []);

        // Use ShippingRateService for live API rates with DB fallback
        $rateResult = $this->shippingRateService->getRate(
            carrierId: $shipMethod,
            weight: $shipWeight,
            dimensions: $dimensions,
            destination: $destination
        );

        $internationalShippingAmount = $rateResult->price;

        // Only sum if arrays are not empty (fix for empty array being truthy)
        $packingOptions = $request->input('packingOption', []);
        if (is_array($packingOptions) && count($packingOptions) > 0) {
            $packingOptionAmount = (float) $this->shippingPreferenceRepository->sumPackingOption($packingOptions);
        }

        $shippingPreferences = $request->input('shippingPreferenceOption', []);
        if (is_array($shippingPreferences) && count($shippingPreferences) > 0) {
            $shippingPreferenceOptionAmount = (float) $this->shippingPreferenceRepository->sumShippingPreferenceOption($shippingPreferences);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipping cost calculated successfully.',
            'data' => [
                'international_shipping_amount' => $internationalShippingAmount,
                'packing_option_amount' => $packingOptionAmount,
                'shipping_preference_option_amount' => $shippingPreferenceOptionAmount,
                'rate_source' => $rateResult->source,
                'is_live_rate' => $rateResult->isLiveRate(),
            ]
        ], 200);
    }

    /**
     * Get all shipping rates from all carriers
     * Returns rates grouped by carrier with service options + addon costs
     * 
     * Accepts only IDs - all data loaded from database
     * Optionally accepts 'carrier' param for single-carrier refresh
     */
    public function getAllShippingRates(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        // Accept IDs only - no raw destination objects from frontend
        $shipId = $request->input('ship_id');
        $addressId = $request->input('address_id');
        $packingOptionIds = $request->input('packing_option_ids', []);
        $shippingPreferenceIds = $request->input('shipping_preference_ids', []);
        $singleCarrier = $request->input('carrier'); // Optional: for single-carrier refresh

        // Legacy support: still accept package_ids for backwards compatibility
        $packageIds = $request->input('package_ids', []);

        try {
            // If ship_id provided, load packages from the ship
            if ($shipId) {
                $ship = \App\Models\Ship::with(['packages'])->find($shipId);
                if ($ship) {
                    $packageIds = $ship->packages->pluck('id')->toArray();
                }
            }

            // Resolve destination from address ID (backend loads from DB)
            $destination = $this->resolveDestination($addressId, $customer);

            if (empty($packageIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No packages found for rate calculation.',
                ], 400);
            }

            // Get carrier rates - single carrier or all carriers
            if ($singleCarrier) {
                $allRates = $this->shippingRateService->getSingleCarrierRates(
                    $singleCarrier,
                    $packageIds,
                    $destination
                );
            } else {
                $allRates = $this->shippingRateService->getRatesForPackages($packageIds, $destination);
            }

            // Calculate addon costs from DB (if IDs provided)
            $packingCost = 0;
            $preferenceCost = 0;
            $specialRequestCost = 0;

            if (!empty($packingOptionIds) && is_array($packingOptionIds)) {
                $packingCost = (float) $this->shippingPreferenceRepository->sumPackingOption($packingOptionIds);
            }

            if (!empty($shippingPreferenceIds) && is_array($shippingPreferenceIds)) {
                $preferenceCost = (float) $this->shippingPreferenceRepository->sumShippingPreferenceOption($shippingPreferenceIds);
            }

            // Calculate special request costs from packages
            $packages = \App\Models\Package::whereIn('id', $packageIds)->get();
            foreach ($packages as $package) {
                if (!empty($package->selected_addon_ids)) {
                    $specialRequestIds = is_array($package->selected_addon_ids) 
                        ? $package->selected_addon_ids 
                        : json_decode($package->selected_addon_ids, true) ?? [];
                    
                    if (!empty($specialRequestIds)) {
                        $specialRequests = \App\Models\SpecialRequest::whereIn('id', $specialRequestIds)->get();
                        $specialRequestCost += $specialRequests->sum('price');
                    }
                }
            }

            // Enrich addons with live pricing from surcharge data for each carrier
            $enrichedAddons = [];
            foreach ($allRates as $carrierCode => $carrierData) {
                // Get surcharge breakdown from first rate (if available)
                $surchargeBreakdown = [];
                $baseRate = 0;
                if (!empty($carrierData['rates'])) {
                    $firstRate = $carrierData['rates'][0];
                    $surchargeBreakdown = $firstRate['surcharge_breakdown'] ?? [];
                    $baseRate = $firstRate['price'] ?? 0;
                }

                // Get enriched addons with live pricing, mandatory flags, and availability
                $enrichedAddons[$carrierCode] = $this->shippingRateService->getAddonsForRate(
                    $carrierCode,
                    $surchargeBreakdown,
                    $baseRate,
                    $packageIds
                );

                // Also check checkout eligibility for this carrier
                $eligibility = $this->shippingRateService->validateCheckoutEligibility(
                    $carrierCode,
                    $surchargeBreakdown,
                    $packageIds
                );

                // Add eligibility info to carrier data
                $allRates[$carrierCode]['checkout_eligible'] = $eligibility['eligible'];
                $allRates[$carrierCode]['checkout_errors'] = $eligibility['errors'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'carriers' => $allRates,
                    'carrier_addons' => $enrichedAddons,
                    'addons' => [
                        'packing_cost' => $packingCost,
                        'preference_cost' => $preferenceCost,
                        'special_request_cost' => $specialRequestCost,
                    ],
                    // Classification summary for UI messaging
                    'package_classifications' => $this->shippingRateService->getClassificationSummary($packageIds),
                    'classification_charges' => app(\App\Services\CarrierAddonService::class)
                        ->calculateClassificationCharges($packageIds),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch shipping rates.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Resolve destination address from ID
     * Priority: 1) Passed address_id, 2) User preference, 3) Default address
     */
    private function resolveDestination(?int $addressId, $customer): array
    {
        $address = null;

        // Try passed address_id first
        if ($addressId) {
            $address = CustomerAddress::where('id', $addressId)
                ->where('customer_id', $customer->id)
                ->first();
        }

        // Fallback to user preference
        if (!$address) {
            $preferences = ShippingPreferences::where('customer_id', $customer->id)->first();
            if ($preferences?->user_address_id) {
                $address = CustomerAddress::find($preferences->user_address_id);
            }
        }

        // Final fallback: default address
        if (!$address) {
            $address = CustomerAddress::where('customer_id', $customer->id)
                ->where(fn($q) => $q->where('is_default_us', true)->orWhere('is_default_uk', true))
                ->first();
        }

        return $address ? [
            'street1' => $address->address_line_1 ?? '',
            'city' => $address->city ?? '',
            'state' => $address->state ?? '',
            'zip' => $address->postal_code ?? '',
            'country' => $address->country_code ?? $address->country ?? 'US',
        ] : [];
    }

    public function addNationalId(Request $request, $id)
    {
        $request->validate([
            'national_id' => 'required|string|max:255',
        ]);

        try {
            $ship = $this->shipRepository->findById($id);
            if (!$ship) {
                return Redirect::back()->withErrors(['message' => 'Shipment not found.']);
            }
            DB::beginTransaction();
            $ship->national_id = $request->input('national_id');
            $ship->save();

            DB::commit();
            return Redirect::back()->with('alert', 'National ID added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Error adding national ID: ' . $e->getMessage()]);
        }
    }

    public function checkout(Request $request)
    {
        // Log incoming request data for debugging
        \Log::info('CheckoutController: Received checkout request', [
            'all_input' => $request->all(),
            'international_shipping_option_id_raw' => $request->input('international_shipping_option_id'),
            'international_shipping_option_id_type' => gettype($request->input('international_shipping_option_id')),
            'carrier_service_id_raw' => $request->input('carrier_service_id'),
            'carrier_service_id_type' => gettype($request->input('carrier_service_id')),
        ]);
        
        // Normalize integer fields before validation
        if ($request->has('international_shipping_option_id') && $request->input('international_shipping_option_id') !== null) {
            $request->merge(['international_shipping_option_id' => (int) $request->input('international_shipping_option_id')]);
        }
        if ($request->has('carrier_service_id') && $request->input('carrier_service_id') !== null) {
            $request->merge(['carrier_service_id' => (int) $request->input('carrier_service_id')]);
        }
        if ($request->has('id')) {
            $request->merge(['id' => (int) $request->input('id')]);
        }
        if ($request->has('card_id')) {
            $request->merge(['card_id' => (int) $request->input('card_id')]);
        }
        if ($request->has('customer_address_id')) {
            $request->merge(['customer_address_id' => (int) $request->input('customer_address_id')]);
        }
        
        // Validate required fields
        $request->validate([
            'id' => 'required|integer',
            'card_id' => 'required|integer',
            'estimated_shipping_charges' => 'required|numeric|min:0.01|max:999999.99',
            'customer_address_id' => 'required|integer',
            'carrier_service_id' => 'required_without:international_shipping_option_id|nullable|integer|exists:carrier_services,id',
            'international_shipping_option_id' => 'required_without:carrier_service_id|nullable|integer',
            'loyalty_points_used' => 'nullable|integer|min:0',
            'loyalty_discount' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string|max:50',
            'coupon_order_amount' => 'nullable|numeric|min:0',
            'coupon_discount' => 'nullable|numeric|min:0',
        ], [
            'carrier_service_id.required_without' => 'Please select a carrier service (e.g., DHL Express Worldwide) before checkout.',
            'international_shipping_option_id.required_without' => 'Please select a shipping method before checkout.',
            'international_shipping_option_id.integer' => 'Invalid shipping method selected. Please refresh and try again.',
            'carrier_service_id.integer' => 'Invalid carrier service selected. Please refresh and try again.',
        ]);

        $customer = Auth::guard('customer')->user();
        $checkoutRequest = CheckoutRequest::fromRequest($request);

        $result = $this->checkoutService->processCheckout($customer, $checkoutRequest);

        if (!$result->success) {
            return Redirect::back()->withErrors(['message' => $result->error]);
        }

        return Redirect::route('customer.shipment.success', ['shipId' => $result->ship->id]);
    }

    public function successPage($shipId)
    {
        $shipment = $this->shipRepository->findById($shipId);
        $shipment->load('customerAddress', 'customer');
        return Inertia::render('Customers/Shipment/SuccessPage', [
            'shipment' => $shipment,
        ]);
    }

    public function myShipments()
    {
        $customer = Auth::guard('customer')->user();
        $shipments = $this->shipRepository->getShipsByCustomerId($customer->id);
        return Inertia::render('Customers/Shipment/MyShipment', ['shipments' => $shipments]);
    }

    public function viewShipment($ship)
    {
        $details = $this->shipRepository->getShipDetails($ship);
        
        // Safely decode JSON and handle null values
        $packingOptionIds = $details->packing_option_id 
            ? json_decode($details->packing_option_id, true) 
            : [];
        $shippingPreferenceOptionIds = $details->shipping_preference_option_id 
            ? json_decode($details->shipping_preference_option_id, true) 
            : [];
        
        $packingOptions = $this->shippingPreferenceRepository->getPackingOptionByIds($packingOptionIds);
        $shippingPreferenceOption = $this->shippingPreferenceRepository->shippingPreferenceOptionByIds($shippingPreferenceOptionIds);
        
        return Inertia::render('Customers/Shipment/Detail', [
            'shipDetails' => $details,
            'packingOptions' => $packingOptions,
            'shippingPreferenceOption' => $shippingPreferenceOption,
        ]);
    }

}
