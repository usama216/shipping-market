<?php

namespace App\Models;

use App\Helpers\PackageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    protected $fillable = [
        'package_id', 
        'store_tracking_id', 
        'customer_id', 
        'warehouse_id', 
        'special_request', 
        'selected_addon_ids', // JSON array of carrier addon IDs
        'date_received', 
        'from', 
        'total_value', 
        'weight_unit', 
        'dimension_unit', 
        'package_type', 
        'note', 
        'status',
        // Export compliance fields
        'incoterm',
        'invoice_signature_name',
        'exporter_id_license',
        'us_filing_type',
        'exporter_code',
        'itn_number',
    ];

    protected $casts = [
        'total_value' => 'float',
        'date_received' => 'date',
        'selected_addon_ids' => 'array',
    ];

    protected $appends = ['status_name', 'total_weight', 'total_volumetric_weight', 'billed_weight'];

    /**
     * Get total weight from sum of item weights
     */
    public function getTotalWeightAttribute(): float
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        return (float) ($this->items->sum('total_line_weight') ?? 0);
    }

    /**
     * Get total volumetric weight from sum of item volumetric weights
     */
    public function getTotalVolumetricWeightAttribute(): float
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        return (float) ($this->items->sum('volumetric_weight') ?? 0);
    }

    /**
     * Get billed weight (max of actual weight vs volumetric weight)
     */
    public function getBilledWeightAttribute(): float
    {
        return max($this->total_weight, $this->total_volumetric_weight);
    }
    public function items()
    {
        return $this->hasMany(PackageItem::class, 'package_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(PackageFile::class, 'package_id', 'id');
    }

    /**
     * Get invoices for this package
     */
    public function invoices()
    {
        return $this->hasMany(PackageInvoice::class, 'package_id', 'id');
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case PackageStatus::ACTION_REQUIRED:
                return "Action Required";
            case PackageStatus::IN_REVIEW:
                return "In Review";
            case PackageStatus::READY_TO_SEND:
                return "Ready to Send";
            case PackageStatus::CONSOLIDATE:
                return "Consolidated";
            default:
                return "Invalid status code";
        }
    }

    /**
     * Get the customer who owns this package
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Get the warehouse this package originates from
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function specialRequest()
    {
        return $this->belongsTo(SpecialRequest::class, 'special_request', 'id');
    }

    /**
     * Get selected special requests for this package
     * Uses selected_addon_ids field to store special_request IDs
     */
    public function selectedSpecialRequests()
    {
        if (empty($this->selected_addon_ids)) {
            return collect([]);
        }
        return SpecialRequest::whereIn('id', $this->selected_addon_ids)->get();
    }
    
    /**
     * Get selected carrier addons for this package (legacy method)
     */
    public function selectedAddons()
    {
        if (empty($this->selected_addon_ids)) {
            return collect([]);
        }
        return CarrierAddon::whereIn('id', $this->selected_addon_ids)->get();
    }

    /**
     * Get admin responses for special requests on this package
     */
    public function specialRequestResponses()
    {
        return $this->hasMany(PackageSpecialRequestResponse::class, 'package_id');
    }

    /**
     * Get the shipments this package belongs to
     */
    public function ships()
    {
        return $this->belongsToMany(Ship::class, 'ship_packages', 'package_id', 'ship_id');
    }

    /**
     * Get total invoice file count across all items
     */
    public function getInvoicesCountAttribute(): int
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items.invoiceFiles');
        }
        return $this->items->sum(fn($item) => $item->invoiceFiles->count());
    }

    /**
     * Check if package has any invoice files attached to items
     */
    public function getHasInvoicesAttribute(): bool
    {
        return $this->invoices_count > 0;
    }
}
