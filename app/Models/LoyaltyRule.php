<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'spend_amount',
        'earn_points',
        'redeem_points',
        'redeem_value',
        'is_active'
    ];

    protected $casts = [
        'spend_amount' => 'decimal:2',
        'redeem_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default loyalty rule
     */
    public static function getDefaultRule(): ?self
    {
        return static::active()->first();
    }

    /**
     * Calculate points to earn for a given amount
     */
    public function calculateEarnPoints(float $amount): int
    {
        if ($this->spend_amount <= 0) {
            return 0;
        }

        return (int) floor($amount / $this->spend_amount) * $this->earn_points;
    }

    /**
     * Calculate discount value for given points
     */
    public function calculateRedeemValue(int $points): float
    {
        if ($this->redeem_points <= 0) {
            return 0;
        }

        return ($points / $this->redeem_points) * $this->redeem_value;
    }

    /**
     * Get maximum points that can be redeemed for a given amount
     */
    public function getMaxRedeemablePoints(float $amount): int
    {
        if ($this->redeem_points <= 0) {
            return 0;
        }

        $maxValue = $amount; // Don't redeem more than the order amount
        return (int) floor(($maxValue / $this->redeem_value) * $this->redeem_points);
    }
}
