<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ship;
use Illuminate\Support\Facades\Auth;

/**
 * InvoiceController - Serves commercial invoice downloads for customers
 */
class InvoiceController extends Controller
{
    /**
     * Download commercial invoice PDF
     * Regenerates invoice to include latest tracking number
     */
    public function download(Ship $ship)
    {
        $customer = Auth::guard('customer')->user();
        
        // Verify the shipment belongs to the authenticated customer
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to shipment.');
        }

        $invoiceService = app(\App\Services\CommercialInvoiceService::class);
        
        // Regenerate invoice to include latest tracking number (same as admin)
        $invoiceBase64 = $invoiceService->getInvoiceBase64($ship, true);
        
        if (!$invoiceBase64) {
            abort(404, 'Invoice for customs purposes not available yet.');
        }

        // Decode base64 PDF data
        $invoiceBinary = base64_decode($invoiceBase64);
        
        return response($invoiceBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="commercial-invoice-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
            'Content-Length' => strlen($invoiceBinary),
        ]);
    }

    /**
     * View invoice in browser (for preview)
     * Regenerates invoice to include latest tracking number
     */
    public function view(Ship $ship)
    {
        $customer = Auth::guard('customer')->user();
        
        // Verify the shipment belongs to the authenticated customer
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to shipment.');
        }

        $invoiceService = app(\App\Services\CommercialInvoiceService::class);
        
        // Regenerate invoice to include latest tracking number (same as admin)
        $invoiceBase64 = $invoiceService->getInvoiceBase64($ship, true);
        
        if (!$invoiceBase64) {
            abort(404, 'Invoice for customs purposes not available yet.');
        }

        // Decode base64 PDF data
        $invoiceBinary = base64_decode($invoiceBase64);
        
        return response($invoiceBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="commercial-invoice-' . ($ship->carrier_tracking_number ?? $ship->tracking_number) . '.pdf"',
        ]);
    }
}
