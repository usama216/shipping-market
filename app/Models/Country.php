<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country Model - Caribbean territories for address selection
 */
class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'carrier_code',     // ISO code for carrier APIs (e.g., BQ for Bonaire/Saba/Sint Eustatius)
        'phone_prefix',
        'has_postal_code',
        'fedex_accepts_state', // FedEx-specific: whether state/province is accepted
        'dhl_accepts_state',   // DHL-specific: whether state/province is accepted
        'ups_accepts_state',   // UPS-specific: whether state/province is accepted
        'postal_code_format',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'has_postal_code' => 'boolean',
        'fedex_accepts_state' => 'boolean',
        'dhl_accepts_state' => 'boolean',
        'ups_accepts_state' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the carrier-compatible country code (ISO 2-letter).
     * For countries with internal codes (e.g., BQ-BO for Bonaire),
     * this returns the actual ISO code (BQ) for carrier API calls.
     */
    public function getCarrierCode(): string
    {
        return $this->carrier_code ?? $this->code;
    }

    /**
     * Get all states/parishes in this country
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope to get only active countries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order then name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
