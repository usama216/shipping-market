<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPreferences extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'user_address_id',
        'preferred_ship_method',
        'international_shipping_option',
        'shipping_preference_option',
        'packing_option',
        'proforma_invoice_options',
        'login_option',
        'tax_id',
        'additional_email',
        'maximum_weight_per_box',
    ];

    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'user_address_id', 'id');
    }

    /**
     * Get the customer who owns these preferences
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}

