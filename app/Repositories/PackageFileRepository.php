<?php

namespace App\Repositories;

use App\Interfaces\PackageFileInterface;
use App\Models\PackageFile;
use App\Traits\CommonTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PackageFileRepository implements PackageFileInterface
{
    use CommonTrait;
    protected $packageFile;

    public function __construct(PackageFile $packageFile)
    {
        $this->packageFile = $packageFile;
    }

    public function store($data)
    {
        return $this->packageFile->create($data);
    }

    public function insert($files, $package)
    {
        $this->packageFile->where('package_id', $package->id)->delete();
        $data = [];
        foreach ($files as $file) {
            $path = $this->addFile($file, 'storage/app/public/package_files/');
            $data[] = [
                'file' => $path,
                'package_id' => $package->id,
                'name' => $file->getClientOriginalName(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        return $this->packageFile->insert($data);
    }

    public function deletePackageFilesByPackageId($packageId)
    {
        // Get files to delete from storage
        $files = $this->packageFile->where('package_id', $packageId)->get();
        foreach ($files as $file) {
            if (Storage::exists($file->file)) {
                Storage::delete($file->file);
            }
        }
        return $this->packageFile->where('package_id', $packageId)->delete();
    }

    public function deletePackageFilesByItemId($itemId)
    {
        // Get files to delete from storage
        $files = $this->packageFile->where('package_item_id', $itemId)->get();
        foreach ($files as $file) {
            if (Storage::exists($file->file)) {
                Storage::delete($file->file);
            }
        }
        return $this->packageFile->where('package_item_id', $itemId)->delete();
    }

    public function getPackageFiles($packageId)
    {
        return $this->packageFile->where('package_id', $packageId)->get();
    }

    public function getItemFiles($itemId)
    {
        return $this->packageFile->where('package_item_id', $itemId)->get();
    }

    public function insertOne($data)
    {
        return $this->packageFile->create($data);
    }

    public function deleteFile($fileId)
    {
        $file = $this->packageFile->find($fileId);
        if ($file) {
            if (Storage::exists($file->file)) {
                Storage::delete($file->file);
            }
            return $file->delete();
        }
        return false;
    }
}
