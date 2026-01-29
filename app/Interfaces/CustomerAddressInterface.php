<?php

namespace App\Interfaces;

use App\Models\CustomerAddress;

interface CustomerAddressInterface
{
    public function customerAddresses($customerId);

    public function create(array $data);

    public function update(CustomerAddress $address, array $data);

    public function delete(CustomerAddress $address);

    public function findById($id, $customerId);

    public function getDefaultAddresses($customerId);

    public function unsetDefaultForType($customerId, $type);
}
