<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ship extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'tracking_number',
        'carrier_tracking_number',
        'carrier_name',
        'carrier_service_type',
        'label_url',
        'label_data',
        'carrier_status',
        'submitted_to_carrier_at',
        'carrier_response',
        'carrier_errors',
        'status',
        'total_weight',
        'total_price',
        'total_ship_payment',
        'customer_address_id',
        'international_shipping_option_id',
        'carrier_service_id', // New: references carrier_services table
        'selected_addon_ids', // New: JSON array of selected addon IDs
        'addon_charges', // New: total addon charges
        'declared_value', // New: for customs/insurance
        'declared_value_currency',
        'eei_code', // Electronic Export Information code (per shipment, not per item)
        'eei_required', // Whether EEI is required for this shipment
        'eei_exemption_reason', // Reason if EEI not required (e.g., "NO EEI REQUIRED – FTR 30.37(a)")
        'customs_status',
        'customs_cleared_at',
        'shipment_type', // standard or consolidated
        'rate_source', // live_api, cached, fallback, manual
        'packing_option_id',
        'shipping_preference_option_id',
        'national_id',
        'handling_fee',
        'subtotal',
        'package_level_charges',
        'estimated_shipping_charges',
        'invoice_status',
        'packed_at',
        // Custom invoice for customs/paperless trade
        'use_custom_invoice',
        'custom_invoice_path',
        'generated_invoice_path',
    ];

    protected $casts = [
        'carrier_response' => 'array',
        'carrier_errors' => 'array',
        'selected_addon_ids' => 'array',
        'addon_charges' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'submitted_to_carrier_at' => 'datetime',
        'customs_cleared_at' => 'datetime',
        'packed_at' => 'datetime',
    ];

    protected $appends = ['total_billed_weight', 'total_volumetric_weight', 'operator_status'];

    /**
     * Accessor for international_shipping_option_id to handle legacy JSON-encoded values
     * Legacy values may be stored as "\"123\"" instead of just "123" or 123
     */
    public function getInternationalShippingOptionIdAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        // If it's already an integer, return as-is
        if (is_int($value)) {
            return $value;
        }

        // If it's a string, try to decode legacy JSON values
        if (is_string($value)) {
            $value = trim($value);

            // Try JSON decode for legacy values like "\"6948a662abc42\""
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                $value = $decoded;
            }

            // Strip any remaining quotes
            if (is_string($value)) {
                $value = trim($value, "\"'");
            }

            // Convert numeric strings to integers
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return $value;
    }


    /**
     * Get total billed weight for all packages in this shipment
     * Uses package-level computed attributes which aggregate from items
     */
    public function getTotalBilledWeightAttribute(): float
    {
        if (!$this->relationLoaded('packages')) {
            $this->load('packages.items');
        }

        return $this->packages->sum(fn($package) => $package->billed_weight);
    }

    /**
     * Get total volumetric weight for all packages
     */
    public function getTotalVolumetricWeightAttribute(): float
    {
        if (!$this->relationLoaded('packages')) {
            $this->load('packages.items');
        }

        return $this->packages->sum(fn($package) => $package->total_volumetric_weight);
    }

    /**
     * Get operator-friendly status that accounts for packed_at and carrier_status
     * Used for consistent categorization across all UIs
     * 
     * Categories:
     * - 'ready_to_pack' = Label received, needs packing
     * - 'awaiting_pickup' = Packed, waiting for carrier
     * - 'failed' = Carrier submission failed, needs retry
     * - Otherwise returns the raw status for further categorization
     */
    public function getOperatorStatusAttribute(): string
    {
        // Failed carrier submission - needs operator action
        if ($this->carrier_status === 'failed') {
            return 'failed';
        }

        // Packed and ready for pickup (status is label_ready, packed_at is set)
        if ($this->packed_at && in_array($this->status, ['label_ready', 'submitted'])) {
            return 'awaiting_pickup';
        }

        // Label received but not packed yet
        if ($this->carrier_status === 'submitted' && $this->label_data && !$this->packed_at) {
            return 'ready_to_pack';
        }

        // Fall through to raw status for other categories (in_transit, delivered, etc.)
        return $this->status ?? 'pending';
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'ship_packages', 'ship_id', 'package_id');
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the customer who owns this shipment
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function internationalShipping()
    {
        return $this->belongsTo(InternationalShippingOptions::class, 'international_shipping_option_id', 'id');
    }

    /**
     * Get the carrier service used for this shipment (new system)
     */
    public function carrierService()
    {
        return $this->belongsTo(CarrierService::class, 'carrier_service_id');
    }

    /**
     * Get selected addons for this shipment
     */
    public function getSelectedAddonsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->selected_addon_ids)) {
            return collect();
        }
        return CarrierAddon::whereIn('id', $this->selected_addon_ids)->get();
    }

    /**
     * Calculate total addon charges based on selected addons
     */
    public function calculateAddonCharges(): float
    {
        $total = 0;
        foreach ($this->selected_addons as $addon) {
            $total += $addon->calculatePrice($this->estimated_shipping_charges, $this->declared_value);
        }
        return $total;
    }

    /**
     * Get the effective carrier code from either new or legacy system
     */
    public function getEffectiveCarrierCode(): ?string
    {
        if ($this->carrierService) {
            return $this->carrierService->carrier_code;
        }
        return $this->carrier_name;
    }

    /**
     * Get the effective service code from either new or legacy system
     */
    public function getEffectiveServiceCode(): ?string
    {
        if ($this->carrierService) {
            return $this->carrierService->service_code;
        }
        return $this->carrier_service_type;
    }

    /**
     * Get all tracking events for this shipment
     */
    public function trackingEvents()
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('event_time', 'desc');
    }

    /**
     * Get the latest tracking event
     */
    public function latestTrackingEvent()
    {
        return $this->hasOne(ShipmentEvent::class)->latestOfMany('event_time');
    }
}
