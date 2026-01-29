<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * City Model - Towns/cities with optional postal codes
 */
class City extends Model
{
    protected $fillable = [
        'state_id',
        'name',
        'postal_code',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the state this city belongs to
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Scope to order by sort_order then name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this city has a postal code
     */
    public function hasPostalCode(): bool
    {
        return !empty($this->postal_code);
    }
}
