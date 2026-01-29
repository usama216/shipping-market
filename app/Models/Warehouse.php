<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse Model
 * 
 * Represents a warehouse/fulfillment center location.
 * Used as the origin address for shipments and assigned to customers.
 */
class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'code',
        'company_name',
        'full_name',
        'country',
        'country_code',
        'state',
        'city',
        'zip',
        'address',
        'address_line_2',
        'phone_number',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_address'];

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Customers assigned to this warehouse
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Packages originating from this warehouse
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    // ==========================================
    // Accessors
    // ==========================================

    /**
     * Get full address as formatted string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    // ==========================================
    // Business Logic
    // ==========================================

    /**
     * Get the default warehouse
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Set this warehouse as default (unset others)
     */
    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Check if warehouse can be deactivated
     * Cannot deactivate if it's the only active warehouse
     */
    public function canDeactivate(): bool
    {
        if (!$this->is_active) {
            return false; // Already inactive
        }

        $activeCount = static::where('is_active', true)->count();
        return $activeCount > 1;
    }

    /**
     * Check if this is the default warehouse
     * Default warehouse cannot be deactivated
     */
    public function isProtected(): bool
    {
        return $this->is_default;
    }
}
