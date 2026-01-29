<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\CustomerAddress;
use App\Repositories\CustomerAddressRepository;
use App\Traits\CommonTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use CommonTrait;
    protected $customerAddressRepository;
    public function __construct(CustomerAddressRepository $customerAddressRepository)
    {
        $this->customerAddressRepository = $customerAddressRepository;
    }
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::back();
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // Determine which guard is authenticated
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            Auth::guard('customer')->logout();
            $customer->delete();
        } else {
            $user = $request->user();
            Auth::logout();
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/login');
    }

    public function customerProfile(Request $request)
    {
        return Inertia::render('Customers/Profile/EditTabs/AccountSetting', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }
    public function addressBook()
    {
        $customer = Auth::guard('customer')->user();
        $customerAddresses = $this->customerAddressRepository->customerAddresses($customer->id);
        return Inertia::render('Customers/Profile/EditTabs/AddressBook', ['customerAddresses' => $customerAddresses]);
    }
    public function addressStore(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if ($customer->addresses()->count() >= 12) {
            return Redirect::back()->withErrors(['message' => 'You can only save up to 12 addresses.']);
        }

        $validated = $request->validate([
            'address_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
        ]);
        try {
            $validated['customer_id'] = $customer->id;
            $customer->addresses()->create($validated);

            return Redirect::back()->with('alert', 'Address added successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function setDefault(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:us,uk,both',
        ]);

        $customer = Auth::guard('customer')->user();
        $address = $customer->addresses()->findOrFail($id);

        if ($request->type === 'us' || $request->type === 'both') {
            $customer->addresses()->where('is_default_us', true)->update(['is_default_us' => false]);
        }

        if ($request->type === 'uk' || $request->type === 'both') {
            $customer->addresses()->where('is_default_uk', true)->update(['is_default_uk' => false]);
        }

        $address->update([
            'is_default_us' => in_array($request->type, ['us', 'both']),
            'is_default_uk' => in_array($request->type, ['uk', 'both']),
        ]);

        return Redirect::route('customer.account.addressBook')->with('alert', "Default address updated.");
    }

    public function updateCustomerAddress(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'address_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);
        try {
            $customer = Auth::guard('customer')->user();
            $address = CustomerAddress::where('id', $id)->where('customer_id', $customer->id)->firstOrFail();

            $address->update($request->only([
                'full_name',
                'address_name',
                'address_line_1',
                'city',
                'state',
                'postal_code',
                'country',
                'phone_number',
            ]));
            return Redirect::route('customer.account.addressBook')->with('alert', "Address updated successfully.");
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['message' => $e->getMessage()]);
        }
    }



}
