<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageSpecialRequestPhoto extends Model
{
    protected $fillable = [
        'package_special_request_response_id',
        'name',
        'file',
        'file_type',
    ];

    protected $appends = ['file_with_url'];

    /**
     * Get the response this photo belongs to
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(PackageSpecialRequestResponse::class, 'package_special_request_response_id');
    }

    /**
     * Get file URL
     */
    public function getFileWithUrlAttribute()
    {
        $path = $this->file;
        
        if (str_starts_with($path, 'storage/')) {
            $path = preg_replace('#^storage/app/public/#', '', $path);
        } elseif (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        }

        return asset('storage/' . $path);
    }
}
