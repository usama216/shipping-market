<?php

namespace App\Http\Controllers;

use App\Helpers\PackageStatus;
use App\Http\Requests\PackageRequest;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackageFile;
use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Notifications\UpdateStatusWithNoteNotification;
use App\Repositories\PackageFileRepository;
use App\Repositories\PackageItemRepository;
use App\Models\ItemInvoiceFile;
use App\Repositories\PackageRepository;
use App\Repositories\CustomerRepository;
use App\Services\NotificationService;
use App\Traits\CommonTrait;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Redirect;
use Response;

class PackageController extends Controller
{
    use CommonTrait;
    protected $packageRepository, $packageItemRepository, $packageFileRepository, $customerRepository, $notificationService;

    public function __construct(
        PackageRepository $packageRepository,
        PackageItemRepository $packageItemRepository,
        PackageFileRepository $packageFileRepository,
        CustomerRepository $customerRepository,
        NotificationService $notificationService
    ) {
        $this->packageRepository = $packageRepository;
        $this->packageItemRepository = $packageItemRepository;
        $this->packageFileRepository = $packageFileRepository;
        $this->customerRepository = $customerRepository;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        // View Mode: 'list' or 'board' (default to list)
        // If board, we might need all packages or pagination differently, but for now let's stick to standard pagination
        // and let the user filter if they have too many items. Or we can load separate data for board.
        // For MVP consolidation, we will use the same data source.

        // Validate filter parameters
        $validated = $request->validate([
            'status' => 'nullable|integer', // Removed restrictive 'in' for flexibility or keeping it if needed
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'customer_id' => 'nullable|exists:customers,id',
            'suite' => 'nullable|string|max:255',
            'store_tracking_id' => 'nullable|string|max:255',
        ]);

        // Get customers for filter dropdowns
        $customers = $this->customerRepository->customers();

        // Get unique suites
        $suites = Customer::where('is_active', 1)
            ->whereNotNull('suite')
            ->where('suite', '!=', '')
            ->distinct()
            ->orderBy('suite')
            ->pluck('suite')
            ->toArray();

        // Get packages with filters
        // Note: For Kanban, usually we want ALL items to drag/drop, but if there are thousands, that's bad.
        // We'll stick to pagination + search for now, and maybe a "Load All" toggle for Board later if requested.
        $packages = $this->packageRepository->packages($validated);

        // Get warehouses for create form
        $warehouses = Warehouse::active()->select('id', 'name', 'code', 'city', 'is_default')->orderBy('is_default', 'desc')->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true);

