<?php

namespace App\Http\Controllers;

use App\Carriers\CarrierFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * MyUS Controller
 * 
 * Handles retrieval and display of packages/shipments from MyUS API
 */
class MyUSController extends Controller
{
    /**
     * List packages from MyUS
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function packages(Request $request)
    {
        try {
            $carrier = CarrierFactory::make('myus');
            
            // Get filters from request
            $filters = [
                'suite' => $request->input('suite'),
                'member_id' => $request->input('member_id'),
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            // Remove empty filters
            $filters = array_filter($filters, fn($value) => !empty($value));

            // Get packages from MyUS
            $packages = $carrier->getPackages(
                suite: $filters['suite'] ?? null,
                memberId: $filters['member_id'] ?? null,
                filters: $filters
            );

            return Inertia::render('Admin/MyUS/Packages', [
                'packages' => $packages,
                'filters' => $filters,
                'config' => [
                    'suite' => config('carriers.myus.suite'),
                    'member_id' => config('carriers.myus.member_id'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('MyUS packages retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Admin/MyUS/Packages', [
                'packages' => [],
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
        }
    }

    /**
     * List shipments from MyUS
     * 
     * @param Request $request
     * @return \Inertia\Response
     */
    public function shipments(Request $request)
    {
        try {
            $carrier = CarrierFactory::make('myus');
            
            // Get filters from request
            $filters = [
                'suite' => $request->input('suite'),
                'member_id' => $request->input('member_id'),
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            // Remove empty filters
            $filters = array_filter($filters, fn($value) => !empty($value));

            // Get shipments from MyUS
            $shipments = $carrier->getShipments(
                suite: $filters['suite'] ?? null,
                memberId: $filters['member_id'] ?? null,
                filters: $filters
            );

            return Inertia::render('Admin/MyUS/Shipments', [
                'shipments' => $shipments,
                'filters' => $filters,
                'config' => [
                    'suite' => config('carriers.myus.suite'),
                    'member_id' => config('carriers.myus.member_id'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('MyUS shipments retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Admin/MyUS/Shipments', [
                'shipments' => [],
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);
        }
    }

    /**
     * Get package details
     * 
     * @param Request $request
     * @param string $packageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function packageDetails(Request $request, string $packageId)
    {
        try {
            $carrier = CarrierFactory::make('myus');
            $package = $carrier->getPackageDetails($packageId);

            return response()->json([
                'success' => true,
                'package' => $package,
            ]);

        } catch (\Exception $e) {
            Log::error('MyUS package details retrieval failed', [
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API endpoint to get packages (for AJAX calls)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiPackages(Request $request)
    {
        try {
            $carrier = CarrierFactory::make('myus');
            
            $filters = [
                'suite' => $request->input('suite'),
                'member_id' => $request->input('member_id'),
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            $filters = array_filter($filters, fn($value) => !empty($value));

            $packages = $carrier->getPackages(
                suite: $filters['suite'] ?? null,
                memberId: $filters['member_id'] ?? null,
                filters: $filters
            );

            return response()->json([
                'success' => true,
                'packages' => $packages,
                'count' => count($packages),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API endpoint to get shipments (for AJAX calls)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiShipments(Request $request)
    {
        try {
            $carrier = CarrierFactory::make('myus');
            
            $filters = [
                'suite' => $request->input('suite'),
                'member_id' => $request->input('member_id'),
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            $filters = array_filter($filters, fn($value) => !empty($value));

            $shipments = $carrier->getShipments(
                suite: $filters['suite'] ?? null,
                memberId: $filters['member_id'] ?? null,
                filters: $filters
            );

            return response()->json([
                'success' => true,
                'shipments' => $shipments,
                'count' => count($shipments),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
