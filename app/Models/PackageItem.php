<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageItem extends Model
{
    protected $fillable = [
        'package_id',
        'title',
        'description',
        'hs_code',
        'eei_code', // Electronic Export Information code
        // 'country_of_origin' - REMOVED per user request
        'material',
        'manufacturer',
        'item_note',
        'quantity',
        'value_per_unit',
        'weight_per_unit',
        'weight_unit',
        'total_line_value',
        'total_line_weight',
        // Item-level dimensions
        'length',
        'width',
        'height',
        'dimension_unit',
        // Item classification
        'is_dangerous',
        'un_code', // UN number for dangerous goods (e.g., UN1202, UN1263)
        'is_fragile',
        'is_oversized',
        'classification_notes',
    ];

    protected $casts = [
        'is_dangerous' => 'boolean',
        'is_fragile' => 'boolean',
        'is_oversized' => 'boolean',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'value_per_unit' => 'decimal:2',
        'weight_per_unit' => 'decimal:2',
        'total_line_value' => 'decimal:2',
        'total_line_weight' => 'decimal:2',
    ];

    protected $appends = ['volumetric_weight', 'has_dimensions', 'has_classification_flags'];

    /**
     * Calculate item volumetric weight based on dimensions
     * Formula: L x W x H / divisor (139 for inches, 5000 for cm)
     */
    public function getVolumetricWeightAttribute(): ?float
    {
        if (!$this->length || !$this->width || !$this->height) {
            return null;
        }

        $volume = $this->length * $this->width * $this->height;
        $divisor = $this->dimension_unit === 'in' ? 139 : 5000;

        return round($volume / $divisor, 2);
    }

    /**
     * Check if item has complete dimensions
     */
    public function getHasDimensionsAttribute(): bool
    {
        return $this->length && $this->width && $this->height;
    }

    /**
     * Check if item has any classification flags set
     */
    public function getHasClassificationFlagsAttribute(): bool
    {
        return $this->is_dangerous || $this->is_fragile || $this->is_oversized;
    }

    public function packageFiles()
    {
        return $this->hasMany(PackageFile::class, 'package_item_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get invoice files for this item
     */
    public function invoiceFiles()
    {
        return $this->hasMany(ItemInvoiceFile::class, 'package_item_id');
    }
}
