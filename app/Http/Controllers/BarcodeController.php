<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BarcodeController extends Controller
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * Download barcode as PDF - saves to storage and returns URL
     */
    public function downloadPDF(Package $package)
    {
        Log::info('Barcode PDF download requested', [
            'package_id' => $package->id,
            'package_id_string' => $package->package_id,
            'user_id' => auth()->id() ?? 'guest',
        ]);

        if (!$package->package_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package ID not found.'
            ], 404);
        }

        try {
            $pdfContent = $this->barcodeService->generatePDF($package->package_id);
            $filename = $package->package_id . '.pdf';
            $storagePath = 'temp/barcodes/' . $filename;

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('temp/barcodes');

            // Save file to storage
            Storage::disk('public')->put($storagePath, $pdfContent);

            // Get public URL
            $url = Storage::disk('public')->url($storagePath);

            Log::info('PDF saved successfully', [
                'filename' => $filename,
                'url' => $url,
                'size' => strlen($pdfContent),
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Barcode PDF generation failed', [
                'package_id' => $package->id,
                'package_id_string' => $package->package_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View barcode as PDF in browser - saves to storage and returns URL
     */
    public function viewPDF(Package $package)
    {
        if (!$package->package_id) {
            return response()->json([
                'success' => false,
                'message' => 'Package ID not found.'
            ], 404);
        }

        try {
            $pdfContent = $this->barcodeService->generatePDF($package->package_id);
            $filename = $package->package_id . '.pdf';
            $storagePath = 'temp/barcodes/' . $filename;

            // Ensure directory exists
            Storage::disk('public')->makeDirectory('temp/barcodes');

            // Save file to storage
            Storage::disk('public')->put($storagePath, $pdfContent);

            // Get public URL
            $url = Storage::disk('public')->url($storagePath);

            Log::info('PDF saved for viewing', [
                'filename' => $filename,
                'url' => $url,
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Barcode PDF view failed', [
                'package_id' => $package->id,
                'package_id_string' => $package->package_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download barcode as ZPL (for thermal printers) - returns file directly with download headers
     */
    public function downloadZPL(Package $package)
    {
        if (!$package->package_id) {
            abort(404, 'Package ID not found.');
        }

        try {
            $zpl = $this->barcodeService->generateZPL($package->package_id);
            $filename = $package->package_id . '.zpl';

            Log::info('ZPL generated for download', [
                'filename' => $filename,
                'package_id' => $package->package_id,
            ]);

            // Return file directly with download headers
            return Response::make($zpl, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($zpl),
            ]);
        } catch (\Exception $e) {
            Log::error('Barcode ZPL generation failed', [
                'package_id' => $package->id,
                'package_id_string' => $package->package_id,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Failed to generate barcode ZPL: ' . $e->getMessage());
        }
    }

    /**
     * Get barcode image as PNG
     */
    public function image(Package $package)
    {
        if (!$package->package_id) {
            abort(404, 'Package ID not found.');
        }

        $png = $this->barcodeService->generatePNG($package->package_id);

        return Response::make($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
