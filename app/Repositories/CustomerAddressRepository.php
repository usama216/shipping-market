<?php

namespace App\Repositories;

use App\Interfaces\CustomerAddressInterface;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;

class CustomerAddressRepository implements CustomerAddressInterface
{
    protected $customerAddress;

    public function __construct(CustomerAddress $customerAddress)
    {
        $this->customerAddress = $customerAddress;
    }

    /**
     * Get addresses for a customer
     */
    public function customerAddresses($customerId)
    {
        return $this->customerAddress
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data)
    {
        return $this->customerAddress->create($data);
    }

    public function update(CustomerAddress $address, array $data)
    {
        return $address->update($data);
    }

    public function delete(CustomerAddress $address)
    {
        return $address->delete();
    }

    public function findById($id, $customerId)
    {
        return $this->customerAddress->where('id', $id)
            ->where('customer_id', $customerId)
            ->first();
    }

    /**
     * Get default addresses for customer
     */
    public function getDefaultAddresses($customerId)
    {
        return $this->customerAddress
            ->where('customer_id', $customerId)
            ->where(function ($query) {
                $query->where('is_default_us', true)
                    ->orWhere('is_default_uk', true);
            })
            ->get();
    }

    /**
     * Unset default flag for customer addresses
     */
    public function unsetDefaultForType($customerId, $type)
    {
        $field = match ($type) {
            'us' => 'is_default_us',
            'uk' => 'is_default_uk',
            default => null
        };

        if (!$field) {
            return false;
        }

        return $this->customerAddress
            ->where('customer_id', $customerId)
            ->where($field, true)
            ->update([$field => false]);
    }

    /**
     * Get default address by type for authenticated customer
     */
    public function getDefaultAddressByType($type)
    {
        $customer = Auth::guard('customer')->user();
        $customerId = $customer?->id;

        $query = $this->customerAddress->query()
            ->where('customer_id', $customerId);

        if ($type == "is_uk") {
            $query->where('is_default_uk', true);
        } else if ($type == "is_us") {
            $query->where('is_default_us', true);
        }
        return $query->first();
    }
}
