<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternationalShippingOptions extends Model
{
    const DHL_EXPRESS = 1;
    const FEDEX_ECONOMY = 2;
    const SEA_FREIGHT = 3;
    const AIR_CARGO = 4;

    const DHL_NAME = 'DHL';
    const FEDEX_NAME = 'FDX E';
    const SEA_FREIGHT_NAME = 'Seafreight';
    const AIR_CARGO_NAME = 'Aircargo';
    protected $fillable = ['title', 'description'];

}
