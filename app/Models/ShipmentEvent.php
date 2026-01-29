<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    protected $fillable = [
        'ship_id',
        'status',
        'description',
        'location',
        'source',
        'event_time',
        'raw_data',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Status constants for tracking events
     */
    const STATUS_PENDING = 'pending';
    const STATUS_LABEL_CREATED = 'label_created';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_EXCEPTION = 'exception';
    const STATUS_RETURNED = 'returned';

    /**
     * Get all available statuses with labels
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_LABEL_CREATED => 'Label Created',
            self::STATUS_PICKED_UP => 'Picked Up',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_EXCEPTION => 'Exception',
            self::STATUS_RETURNED => 'Returned',
        ];
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the shipment this event belongs to
     */
    public function ship()
    {
        return $this->belongsTo(Ship::class);
    }
}
