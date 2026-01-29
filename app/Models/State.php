<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * State Model - Parishes, provinces, districts within a country
 */
class State extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the country this state belongs to
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all cities in this state
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope to order by sort_order then name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
