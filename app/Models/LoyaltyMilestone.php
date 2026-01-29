<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'milestone_type',
        'milestone_value',
        'points_awarded',
        'achieved_at',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
    ];

    /**
     * Get the customer that achieved this milestone
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