        // Stats for dashboard cards
        $stats = [
            'action_required' => Package::where('status', 1)->count(),
            'in_review' => Package::where('status', 2)->count(),
            'ready_to_send' => Package::where('status', 3)->count(),
            'total' => Package::count(),
        ];

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
            'customers' => $customers,
            'suites' => $suites,
            'filters' => $validated,
            'warehouses' => $warehouses,
            'defaultWarehouseId' => $defaultWarehouse?->id,
            'stats' => $stats,
        ]);
    }

    public function kanban()
    {
        $packages = $this->packageRepository->allPackages();
        return Inertia::render('Package/Kanban', ['packages' => $packages]);
    }

    public function create()
    {
        $customers = Customer::where('is_active', 1)->get();
        $warehouses = Warehouse::active()->select('id', 'name', 'code', 'city', 'is_default')->orderBy('is_default', 'desc')->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true);

        // Pre-generate package ID so it's visible during creation
        $pendingPackageId = $this->generateRandomNumberFormat();

        return Inertia::render('Package/Form', [
            'users' => $customers,
            'warehouses' => $warehouses,
            'defaultWarehouseId' => $defaultWarehouse?->id,
            'pendingPackageId' => $pendingPackageId,
        ]);
    }

    public function store(PackageRequest $request)
    {
        try {
            DB::beginTransaction();

            // Determine status based on draft flag, item invoices, and skip_action_required flag
            $isDraft = $request->boolean('is_draft');
            $hasInvoices = $this->checkForItemInvoiceFiles($request);
            $skipActionRequired = $this->checkSkipActionRequired($request);

            if ($isDraft) {
                $status = PackageStatus::DRAFT;
            } else {
                // If operator checks "skip action required" OR uploads invoices -> READY_TO_SEND, else ACTION_REQUIRED
                $status = ($hasInvoices || $skipActionRequired) ? PackageStatus::READY_TO_SEND : PackageStatus::ACTION_REQUIRED;
            }

            // Use pre-generated package ID from frontend if provided, otherwise generate new one
            $packageId = $request->input('pending_package_id') ?: $this->generateRandomNumberFormat();

            $request->merge([
                'package_id' => $packageId,
                'status' => $status,
            ]);
            $package = $this->packageRepository->store($request->all());
            if ($package) {
                if ($request->items) {
                    $items = $request->items;

                    foreach ($items as $index => $item) {
                        // Save item
                        $packageItem = $this->packageItemRepository->insertOne($item, $package);

                        // Handle nested item photos (items.0.files, items.1.files, etc.)
                        $nestedKey = "items.{$index}.files";
                        if ($request->hasFile($nestedKey)) {
                            $files = $request->file($nestedKey);
                            $files = is_array($files) ? $files : [$files];
                            foreach ($files as $file) {
                                $path = $this->addFile($file, 'storage/app/public/package_items/');
                                $this->packageFileRepository->insertOne([
                                    'package_id' => $package->id,
                                    'package_item_id' => $packageItem->id,
                                    'name' => $file->getClientOriginalName(),
                                    'file' => $path,
                                ]);
                            }
                        }

                        // Handle item-level invoice files (items.0.invoice_files, items.1.invoice_files, etc.)
                        $invoiceKey = "items.{$index}.invoice_files";
                        if ($request->hasFile($invoiceKey)) {
                            $invoiceFiles = $request->file($invoiceKey);
                            $invoiceFiles = is_array($invoiceFiles) ? $invoiceFiles : [$invoiceFiles];
                            foreach ($invoiceFiles as $invoiceFile) {
                                $path = $this->addFile($invoiceFile, 'storage/app/public/invoices/');
                                $fileType = str_starts_with($invoiceFile->getMimeType(), 'image/') ? 'image' : 'pdf';
                                ItemInvoiceFile::create([
                                    'package_item_id' => $packageItem->id,
                                    'name' => $invoiceFile->getClientOriginalName(),
                                    'file' => $path,
                                    'file_type' => $fileType,
                                ]);
                            }
                        }
                    }
                }

                if ($request->hasFile('files')) {
                    $files = $request->file('files');
                    $this->packageFileRepository->insert($files, $package);
                }
            }
            DB::commit();

            // Send notification to customer for non-draft packages
            // Wrapped in try-catch to prevent package creation failure if email fails
            if (!$isDraft && $package->customer) {
                try {
                    $this->notificationService->notifyPackageReceived($package->customer, $package);
                } catch (\Exception $e) {
                    // Log email error but don't fail package creation
                    \Log::warning('Failed to send package received notification', [
                        'package_id' => $package->id,
                        'customer_id' => $package->customer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $message = $isDraft ? 'Package saved as draft.' : 'Package added successfully.';
            return Redirect::route('admin.packages')->with('alert', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Check if any item has invoice files in the request
     */
    private function checkForItemInvoiceFiles(Request $request): bool
    {
        if (!$request->items || !is_array($request->items)) {
            return false;
        }
        foreach ($request->items as $index => $item) {
            if ($request->hasFile("items.{$index}.invoice_files")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if any item has skip_action_required flag set
     */
    private function checkSkipActionRequired(Request $request): bool
    {
        if (!$request->items || !is_array($request->items)) {
            return false;
        }
        foreach ($request->items as $item) {
            if (!empty($item['skip_action_required']) && filter_var($item['skip_action_required'], FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }
        return false;
    }

    public function edit(Package $package)
    {
        $package->load('files', 'items.invoiceFiles', 'specialRequest', 'specialRequestResponses.photos', 'specialRequestResponses.specialRequest', 'specialRequestResponses.admin');
        $package->items->load('packageFiles');
        
        // Load selected special requests
        $selectedSpecialRequests = $package->selectedSpecialRequests();

        $customers = Customer::where('is_active', 1)->get();
        $warehouses = Warehouse::active()->select('id', 'name', 'code', 'city', 'is_default')->orderBy('is_default', 'desc')->get();
        
        // Get all available special requests for reference
        $allSpecialRequests = \App\Models\SpecialRequest::orderBy('title')->get();

        return Inertia::render('Package/Form', [
            'package' => $package,
            'users' => $customers,
            'warehouses' => $warehouses,
            'selectedSpecialRequests' => $selectedSpecialRequests,
            'allSpecialRequests' => $allSpecialRequests,
        ]);
    }

    public function update(PackageRequest $request, Package $package)
    {
        // Debug: Log incoming files
        \Log::info('📦 Package Update - Files received:', [
            'all_files' => array_keys($request->allFiles()),
            'items_count' => count($request->input('items', [])),
        ]);
        foreach ($request->allFiles() as $key => $file) {
            \Log::info("   File key: {$key}", ['is_array' => is_array($file)]);
        }

        DB::beginTransaction();

        try {
            // Build update data
            $updateData = [
                'from' => $request->from,
                'date_received' => $request->date_received,
                'customer_id' => $request->customer_id,
                'warehouse_id' => $request->warehouse_id,
                'store_tracking_id' => $request->store_tracking_id,
                'total_value' => $request->total_value,
                'note' => $request->note,
                // Export compliance fields
                'incoterm' => $request->incoterm,
                'invoice_signature_name' => $request->invoice_signature_name,
                'exporter_id_license' => $request->exporter_id_license,
                'us_filing_type' => $request->us_filing_type,
                'exporter_code' => $request->exporter_code,
                'itn_number' => $request->itn_number,
            ];

            // Store original status BEFORE update for validation
            $originalStatus = $package->status;
            
            // Store status for validation after items are updated
            $requestedStatus = null;
            if ($request->has('status') && $request->status !== null) {
                $requestedStatus = (int) $request->status;
                $updateData['status'] = $requestedStatus;
            }

            $package->update($updateData);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('package_files', 'public');
                    $package->files()->create([
                        'name' => $file->getClientOriginalName(),
                        'file' => $path,
                    ]);
                }
            }

            $items = $request->input('items', []);
            $updatedItemIds = [];

            foreach ($items as $index => $itemData) {
                // Existing item
                if (!empty($itemData['id'])) {
                    $item = PackageItem::find($itemData['id']);
                    if (!$item)
                        continue;

                    $item->update([
                        'title' => $itemData['title'],
                        'description' => $itemData['description'],
                        'hs_code' => $itemData['hs_code'] ?? null,
                        'eei_code' => $itemData['eei_code'] ?? null,
                        'country_of_origin' => $itemData['country_of_origin'] ?? null,
                        'material' => $itemData['material'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'value_per_unit' => $itemData['value_per_unit'],
                        'weight_per_unit' => $itemData['weight_per_unit'] ?? null,
                        'weight_unit' => $itemData['weight_unit'] ?? 'lb',
                        'total_line_value' => $itemData['total_line_value'],
                        'total_line_weight' => $itemData['total_line_weight'],
                        // Item dimensions
                        'length' => $itemData['length'] ?? null,
                        'width' => $itemData['width'] ?? null,
                        'height' => $itemData['height'] ?? null,
                        'dimension_unit' => $itemData['dimension_unit'] ?? 'in',
                        // Item classification
                        'is_dangerous' => $itemData['is_dangerous'] ?? false,
                        'un_code' => $itemData['un_code'] ?? null,

                        'dangerous_goods_class' => $itemData['dangerous_goods_class'] ?? null,

                        'is_fragile' => $itemData['is_fragile'] ?? false,
                        'is_oversized' => $itemData['is_oversized'] ?? false,
                        'classification_notes' => $itemData['classification_notes'] ?? null,
                    ]);

                    $updatedItemIds[] = $item->id;

                    // Handle deleted item photo files
                    if (!empty($itemData['delete_file_ids'])) {
                        foreach ($itemData['delete_file_ids'] as $fileId) {
                            $file = PackageFile::find($fileId);
                            if ($file && Storage::exists($file->file)) {
                                Storage::delete($file->file);
                                $file->delete();
                            }
                        }
                    }

                    // Handle deleted item invoice files
                    if (!empty($itemData['delete_invoice_file_ids'])) {
                        foreach ($itemData['delete_invoice_file_ids'] as $fileId) {
                            $invFile = ItemInvoiceFile::find($fileId);
                            if ($invFile) {
                                if (Storage::disk('public')->exists($invFile->file)) {
                                    Storage::disk('public')->delete($invFile->file);
                                }
                                $invFile->delete();
                            }
                        }
                    }

                    // Handle nested item photo files (items.0.files, items.1.files, etc.)
                    $nestedKey = "items.{$index}.files";
                    if ($request->hasFile($nestedKey)) {
                        $files = $request->file($nestedKey);
                        $files = is_array($files) ? $files : [$files];
                        foreach ($files as $file) {
                            $path = $this->addFile($file, 'storage/app/public/package_items/');
                            $item->packageFiles()->create([
                                'package_id' => $package->id,
                                'name' => $file->getClientOriginalName(),
                                'file' => $path,
                            ]);
                        }
                    }

                    // Handle item-level invoice files (items.0.invoice_files, items.1.invoice_files, etc.)
                    $invoiceKey = "items.{$index}.invoice_files";
                    if ($request->hasFile($invoiceKey)) {
                        $invoiceFiles = $request->file($invoiceKey);
                        $invoiceFiles = is_array($invoiceFiles) ? $invoiceFiles : [$invoiceFiles];
                        foreach ($invoiceFiles as $invoiceFile) {
                            $path = $this->addFile($invoiceFile, 'storage/app/public/invoices/');
                            $fileType = str_starts_with($invoiceFile->getMimeType(), 'image/') ? 'image' : 'pdf';
                            ItemInvoiceFile::create([
                                'package_item_id' => $item->id,
                                'name' => $invoiceFile->getClientOriginalName(),
                                'file' => $path,
                                'file_type' => $fileType,
                            ]);
                        }
                    }
                } else {
                    $newItem = $package->items()->create([
                        'title' => $itemData['title'],
                        'description' => $itemData['description'],
                        'hs_code' => $itemData['hs_code'] ?? null,
                        'eei_code' => $itemData['eei_code'] ?? null,
                        'country_of_origin' => $itemData['country_of_origin'] ?? null,
                        'material' => $itemData['material'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'value_per_unit' => $itemData['value_per_unit'],
                        'weight_per_unit' => $itemData['weight_per_unit'] ?? null,
                        'weight_unit' => $itemData['weight_unit'] ?? 'lb',
                        'total_line_value' => $itemData['total_line_value'],
                        'total_line_weight' => $itemData['total_line_weight'],
                        // Item dimensions
                        'length' => $itemData['length'] ?? null,
                        'width' => $itemData['width'] ?? null,
                        'height' => $itemData['height'] ?? null,
                        'dimension_unit' => $itemData['dimension_unit'] ?? 'in',
                        // Item classification
                        'is_dangerous' => $itemData['is_dangerous'] ?? false,
                        'un_code' => $itemData['un_code'] ?? null,

                        'is_fragile' => $itemData['is_fragile'] ?? false,
                        'is_oversized' => $itemData['is_oversized'] ?? false,
                        'classification_notes' => $itemData['classification_notes'] ?? null,
                    ]);

                    $updatedItemIds[] = $newItem->id;

                    // Handle nested item photo files (items.0.files, items.1.files, etc.)
                    $nestedKey = "items.{$index}.files";
                    if ($request->hasFile($nestedKey)) {
                        $files = $request->file($nestedKey);
                        $files = is_array($files) ? $files : [$files];
                        foreach ($files as $file) {
                            $path = $this->addFile($file, 'storage/app/public/package_items/');
                            $newItem->packageFiles()->create([
                                'package_id' => $package->id,
                                'name' => $file->getClientOriginalName(),
                                'file' => $path,
                            ]);
                        }
                    }

                    // Handle item-level invoice files (items.0.invoice_files, items.1.invoice_files, etc.)
                    $invoiceKey = "items.{$index}.invoice_files";
                    if ($request->hasFile($invoiceKey)) {
                        $invoiceFiles = $request->file($invoiceKey);
                        $invoiceFiles = is_array($invoiceFiles) ? $invoiceFiles : [$invoiceFiles];
                        foreach ($invoiceFiles as $invoiceFile) {
                            $path = $this->addFile($invoiceFile, 'storage/app/public/invoices/');
                            $fileType = str_starts_with($invoiceFile->getMimeType(), 'image/') ? 'image' : 'pdf';
                            ItemInvoiceFile::create([
                                'package_item_id' => $newItem->id,
                                'name' => $invoiceFile->getClientOriginalName(),
                                'file' => $path,
                                'file_type' => $fileType,
                            ]);
                        }
                    }
                }
            }

            // Delete items that were removed from the form
            if (!empty($updatedItemIds)) {
                $package->items()->whereNotIn('id', $updatedItemIds)->delete();
            } elseif (empty($items)) {
                // If no items in request, delete all items
                $package->items()->delete();
            }

            // Refresh package to get latest items after updates/deletions
            $package->refresh();
            $package->load('items.invoiceFiles');

            // Validate status AFTER items are updated
            // If status is IN_REVIEW (2), cannot move to READY_TO_SEND (3) or CONSOLIDATE (4) without invoices on ALL items
            if ($requestedStatus !== null && $originalStatus === PackageStatus::IN_REVIEW) {
                if ($requestedStatus === PackageStatus::READY_TO_SEND || $requestedStatus === PackageStatus::CONSOLIDATE) {
                    // Check that ALL items have at least one invoice
                    $itemsWithoutInvoices = $package->items->filter(function($item) {
                        return $item->invoiceFiles->count() === 0;
                    });

                    if ($itemsWithoutInvoices->count() > 0) {
                        DB::rollBack();
                        $itemNames = $itemsWithoutInvoices->pluck('title')->join(', ');
                        $statusName = $requestedStatus === PackageStatus::READY_TO_SEND ? 'Ready to Send' : 'Consolidate';
                        return redirect()->back()->withErrors([
                            'status' => "Cannot change to {$statusName}. The following items are missing invoices: {$itemNames}"
                        ]);
                    }
                }
            }
            
            // Also validate when changing TO READY_TO_SEND from any other status (except IN_REVIEW which is handled above)
            if ($requestedStatus !== null && 
                $requestedStatus === PackageStatus::READY_TO_SEND && 
                $originalStatus !== PackageStatus::READY_TO_SEND &&
                $originalStatus !== PackageStatus::IN_REVIEW) {
                // Status is changing TO READY_TO_SEND from other statuses - require at least one invoice
                $totalInvoices = $package->items->sum(fn($item) => $item->invoiceFiles->count());

                if ($totalInvoices === 0) {
                    DB::rollBack();
                    return redirect()->back()->withErrors([
                        'status' => "Cannot change to Ready to Send. At least one invoice is required for the package."
                    ]);
                }
            }

            // Note: Auto-status update removed. Admin has full control when explicitly setting status.
            // If you need auto-promotion to READY_TO_SEND when invoices are added, 
            // only do so when status wasn't explicitly provided in the request.
            // Changed: Only require at least ONE invoice for the entire package (not per item)
            if ($requestedStatus === null) {
                $totalInvoices = $package->items->sum(fn($item) => $item->invoiceFiles->count());
                if ($totalInvoices > 0 && $package->items->count() > 0 && $package->status < PackageStatus::READY_TO_SEND && $package->status != PackageStatus::DRAFT) {
                    $package->update(['status' => PackageStatus::READY_TO_SEND]);
                }
            }

            DB::commit();
            // Return back to same page (edit page) with success message - Inertia will handle this without full page reload
            return redirect()->back()->with('alert', 'Package updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }



    public function destroy(Package $package)
    {
        try {
            DB::beginTransaction();
            $isDelete = $this->packageRepository->deletePackage($package->id);
            if ($isDelete) {
                $this->packageItemRepository->itemsDeleteByPackageId($package->id);
                $this->packageFileRepository->deletePackageFilesByPackageId($package->id);
            }
            DB::commit();
            return Redirect::route('admin.packages')->with('alert', 'Package deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function getUserPackages(User $user)
    {
        $userPackages = $this->packageRepository->shipmentPackages($user->id, [PackageStatus::ACTION_REQUIRED, PackageStatus::IN_REVIEW, PackageStatus::READY_TO_SEND, PackageStatus::CONSOLIDATE]);
        return Inertia::render('Admin/Users/EditTabs/Packages', ['user' => $user, 'userPackages' => $userPackages]);
    }

    /**
     * Update package status via AJAX for Kanban board
     */
    public function updateStatus(Request $request, Package $package)
    {
        try {
            DB::beginTransaction();

            // Validate the request
            $request->validate([
                'status' => 'required|integer|in:1,2,3,4',
            ]);

            // Capture old status BEFORE update for notification comparison
            $oldStatusId = $package->status;
            $oldStatus = $this->getStatusName($oldStatusId);
            $newStatusId = (int) $request->status;

            // Validate invoices when changing status
            $package->load('items.invoiceFiles');
            
            // If status is IN_REVIEW (2), cannot move to READY_TO_SEND (3) or CONSOLIDATE (4) without invoices on ALL items
            if ($oldStatusId === PackageStatus::IN_REVIEW) {
                if ($newStatusId === PackageStatus::READY_TO_SEND || $newStatusId === PackageStatus::CONSOLIDATE) {
                    // Check that ALL items have at least one invoice
                    $itemsWithoutInvoices = $package->items->filter(function($item) {
                        return $item->invoiceFiles->count() === 0;
                    });

                    if ($itemsWithoutInvoices->count() > 0) {
                        DB::rollBack();
                        $itemNames = $itemsWithoutInvoices->pluck('title')->join(', ');
                        $statusName = $newStatusId === PackageStatus::READY_TO_SEND ? 'Ready to Send' : 'Consolidate';
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot change to {$statusName}. The following items are missing invoices: {$itemNames}",
                        ], 422);
                    }
                }
            }
            
            // Also validate when changing TO READY_TO_SEND from any other status (except IN_REVIEW which is handled above)
            if ($newStatusId === PackageStatus::READY_TO_SEND && $oldStatusId !== PackageStatus::IN_REVIEW) {
                $totalInvoices = $package->items->sum(fn($item) => $item->invoiceFiles->count());

                if ($totalInvoices === 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot change to Ready to Send. At least one invoice is required for the package.",
                    ], 422);
                }
            }

            // Update the package status
            $package->update(['status' => $newStatusId]);

            DB::commit();

            // Send notification to customer if status actually changed
            if ($package->customer && $newStatusId !== $oldStatusId) {
                $this->notificationService->notifyPackageStatusChanged(
                    $package->customer,
                    $package->fresh(),
                    $oldStatus
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Package status updated successfully',
                'package' => $package->fresh()->load('customer', 'items', 'files'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update package status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get human-readable status name for notifications.
     */
    private function getStatusName(int $status): string
    {
        return match ($status) {
            PackageStatus::DRAFT => 'Draft',
            PackageStatus::ACTION_REQUIRED => 'Action Required',
            PackageStatus::IN_REVIEW => 'In Review',
            PackageStatus::READY_TO_SEND => 'Ready to Send',
            PackageStatus::CONSOLIDATE => 'Consolidated',
            default => 'Unknown',
        };
    }

    public function updateNote(Request $request, Package $package)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'note' => 'required|string|max:1000',
            ]);
            $package->update(['note' => $request->note]);
            $user = $package->customer;
            $user->notify(new UpdateStatusWithNoteNotification($request->input('note'), $package));
            DB::commit();
            return Redirect::back()->with('alert', 'Package note updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Download merged master PDF of all invoice files for a package
     * Combines all invoice files uploaded for package items into a single PDF
     * Saves to storage and returns URL
     */
    public function downloadMergedInvoices(Package $package)
    {
        // Load items with invoice files
        $package->load('items.invoiceFiles');
        
        $invoiceFiles = [];
        foreach ($package->items as $item) {
            foreach ($item->invoiceFiles as $invoiceFile) {
                $invoiceFiles[] = $invoiceFile;
            }
        }

        if (empty($invoiceFiles)) {
            return response()->json([
                'success' => false,
                'message' => 'No invoice files found for this package.'
            ], 404);
        }

        try {
            // Use Imagick to merge PDFs if available
            if (extension_loaded('imagick')) {
                $mergedPdf = $this->mergePdfsWithImagick($invoiceFiles);
            } else {
                // Fallback: try to use setasign/fpdi if available
                $mergedPdf = $this->mergePdfsWithFpdi($invoiceFiles);
            }

            if (!$mergedPdf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to merge PDF files. Please ensure Imagick extension is installed.'
                ], 500);
            }

            $filename = ($package->package_id ?? $package->id) . '-invoices-merged.pdf';
            $storagePath = 'temp/packages/' . $filename;

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('temp/packages');

            // Save file to storage
            Storage::disk('public')->put($storagePath, $mergedPdf);

            // Get public URL
            $url = Storage::disk('public')->url($storagePath);

            Log::info('Package invoices merged PDF saved successfully', [
                'package_id' => $package->id,
                'filename' => $filename,
                'url' => $url,
                'size' => strlen($mergedPdf),
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to merge package invoices', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge invoice files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Merge PDFs using Imagick
     */
    private function mergePdfsWithImagick(array $invoiceFiles): ?string
    {
        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);

            foreach ($invoiceFiles as $invoiceFile) {
                $filePath = $this->getInvoiceFilePath($invoiceFile);
                
                if (!$filePath || !file_exists($filePath)) {
                    Log::warning('Invoice file not found', [
                        'invoice_file_id' => $invoiceFile->id,
                        'file_path' => $invoiceFile->file,
                    ]);
                    continue;
                }

                // Handle different file types
                if ($invoiceFile->file_type !== 'pdf') {
                    // Convert images to PDF first
                    try {
                        $imageImagick = new \Imagick($filePath);
                        $imageImagick->setImageFormat('pdf');
                        $pdfBlob = $imageImagick->getImageBlob();
                        $imageImagick->clear();
                        $imageImagick->destroy();
                        
                        // Read the converted PDF
                        $tempPdf = new \Imagick();
                        $tempPdf->readImageBlob($pdfBlob);
                        $imagick->addImage($tempPdf);
                        $tempPdf->clear();
                        $tempPdf->destroy();
                    } catch (\Exception $e) {
                        Log::warning('Failed to convert image to PDF', [
                            'invoice_file_id' => $invoiceFile->id,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                } else {
                    // Read PDF file - handle multi-page PDFs
                    try {
                        $pdfImagick = new \Imagick();
                        $pdfImagick->setResolution(150, 150);
                        $pdfImagick->readImage($filePath);
                        
                        // Add all pages from this PDF
                        foreach ($pdfImagick as $page) {
                            $imagick->addImage($page);
                        }
                        
                        $pdfImagick->clear();
                        $pdfImagick->destroy();
                    } catch (\Exception $e) {
                        Log::warning('Failed to read PDF file', [
                            'invoice_file_id' => $invoiceFile->id,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            }

            if ($imagick->getNumberImages() === 0) {
                $imagick->clear();
                $imagick->destroy();
                return null;
            }

            // Merge all pages into a single PDF
            $imagick->setImageFormat('pdf');
            $imagick->setImageCompressionQuality(95);
            $mergedPdf = $imagick->getImagesBlob();
            
            $imagick->clear();
            $imagick->destroy();

            return $mergedPdf;
        } catch (\Exception $e) {
            Log::error('Imagick PDF merge failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Merge PDFs using FPDI (setasign/fpdi)
     */
    private function mergePdfsWithFpdi(array $invoiceFiles): ?string
    {
        // Check if FPDI is available
        if (!class_exists(\setasign\Fpdi\Fpdi::class)) {
            return null;
        }

        try {
            $pdf = new \setasign\Fpdi\Fpdi();

            foreach ($invoiceFiles as $invoiceFile) {
                $filePath = $this->getInvoiceFilePath($invoiceFile);
                
                if (!$filePath || !file_exists($filePath)) {
                    continue;
                }

                // FPDI only works with PDFs
                if ($invoiceFile->file_type === 'pdf') {
                    try {
                        $pageCount = $pdf->setSourceFile($filePath);
                        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                            $tplId = $pdf->importPage($pageNo);
                            $pdf->addPage();
                            $pdf->useTemplate($tplId);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to merge PDF page', [
                            'invoice_file_id' => $invoiceFile->id,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                } else {
                    Log::warning('FPDI cannot merge non-PDF files', [
                        'invoice_file_id' => $invoiceFile->id,
                        'file_type' => $invoiceFile->file_type,
                    ]);
                }
            }

            return $pdf->Output('S'); // Return as string
        } catch (\Exception $e) {
            Log::error('FPDI PDF merge failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the full file path for an invoice file
     */
    private function getInvoiceFilePath($invoiceFile): ?string
    {
        $filePath = $invoiceFile->file;
        
        // Normalize path
        if (str_starts_with($filePath, 'storage/app/public/')) {
            $filePath = str_replace('storage/app/public/', '', $filePath);
        } elseif (str_starts_with($filePath, 'storage/')) {
            $filePath = str_replace('storage/', '', $filePath);
        }
        
        // Get full path
        $fullPath = Storage::disk('public')->path($filePath);
        
        return file_exists($fullPath) ? $fullPath : null;
    }
}
