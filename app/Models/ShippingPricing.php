<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPricing extends Model
{
    protected $table = 'shipping_pricing';

    protected $fillable = [
        'id',
        'service',
        'type',
        'range_value',
        'price',
    ];
}
