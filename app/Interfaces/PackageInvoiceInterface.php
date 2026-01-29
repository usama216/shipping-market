<?php

namespace App\Interfaces;

interface PackageInvoiceInterface
{
    public function uploadInvoices($path, $package_id);
}
