<?php

namespace App\Interfaces;

use App\Models\Ship;
use Illuminate\Http\Request;

interface ShipInterface
{

    public function getShipments(Request $request);
    public function create(array $data);

    public function update(Ship $ship, array $data);

    public function findById($id);

    public function delete(Ship $ship);

    public function getAllShips();

    public function getShipsByUserId($userId);

    public function getShipsWithPackages();

    public function getShipByTrackingNumber($trackingNumber);

    public function getShipsByStatus($status);

    public function getShipDetails($ship);

    public function deletePendingShipment($userId);


}
