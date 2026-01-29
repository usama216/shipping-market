<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_lifetime_spend',
        'earn_multiplier',
        'color',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_lifetime_spend' => 'decimal:2',
            'earn_multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // ==========================================
    // Static Methods
    // ==========================================

    /**
     * Get the tier for a given lifetime spend amount
     */
    public static function getForSpend(float $lifetimeSpend): ?self
    {
        return static::active()
            ->where('min_lifetime_spend', '<=', $lifetimeSpend)
            ->orderBy('min_lifetime_spend', 'desc')
            ->first();
    }

    /**
     * Get the default (lowest) tier
     */
    public static function getDefaultTier(): ?self
    {
        return static::active()
            ->ordered()
            ->first();
    }

    /**
     * Get the next tier above the given tier
     */
    public static function getNextTier(self $currentTier): ?self
    {
        return static::active()
            ->where('min_lifetime_spend', '>', $currentTier->min_lifetime_spend)
            ->orderBy('min_lifetime_spend', 'asc')
            ->first();
    }

    /**
     * Get all tiers for display
     */
    public static function getAllTiers()
    {
        return static::active()->ordered()->get();
    }

    // ==========================================
    // Instance Methods
    // ==========================================

    /**
     * Calculate points with tier multiplier
     */
    public function applyMultiplier(int $basePoints): int
    {
        return (int) round($basePoints * $this->earn_multiplier);
    }

    /**
     * Get progress percentage to next tier
     */
    public function getProgressToNext(float $currentSpend): array
    {
        $nextTier = static::getNextTier($this);

        if (!$nextTier) {
            return [
                'next_tier' => null,
                'spend_required' => 0,
                'spend_remaining' => 0,
                'percentage' => 100,
            ];
        }

        $spendRequired = $nextTier->min_lifetime_spend - $this->min_lifetime_spend;
        $spendProgress = $currentSpend - $this->min_lifetime_spend;
        $percentage = min(100, ($spendProgress / $spendRequired) * 100);

        return [
            'next_tier' => $nextTier,
            'spend_required' => $spendRequired,
            'spend_remaining' => max(0, $nextTier->min_lifetime_spend - $currentSpend),
            'percentage' => round($percentage, 1),
        ];
    }
}
