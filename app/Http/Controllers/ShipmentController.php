<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateShipmentRequest;
use App\Helpers\CarrierStatus;
use App\Helpers\ShipmentStatus;
use App\Models\CarrierService;
use App\Models\Ship;
use App\Models\ShipmentEvent;
use App\Models\PackingOptions;
use App\Models\ShippingPreferenceOption;
use App\Repositories\ShipRepository;
use App\Services\NotificationService;
use App\Services\TrackingService;
use App\Services\OperatorShipmentService;
use App\Services\DTOs\OperatorShipmentRequest;
use App\Models\Customer;
use App\Models\CarrierAddon;
use App\Helpers\PackageStatus;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Redirect;

class ShipmentController extends Controller
{
    protected $shipRepository;
    protected TrackingService $trackingService;
    protected NotificationService $notificationService;
    protected OperatorShipmentService $operatorShipmentService;

    public function __construct(
        ShipRepository $shipRepository,
        TrackingService $trackingService,
        NotificationService $notificationService,
        OperatorShipmentService $operatorShipmentService
    ) {
        $this->shipRepository = $shipRepository;
        $this->trackingService = $trackingService;
        $this->notificationService = $notificationService;
        $this->operatorShipmentService = $operatorShipmentService;
    }
    public function index(Request $request)
    {
        $shipments = $this->shipRepository->getShipments($request);

        // Process shipments to add additional services information
        $shipments->getCollection()->transform(function ($shipment) {
            // Get packing options
            if ($shipment->packing_option_id) {
                $packingOptionIds = is_string($shipment->packing_option_id)
                    ? json_decode($shipment->packing_option_id, true)
                    : $shipment->packing_option_id;
                if (is_array($packingOptionIds)) {
                    $shipment->packing_options = PackingOptions::whereIn('id', $packingOptionIds)->get();
                }
            }

            // Get shipping preference options
            if ($shipment->shipping_preference_option_id) {
                $shippingPreferenceOptionIds = is_string($shipment->shipping_preference_option_id)
                    ? json_decode($shipment->shipping_preference_option_id, true)
                    : $shipment->shipping_preference_option_id;
                if (is_array($shippingPreferenceOptionIds)) {
                    $shipment->shipping_preference_options = ShippingPreferenceOption::whereIn('id', $shippingPreferenceOptionIds)->get();
                }
            }

            return $shipment;
        });

        // Get status counts for stats (including all shipments)
        $statusCounts = Ship::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Calculate category counts based on operator workflow logic
        // This accounts for packed_at and carrier_status, not just status field
        $categoryCounts = [
            // Ready to Prepare = paid status, carrier not yet submitted
            'ready_to_prepare' => Ship::where('status', 'paid')
                ->where(fn($q) => $q->whereNull('carrier_status')->orWhere('carrier_status', 'pending'))
                ->count(),
            // Ready to Pack = label received but not packed yet
            'ready_to_pack' => Ship::where('carrier_status', 'submitted')
                ->whereNotNull('label_data')
                ->whereNull('packed_at')
                ->count(),
            // Awaiting Pickup = packed but not picked up
            'awaiting_pickup' => Ship::whereNotNull('packed_at')
                ->whereIn('status', ['label_ready', 'submitted'])
                ->count(),
            // In Transit = moving statuses
            'in_transit' => Ship::whereIn('status', ['picked_up', 'shipped', 'in_transit', 'out_for_delivery', 'customs_pending', 'customs_cleared'])
                ->count(),
            // Completed = delivered
            'completed' => Ship::where('status', 'delivered')->count(),
            // Needs Attention = failed, returned, on_hold, customs_hold, cancelled
            'needs_attention' => Ship::where(function ($q) {
                    $q->whereIn('status', ['failed', 'returned', 'on_hold', 'customs_hold', 'cancelled'])
                      ->orWhere('carrier_status', 'failed');
                })
                ->count(),
        ];

        // Build stats with category info for frontend
        $stats = [
            'total' => array_sum($statusCounts),
            'categories' => $categoryCounts,
        ];

        // Get carrier services for filtering
        $carrierServices = CarrierService::active()->ordered()->get(['id', 'display_name', 'carrier_code']);

        return Inertia::render('Admin/Shipments/Report', [
            'shipments' => $shipments,
            'stats' => $stats,
            'statusOptions' => ShipmentStatus::forFrontend(),
            'statusGroups' => ShipmentStatus::grouped(),
            'carrierServices' => $carrierServices,
            'filters' => $request->only(['status', 'category', 'search', 'carrier_service_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Warehouse operator view of actionable shipments
     * Two tabs: Actionable Items (Ready to Pack + Failed) and Awaiting Pickup
     */
    public function requests(Request $request)
    {
        $tab = $request->get('tab', 'actionable');

        // Base query with related data
        $baseQuery = fn() => Ship::query()
            ->with([
                    'customer',
                    'customerAddress',
                    'packages.items',
                    'carrierService',
                ])
            ->orderBy('created_at', 'asc'); // Oldest first = priority

        // Tab 1: Actionable Items = Ready to Pack + Failed
        if ($tab === 'actionable') {
            $query = $baseQuery()
                ->where(function ($q) {
                    // Ready to Pack: Label received from carrier
                    $q->where('carrier_status', 'submitted')
                        ->whereNotNull('label_data')
                        ->whereNull('packed_at');
                })
                ->orWhere(function ($q) {
                    // Failed: Needs operator intervention (retry or manual entry)
                    $q->where('carrier_status', 'failed');
                });
        } else {
            // Tab 2: Awaiting Pickup = Packed but not picked up yet
            $query = $baseQuery()
                ->whereNotNull('packed_at')
                ->whereIn('status', [ShipmentStatus::LABEL_READY, ShipmentStatus::SUBMITTED]);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('carrier_tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('suite', 'like', "%{$search}%");
                    });
            });
        }

        $shipments = $query->paginate(20)->withQueryString();

        // Get counts for tab badges
        $counts = [
            'actionable' => Ship::where(function ($q) {
                $q->where('carrier_status', 'submitted')
                    ->whereNotNull('label_data')
                    ->whereNull('packed_at');
            })
                ->orWhere('carrier_status', 'failed')
                ->count(),
            'awaiting_pickup' => Ship::whereNotNull('packed_at')
                ->whereIn('status', [ShipmentStatus::LABEL_READY, ShipmentStatus::SUBMITTED])
                ->count(),
            'ready_to_pack' => Ship::where('carrier_status', 'submitted')
                ->whereNotNull('label_data')
                ->whereNull('packed_at')
                ->count(),
            'failed' => Ship::where('carrier_status', 'failed')->count(),
        ];

        return Inertia::render('Admin/Shipments/Requests', [
            'shipments' => $shipments,
            'counts' => $counts,
            'currentTab' => $tab,
            'filters' => $request->only(['search', 'tab']),
        ]);
    }

    /**
     * Mark a shipment as packed by operator
     */
    public function markPacked(Ship $ship)
    {
        if ($ship->carrier_status !== 'submitted' || empty($ship->label_data)) {
            return back()->withErrors(['message' => 'Shipment must have a label before marking as packed.']);
        }

        $ship->update(['packed_at' => now()]);

        Log::info('Operator marked shipment as packed', [
            'ship_id' => $ship->id,
            'operator_id' => auth()->id(),
        ]);

        return back()->with('alert', 'Shipment marked as packed. Awaiting carrier pickup.');
    }

    /**
     * Mark a shipment as picked up by carrier
     */
    public function markPickedUp(Ship $ship)
    {
        if (empty($ship->packed_at)) {
            return back()->withErrors(['message' => 'Shipment must be packed before marking as picked up.']);
        }

        $ship->update(['status' => ShipmentStatus::PICKED_UP]);

        // Record tracking event
        $this->trackingService->recordEvent(
            $ship,
            'picked_up',
            'Package picked up by carrier from warehouse',
            null,
            'operator'
        );

        Log::info('Operator marked shipment as picked up', [
            'ship_id' => $ship->id,
            'operator_id' => auth()->id(),
        ]);

        return back()->with('alert', 'Shipment marked as picked up. Now in transit.');
    }

    public function outbondRequests(Request $request)
    {
        $shipments = $this->shipRepository->getShipments($request);
        return Inertia::render('Admin/Shipments/OutbondShipRequest/Report', ['shipments' => $shipments]);
    }

    public function edit(Ship $ship)
    {
        $ship->load([
            'packages.items.packageFiles',
            'packages.items.invoiceFiles',
            'packages.files',
            'packages.customer',
            'customer',
            'customerAddress',
            'internationalShipping',
            'carrierService',
            'trackingEvents'
        ]);
        return Inertia::render('Admin/Shipments/ShipmentEdit', ['ship' => $ship]);
    }

    public function update(UpdateShipmentRequest $request, Ship $ship)
    {
        try {
            DB::beginTransaction();

            // Handle custom invoice file upload
            $data = $request->except('custom_invoice_file');

            if ($request->hasFile('custom_invoice_file')) {
                $path = $request->file('custom_invoice_file')
                    ->store('customs_invoices', 'public');
                $data['custom_invoice_path'] = $path;
            }

            $this->shipRepository->update($ship, $data);
            DB::commit();
            return Redirect::back()->with('alert', 'Shipment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function shipPackages(Ship $ship)
    {
        try {
            $ship->load([
                'packages.items.packageFiles',
                'packages.items.invoiceFiles',
                'packages.files',
                'packages.customer',
                'customer'
            ]);
            return Inertia::render('Admin/Shipments/EditTabs/Packages', ['ship' => $ship]);
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, Ship $ship)
    {
        try {
            $request->validate([
                'status' => 'required|' . ShipmentStatus::fullValidationRule()
            ]);

            // Capture old status before update for comparison
            $oldStatus = $ship->status;
            $newStatus = $request->status;

            DB::beginTransaction();
            $ship->update(['status' => $newStatus]);
            DB::commit();

            // Send notification to customer when shipment status changes to 'delivered'
            if ($ship->customer && $oldStatus !== $newStatus && $newStatus === ShipmentStatus::DELIVERED) {
                $this->notificationService->notifyShipmentDelivered($ship->customer, $ship);
            }

            return Redirect::back()->with('alert', 'Shipment status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    // ==========================================
    // TRACKING METHODS (Consolidated from OrderTrackingController)
    // ==========================================

    /**
     * Show tracking details for a shipment (integrated view)
     */
    public function showTracking(Ship $ship)
    {
        $ship->load([
            'user',
            'customer',
            'customerAddress',
            'internationalShipping',
            'carrierService',
            'packages.items.invoiceFiles',
            'trackingEvents'
        ]);

        // Load special requests from packages
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
        
        // Get carrier addons if any
        $carrierAddons = collect([]); // Initialize as collection
        if (!empty($ship->selected_addon_ids)) {
            $addonIds = is_array($ship->selected_addon_ids) 
                ? $ship->selected_addon_ids 
                : json_decode($ship->selected_addon_ids, true) ?? [];
            if (!empty($addonIds)) {
                $carrierAddons = \App\Models\CarrierAddon::whereIn('id', $addonIds)->get();
            }
        }

        // Calculate classification charges
        $classificationCharges = [
            'total' => 0.00,
            'breakdown' => [
                'dangerous' => 0.00,
                'fragile' => 0.00,
                'oversized' => 0.00,
            ],
            'item_counts' => [
                'dangerous' => 0,
                'fragile' => 0,
                'oversized' => 0,
            ],
        ];
        if (!empty($packageIds)) {
            $classificationCharges = app(\App\Services\CarrierAddonService::class)
                ->calculateClassificationCharges($packageIds);
        }

        $timeline = $this->trackingService->getTrackingTimeline($ship);
        $carrierTrackingUrl = $this->trackingService->getCarrierTrackingUrl($ship);

        return Inertia::render('Admin/Shipments/Tracking', [
            'ship' => $ship,
            'timeline' => $timeline,
            'carrierTrackingUrl' => $carrierTrackingUrl,
            'statuses' => ShipmentEvent::getStatuses(),
            'carrierServices' => CarrierService::active()->ordered()->get(),
            'specialRequests' => $specialRequests->map(fn($sr) => [
                'id' => $sr->id,
                'title' => $sr->title,
                'description' => $sr->description,
                'price' => (float) $sr->price,
            ]),
            'specialRequestCost' => $specialRequestCost,
            'carrierAddons' => $carrierAddons->map(fn($addon) => [
                'id' => $addon->id,
                'display_name' => $addon->display_name,
                'description' => $addon->description,
                'price' => $addon->calculatePrice($ship->estimated_shipping_charges ?? 0, $ship->declared_value ?? 0),
            ]),
            'classificationCharges' => $classificationCharges,
        ]);
    }

    /**
     * Refresh tracking status from carrier API
     */
    public function refreshTracking(Ship $ship)
    {
        $result = $this->trackingService->fetchLiveStatus($ship);

        if ($result) {
            return back()->with('alert', 'Tracking status updated from carrier.');
        }

        return back()->withErrors(['message' => 'Could not fetch status from carrier. Please try again later.']);
    }

    /**
     * Add a tracking event manually
     */
    public function addTrackingEvent(Request $request, Ship $ship)
    {
        $request->validate([
            'status' => 'required|string',
            'description' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        $this->trackingService->recordEvent(
            $ship,
            $request->status,
            $request->description,
            $request->location,
            'system'
        );

        return back()->with('alert', 'Tracking event added successfully.');
    }

    // ==========================================
    // CARRIER OPERATIONS (Consolidated from OrderTrackingController)
    // ==========================================

    /**
     * Retry carrier API submission for failed shipments
     */
    public function retryCarrierSubmission(Ship $ship)
    {
        if (!in_array($ship->carrier_status, ['failed', 'pending', null])) {
            return back()->withErrors(['message' => 'Shipment has already been submitted to carrier.']);
        }

        // IMPORTANT: Verify shipment is already paid before retry
        // This ensures payment was already processed and won't be charged again
        if ($ship->status !== ShipmentStatus::PAID && $ship->invoice_status !== 'paid') {
            return back()->withErrors(['message' => 'Cannot retry: Shipment must be paid first. Payment will not be processed during retry.']);
        }

        // Reset carrier status and dispatch job
        $ship->update([
            'carrier_status' => 'pending',
            'carrier_errors' => null,
        ]);

        // IMPORTANT: Only resubmit to carrier API - NO payment processing
        // Payment was already charged during initial checkout
        app(\App\Services\ShipmentSubmissionService::class)->submit($ship);

        Log::info('Admin retried carrier submission', [
            'ship_id' => $ship->id,
            'admin_id' => auth()->id(),
            'shipment_status' => $ship->status,
            'invoice_status' => $ship->invoice_status,
            'note' => 'Payment was NOT charged - shipment already paid',
        ]);

        return back()->with('alert', 'Shipment resubmitted to carrier. Payment was not charged again (shipment already paid).');
    }

    /**
     * Manually enter tracking details when carrier API is not available
     */
    public function setManualTracking(Request $request, Ship $ship)
    {
        $request->validate([
            'carrier_name' => 'required|string|in:fedex,dhl,ups,sea_freight,air_cargo,other',
            'carrier_tracking_number' => 'required|string|max:100',
            'carrier_service_type' => 'nullable|string|max:100',
            'carrier_service_id' => 'nullable|exists:carrier_services,id',
            'label_file' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:5120',
            'total_charge' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $updateData = [
            'carrier_name' => $request->carrier_name,
            'carrier_tracking_number' => $request->carrier_tracking_number,
            'carrier_service_type' => $request->carrier_service_type,
            'carrier_service_id' => $request->carrier_service_id,
            'carrier_status' => 'submitted',
            'submitted_to_carrier_at' => now(),
            'carrier_errors' => null,
            'rate_source' => 'manual',
        ];

        // Handle label file upload
        if ($request->hasFile('label_file')) {
            $path = $request->file('label_file')->store('shipment-labels', 'public');
            $updateData['label_url'] = Storage::url($path);
        }

        // Update estimated shipping charges if provided
        if ($request->filled('total_charge')) {
            $updateData['estimated_shipping_charges'] = $request->total_charge;
        }

        $ship->update($updateData);

        // Record event for audit trail
        $this->trackingService->recordEvent(
            $ship,
            'submitted',
            "Manually submitted to {$request->carrier_name}. Tracking: {$request->carrier_tracking_number}" .
            ($request->notes ? ". Notes: {$request->notes}" : ''),
            null,
            'admin'
        );

        Log::info('Admin manually entered tracking', [
            'ship_id' => $ship->id,
            'carrier' => $request->carrier_name,
            'tracking' => $request->carrier_tracking_number,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('alert', 'Tracking details saved successfully.');
    }

    /**
     * Sync tracking status from carrier API
     */
    public function syncFromCarrier(Request $request, Ship $ship)
    {
        $request->validate([
            'tracking_number' => 'nullable|string|max:100',
        ]);

        // Use provided tracking number or existing one
        $trackingNumber = $request->tracking_number ?: $ship->carrier_tracking_number;

        if (!$trackingNumber) {
            return back()->withErrors(['message' => 'No tracking number provided.']);
        }

        // If new tracking number provided, update the ship
        if ($request->tracking_number && $request->tracking_number !== $ship->carrier_tracking_number) {
            $ship->update(['carrier_tracking_number' => $request->tracking_number]);
        }

        // Fetch live status from carrier
        $result = $this->trackingService->fetchLiveStatus($ship);

        if ($result) {
            return back()->with('alert', 'Tracking details synced from carrier successfully.');
        }

        return back()->withErrors(['message' => 'Could not fetch tracking details from carrier.']);
    }

    /**
     * View shipping label in browser (for printing)
     */
    public function viewLabel(Ship $ship)
    {
        // Check if we have label data stored as base64
        if ($ship->label_data) {
            $labelBinary = base64_decode($ship->label_data);

            return response($labelBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="shipping-label-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
            ]);
        }

        // If we have a label URL from the carrier, redirect to it
        if ($ship->label_url) {
            return redirect()->away($ship->label_url);
        }

        abort(404, 'Shipping label not available yet.');
    }

    /**
     * Preview/Generate commercial invoice PDF for testing
     * Allows viewing the invoice without submitting to carrier
     */
    public function previewCommercialInvoice(Ship $ship)
    {
        $invoiceService = app(\App\Services\CommercialInvoiceService::class);

        // Generate the invoice (will also save to storage)
        $path = $invoiceService->generate($ship);

        // Return the PDF for viewing in browser
        $fullPath = Storage::disk('public')->path($path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="commercial-invoice-' . ($ship->tracking_number ?? $ship->id) . '.pdf"',
        ]);
    }

    /**
     * Download shipping label as file
     */
    public function downloadLabel(Ship $ship)
    {
        // Check if we have label data stored as base64
        if ($ship->label_data) {
            $labelBinary = base64_decode($ship->label_data);

            return response($labelBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="shipping-label-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
                'Content-Length' => strlen($labelBinary),
            ]);
        }

        // If we have a label URL from the carrier, redirect to it
        if ($ship->label_url) {
            return redirect()->away($ship->label_url);
        }

        abort(404, 'Shipping label not available yet.');
    }

    /**
     * View shipping label in ZPL format (for thermal printers)
     * Converts stored PDF label to ZPL format
     * Opens in new tab as plain text ZPL
     */
    public function viewLabelZPL(Ship $ship)
    {
        // Check if we have label data stored as base64 PDF
        if (!$ship->label_data) {
            abort(404, 'Shipping label not available yet.');
        }

        try {
            // Get PDF label data
            $pdfData = $ship->label_data;
            
            // Convert PDF to ZPL
            $zplConverter = app(\App\Services\LabelZPLConverter::class);
            $zplData = $zplConverter->convertPdfToZpl($pdfData);

            // Get tracking number for filename
            $trackingNumber = $ship->carrier_tracking_number ?? $ship->tracking_number ?? $ship->id;

            // Return ZPL file for download (can be sent directly to Zebra printer)
            return response($zplData, 200, [
                'Content-Type' => 'application/x-zpl', // ZPL MIME type
                'Content-Disposition' => 'attachment; filename="shipping-label-' . $trackingNumber . '.zpl"',
                'Content-Length' => strlen($zplData),
            ]);
        } catch (\Exception $e) {
            \Log::error('ZPL conversion failed', [
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Failed to convert label to ZPL format: ' . $e->getMessage());
        }
    }

    /**
     * View commercial invoice in browser (for printing)
     * Regenerates invoice to include latest tracking number
     */
    public function viewInvoice(Ship $ship)
    {
        $invoiceService = app(\App\Services\CommercialInvoiceService::class);
        // Regenerate invoice to include latest tracking number
        $invoiceBase64 = $invoiceService->getInvoiceBase64($ship, true);
        
        if ($invoiceBase64) {
            $invoiceBinary = base64_decode($invoiceBase64);
            
            return response($invoiceBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice-for-customs-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
            ]);
        }

        abort(404, 'Invoice for customs purposes not available yet.');
    }

    /**
     * Download commercial invoice as file
     * Regenerates invoice to include latest tracking number
     */
    public function downloadInvoice(Ship $ship)
    {
        $invoiceService = app(\App\Services\CommercialInvoiceService::class);
        // Regenerate invoice to include latest tracking number
        $invoiceBase64 = $invoiceService->getInvoiceBase64($ship, true);
        
        if ($invoiceBase64) {
            $invoiceBinary = base64_decode($invoiceBase64);
            
            return response($invoiceBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-for-customs-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
                'Content-Length' => strlen($invoiceBinary),
            ]);
        }

        abort(404, 'Invoice for customs purposes not available yet.');
    }

    /**
     * Download merged master PDF of all merchant invoices
     * Combines all merchant invoices uploaded by the client into a single PDF
     * Saves to storage and returns URL
     */
    public function downloadMergedMerchantInvoices(Ship $ship)
    {
        try {
            // Increase memory and time limits for PDF processing
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5 minutes

            // Validate shipment exists
            if (!$ship || !$ship->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found.'
                ], 404);
            }

            // Load packages with items and invoice files
            $ship->load('packages.items.invoiceFiles');
            
            $invoiceFiles = [];
            foreach ($ship->packages as $package) {
                if (!$package || !$package->items) {
                    continue;
                }
                foreach ($package->items as $item) {
                    if (!$item || !$item->invoiceFiles) {
                        continue;
                    }
                    foreach ($item->invoiceFiles as $invoiceFile) {
                        if ($invoiceFile) {
                            $invoiceFiles[] = $invoiceFile;
                        }
                    }
                }
            }

            if (empty($invoiceFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No merchant invoices found for this shipment.'
                ], 404);
            }

            Log::info('Starting PDF merge', [
                'ship_id' => $ship->id,
                'invoice_files_count' => count($invoiceFiles),
            ]);

            // Use Imagick to merge PDFs if available
            if (extension_loaded('imagick')) {
                $mergedPdf = $this->mergePdfsWithImagick($invoiceFiles);
            } else {
                // Fallback: try to use setasign/fpdi if available
                $mergedPdf = $this->mergePdfsWithFpdi($invoiceFiles);
            }

            if (!$mergedPdf) {
                Log::error('PDF merge returned null', [
                    'ship_id' => $ship->id,
                    'imagick_loaded' => extension_loaded('imagick'),
                    'fpdi_available' => class_exists(\setasign\Fpdi\Fpdi::class),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to merge PDF files. Please ensure Imagick extension is installed.'
                ], 500);
            }

            $trackingNumber = $ship->carrier_tracking_number ?? $ship->tracking_number ?? $ship->id;
            $filename = $trackingNumber . '-merchant-invoices-merged.pdf';
            $storagePath = 'temp/shipments/' . $filename;

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('temp/shipments');

            // Save file to storage
            $saved = Storage::disk('public')->put($storagePath, $mergedPdf);

            if (!$saved) {
                Log::error('Failed to save merged PDF to storage', [
                    'ship_id' => $ship->id,
                    'storage_path' => $storagePath,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save merged PDF file.'
                ], 500);
            }

            // Get public URL
            $url = Storage::disk('public')->url($storagePath);

            Log::info('Master PDF saved successfully', [
                'ship_id' => $ship->id,
                'filename' => $filename,
                'url' => $url,
                'size' => strlen($mergedPdf),
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to merge merchant invoices', [
                'ship_id' => $ship->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge merchant invoices: ' . $e->getMessage()
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
                    Log::warning('Invoice file not found', [
                        'invoice_file_id' => $invoiceFile->id,
                        'file_path' => $invoiceFile->file,
                    ]);
                    continue;
                }

                // Only process PDF files with FPDI
                if ($invoiceFile->file_type === 'pdf') {
                    $pageCount = $pdf->setSourceFile($filePath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } else {
                    // For images, we'd need to convert to PDF first
                    // This is a limitation - Imagick handles this better
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
        
        $fullPath = storage_path('app/public/' . $filePath);
        
        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * Delete a shipment (admin only)
     * Shipments can be deleted at any phase
     */
    public function destroy(Ship $ship)
    {
        try {
            DB::beginTransaction();

            // Delete tracking events first
            $ship->trackingEvents()->delete();

            // Delete the shipment (packages remain, just unassigned from this shipment)
            $ship->delete();

            DB::commit();

            Log::info('Admin deleted shipment', [
                'ship_id' => $ship->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.shipments')->with('alert', 'Shipment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete shipment', [
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['message' => 'Failed to delete shipment. ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // OPERATOR SHIPMENT CREATION
    // ==========================================

    /**
     * Show the operator create shipment form
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('first_name')
            ->limit(50)
            ->get(['id', 'first_name', 'last_name', 'email', 'suite']);

        $carrierServices = CarrierService::active()->ordered()->get()->map->toFrontendFormat();
        $carrierAddons = CarrierAddon::active()->ordered()->get()->map(fn($addon) => $addon->toFrontendFormat());

        return Inertia::render('Admin/Shipments/Create', [
            'customers' => $customers,
            'carrierServices' => $carrierServices,
            'carrierAddons' => $carrierAddons,
            'preselectedCustomerId' => $request->input('customer_id'),
        ]);
    }

    /**
     * Store a new operator-created shipment
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'package_ids' => 'required|array|min:1',
            'package_ids.*' => 'exists:packages,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'carrier_service_id' => 'required|exists:carrier_services,id',
            'estimated_shipping_charges' => 'required|numeric|min:0',
            'eei_code' => 'nullable|string|max:50',
            'eei_required' => 'nullable|boolean',
            'eei_exemption_reason' => 'nullable|string|max:255',
        ]);

        try {
            $shipmentRequest = OperatorShipmentRequest::fromRequest($request);
            $ship = $this->operatorShipmentService->createShipment($shipmentRequest);

            return Redirect::route('admin.shipments.tracking', $ship)
                ->with('alert', 'Shipment created successfully. Carrier submission in progress.');
        } catch (\Exception $e) {
            Log::error('Operator shipment creation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return Redirect::back()
                ->withInput()
                ->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Search customers for the create shipment form
     */
    public function searchCustomers(Request $request)
    {
        $search = $request->input('search', '');

        $query = Customer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('suite', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'suite']);

        return response()->json([
            'customers' => $customers->map(fn($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name . ' ' . $c->last_name),
                'email' => $c->email,
                'suite' => $c->suite,
            ]),
        ]);
    }

    /**
     * Get available packages for a customer
     */
    public function getAvailablePackages(Customer $customer)
    {
        $packages = $this->operatorShipmentService->getAvailablePackages($customer->id);

        return response()->json([
            'packages' => $packages->map(fn($pkg) => [
                'id' => $pkg->id,
                'package_id' => $pkg->package_id,
                'status' => $pkg->status,
                'status_name' => $pkg->status_name,
                'total_weight' => $pkg->total_weight,
                'total_value' => $pkg->total_value,
                'billed_weight' => $pkg->billed_weight,
                'from' => $pkg->from,
                'date_received' => $pkg->date_received,
                'items_count' => $pkg->items->count(),
                'items' => $pkg->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'value' => $item->value,
                ]),
            ]),
            'addresses' => $customer->addresses->map(fn($addr) => [
                'id' => $addr->id,
                'label' => $addr->address_line_1 . ', ' . $addr->city . ', ' . ($addr->state ?? '') . ' ' . ($addr->postal_code ?? ''),
                'city' => $addr->city,
                'state' => $addr->state,
                'country' => $addr->country_code ?? $addr->country,
                'is_default' => $addr->is_default_us || $addr->is_default_uk,
            ]),
        ]);
    }

    /**
     * Get shipping rates for packages (operator create flow)
     * Used by CarrierSelector when creating new shipment without existing ship_id
     */
    public function getRates(Request $request)
    {
        $request->validate([
            'package_ids' => 'required|array|min:1',
            'package_ids.*' => 'exists:packages,id',
            'address_id' => 'required|exists:customer_addresses,id',
            'carrier' => 'nullable|string', // Optional single carrier mode
        ]);

        $packageIds = $request->input('package_ids');
        $addressId = $request->input('address_id');
        $singleCarrier = $request->input('carrier');

        try {
            // Resolve destination from address
            $address = \App\Models\CustomerAddress::find($addressId);
            $destination = [
                'street1' => $address->address_line_1 ?? '',
                'city' => $address->city ?? '',
                'state' => $address->state ?? '',
                'zip' => $address->postal_code ?? '',
                'country' => $address->country_code ?? $address->country ?? 'US',
            ];

            // Get shipping rate service
            $shippingRateService = app(\App\Services\ShippingRateService::class);

            // Get carrier rates
            if ($singleCarrier) {
                $allRates = $shippingRateService->getSingleCarrierRates(
                    $singleCarrier,
                    $packageIds,
                    $destination
                );
            } else {
                $allRates = $shippingRateService->getRatesForPackages($packageIds, $destination);
            }

            // Enrich addons with live pricing for each carrier
            $enrichedAddons = [];
            foreach ($allRates as $carrierCode => $carrierData) {
                $surchargeBreakdown = [];
                $baseRate = 0;
                if (!empty($carrierData['rates'])) {
                    $firstRate = $carrierData['rates'][0];
                    $surchargeBreakdown = $firstRate['surcharge_breakdown'] ?? [];
                    $baseRate = $firstRate['price'] ?? 0;
                }

                $enrichedAddons[$carrierCode] = $shippingRateService->getAddonsForRate(
                    $carrierCode,
                    $surchargeBreakdown,
                    $baseRate,
                    $packageIds
                );

                // Check checkout eligibility
                $eligibility = $shippingRateService->validateCheckoutEligibility(
                    $carrierCode,
                    $surchargeBreakdown,
                    $packageIds
                );
                $allRates[$carrierCode]['checkout_eligible'] = $eligibility['eligible'];
                $allRates[$carrierCode]['checkout_errors'] = $eligibility['errors'];
            }

            // Calculate classification charges
            $classificationCharges = app(\App\Services\CarrierAddonService::class)
                ->calculateClassificationCharges($packageIds);

            return response()->json([
                'success' => true,
                'data' => [
                    'carriers' => $allRates,
                    'carrier_addons' => $enrichedAddons,
                    'package_classifications' => $shippingRateService->getClassificationSummary($packageIds),
                    'classification_charges' => $classificationCharges,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Operator rates fetch failed', [
                'error' => $e->getMessage(),
                'package_ids' => $packageIds,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch shipping rates.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
