<?php

namespace App\Repositories;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * CustomerRepository
 * 
 * Handles all customer-related database operations.
 * Replaces the customer() scope pattern from the old UserRepository.
 */
class CustomerRepository
{
    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Get all active customers
     */
    public function customers()
    {
        return $this->customer->active()->get();
    }

    /**
     * Find customer by ID
     */
    public function findById($customerId)
    {
        return $this->customer->find($customerId);
    }

    /**
     * Find customer by email
     */
    public function findByEmail($email)
    {
        return $this->customer->where('email', $email)->first();
    }

    /**
     * Update customer
     */
    public function update($customerId, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if (isset($data['date_of_birth'])) {
            $data['date_of_birth'] = Carbon::parse($data['date_of_birth'])->format('Y-m-d');
        }
        return $this->customer->where('id', $customerId)->update($data);
    }

    /**
     * Get paginated list of customers with search
     */
    public function customers_paginated(Request $request, $perPage = 25)
    {
        $query = $this->customer->query();

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('suite', 'like', $search)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$search]); // Search full name
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Create new customer
     */
    public function store($data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        if (isset($data['date_of_birth']) && $data['date_of_birth']) {
            $data['date_of_birth'] = Carbon::parse($data['date_of_birth'])->format('Y-m-d');
        }

        // Generate suite number if not provided
        if (empty($data['suite'])) {
            $countryCode = $data['country_code'] ?? null;
            $countryName = $data['country'] ?? null;
            $data['suite'] = Customer::generateSuiteNumber($countryCode, $countryName);
        }

        // Auto-verify email for admin-created customers
        $data['email_verified_at'] = now();

        return $this->customer->create($data);
    }

    /**
     * Get customer count
     */
    public function customerCount()
    {
        return $this->customer->active()->count();
    }

    /**
     * Get customers for dropdown/select
     */
    public function getCustomersForSelect()
    {
        return $this->customer->active()
            ->select('id', 'first_name', 'last_name', 'email', 'suite')
            ->orderBy('first_name')
            ->get();
    }
}
