<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CarrierCommissionSetting - Stores the commission percentage for each carrier
 * 
 * This is a singleton model - there should only be one record in the table.
 * Each carrier (DHL, FedEx, UPS) has its own commission percentage.
 */
class CarrierCommissionSetting extends Model
{
    protected $fillable = [
        'dhl_commission_percentage',
        'fedex_commission_percentage',
        'ups_commission_percentage',
        'dangerous_goods_charge',
        'fragile_item_charge',
        'oversized_item_charge',
    ];

    protected $casts = [
        'dhl_commission_percentage' => 'decimal:2',
        'fedex_commission_percentage' => 'decimal:2',
        'ups_commission_percentage' => 'decimal:2',
        'dangerous_goods_charge' => 'decimal:2',
        'fragile_item_charge' => 'decimal:2',
        'oversized_item_charge' => 'decimal:2',
    ];

    /**
     * Get the current commission setting (singleton pattern)
     */
    public static function getCurrent(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'dhl_commission_percentage' => 15.00,
                'fedex_commission_percentage' => 15.00,
                'ups_commission_percentage' => 15.00,
                'dangerous_goods_charge' => 0.00,
                'fragile_item_charge' => 0.00,
                'oversized_item_charge' => 0.00,
            ]
        );
    }

    /**
     * Get the commission multiplier for a specific carrier (e.g., 15% = 1.15)
     * 
     * @param string $carrier Carrier code: 'dhl', 'fedex', or 'ups'
     * @return float
     */
    public function getMultiplier(string $carrier): float
    {
        $percentage = $this->getCommissionPercentage($carrier);
        return 1 + ($percentage / 100);
    }

    /**
     * Get the commission percentage for a specific carrier
     * 
     * @param string $carrier Carrier code: 'dhl', 'fedex', or 'ups'
     * @return float
     */
    public function getCommissionPercentage(string $carrier): float
    {
        $carrier = strtolower($carrier);
        
        return match ($carrier) {
            'dhl' => (float) $this->dhl_commission_percentage,
            'fedex' => (float) $this->fedex_commission_percentage,
            'ups' => (float) $this->ups_commission_percentage,
            default => (float) $this->dhl_commission_percentage, // Default to DHL if unknown
        };
    }

    /**
     * Update the commission percentages for all carriers
     * 
     * @param array $commissions Array with keys: dhl_commission_percentage, fedex_commission_percentage, ups_commission_percentage
     * @return self
     */
    public static function updateCommissions(array $commissions): self
    {
        $setting = static::getCurrent();
        $setting->update($commissions);
        return $setting->fresh();
    }

    /**
     * Get all commission percentages as an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dhl_commission_percentage' => (float) $this->dhl_commission_percentage,
            'fedex_commission_percentage' => (float) $this->fedex_commission_percentage,
            'ups_commission_percentage' => (float) $this->ups_commission_percentage,
            'dangerous_goods_charge' => (float) $this->dangerous_goods_charge,
            'fragile_item_charge' => (float) $this->fragile_item_charge,
            'oversized_item_charge' => (float) $this->oversized_item_charge,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get classification charge for a specific type
     * 
     * @param string $type 'dangerous', 'fragile', or 'oversized'
     * @return float
     */
    public function getClassificationCharge(string $type): float
    {
        return match (strtolower($type)) {
            'dangerous' => (float) $this->dangerous_goods_charge,
            'fragile' => (float) $this->fragile_item_charge,
            'oversized' => (float) $this->oversized_item_charge,
            default => 0.00,
        };
    }
}
