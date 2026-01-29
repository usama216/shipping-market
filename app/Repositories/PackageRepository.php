<?php

namespace App\Repositories;

use App\Helpers\PackageStatus;
use App\Interfaces\PackageInterface;
use App\Models\Package;
use App\Models\SpecialRequest;
use Illuminate\Support\Facades\Auth;

class PackageRepository implements PackageInterface
{

    protected $package, $specialRequest;
    public function __construct(Package $package, SpecialRequest $specialRequest)
    {
        $this->package = $package;
        $this->specialRequest = $specialRequest;
    }

    public function packages($filters = [])
    {
        $query = $this->package->query();

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->where('date_received', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date_received', '<=', $filters['date_to']);
        }

        // Apply customer filter
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Apply suite filter
        if (!empty($filters['suite'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('suite', $filters['suite']);
            });
        }

        // Apply store tracking ID filter
        if (!empty($filters['store_tracking_id'])) {
            $query->where('store_tracking_id', 'LIKE', '%' . $filters['store_tracking_id'] . '%');
        }

        // Apply total value range filter
        if (!empty($filters['total_value_min'])) {
            $query->where('total_value', '>=', $filters['total_value_min']);
        }
        if (!empty($filters['total_value_max'])) {
            $query->where('total_value', '<=', $filters['total_value_max']);
        }

        return $query
            ->with(['customer', 'items:id,package_id,is_dangerous,is_fragile,is_oversized,total_line_weight,length,width,height,dimension_unit', 'items.invoiceFiles', 'specialRequest'])
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }
    public function allPackages()
    {
        return $this->package
            ->with(['customer', 'items:id,package_id,is_dangerous,is_fragile,is_oversized', 'items.invoiceFiles', 'specialRequest'])
            ->withCount('items')
            ->get();
    }
    public function store($data)
    {
        $packageId = isset($data['id']) ? $data['id'] : null;
        $package = $this->package->updateOrCreate(['id' => $packageId], $data);

        return $package;
    }

    public function deletePackage($packageId)
    {
        return $this->package->where('id', $packageId)->delete();
    }

    /**
     * Get packages for shipment by customer ID
     */
    public function shipmentPackages($customerId, $status = null)
    {
        $query = $this->package->query();
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($status == null) {
            $query->whereIn('status', [PackageStatus::ACTION_REQUIRED, PackageStatus::IN_REVIEW, PackageStatus::READY_TO_SEND]);
        } else {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }
        $packages = $query->with('files', 'items.invoiceFiles', 'items.packageFiles', 'customer', 'specialRequest')->get();
        
        // Ensure selected_addon_ids is included in the response
        return $packages->map(function($package) {
            // Ensure selected_addon_ids is properly cast
            if ($package->selected_addon_ids && is_string($package->selected_addon_ids)) {
                try {
                    $package->selected_addon_ids = json_decode($package->selected_addon_ids, true);
                } catch (\Exception $e) {
                    $package->selected_addon_ids = [];
                }
            } elseif (!$package->selected_addon_ids) {
                $package->selected_addon_ids = [];
            }
            return $package;
        });
    }

    public function packageSpecialRequests()
    {
        // Return special_requests from database (e.g., "abandon entire package", "advanced photos", etc.)
        return $this->specialRequest->orderBy('title')->get()->map(function($request) {
            return [
                'id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => (float) $request->price,
                'type' => $request->type,
            ];
        })->values();
    }

    public function addPackageNote($data)
    {
        $this->package->updateOrCreate(['id' => $data['id']], [
            'note' => $data['note'],
        ]);
    }

    public function changeStatus($data)
    {
        return $this->package->where('id', $data['package_id'])->update(['status' => $data['status']]);
    }

    /**
     * Get package counts for customer
     */
    public function packageCounts($customerId)
    {
        return [
            'action_required' => $this->shipmentPackages($customerId, PackageStatus::ACTION_REQUIRED)->count(),
            'in_review' => $this->shipmentPackages($customerId, PackageStatus::IN_REVIEW)->count(),
            'ready_to_send' => $this->shipmentPackages($customerId, PackageStatus::READY_TO_SEND)->count(),
            'all' => $this->shipmentPackages($customerId)->count(),
        ];
    }

    public function getPackageByIds($ids)
    {
        return $this->package->whereIn('id', $ids)->get();
    }
    public function sumWeightPackageByIds($ids)
    {
        return $this->package->whereIn('id', $ids)->get()->sum('total_weight');
    }

    public function findById($id)
    {
        return $this->package->findOrFail($id);
    }
}
