<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyMilestoneRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'milestone_type',
        'milestone_value',
        'points_reward',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'milestone_value' => 'integer',
        'points_reward' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific milestone type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('milestone_type', $type);
    }

    /**
     * Get rules ordered by milestone value
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('milestone_value');
    }
}
