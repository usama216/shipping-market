<?php


namespace App\Interfaces;

interface PackageFileInterface
{
    public function store($data);
    public function insert($files, $package);
    public function deletePackageFilesByPackageId($packageId);
    public function deletePackageFilesByItemId($itemId);
    public function getPackageFiles($packageId);
    public function getItemFiles($itemId);
    public function insertOne($data);
    public function deleteFile($fileId);
}
