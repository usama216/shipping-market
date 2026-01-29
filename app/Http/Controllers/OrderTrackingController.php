<?php

namespace App\Http\Controllers;

use App\Helpers\CarrierStatus;
use App\Helpers\ShipmentStatus;
use App\Services\ShipmentSubmissionService;
use App\Models\Ship;
use App\Models\ShipmentEvent;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Display the tracking dashboard with all shipments
     */
    public function index(Request $request)
    {
        $query = Ship::query()
            ->with(['customer', 'customerAddress', 'internationalShipping', 'latestTrackingEvent'])
            ->where('invoice_status', 'paid');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by carrier
        if ($request->filled('carrier')) {
            $query->where('carrier_name', $request->carrier);
        }

        // Filter by carrier status
        if ($request->filled('carrier_status')) {
            $query->where('carrier_status', $request->carrier_status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by tracking number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('carrier_tracking_number', 'like', "%{$search}%");
            });
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(25);

        // Add tracking URLs to shipments
        $shipments->getCollection()->transform(function ($shipment) {
            $shipment->carrier_tracking_url = $this->trackingService->getCarrierTrackingUrl($shipment);
            return $shipment;
        });

        return Inertia::render('Admin/OrderTracking/Index', [
            'shipments' => $shipments,
            'filters' => $request->only(['status', 'carrier', 'carrier_status', 'date_from', 'date_to', 'search']),
            'statuses' => ShipmentStatus::all(),
            'carriers' => ['fedex', 'dhl', 'ups'],
            'carrierStatuses' => CarrierStatus::all(),
        ]);
    }

    /**
     * Search shipments by tracking number
     */
    public function search(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|min:3'
        ]);

        $ship = $this->trackingService->searchByTrackingNumber($request->tracking_number);

        if (!$ship) {
            return back()->withErrors(['tracking_number' => 'No shipment found with this tracking number.']);
        }

        return redirect()->route('admin.tracking.show', $ship->id);
    }

    /**
     * Display detailed tracking for a shipment
     */
    public function show(Ship $ship)
    {
        $ship->load([
            'user',
            'customer',
            'customerAddress',
            'internationalShipping',
            'packages.items',
            'trackingEvents'
        ]);

        $timeline = $this->trackingService->getTrackingTimeline($ship);
        $carrierTrackingUrl = $this->trackingService->getCarrierTrackingUrl($ship);

        return Inertia::render('Admin/OrderTracking/Show', [
            'ship' => $ship,
            'timeline' => $timeline,
            'carrierTrackingUrl' => $carrierTrackingUrl,
            'statuses' => ShipmentEvent::getStatuses(),
        ]);
    }

    /**
     * Fetch live status from carrier API
     */
    public function fetchLiveStatus(Ship $ship)
    {
        $result = $this->trackingService->fetchLiveStatus($ship);

        if ($result) {
            return back()->with('alert', 'Tracking status updated from carrier.');
        }

        return back()->withErrors(['message' => 'Could not fetch status from carrier. Please try again later.']);
    }

    /**
     * Manually add a tracking event
     */
    public function addEvent(Request $request, Ship $ship)
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

    /**
     * Retry carrier API submission for failed shipments
     */
    public function retrySubmission(Ship $ship)
    {
        if (!in_array($ship->carrier_status, ['failed', 'pending', null])) {
            return back()->withErrors(['message' => 'Shipment has already been submitted to carrier.']);
        }

        // Reset carrier status and dispatch job
        $ship->update([
            'carrier_status' => 'pending',
            'carrier_errors' => null,
        ]);

        app(ShipmentSubmissionService::class)->submit($ship);

        Log::channel('carrier')->info('Admin retried carrier submission', [
            'ship_id' => $ship->id,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('alert', 'Shipment resubmitted to carrier. Please check back in a few moments.');
    }

    /**
     * Manually enter tracking details when carrier API is not available
     */
    public function manualTracking(Request $request, Ship $ship)
    {
        $request->validate([
            'carrier_name' => 'required|string|in:fedex,dhl,ups,other',
            'carrier_tracking_number' => 'required|string|max:100',
            'carrier_service_type' => 'nullable|string|max:100',
            'label_file' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:5120',
            'total_charge' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $updateData = [
            'carrier_name' => $request->carrier_name,
            'carrier_tracking_number' => $request->carrier_tracking_number,
            'carrier_service_type' => $request->carrier_service_type,
            'carrier_status' => 'submitted',
            'submitted_to_carrier_at' => now(),
            'carrier_errors' => null,
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

        Log::channel('carrier')->info('Admin manually entered tracking', [
            'ship_id' => $ship->id,
            'carrier' => $request->carrier_name,
            'tracking' => $request->carrier_tracking_number,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('alert', 'Tracking details saved successfully.');
    }

    /**
     * Sync tracking status from carrier API using manual tracking number
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
}
