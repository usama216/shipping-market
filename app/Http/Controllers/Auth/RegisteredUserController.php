<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Warehouse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     * Creates a Customer record in the customers table.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'country_code' => 'required|string|max:10', // Supports internal codes like BQ-BO
            'year' => 'required|digits:4',
            'day' => 'required|numeric|min:1|max:31',
            'month' => 'required|string',
            'email' => 'required|string|lowercase|email|max:255|unique:customers,email',
            'tax_id' => 'nullable|string',
            'password' => ['required', Rules\Password::defaults()],
            'ref' => 'nullable|string|max:10', // Referral code
        ]);

        $suite = Customer::generateSuiteNumber($request->country_code, $request->country);
        $monthNumber = date_parse($request->month)['month'];
        $dateOfBirth = sprintf('%04d-%02d-%02d', $request->year, $monthNumber, $request->day);

        // Check for referrer
        $referredById = null;
        if ($request->ref) {
            $referrer = Customer::where('referral_code', $request->ref)->first();
            if ($referrer) {
                $referredById = $referrer->id;
            }
        }

        $customer = Customer::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'zip_code' => $request->zip_code,
            'state' => $request->state,
            'city' => $request->city,
            'country' => $request->country,
            'tax_id' => $request->tax_id,
            'suite' => $suite,
            'warehouse_id' => Warehouse::getDefault()?->id,
            'date_of_birth' => $dateOfBirth,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'email_verified_at' => now(),
            'referral_code' => Customer::generateReferralCode(),
            'referred_by_id' => $referredById,
        ]);

        // Create default shipping address from registration data
        // This ensures the registration address appears in Address Book and Checkout
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_name' => 'Primary Address',
            'full_name' => trim($request->first_name . ' ' . $request->last_name),
            'address_line_1' => $request->address ?? '',
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->zip_code,
            'country' => $request->country,
            'country_code' => $request->country_code, // Added country_code
            'phone_number' => $request->phone,
            'is_default_us' => true,
            'is_default_uk' => false,
        ]);

        // event(new Registered($customer));
        // Auth::guard('customer')->login($customer);
        
        return redirect()->route('login')->with('success', 'Registration successful! You can now log in.');



        // Redirect to verification page - email will be sent via Registered event
        // return redirect(route('customer.verification.notice', absolute: false));
    }
}


