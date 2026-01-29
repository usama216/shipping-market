<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property bool $auto_apply
 * @property string $target_audience
 * @property bool $is_private
 */
class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'usage_limit',
        'per_customer_limit',
        'used_count',
        'start_date',
        'expiry_date',
        'is_active',
        'auto_apply',
        'target_audience',
        'is_private',
        'assigned_customer_id',
        'description',
        'selected_customer_ids'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'auto_apply' => 'boolean',
        'is_private' => 'boolean',
        'selected_customer_ids' => 'array',
    ];

    protected $appends = ['is_valid', 'is_expired', 'is_started'];

    /**
     * Get coupon usages
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Get the assigned customer (if coupon is assigned to a specific customer)
     */
    public function assignedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'assigned_customer_id');
    }

    /**
     * Check if coupon is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if coupon has started
     */
    public function getIsStartedAttribute(): bool
    {
        return !$this->start_date || $this->start_date->isPast();
    }

    /**
     * Check if coupon is valid (started, not expired, active, and within usage limit)
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active &&
            $this->is_started &&
            !$this->is_expired &&
            ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    /**
     * Check if coupon can be used for a given order amount
     * Note: minimum_order_amount check removed per requirements
     */
    public function canBeUsedForAmount(float $amount): bool
    {
        return $this->is_valid;
    }

    /**
     * Calculate discount amount for a given order amount
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if (!$this->canBeUsedForAmount($orderAmount)) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            $discount = ($orderAmount * $this->discount_value) / 100;
            return min($discount, $orderAmount); // Don't discount more than order amount
        }

        return min($this->discount_value, $orderAmount); // Fixed amount, but don't exceed order amount
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid coupons (not expired)
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>', Carbon::now());
        });
    }

    /**
     * Scope for available coupons
     * Note: usage_limit check removed per requirements
     */
    public function scopeAvailable($query)
    {
        return $query; // All active, valid coupons are available
    }
}
