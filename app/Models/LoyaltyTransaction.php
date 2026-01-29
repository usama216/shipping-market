<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'transaction_id',
        'type',
        'points',
        'amount',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the customer associated with this loyalty transaction
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * @deprecated Use customer() instead
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction associated with this loyalty transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for earning transactions
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', 'earn');
    }

    /**
     * Scope for redemption transactions
     */
    public function scopeRedemptions($query)
    {
        return $query->where('type', 'redeem');
    }

    /**
     * Get formatted points with sign
     */
    public function getFormattedPointsAttribute(): string
    {
        $sign = $this->type === 'earn' ? '+' : '-';
        return $sign . $this->points;
    }
}
