<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HSCode extends Model
{
    protected $table = 'hs_codes';
    
    protected $fillable = [
        'code',
        'description',
        'chapter',
        'heading',
        'subheading',
        'category',
        'active_year',
        'is_active',
        'keywords',
        'materials',
        'notes',
    ];

    protected $casts = [
        'keywords' => 'array',
        'materials' => 'array',
        'is_active' => 'boolean',
        'active_year' => 'integer',
    ];

    /**
     * Scope for active codes
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
     * Scope for searching by keywords
     */
    public function scopeSearchKeywords($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
              ->orWhereJsonContains('keywords', $search);
        });
    }
}
