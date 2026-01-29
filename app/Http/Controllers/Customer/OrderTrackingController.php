<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Helpers\ShipmentStatus;
use App\Models\Ship;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Display customer's shipments with tracking
     */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $query = Ship::query()
            ->with(['customerAddress', 'internationalShipping', 'latestTrackingEvent'])
            ->where('customer_id', $customer->id)
            ->where('invoice_status', 'paid');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by tracking number (only within customer's orders)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('carrier_tracking_number', 'like', "%{$search}%");
            });
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(15);

        // Add tracking URLs to shipments
        $shipments->getCollection()->transform(function ($shipment) {
            $shipment->carrier_tracking_url = $this->trackingService->getCarrierTrackingUrl($shipment);
            return $shipment;
        });

        return Inertia::render('Customers/OrderTracking/Index', [
            'shipments' => $shipments,
            'filters' => $request->only(['status', 'search']),
            'statuses' => ShipmentStatus::all(),
        ]);
    }

    /**
     * Search customer's shipments by tracking number
     */
    public function search(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|min:3'
        ]);

        $customer = Auth::guard('customer')->user();
        $ship = $this->trackingService->searchByTrackingNumber(
            $request->tracking_number,
            $customer->id // Only search this customer's orders
        );

        if (!$ship) {
            return back()->withErrors(['tracking_number' => 'No shipment found with this tracking number.']);
        }

        return redirect()->route('customer.tracking.show', $ship->id);
    }

    /**
     * Display detailed tracking for a customer's shipment
     */
    public function show(Ship $ship)
    {
        $customer = Auth::guard('customer')->user();
        // Ensure customer can only view their own shipments
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'You are not authorized to view this shipment.');
        }

        $ship->load([
            'customerAddress',
            'internationalShipping',
            'packages',
            'trackingEvents'
        ]);

        $timeline = $this->trackingService->getTrackingTimeline($ship);
        $carrierTrackingUrl = $this->trackingService->getCarrierTrackingUrl($ship);

        return Inertia::render('Customers/OrderTracking/Show', [
            'ship' => $ship,
            'timeline' => $timeline,
            'carrierTrackingUrl' => $carrierTrackingUrl,
        ]);
    }
}
