<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerAddress Model
 * 
 * Represents a customer's shipping address.
 * Renamed from UserAddress for clarity with customer-centric architecture.
 */
class CustomerAddress extends Model
{
    protected $table = 'customer_addresses';

    protected $guarded = ['id'];

    protected $fillable = [
        'customer_id',
        'address_name',
        'full_name',
        'address_line_1',
        'address_line_2',
        'country_id',
        'country',
        'state_id',
        'state',
        'city_id',
        'city',
        'postal_code',
        'country_code',
        'phone_number',
        'is_default_us',
        'is_default_uk',
    ];

    protected $casts = [
        'is_default_us' => 'boolean',
        'is_default_uk' => 'boolean',
    ];

    /**
     * Get the customer that owns the address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the country.
     */
    public function countryModel(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the state.
     */
    public function stateModel(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Get the city.
     */
    public function cityModel(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Scope to get default US addresses
     */
    public function scopeDefaultUs($query)
    {
        return $query->where('is_default_us', true);
    }

    /**
     * Scope to get default UK addresses
     */
    public function scopeDefaultUk($query)
    {
        return $query->where('is_default_uk', true);
    }

    /**
     * Get full address as a formatted string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Check if this address is a default for any type
     */
    public function isDefault(): bool
    {
        return $this->is_default_us || $this->is_default_uk;
    }

    /**
     * Get the default types for this address
     */
    public function getDefaultTypes(): array
    {
        $types = [];

        if ($this->is_default_us) {
            $types[] = 'us';
        }

        if ($this->is_default_uk) {
            $types[] = 'uk';
        }

        return $types;
    }

    /**
     * Get the default type display text
     */
    public function getDefaultTypeText(): string
    {
        $types = $this->getDefaultTypes();

        if (empty($types)) {
            return '';
        }

        if (count($types) === 2) {
            return 'US & UK';
        }

        return strtoupper($types[0]);
    }
}
