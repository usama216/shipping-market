<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageFile extends Model
{
    protected $fillable = [
        'package_id',
        'package_item_id',
        'name',
        'file'
    ];

    protected $appends = ['file_with_url'];

    public function packageItem()
    {
        return $this->belongsTo(PackageItem::class, 'package_item_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }

    public function getFileWithUrlAttribute()
    {
        // Handle both legacy paths (with storage/) and new paths (relative to public disk)
        $path = $this->file;

        // If path already starts with 'storage/' or '/storage/', normalize it
        if (str_starts_with($path, 'storage/')) {
            // Legacy path like 'storage/app/public/package_items/file.jpg' -> extract relative part
            $path = preg_replace('#^storage/app/public/#', '', $path);
        } elseif (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9); // Remove leading '/storage/'
        }

        // Return proper URL: /storage/relative_path
        return asset('storage/' . $path);
    }
}
