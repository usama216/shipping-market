<?php

namespace App\Repositories;

use App\Helpers\PackageStatus;
use App\Interfaces\PackageInterface;
use App\Interfaces\PackageInvoiceInterface;
use App\Models\Package;
use App\Models\PackageInvoice;
use App\Models\SpecialRequest;
use Auth;

class PackageInvoiceRepository implements PackageInvoiceInterface
{

    protected $packageInvoice;
    public function __construct(PackageInvoice $packageInvoice)
    {
        $this->packageInvoice = $packageInvoice;
    }


    public function uploadInvoices($path, $package_id)
    {
        return $this->packageInvoice->create(['package_id' => $package_id, 'image' => $path]);
    }

    /**
     * Insert a single invoice with full metadata
     */
    public function insertOne(array $data, $packageId, ?string $imagePath = null)
    {
        return $this->packageInvoice->create([
            'package_id' => $packageId,
            'type' => $data['type'] ?? 'received',
            'invoice_number' => $data['invoice_number'] ?? null,
            'vendor_name' => $data['vendor_name'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'invoice_amount' => $data['invoice_amount'] ?? null,
            'notes' => $data['notes'] ?? null,
            'image' => $imagePath,
        ]);
    }

    /**
     * Create a customer-submitted invoice record (files stored in package_invoice_files)
     */
    public function createForCustomer($packageId)
    {
        return $this->packageInvoice->create([
            'package_id' => $packageId,
            'type' => 'customer_submitted',
        ]);
    }
}
