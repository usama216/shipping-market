<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ship;
use App\Models\ItemInvoiceFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

/**
 * MerchantInvoiceController - Serves merchant invoice downloads for customers
 * Merchant invoices are the invoices uploaded by customers during package creation
 */
class MerchantInvoiceController extends Controller
{
    /**
     * Download all merchant invoices for a shipment as a ZIP file
     */
    public function downloadZip(Ship $ship)
    {
        $customer = Auth::guard('customer')->user();
        
        // Verify the shipment belongs to the authenticated customer
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to shipment.');
        }

        // Load packages with items and invoice files
        $ship->load('packages.items.invoiceFiles');
        
        $invoiceFiles = [];
        foreach ($ship->packages as $package) {
            foreach ($package->items as $item) {
                foreach ($item->invoiceFiles as $invoiceFile) {
                    $invoiceFiles[] = $invoiceFile;
                }
            }
        }

        if (empty($invoiceFiles)) {
            abort(404, 'No merchant invoices found for this shipment.');
        }

        // Create a temporary ZIP file
        $zipPath = storage_path('app/temp/merchant-invoices-' . $ship->id . '-' . time() . '.zip');
        $zipDir = dirname($zipPath);
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            abort(500, 'Could not create ZIP file.');
        }

        foreach ($invoiceFiles as $invoiceFile) {
            $filePath = $invoiceFile->file;
            
            // Normalize path
            if (str_starts_with($filePath, 'storage/app/public/')) {
                $filePath = str_replace('storage/app/public/', '', $filePath);
            } elseif (str_starts_with($filePath, 'storage/')) {
                $filePath = str_replace('storage/', '', $filePath);
            }
            
            $fullPath = storage_path('app/public/' . $filePath);
            
            if (file_exists($fullPath)) {
                $item = $invoiceFile->item;
                $package = $item->package;
                $fileName = sprintf(
                    'Package-%s_Item-%s_%s',
                    $package->package_id ?? $package->id,
                    $item->title ?? $item->id,
                    $invoiceFile->name
                );
                // Sanitize filename
                $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $zip->addFile($fullPath, $fileName);
            }
        }

        $zip->close();

        return Response::download($zipPath, 'merchant-invoices-shipment-' . $ship->tracking_number . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Download a single merchant invoice file
     */
    public function download(Ship $ship, ItemInvoiceFile $invoiceFile)
    {
        $customer = Auth::guard('customer')->user();
        
        // Verify the shipment belongs to the authenticated customer
        if ($ship->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to shipment.');
        }

        // Verify the invoice file belongs to a package in this shipment
        $item = $invoiceFile->item;
        $package = $item->package;
        $shipPackage = $ship->packages()->where('id', $package->id)->first();
        
        if (!$shipPackage) {
            abort(403, 'Invoice file does not belong to this shipment.');
        }

        $filePath = $invoiceFile->file;
        
        // Normalize path
        if (str_starts_with($filePath, 'storage/app/public/')) {
            $filePath = str_replace('storage/app/public/', '', $filePath);
        } elseif (str_starts_with($filePath, 'storage/')) {
            $filePath = str_replace('storage/', '', $filePath);
        }
        
        $fullPath = storage_path('app/public/' . $filePath);
        
        if (!file_exists($fullPath)) {
            abort(404, 'Invoice file not found.');
        }

        $mimeType = $invoiceFile->file_type === 'pdf' 
            ? 'application/pdf' 
            : 'image/' . pathinfo($fullPath, PATHINFO_EXTENSION);

        return Response::download($fullPath, $invoiceFile->name, [
            'Content-Type' => $mimeType,
        ]);
    }
}
