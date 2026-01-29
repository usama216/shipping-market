<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageSpecialRequestResponse extends Model
{
    protected $fillable = [
        'package_id',
        'special_request_id',
        'admin_note',
        'admin_id',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * Get the package this response belongs to
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the special request this response is for
     */
    public function specialRequest(): BelongsTo
    {
        return $this->belongsTo(SpecialRequest::class);
    }

    /**
     * Get the admin who created this response
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'admin_id');
    }

    /**
     * Get photos for this response
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PackageSpecialRequestPhoto::class, 'package_special_request_response_id');
    }
}
