<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Customer;
use App\Payments\Stripe;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    protected $customerRepository;
    
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function index(Request $request)
    {
        $users = $this->customerRepository->customers_paginated($request);
        return Inertia::render('Admin/Customers/Report', ['users' => $users, 'filters' => ['search' => $request->input('search', '')]]);
    }

    public function create()
    {
        return Inertia::render('Admin/Customers/Create');
    }

    public function store(UserStoreRequest $request)
    {
        try {
            DB::beginTransaction();
            $this->customerRepository->store($request->all());
            DB::commit();
            return Redirect::route('admin.customers')->with('alert', 'Customer created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Admin/Customers/EditTabs/Basic', ['user' => $customer]);
    }

    public function update(UserUpdateRequest $request, Customer $customer)
    {
        try {
            DB::beginTransaction();
            $this->customerRepository->update($customer->id, $request->all());
            DB::commit();
            return Redirect::back()->with('alert', 'Customer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Delete a customer
     */
    public function destroy(Customer $customer)
    {
        try {
            DB::beginTransaction();
            
            // Check if customer has any shipments or packages
            $hasShipments = $customer->shipments()->exists();
            $hasPackages = $customer->packages()->exists();
            
            if ($hasShipments || $hasPackages) {
                return Redirect::back()->withErrors([
                    'message' => 'Cannot delete customer with existing shipments or packages. Please archive instead.'
                ]);
            }
            
            // Delete related data
            $customer->addresses()->delete();
            $customer->transactions()->delete();
            
            // Delete customer
            $customer->delete();
            
            DB::commit();
            return Redirect::route('admin.customers')->with('alert', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Failed to delete customer: ' . $e->getMessage()]);
        }
    }
}
