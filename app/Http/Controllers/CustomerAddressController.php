<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Models\CustomerAddress;
use App\Repositories\CustomerAddressRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAddressController extends Controller
{
    protected $customerAddressRepository;

    public function __construct(CustomerAddressRepository $customerAddressRepository)
    {
        $this->customerAddressRepository = $customerAddressRepository;
    }

    /**
     * Display a listing of the customer's addresses.
     */
    public function index(): Response
    {
        $customer = Auth::guard('customer')->user();
        $customerAddresses = $this->customerAddressRepository->customerAddresses($customer->id);

        return Inertia::render('Customers/Profile/EditTabs/AddressBook', [
            'customerAddresses' => $customerAddresses
        ]);
    }

    /**
     * Store a newly created address.
     */
    public function store(AddressRequest $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        // Check if customer has reached the limit of 12 addresses
        if ($customer->addresses()->count() >= 12) {
            return redirect()->back()->withErrors(['message' => 'You can only save up to 12 addresses.']);
        }

        try {
            $data = $request->validated();
            $data['customer_id'] = $customer->id;

            $this->customerAddressRepository->create($data);

            return redirect()->back()->with('alert', 'Address added successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Failed to add address. Please try again.']);
        }
    }

    /**
     * Update the specified address.
     */
    public function update(AddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $data = $request->validated();

            $this->customerAddressRepository->update($address, $data);

            return redirect()->back()->with('success', 'Address updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Failed to update address. Please try again.']);
        }
    }

    /**
     * Remove the specified address.
     */
    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->customerAddressRepository->delete($address);

            return redirect()->back()->with('success', 'Address deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Failed to delete address. Please try again.']);
        }
    }

    /**
     * Set an address as default for US or UK.
     * Also updates ShippingPreferences.user_address_id for rate calculations.
     */
    public function setDefault(Request $request, CustomerAddress $address): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:us,uk,both',
        ]);

        $customer = Auth::guard('customer')->user();
        // Ensure the address belongs to the authenticated customer
        if ($address->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Unset previous defaults based on type
            if ($request->type === 'us' || $request->type === 'both') {
                $this->customerAddressRepository->unsetDefaultForType($customer->id, 'us');
            }

            if ($request->type === 'uk' || $request->type === 'both') {
                $this->customerAddressRepository->unsetDefaultForType($customer->id, 'uk');
            }

            // Set new default
            $address->update([
                'is_default_us' => in_array($request->type, ['us', 'both']),
                'is_default_uk' => in_array($request->type, ['uk', 'both']),
            ]);

            // Also update ShippingPreferences.user_address_id for rate calculation fallback
            // This ensures getAllShippingRates can find the default destination
            \App\Models\ShippingPreferences::updateOrCreate(
                ['customer_id' => $customer->id],
                ['user_address_id' => $address->id]
            );

            return redirect()->back()->with('success', "Address set as default successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Failed to set default address. Please try again.']);
        }
    }
}
