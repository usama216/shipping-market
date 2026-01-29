<?php

namespace App\Repositories;

use App\Interfaces\ShipInterface;
use App\Models\Ship;
use App\Models\ShippingPricing;
use App\Helpers\ShipmentStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShipRepository implements ShipInterface
{
    protected $ship, $shipPricing;

    public function __construct(Ship $ship, ShippingPricing $shipPricing)
    {
        $this->ship = $ship;
        $this->shipPricing = $shipPricing;
    }

    public function getShipments(Request $request)
    {
        $shipments = $this->ship->query();

        $shipments->with([
            'customer',
            'customerAddress',
            'internationalShipping',
            'carrierService', // Load carrier service for display
            'packages' => function ($query) {
                $query->with([
                    'files',
                    'items.packageFiles',
                    'items.invoiceFiles',
                    'customer'
                ]);
            }
        ])
            ->withCount('packages') // Add packages_count attribute
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            // Category filter - now handles operator workflow categories
            ->when($request->category, function ($query) use ($request) {
                $category = $request->category;

                // Operator workflow categories (use packed_at/carrier_status logic)
                if ($category === 'ready_to_pack') {
                    $query->where('carrier_status', 'submitted')
                        ->whereNotNull('label_data')
                        ->whereNull('packed_at');
                } elseif ($category === 'awaiting_pickup') {
                    $query->whereNotNull('packed_at')
                        ->whereIn('status', ['label_ready', 'submitted']);
                } elseif ($category === 'ready_to_prepare') {
                    $query->where('status', 'paid')
                        ->where(fn($q) => $q->whereNull('carrier_status')->orWhere('carrier_status', 'pending'));
                } elseif ($category === 'needs_attention') {
                    $query->where(
                        fn($q) =>
                        $q->whereIn('status', ['failed', 'returned', 'on_hold', 'customs_hold', 'cancelled'])
                            ->orWhere('carrier_status', 'failed')
                    );
                } else {
                    // Standard status-group categories (in_transit, completed, etc.)
                    $groups = ShipmentStatus::grouped();
                    if (isset($groups[$category])) {
                        $statuses = $groups[$category]['statuses'];
                        $query->whereIn('status', $statuses);
                    }
                }
            })
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('tracking_number', 'like', "%{$request->search}%")
                        ->orWhere('carrier_tracking_number', 'like', "%{$request->search}%")
                        ->orWhereHas('customer', function ($q) use ($request) {
                            $q->where('first_name', 'like', "%{$request->search}%")
                                ->orWhere('last_name', 'like', "%{$request->search}%")
                                ->orWhere('email', 'like', "%{$request->search}%")
                                ->orWhere('suite', 'like', "%{$request->search}%");
                        });
                });
            });

        // Include all shipments including pending (unpaid) shipments
        // Pending shipments are draft orders that haven't been paid yet

        return $shipments->orderBy('created_at', 'desc')->paginate(25);

    }

    public function create(array $data)
    {
        return $this->ship->create($data);
    }

    public function update(Ship $ship, array $data)
    {
        return $ship->update($data);
    }

    public function findById($id)
    {
        return $this->ship->findOrFail($id);
    }

    public function delete(Ship $ship)
    {
        return $ship->delete();
    }

    public function getAllShips()
    {
        return $this->ship->all();
    }
    /**
     * Get paid shipments by customer ID
     */
    public function getShipsByCustomerId($customerId)
    {
        return $this->ship->where('customer_id', $customerId)->where('invoice_status', 'paid')->get();
    }

    /**
     * @deprecated Use getShipsByCustomerId instead
     */
    public function getShipsByUserId($userId)
    {
        return $this->getShipsByCustomerId($userId);
    }

    public function getShipsWithPackages()
    {
        return $this->ship->with('packages')->get();
    }

    public function getShipByTrackingNumber($trackingNumber)
    {
        return $this->ship->where('tracking_number', $trackingNumber)->first();
    }

    public function getShipsByStatus($status)
    {
        return $this->ship->where('status', $status)->get();
    }

    /**
     * Get shipments with packages by customer ID
     */
    public function getShipsByCustomerIdWithPackages($customerId)
    {
        return $this->ship->where('customer_id', $customerId)->with('packages')->get();
    }

    /**
     * @deprecated Use getShipsByCustomerIdWithPackages instead
     */
    public function getShipsByUserIdWithPackages($userId)
    {
        return $this->getShipsByCustomerIdWithPackages($userId);
    }

    public function attachPackageToShip($shipId, $packageId)
    {
        $ship = $this->findById($shipId);
        $ship->packages()->attach($packageId);
        return $ship;
    }

    /**
     * Get shipping price by weight using range-based matching.
     * Finds the pricing tier where weight falls within the range.
     */
    public function getShipPriceByWeightAndService($weight, $shippingMethodName)
    {
        return $this->shipPricing
            ->where('type', 'Weight')
            ->where('service', $shippingMethodName)
            ->where('range_value', '<=', $weight)
            ->where(function ($query) use ($weight) {
                $query->where('range_to', '>=', $weight)
                    ->orWhereNull('range_to');
            })
            ->orderBy('range_value', 'desc')
            ->first();
    }

    /**
     * @deprecated Use getShipPriceByWeightAndService instead
     */
    public function getShipPriceByWightAndService($weight, $shippingMethodName)
    {
        return $this->getShipPriceByWeightAndService($weight, $shippingMethodName);
    }
    public function getShipPriceByVolumeAndService($volume, $shippingMethodName)
    {
        return $this->shipPricing
            ->where('type', 'Volume')
            ->where('service', $shippingMethodName)
            ->where('range_value', '<=', $volume)
            ->where(function ($query) use ($volume) {
                $query->where('range_to', '>=', $volume)
                    ->orWhereNull('range_to');
            })
            ->orderBy('range_value', 'desc')
            ->first();
    }

    public function getShipDetails($shipId)
    {
        return $this->ship->where('id', $shipId)
            ->with([
                'customer', 
                'customerAddress', 
                'packages.items.invoiceFiles',
                'packages.items.packageFiles',
                'internationalShipping'
            ])
            ->first();
    }

    /**
     * Delete pending shipment for customer
     */
    public function deletePendingShipmentForCustomer($customerId)
    {
        return $this->ship->where('customer_id', $customerId)->where('invoice_status', 'pending')->delete();
    }

    /**
     * @deprecated Use deletePendingShipmentForCustomer instead
     */
    public function deletePendingShipment($userId)
    {
        return $this->deletePendingShipmentForCustomer($userId);
    }
}
