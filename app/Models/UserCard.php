<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCard extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'card_id',
        'exp_month',
        'exp_year',
        'brand',
        'last4',
        'card_holder_name',
        'address_line1',
        'address_line2',
        'country',
        'state',
        'city',
        'postal_code',
        'country_code',
        'phone_number',
        'is_default',
    ];

    /**
     * @deprecated Use customer() instead for customer cards
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the customer who owns this card
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}

