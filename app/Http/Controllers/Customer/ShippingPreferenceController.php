<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Repositories\ShippingPreferencesRepository;
use App\Repositories\CustomerAddressRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ShippingPreferenceController extends Controller
{
    protected $shippingPreferenceRepository, $customerAddressRepository;
    public function __construct(ShippingPreferencesRepository $shippingPreferenceRepository, CustomerAddressRepository $customerAddressRepository)
    {
        $this->shippingPreferenceRepository = $shippingPreferenceRepository;
        $this->customerAddressRepository = $customerAddressRepository;
    }

    public function index()
    {
        $customer = Auth::guard('customer')->user();
        return Inertia::render('Customers/Profile/EditTabs/ShippingPreferences', [
            'shippingPreference' => $this->shippingPreferenceRepository->getShippingPreference($customer->id),
            'preferredShipMethods' => $this->shippingPreferenceRepository->getPreferredShipMethod(),
            'internationalShippingOptions' => $this->shippingPreferenceRepository->getInternationalShippingOptions(),
            'shippingPreferenceOptions' => $this->shippingPreferenceRepository->shippingPreferenceOptions(),
            'packingOptions' => $this->shippingPreferenceRepository->getPackingOption(),
            'proformaInvoiceOptions' => $this->shippingPreferenceRepository->getProformaInvoiceOptions(),
            'loginOptions' => $this->shippingPreferenceRepository->getLoginOptions(),
            'addresses' => $this->customerAddressRepository->getDefaultAddresses($customer->id),
        ]);
    }

    public function setPreferAddress(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            DB::beginTransaction();
            $address = $this->customerAddressRepository->getDefaultAddressByType($request->address_type);
            if ($address) {
                $this->shippingPreferenceRepository->updateOrCreateForCustomer($customer->id, ['user_address_id' => $address->id]);
            }
            DB::commit();
            return Redirect::route('customer.shippingPreferences.preference')->with('alert', 'Set address preference successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route('customer.shippingPreferences.preference')->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function saveChangePreferences(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            DB::beginTransaction();
            $data = [
                'preferred_ship_method' => $request->preferred_ship_method,
                'international_shipping_option' => $request->international_shipping_option,
                'shipping_preference_option' => json_encode($request->shipping_preference_option),
                'packing_option' => json_encode($request->packing_option),
                'proforma_invoice_options' => json_encode($request->proforma_invoice_options),
                'login_option' => json_encode($request->login_option),
                'tax_id' => $request->tax_id,
                'additional_email' => $request->additional_email,
                'maximum_weight_per_box' => $request->maximum_weight_per_box,
            ];
            $this->shippingPreferenceRepository->updateOrCreateForCustomer($customer->id, $data);
            DB::commit();
            return Redirect::route('customer.shippingPreferences.preference')->with('alert', 'Preferences updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route('customer.shippingPreferences.preference')->withErrors(['message' => $e->getMessage()]);
        }
    }
}
