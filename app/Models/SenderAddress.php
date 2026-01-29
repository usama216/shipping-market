<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SenderAddress extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'full_name',
        'country',
        'country_code',
        'state',
        'city',
        'zip',
        'address',
        'address_line_2',
        'phone_number',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
