<?php

namespace App\Http\Controllers; 

use App\Models\CarrierAddon;
use App\Models\CarrierService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * CarrierServicesController - Admin management for carrier services and addons
 */
class CarrierServicesController extends Controller
{
    /**
     * Display carrier services and addons management page
     */
    public function index()
    {
        $carrierServices = CarrierService::orderBy('carrier_code')
            ->orderBy('sort_order')
            ->get();

        $carrierAddons = CarrierAddon::orderBy('carrier_code')
            ->orderBy('sort_order')
            ->get();

        // Group services by carrier
        $groupedServices = $carrierServices->groupBy('carrier_code');

        // Get stats
        $stats = [
            'total_services' => $carrierServices->count(),
            'active_services' => $carrierServices->where('is_active', true)->count(),
            'total_addons' => $carrierAddons->count(),
            'active_addons' => $carrierAddons->where('is_active', true)->count(),
        ];

        return Inertia::render('Admin/CarrierServices/Index', [
            'carrierServices' => $carrierServices,
            'carrierAddons' => $carrierAddons,
            'groupedServices' => $groupedServices,
            'stats' => $stats,
        ]);
    }

    /**
     * Toggle service active status
     */
    public function toggleServiceStatus(CarrierService $carrierService)
    {
        $carrierService->update([
            'is_active' => !$carrierService->is_active,
        ]);

        return back()->with(
            'success',
            $carrierService->is_active
            ? "Service '{$carrierService->display_name}' activated"
            : "Service '{$carrierService->display_name}' deactivated"
        );
    }

    /**
     * Toggle addon active status
     */
    public function toggleAddonStatus(CarrierAddon $carrierAddon)
    {
        $carrierAddon->update([
            'is_active' => !$carrierAddon->is_active,
        ]);

        return back()->with(
            'success',
            $carrierAddon->is_active
            ? "Addon '{$carrierAddon->display_name}' activated"
            : "Addon '{$carrierAddon->display_name}' deactivated"
        );
    }

    /**
     * Update service details
     */
    public function updateService(Request $request, CarrierService $carrierService)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'transit_time_min' => 'nullable|integer|min:1',
            'transit_time_max' => 'nullable|integer|min:1',
            'fallback_base_rate' => 'nullable|numeric|min:0',
            'fallback_per_lb_rate' => 'nullable|numeric|min:0',
            'max_weight_lb' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $carrierService->update($validated);

        return back()->with('success', "Service '{$carrierService->display_name}' updated");
    }

    /**
     * Update addon details
     */
    public function updateAddon(Request $request, CarrierAddon $carrierAddon)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'price_type' => 'required|in:fixed,percentage,carrier_rate',
            'price_value' => 'nullable|numeric|min:0',
            'fallback_price' => 'nullable|numeric|min:0',
            'use_fallback' => 'boolean',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $carrierAddon->update($validated);

        return back()->with('success', "Addon '{$carrierAddon->display_name}' updated");
    }

    /**
     * Create new carrier service
     */
    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'carrier_code' => 'required|string|max:50',
            'service_code' => 'required|string|max:100|unique:carrier_services',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_international' => 'boolean',
            'is_domestic' => 'boolean',
            'transit_time_min' => 'nullable|integer|min:1',
            'transit_time_max' => 'nullable|integer|min:1',
            'fallback_base_rate' => 'nullable|numeric|min:0',
            'fallback_per_lb_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service = CarrierService::create($validated);

        return back()->with('success', "Service '{$service->display_name}' created");
    }

    /**
     * Create new addon
     */
    public function storeAddon(Request $request)
    {
        $validated = $request->validate([
            'addon_code' => 'required|string|max:100|unique:carrier_addons',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'carrier_code' => 'nullable|string|max:50',
            'price_type' => 'required|in:fixed,percentage,carrier_rate',
            'price_value' => 'nullable|numeric|min:0',
            'fallback_price' => 'nullable|numeric|min:0',
            'use_fallback' => 'boolean',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'boolean',
        ]);

        // Default carrier_code to 'all' if not provided
        $validated['carrier_code'] = $validated['carrier_code'] ?? 'all';
        $validated['source'] = CarrierAddon::SOURCE_ADMIN;

        $addon = CarrierAddon::create($validated);

        return back()->with('success', "Addon '{$addon->display_name}' created");
    }

    /**
     * Delete a carrier service
     */
    public function destroyService(CarrierService $carrierService)
    {
        // Check if service is in use
        if ($carrierService->shipments()->count() > 0) {
            return back()->withErrors([
                'message' => "Cannot delete service '{$carrierService->display_name}' - it has associated shipments. Deactivate instead."
            ]);
        }

        $name = $carrierService->display_name;
        $carrierService->delete();

        return back()->with('success', "Service '{$name}' deleted");
    }

    /**
     * Delete an addon
     */
    public function destroyAddon(CarrierAddon $carrierAddon)
    {
        $name = $carrierAddon->display_name;
        $carrierAddon->delete();

        return back()->with('success', "Addon '{$name}' deleted");
    }
}
