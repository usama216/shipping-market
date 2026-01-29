<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPreferenceOption extends Model
{
    protected $table = "shipping_preference_options";

    protected $fillable = ['title', 'description', 'price'];
}
