<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Ship;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * CommercialInvoiceService
 * 
 * Generates commercial invoices for international shipments.
 * Includes Marketsz company information, HS codes, EEI codes, and export compliance data.
 */
class CommercialInvoiceService
{
    // Marketsz company information (per client requirements)
    private const COMPANY_NAME = 'Marketz';
    private const COMPANY_ADDRESS = '108 West 13th Street';
    private const COMPANY_CITY = 'Wilmington';
    private const COMPANY_STATE = 'Delaware';
    private const COMPANY_ZIP = '19801';
    private const COMPANY_COUNTRY = 'USA';

    /**
     * Generate commercial invoice PDF for a shipment
     * 
     * @param Ship $ship The shipment to generate invoice for
     * @return string|null Path to generated PDF file, or null on failure
     */
    public function generateInvoice(Ship $ship): ?string
    {
        try {
            // Load all necessary relationships
            $ship->load([
                'packages.items',
                'packages.warehouse',
                'customerAddress',
                'customer',
            ]);

            if ($ship->packages->isEmpty()) {
                Log::warning('CommercialInvoiceService: No packages found for shipment', [
                    'ship_id' => $ship->id,
                ]);
                return null;
            }

            // Get export compliance data from first package (all packages should have same compliance data)
            $firstPackage = $ship->packages->first();
            $complianceData = $this->getComplianceData($firstPackage);

            // Collect all items from all packages
            $allItems = $this->collectAllItems($ship->packages);

            // Calculate totals
            $totals = $this->calculateTotals($allItems);

            // Get seller names from packages (Amazon, eBay, Walmart, etc.)
            $sellerNames = $this->getSellerNames($ship->packages);

            // Get client tax ID from customer
            $clientTaxId = $ship->customer?->tax_id ?? null;

            // Get tracking number (DHL tracking number when available)
            $trackingNumber = $ship->carrier_tracking_number ?? $ship->tracking_number ?? null;

            // Generate PDF
            $pdf = Pdf::loadView('invoices.commercial', [
                'ship' => $ship,
                'company' => $this->getCompanyInfo(),
                'compliance' => $complianceData,
                'items' => $allItems,
                'totals' => $totals,
                'invoiceNumber' => $this->generateInvoiceNumber($ship),
                'invoiceDate' => now()->format('Y-m-d'),
                'sellerNames' => $sellerNames,
                'clientTaxId' => $clientTaxId,
                'trackingNumber' => $trackingNumber,
            ]);

            // Set paper size to Letter (US standard)
            $pdf->setPaper('letter', 'portrait');

            // Save PDF to storage
            $filename = 'invoices/commercial_' . $ship->id . '_' . time() . '.pdf';
            $path = 'public/' . $filename;
            
            Storage::put($path, $pdf->output());

            // Store invoice reference in package_invoices table for each package
            $invoiceNumber = $this->generateInvoiceNumber($ship);
            foreach ($ship->packages as $package) {
                $this->storeInvoiceReference($package, $filename, $invoiceNumber);
            }

            Log::info('CommercialInvoiceService: Invoice generated successfully', [
                'ship_id' => $ship->id,
                'path' => $filename,
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('CommercialInvoiceService: Failed to generate invoice', [
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get company information
     */
    private function getCompanyInfo(): array
    {
        return [
            'name' => self::COMPANY_NAME,
            'address' => self::COMPANY_ADDRESS,
            'city' => self::COMPANY_CITY,
            'state' => self::COMPANY_STATE,
            'zip' => self::COMPANY_ZIP,
            'country' => self::COMPANY_COUNTRY,
            'full_address' => sprintf(
                '%s, %s, %s %s, %s',
                self::COMPANY_ADDRESS,
                self::COMPANY_CITY,
                self::COMPANY_STATE,
                self::COMPANY_ZIP,
                self::COMPANY_COUNTRY
            ),
        ];
    }

    /**
     * Get export compliance data from package
     */
    private function getComplianceData(Package $package): array
    {
        return [
            'incoterm' => $package->incoterm ?? 'DAP',
            'invoice_signature_name' => $package->invoice_signature_name ?? 'Authorized Shipper',
            'exporter_id_license' => $package->exporter_id_license ?? 'EAR99',
            'us_filing_type' => $package->us_filing_type ?? '30.37(a) - Under $2,500',
            'exporter_code' => $package->exporter_code ?? null,
            'itn_number' => $package->itn_number ?? null,
        ];
    }

    /**
     * Collect all items from all packages in shipment
     */
    private function collectAllItems($packages): array
    {
        $allItems = [];

        foreach ($packages as $package) {
            foreach ($package->items as $item) {
                $allItems[] = [
                    'package_id' => $package->package_id ?? $package->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'hs_code' => $item->hs_code,
                    'quantity' => $item->quantity,
                    'unit_value' => $item->value_per_unit,
                    'total_value' => $item->total_line_value,
                    'weight' => $item->total_line_weight,
                    'weight_unit' => $item->weight_unit ?? 'lb',
                    'material' => $item->material,
                    'manufacturer' => $item->manufacturer,
                ];
            }
        }

        return $allItems;
    }

    /**
     * Calculate totals for invoice
     */
    private function calculateTotals(array $items): array
    {
        $totalValue = array_sum(array_column($items, 'total_value'));
        $totalQuantity = array_sum(array_column($items, 'quantity'));
        $totalWeight = array_sum(array_column($items, 'weight'));

        return [
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'currency' => 'USD',
        ];
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(Ship $ship): string
    {
        return 'INV-' . $ship->id . '-' . now()->format('Ymd');
    }

    /**
     * Store invoice reference in package_invoices table
     */
    private function storeInvoiceReference(Package $package, string $filename, string $invoiceNumber): void
    {
        try {
            // Check if invoice already exists for this package
            $existingInvoice = $package->invoices()
                ->where('type', '!=', 'customer_submitted')
                ->where('invoice_number', 'like', 'INV-%')
                ->first();

            if ($existingInvoice) {
                // Update existing invoice
                $existingInvoice->update([
                    'image' => $filename,
                    'invoice_date' => now(),
                    'invoice_number' => $invoiceNumber,
                ]);
            } else {
                // Create new invoice record
                $package->invoices()->create([
                    'type' => 'received', // System-generated invoice
                    'invoice_number' => $invoiceNumber,
                    'vendor_name' => self::COMPANY_NAME,
                    'invoice_date' => now(),
                    'invoice_amount' => $package->total_value,
                    'image' => $filename,
                    'notes' => 'Auto-generated commercial invoice for export compliance',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('CommercialInvoiceService: Failed to store invoice reference', [
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get Base64 encoded invoice for API submission
     * 
     * @param Ship $ship
     * @param bool $regenerate Force regeneration to include latest tracking number
     * @return string|null Base64 encoded PDF content
     */
    public function getInvoiceBase64(Ship $ship, bool $regenerate = false): ?string
    {
        try {
            // Always regenerate when viewing/downloading to include latest tracking number
            if ($regenerate) {
                $filename = $this->generateInvoice($ship);
                if ($filename) {
                    $path = 'public/' . $filename;
                    if (Storage::exists($path)) {
                        $content = Storage::get($path);
                        return base64_encode($content);
                    }
                }
                return null;
            }

            // Try to get existing invoice from first package
            $firstPackage = $ship->packages->first();
            if (!$firstPackage) {
                return null;
            }

            $invoice = $firstPackage->invoices()
                ->where('type', '!=', 'customer_submitted')
                ->where('invoice_number', 'like', 'INV-%')
                ->first();

            if ($invoice && $invoice->image) {
                $path = $this->normalizeInvoicePath($invoice->image);
                if (Storage::exists($path)) {
                    $content = Storage::get($path);
                    return base64_encode($content);
                }
            }

            // If no invoice exists, try to generate one
            try {
                $filename = $this->generateInvoice($ship);
                if ($filename) {
                    $path = 'public/' . $filename;
                    if (Storage::exists($path)) {
                        $content = Storage::get($path);
                        return base64_encode($content);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('CommercialInvoiceService: Failed to generate invoice in getInvoiceBase64', [
                    'ship_id' => $ship->id,
                    'error' => $e->getMessage(),
                ]);
                // Return null - shipment can proceed without invoice
                return null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('CommercialInvoiceService: Failed to get invoice Base64', [
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get seller names from packages (Amazon, eBay, Walmart, etc.)
     */
    private function getSellerNames($packages): array
    {
        $sellerNames = [];
        foreach ($packages as $package) {
            if ($package->from && !in_array($package->from, $sellerNames)) {
                $sellerNames[] = $package->from;
            }
        }
        return $sellerNames;
    }

    /**
     * Normalize invoice file path for storage access
     */
    private function normalizeInvoicePath(string $path): string
    {
        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/app/public/#', '', $path);
        $path = preg_replace('#^storage/#', '', $path);
        
        if (!str_starts_with($path, 'public/')) {
            $path = 'public/' . $path;
        }
        
        return $path;
    }
}
