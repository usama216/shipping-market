<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PackageChangeRequestController extends Controller
{
    /**
     * Get change requests for the authenticated customer
     */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $requests = PackageChangeRequest::forCustomer($customer->id)
            ->with(['package:id,package_id,from,store_tracking_id'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Get pending change requests for a specific package
     */
    public function getForPackage($packageId)
    {
        $customer = Auth::guard('customer')->user();
        $package = Package::where('customer_id', $customer->id)
            ->findOrFail($packageId);

        $requests = PackageChangeRequest::where('package_id', $packageId)
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
            'has_pending' => $requests->where('status', 'pending')->isNotEmpty(),
        ]);
    }

    /**
     * Submit a new change request for a package
     */
    public function store(Request $request, Package $package)
    {
        $customer = Auth::guard('customer')->user();

        // Validate the package belongs to the customer
        if ($package->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to package.',
            ], 403);
        }

        // Check if there's already a pending request
        $existingRequest = PackageChangeRequest::where('package_id', $package->id)
            ->where('customer_id', $customer->id)
            ->pending()
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending change request for this package. Please wait for admin review.',
            ], 422);
        }

        $validated = $request->validate([
            'changes' => 'required|array',
            'changes.length' => 'nullable|numeric|min:0',
            'changes.width' => 'nullable|numeric|min:0',
            'changes.height' => 'nullable|numeric|min:0',
            'changes.weight' => 'nullable|numeric|min:0',
            'changes.dimension_unit' => 'nullable|in:in,cm',
            'changes.weight_unit' => 'nullable|in:lb,kg',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $changeRequest = PackageChangeRequest::create([
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'request_type' => PackageChangeRequest::TYPE_PACKAGE,
                'original_values' => [
                    'weight' => $package->total_weight,
                    'volumetric_weight' => $package->total_volumetric_weight,
                    'billed_weight' => $package->billed_weight,
                ],
                'requested_changes' => $validated['changes'],
                'customer_notes' => $validated['notes'] ?? null,
                'status' => PackageChangeRequest::STATUS_PENDING,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Change request submitted successfully. Admin will review your request.',
                'data' => $changeRequest,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit change request. Please try again.',
            ], 500);
        }
    }

    /**
     * Cancel a pending change request
     */
    public function cancel($requestId)
    {
        $customer = Auth::guard('customer')->user();
        $changeRequest = PackageChangeRequest::where('id', $requestId)
            ->where('customer_id', $customer->id)
            ->pending()
            ->firstOrFail();

        $changeRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Change request cancelled.',
        ]);
    }
}
