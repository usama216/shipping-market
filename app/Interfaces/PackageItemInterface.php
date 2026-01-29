<?php


namespace App\Interfaces;

interface PackageItemInterface
{
    public function store($data);
    public function insert($items, $package);
    public function getPackageById($packageId);

    public function itemsDeleteByPackageId($packageId);

    public function insertOne($item, $package);
}
