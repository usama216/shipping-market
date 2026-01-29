<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * BarcodeService - Generates barcodes for package IDs
 * Supports PDF and ZPL formats for thermal printers
 */
class BarcodeService
{
    /**
     * Generate barcode image as PNG
     */
    public function generatePNG(string $packageId): string
    {
        $generator = new BarcodeGeneratorPNG();
        return $generator->getBarcode($packageId, $generator::TYPE_CODE_128, 3, 50);
    }

    /**
     * Generate barcode image as SVG
     */
    public function generateSVG(string $packageId): string
    {
        $generator = new BarcodeGeneratorSVG();
        return $generator->getBarcode($packageId, $generator::TYPE_CODE_128, 3, 50);
    }

    /**
     * Generate barcode as base64 encoded PNG
     */
    public function generateBase64(string $packageId): string
    {
        $png = $this->generatePNG($packageId);
        return base64_encode($png);
    }

    /**
     * Generate ZPL code for thermal printers
     * Format: Code 128 barcode with package ID text below
     */
    public function generateZPL(string $packageId, int $width = 400, int $height = 200): string
    {
        // ZPL format for Code 128 barcode
        // ^FO = Field Origin (x, y position)
        // ^BY = Barcode Field Default (module width, wide bar width, height)
        // ^BC = Code 128 barcode
        // ^FD = Field Data (the package ID)
        // ^CF = Change Font
        // ^FO = Field Origin for text
        // ^FD = Field Data for text
        // ^FS = Field Separator

        $zpl = "^XA\n"; // Start ZPL
        $zpl .= "^FO20,20^BY3,3,50^BCN,50,Y,N,N^FD{$packageId}^FS\n"; // Barcode
        $zpl .= "^FO20,80^CF0,30^FD{$packageId}^FS\n"; // Text below barcode
        $zpl .= "^XZ\n"; // End ZPL

        return $zpl;
    }

    /**
     * Generate PDF with barcode
     */
    public function generatePDF(string $packageId): string
    {
        try {
            $pdf = Pdf::loadView('barcodes.package', [
                'packageId' => $packageId,
                'barcodeBase64' => $this->generateBase64($packageId),
            ]);

            $pdf->setPaper([0, 0, 144, 72], 'portrait'); // Small label size (2x1 inch in points)
            return $pdf->output();
        } catch (\Exception $e) {
            Log::error('BarcodeService: PDF generation failed', [
                'package_id' => $packageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
