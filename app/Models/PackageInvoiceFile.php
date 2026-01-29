<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageInvoiceFile extends Model
{
    protected $fillable = [
        'package_invoice_id',
        'name',
        'file',
        'file_type',
    ];

    /**
     * Get the invoice that owns this file
     */
    public function invoice()
    {
        return $this->belongsTo(PackageInvoice::class, 'package_invoice_id');
    }
}
