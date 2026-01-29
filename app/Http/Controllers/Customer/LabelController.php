<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * LabelController - Serves shipping label downloads
 */
class LabelController extends Controller
{
    /**
     * Download shipping label PDF
     */
    public function download(Ship $ship)
    {
        $customer = Auth::guard('customer')->user();
        // Verify the shipment belongs to the authenticated customer
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to shipment.');
        }

        // Check if we have label data stored as base64
        if ($ship->label_data) {
            $labelBinary = base64_decode($ship->label_data);

            return Response::make($labelBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="shipping-label-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
                'Content-Length' => strlen($labelBinary),
            ]);
        }

        // If we have a label URL from the carrier, redirect to it
        if ($ship->label_url) {
            return redirect()->away($ship->label_url);
        }

        // No label available
        abort(404, 'Shipping label not available yet. Please check back later.');
    }

    /**
     * View label in browser (for preview)
     */
    public function view(Ship $ship)
    {
        $customer = Auth::guard('customer')->user();
        // Verify the shipment belongs to the authenticated customer
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to shipment.');
        }

        // Check if we have label data stored as base64
        if ($ship->label_data) {
            $labelBinary = base64_decode($ship->label_data);

            return Response::make($labelBinary, 200, [
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
}
