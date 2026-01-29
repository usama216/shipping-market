<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemInvoiceFile extends Model
{
    protected $fillable = [
        'package_item_id',
        'name',
        'file',
        'file_type',
    ];

    protected $appends = ['file_with_url'];

    /**
     * Get the package item that owns this invoice file
     */
    public function item()
    {
        return $this->belongsTo(PackageItem::class, 'package_item_id');
    }

    /**
     * Get the full URL for the file
     */
    public function getFileWithUrlAttribute()
    {
        $path = $this->file;

        // Normalize path by removing legacy prefixes
        if (str_starts_with($path, 'storage/')) {
            $path = preg_replace('#^storage/app/public/#', '', $path);
        } elseif (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        }

        return asset('storage/' . $path);
    }
}
