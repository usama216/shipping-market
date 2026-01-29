<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageInvoice extends Model
{
    // Invoice types
    public const TYPE_RECEIVED = 'received';           // Invoice from merchant/store
    public const TYPE_CUSTOMER_SUBMITTED = 'customer_submitted';  // Customer's declaration

    protected $fillable = [
        'package_id',
        'type',
        'invoice_number',
        'vendor_name',
        'invoice_date',
        'invoice_amount',
        'image',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'invoice_amount' => 'decimal:2',
    ];

    protected $appends = ['invoice_path_url'];

    public function getInvoicePathUrlAttribute()
    {
        return $this->image ? asset($this->image) : null;
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Scope for received invoices (from merchant)
     */
    public function scopeReceived($query)
    {
        return $query->where('type', self::TYPE_RECEIVED);
    }

    /**
     * Scope for customer submitted invoices
     */
    public function scopeCustomerSubmitted($query)
    {
        return $query->where('type', self::TYPE_CUSTOMER_SUBMITTED);
    }

    /**
     * Get all attached files for this invoice
     */
    public function files()
    {
        return $this->hasMany(PackageInvoiceFile::class, 'package_invoice_id');
    }
}
