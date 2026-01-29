<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\PackageStatus;
use App\Http\Controllers\Controller;
use App\Models\InternationalShippingOptions;
use App\Repositories\PackageFileRepository;
use App\Repositories\PackageRepository;
use App\Models\ItemInvoiceFile;
use App\Repositories\ShippingPreferencesRepository;
use App\Repositories\ShipRepository;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class SuiteController extends Controller
{
    use CommonTrait;
    protected $packageRepository, $packageFileRepository, $shipPreferencesRepository, $shipRepository;
    public function __construct(PackageRepository $packageRepository, PackageFileRepository $packageFileRepository, ShippingPreferencesRepository $shippingPreferencesRepository, ShipRepository $shipRepository)
    {
        $this->packageRepository = $packageRepository;
        $this->packageFileRepository = $packageFileRepository;
        $this->shipPreferencesRepository = $shippingPreferencesRepository;
        $this->shipRepository = $shipRepository;
    }
    public function index()
    {
        return redirect()->route('customer.suiteActionRequired');
    }

    public function actionRequired()
    {
        $customer = Auth::guard('customer')->user();
        return Inertia::render('Customers/Suite/SuitTabs/ActionRequired', [
            'actions' => $this->packageRepository->shipmentPackages($customer->id, PackageStatus::ACTION_REQUIRED),
            'specialRequests' => $this->packageRepository->packageSpecialRequests(),
            'packageCounts' => $this->packageRepository->packageCounts($customer->id),
        ]);
    }

    public function inReview()
    {
        $customer = Auth::guard('customer')->user();
        return Inertia::render('Customers/Suite/SuitTabs/InReview', [
            'inReviews' => $this->packageRepository->shipmentPackages($customer->id, PackageStatus::IN_REVIEW),
            'specialRequests' => $this->packageRepository->packageSpecialRequests(),
            'packageCounts' => $this->packageRepository->packageCounts($customer->id),
        ]);
    }
    public function readyToSend()
    {
        $customer = Auth::guard('customer')->user();
        $cards = \App\Models\UserCard::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return Inertia::render('Customers/Suite/SuitTabs/ReadyToSend', [
            'readyToSends' => $this->packageRepository->shipmentPackages($customer->id, PackageStatus::READY_TO_SEND),
            'specialRequests' => $this->packageRepository->packageSpecialRequests(),
            'packageCounts' => $this->packageRepository->packageCounts($customer->id),
            'cards' => $cards,
        ]);
    }

    public function viewAll()
    {
        $customer = Auth::guard('customer')->user();
        return Inertia::render('Customers/Suite/SuitTabs/ViewAll', [
            'viewAllPackages' => $this->packageRepository->shipmentPackages($customer->id),
            'specialRequests' => $this->packageRepository->packageSpecialRequests(),
            'packageCounts' => $this->packageRepository->packageCounts($customer->id),
        ]);
    }

    public function addNote(Request $request)
    {
        try {
            DB::beginTransaction();
            $this->packageRepository->addPackageNote($request->all());
            DB::commit();

            return response()->json(['message' => 'Note added successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function uploadInvoices(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate - accept either single item_id (legacy) or array of item_ids (new)
            $request->validate([
                'package_id' => 'required|exists:packages,id',
                'item_id' => 'nullable|exists:package_items,id', // Legacy support
                'item_ids' => 'nullable|array', // New: multiple items
                'item_ids.*' => 'exists:package_items,id',
                'invoices' => 'required|array|min:1',
                'invoices.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,tif,tiff,webp,pdf|max:10240',
            ]);

            // Get item IDs - support both legacy single item_id and new item_ids array
            $itemIds = $request->has('item_ids') && is_array($request->item_ids) 
                ? $request->item_ids 
                : ($request->item_id ? [$request->item_id] : []);

            if (empty($itemIds)) {
                return response()->json(['message' => 'Please select at least one item for the invoice.'], 422);
            }

            // Status change is now handled separately via markAsComplete method

            if ($request->hasFile('invoices') && count($request->file('invoices')) > 0) {
                foreach ($request->file('invoices') as $file) {
                    $path = $this->addFile($file, 'invoices');
                    $fileType = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'pdf';

                    // Create invoice file for each selected item
                    foreach ($itemIds as $itemId) {
                        ItemInvoiceFile::create([
                            'package_item_id' => $itemId,
                            'name' => $file->getClientOriginalName(),
                            'file' => $path,
                            'file_type' => $fileType,
                        ]);
                    }
                }
            }

            // Check if all items in the package now have invoices
            $package = \App\Models\Package::with('items.invoiceFiles')->find($request->package_id);
            $allItemsHaveInvoices = $package && $package->items->every(function($item) {
                return $item->invoiceFiles->count() > 0;
            });

            $autoCompleted = false;
            // If all items have invoices and package is in ACTION_REQUIRED status, auto-complete it
            if ($allItemsHaveInvoices && $package->status === PackageStatus::ACTION_REQUIRED) {
                $this->packageRepository->changeStatus([
                    'package_id' => $package->id,
                    'status' => PackageStatus::IN_REVIEW,
                ]);
                $autoCompleted = true;
            }

            DB::commit();
            $itemCount = count($itemIds);
            
            $message = "Invoice uploaded successfully for {$itemCount} item(s).";
            if ($autoCompleted) {
                $message .= " Package has been automatically moved to In Review status.";
            }
            
            return response()->json([
                'message' => $message,
                'auto_completed' => $autoCompleted,
                'package_id' => $package->id,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark package as complete (move to IN_REVIEW status)
     * Only allowed if all items have invoices
     */
    public function markAsComplete(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'package_id' => 'required|exists:packages,id',
            ]);

            $package = $this->packageRepository->shipmentPackages(null, null)
                ->where('id', $request->package_id)
                ->first();

            if (!$package) {
                return response()->json(['message' => 'Package not found.'], 404);
            }

            // Verify customer owns this package
            $customer = Auth::guard('customer')->user();
            if ($package->customer_id !== $customer->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // Verify package is in ACTION_REQUIRED status
            if ($package->status !== PackageStatus::ACTION_REQUIRED) {
                return response()->json([
                    'message' => 'Package is not in Action Required status.'
                ], 422);
            }

            // Load items with invoice files
            $package->load('items.invoiceFiles');

            // Verify all items have invoices
            $itemsWithoutInvoices = $package->items->filter(function($item) {
                return $item->invoiceFiles->count() === 0;
            });

            if ($itemsWithoutInvoices->count() > 0) {
                DB::rollBack();
                $itemNames = $itemsWithoutInvoices->pluck('title')->join(', ');
                return response()->json([
                    'message' => "Cannot mark as complete. The following items are missing invoices: {$itemNames}"
                ], 422);
            }

            // Change status to IN_REVIEW
            $this->packageRepository->changeStatus([
                'package_id' => $package->id,
                'status' => PackageStatus::IN_REVIEW,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Package marked as complete and moved to In Review status.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPackagePhotos(Request $request)
    {
        try {
            $packageFiles = $this->packageFileRepository->getPackageFiles($request->package_id);
            return response()->json(['message' => 'Photos fetched successfully', 'data' => $packageFiles], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function setSpecialRequest(Request $request)
    {
        try {
            $package = \App\Models\Package::findOrFail($request->package_id);
            
            // Verify customer owns this package
            $customer = Auth::guard('customer')->user();
            if ($package->customer_id !== $customer->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // Get special requests to calculate cost
            $specialRequestIds = $request->special_request_ids ?? [];
            $specialRequests = \App\Models\SpecialRequest::whereIn('id', $specialRequestIds)->get();
            $totalCost = $specialRequests->sum('price');
            
            // Validate based on whether there's a cost
            if ($totalCost > 0) {
                $request->validate([
                    'package_id' => 'required|exists:packages,id',
                    'special_request_ids' => 'required|array|min:1',
                    'special_request_ids.*' => 'exists:special_requests,id',
                    'card_id' => 'required|exists:user_cards,id', // Payment required when cost > 0
                ]);
                
                // Verify customer owns the payment card
                $card = \App\Models\UserCard::where('id', $request->card_id)
                    ->where('customer_id', $customer->id)
                    ->first();
                
                if (!$card) {
                    return response()->json(['message' => 'Invalid payment method.'], 403);
                }

                // Process special request with payment (will charge customer and move to in_review)
                $specialRequestService = app(\App\Services\SpecialRequestService::class);
                $result = $specialRequestService->processSpecialRequest(
                    $package, 
                    $specialRequestIds,
                    $customer,
                    $request->card_id
                );
                
                return response()->json([
                    'message' => $result['message'],
                    'total_cost' => $result['total_cost'],
                    'requires_return_label' => $result['requires_return_label'],
                    'special_requests' => $result['special_requests'],
                ], 200);
            } else {
                // No cost, just save the selection without payment
                $request->validate([
                    'package_id' => 'required|exists:packages,id',
                    'special_request_ids' => 'nullable|array',
                    'special_request_ids.*' => 'exists:special_requests,id',
                ]);
                
                $package->selected_addon_ids = $specialRequestIds;
                $package->save();
                
                return response()->json([
                    'message' => 'Optional services updated successfully.',
                    'total_cost' => 0,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @deprecated Legacy API - Uses outdated static pricing tables.
     * Real pricing is now calculated via ShippingRateService with live carrier APIs during checkout.
     * Kept for backward compatibility. Frontend call has been removed.
     */
    public function calculateEstimatedShipment(Request $request)
    {
        try {
            $estimatedAmount = 0;
            if (count($request->package_id) > 0) {
                $customer = Auth::guard('customer')->user();
                $preference = $this->shipPreferencesRepository->getShippingPreference($customer->id);
                $shippingPreferenceOption = isset($preference->shipping_preference_option) ? json_decode($preference->shipping_preference_option) : [];
                $shippingPackingOption = isset($preference->packing_option) ? json_decode($preference->packing_option) : [];
                $weight = (float) $this->packageRepository->sumWeightPackageByIds($request->package_id);

                // Use switch/case for cleaner carrier handling with null-safe price access
                $shippingOption = $preference->international_shipping_option ?? null;

                if ($shippingOption == InternationalShippingOptions::DHL_EXPRESS) {
                    $shipPricing = $this->shipRepository->getShipPriceByWeightAndService($weight, InternationalShippingOptions::DHL_NAME);
                    $estimatedAmount += $shipPricing ? (float) $shipPricing->price : 0;
                } elseif ($shippingOption == InternationalShippingOptions::FEDEX_ECONOMY) {
                    $shipPricing = $this->shipRepository->getShipPriceByWeightAndService($weight, InternationalShippingOptions::FEDEX_NAME);
                    $estimatedAmount += $shipPricing ? (float) $shipPricing->price : 0;
                } elseif ($shippingOption == InternationalShippingOptions::SEA_FREIGHT) {
                    $shipPricing = $this->shipRepository->getShipPriceByVolumeAndService($weight, InternationalShippingOptions::SEA_FREIGHT_NAME);
                    $estimatedAmount += $shipPricing ? (float) $shipPricing->price : 0;
                } elseif ($shippingOption == InternationalShippingOptions::AIR_CARGO) {
                    $shipPricing = $this->shipRepository->getShipPriceByVolumeAndService($weight, InternationalShippingOptions::AIR_CARGO_NAME);
                    $estimatedAmount += $shipPricing ? (float) $shipPricing->price : 0;
                }

                if (is_array($shippingPreferenceOption) && count($shippingPreferenceOption) > 0) {
                    $estimatedAmount += $this->shipPreferencesRepository->sumShippingPreferenceOption($shippingPreferenceOption);
                }
                if (is_array($shippingPackingOption) && count($shippingPackingOption) > 0) {
                    $estimatedAmount += $this->shipPreferencesRepository->sumPackingOption($shippingPackingOption);
                }
            }
            return response()->json(['message' => 'Estimated amount.', 'amount' => $estimatedAmount], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
