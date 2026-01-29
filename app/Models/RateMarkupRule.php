<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RateMarkupRule - Admin-configured rate adjustments
 * 
 * Allows adding percentage or fixed markups to carrier rates
 * based on weight ranges, carriers, or destinations.
 */
class RateMarkupRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'carrier',
        'service_code',
        'min_weight',
        'max_weight',
        'destination_country',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCarrier($query, ?string $carrier)
    {
        return $query->where(function ($q) use ($carrier) {
            $q->whereNull('carrier')
                ->orWhere('carrier', strtolower($carrier));
        });
    }

    public function scopeForWeight($query, float $weight)
    {
        return $query->where(function ($q) use ($weight) {
            $q->where(function ($sub) use ($weight) {
                $sub->whereNull('min_weight')
                    ->orWhere('min_weight', '<=', $weight);
            })->where(function ($sub) use ($weight) {
                $sub->whereNull('max_weight')
                    ->orWhere('max_weight', '>=', $weight);
            });
        });
    }

    public function scopeForDestination($query, ?string $countryCode)
    {
        return $query->where(function ($q) use ($countryCode) {
            $q->whereNull('destination_country')
                ->orWhere('destination_country', strtoupper($countryCode));
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')->orderBy('name');
    }

    // ============================================
    // METHODS
    // ============================================

    /**
     * Apply this rule to a base price
     */
    public function applyTo(float $basePrice): float
    {
        if ($this->type === 'percentage') {
            return $basePrice * ($this->value / 100);
        }

        return (float) $this->value;
    }

    /**
     * Check if this rule applies to given criteria
     */
    public function appliesTo(string $carrier, float $weight, ?string $destinationCountry = null): bool
    {
        // Check carrier match
        if ($this->carrier && strtolower($this->carrier) !== strtolower($carrier)) {
            return false;
        }

        // Check weight range
        if ($this->min_weight && $weight < $this->min_weight) {
            return false;
        }
        if ($this->max_weight && $weight > $this->max_weight) {
            return false;
        }

        // Check destination
        if ($this->destination_country && $destinationCountry) {
            if (strtoupper($this->destination_country) !== strtoupper($destinationCountry)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format for frontend display
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => $this->type === 'percentage' ? 'Percentage' : 'Fixed Amount',
            'value' => $this->value,
            'value_display' => $this->type === 'percentage'
                ? number_format((float) $this->value, 1) . '%'
                : '$' . number_format((float) $this->value, 2),
            'carrier' => $this->carrier,
            'carrier_display' => $this->carrier ? strtoupper($this->carrier) : 'All Carriers',
            'service_code' => $this->service_code,
            'min_weight' => $this->min_weight,
            'max_weight' => $this->max_weight,
            'weight_range' => $this->formatWeightRange(),
            'destination_country' => $this->destination_country,
            'destination_display' => $this->destination_country ?: 'All Countries',
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->format('M j, Y'),
        ];
    }

    private function formatWeightRange(): string
    {
        if (!$this->min_weight && !$this->max_weight) {
            return 'Any weight';
        }
        if ($this->min_weight && !$this->max_weight) {
            return "≥ {$this->min_weight} lbs";
        }
        if (!$this->min_weight && $this->max_weight) {
            return "≤ {$this->max_weight} lbs";
        }
        return "{$this->min_weight} - {$this->max_weight} lbs";
    }
}
