<?php

namespace App\Services;

use App\Helpers\PackageStatus;
use App\Helpers\ShipmentStatus;
use App\Services\ShipmentSubmissionService;
use App\Models\CarrierAddon;
use App\Models\CarrierService;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Package;
use App\Models\Ship;
use App\Services\DTOs\OperatorShipmentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OperatorShipmentService - Handles operator-initiated shipment creation
 * 
 * This service bypasses customer payment flow and creates shipments directly
 * as 'paid' status for warehouse operations.
 */
class OperatorShipmentService
{
    public function __construct(
        private ShippingRateService $shippingRateService,
        private ShipmentSubmissionService $shipmentSubmissionService
    ) {
    }

    /**
     * Create a shipment on behalf of a customer (operator bypass)
     */
    public function createShipment(OperatorShipmentRequest $request): Ship
    {
        return DB::transaction(function () use ($request) {
            // Validate packages
            $packages = $this->validatePackages($request->packageIds, $request->customerId);

            // Calculate totals from packages
            // Use billed_weight (max of physical or volumetric) - this is what carriers charge on
            $totalWeight = $packages->sum('billed_weight');
            $totalValue = $packages->sum('total_value');

            // Calculate addon charges if any
            $addonCharges = $this->calculateAddonCharges(
                $request->selectedAddonIds ?? [],
                $request->declaredValue ?? $totalValue
            );

            // Validate carrier service is provided
            if (!$request->carrierServiceId) {
                throw new \Exception('Carrier service is required. Please select a carrier service (e.g., DHL Express Worldwide).');
            }

            // Get carrier service for carrier_name and service_type
            $carrierService = CarrierService::find($request->carrierServiceId);
            
            if (!$carrierService) {
                throw new \Exception("Carrier service ID {$request->carrierServiceId} not found. Please select a valid carrier service.");
            }

            if (!$carrierService) {
                throw new \InvalidArgumentException('A carrier service must be selected before creating a shipment.');
            }

            // Create the shipment
            $ship = Ship::create([
                'customer_id' => $request->customerId,
                'tracking_number' => rand(10000000, 99999999),
                'total_weight' => $totalWeight,
                'total_price' => $totalValue,
                'customer_address_id' => $request->customerAddressId,
                'carrier_service_id' => $request->carrierServiceId,
                'carrier_name' => $carrierService->carrier_code,
                'carrier_service_type' => $carrierService->service_code,
                'selected_addon_ids' => !empty($request->selectedAddonIds) ? $request->selectedAddonIds : null,
                'addon_charges' => $addonCharges,
                'declared_value' => $request->declaredValue ?? $totalValue,
                'declared_value_currency' => 'USD',
                'eei_code' => $request->eeiCode,
                'eei_required' => $request->eeiRequired,
                'eei_exemption_reason' => $request->eeiExemptionReason,
                'estimated_shipping_charges' => $request->estimatedShippingCharges,
                'subtotal' => $request->estimatedShippingCharges,
                'rate_source' => 'manual', // Operator-created
                'invoice_status' => 'paid',
                'status' => ShipmentStatus::PAID,
                'carrier_status' => 'pending',
            ]);

            // Attach packages to shipment
            $ship->packages()->attach($packages->pluck('id'));

            // Update package statuses to Consolidated
            foreach ($packages as $package) {
                $package->status = PackageStatus::CONSOLIDATE;
                $package->save();
            }

            Log::info('Operator created shipment', [
                'ship_id' => $ship->id,
                'customer_id' => $request->customerId,
                'operator_id' => auth()->id(),
                'package_count' => $packages->count(),
            ]);

            // Submit to carrier synchronously (no queue for shared hosting)
            $this->shipmentSubmissionService->submit($ship);

            return $ship;
        });
    }

    /**
     * Validate that packages are eligible for shipment
     * - Must belong to specified customer
     * - Must be status 3 (Ready to Send) or 4 (Consolidated)
     * - Must not be attached to any paid shipment
     */
    public function validatePackages(array $packageIds, int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        $packages = Package::with('items')
            ->where('customer_id', $customerId)
            ->whereIn('id', $packageIds)
            ->whereIn('status', [PackageStatus::READY_TO_SEND, PackageStatus::CONSOLIDATE])
            ->get();

        if ($packages->count() !== count($packageIds)) {
            throw new \Exception('Some packages are not eligible for shipment. Ensure all packages are Ready to Send or Consolidated status.');
        }

        // Check if any package is already in a paid shipment
        foreach ($packages as $package) {
            $existingPaidShipment = Ship::whereHas('packages', function ($q) use ($package) {
                $q->where('packages.id', $package->id);
            })
                ->whereNotIn('status', [ShipmentStatus::PENDING, ShipmentStatus::CANCELLED])
                ->first();

            if ($existingPaidShipment) {
                throw new \Exception("Package {$package->package_id} is already in a paid shipment (#{$existingPaidShipment->tracking_number}).");
            }
        }

        return $packages;
    }

    /**
     * Get available packages for a customer
     */
    public function getAvailablePackages(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return Package::with(['items', 'customer', 'warehouse'])
            ->where('customer_id', $customerId)
            ->whereIn('status', [PackageStatus::READY_TO_SEND, PackageStatus::CONSOLIDATE])
            ->whereDoesntHave('ships', function ($q) {
                // Exclude packages in non-pending/non-cancelled shipments
                $q->whereNotIn('status', [ShipmentStatus::PENDING, ShipmentStatus::CANCELLED]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate total addon charges
     */
    private function calculateAddonCharges(array $addonIds, float $declaredValue): float
    {
        if (empty($addonIds)) {
            return 0.0;
        }

        $addons = CarrierAddon::whereIn('id', $addonIds)->get();
        $total = 0.0;

        foreach ($addons as $addon) {
            $total += $addon->calculatePrice($declaredValue);
        }

        return round($total, 2);
    }
}
