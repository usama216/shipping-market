<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HSCodeRule extends Model
{
    protected $table = 'hs_code_rules';
    
    protected $fillable = [
        'category',
        'keywords',
        'materials',
        'usage_terms',
        'gender',
        'suggested_hs_code',
        'confidence_score',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'keywords' => 'array',
        'materials' => 'array',
        'usage_terms' => 'array',
        'confidence_score' => 'decimal:2',
        'priority' => 'integer',
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
     * Scope for category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('confidence_score', 'desc');
    }
}
