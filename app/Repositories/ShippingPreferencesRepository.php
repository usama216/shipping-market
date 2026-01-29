<?php

namespace App\Repositories;

use App\Interfaces\ShippingPreferencesInterface;
use App\Models\InternationalShippingOptions;
use App\Models\LoginOption;
use App\Models\PackingOptions;
use App\Models\PreferredShipMethod;
use App\Models\ProformaInvoiceOptions;
use App\Models\ShippingPreferenceOption;
use App\Models\ShippingPreferences;
use Illuminate\Support\Facades\Auth;

class ShippingPreferencesRepository implements ShippingPreferencesInterface
{
    protected $shippingPreferences, $packingOption, $internationalShippingOptions, $preferredShipMethod, $proformaInvoiceOptions, $loginOption, $shippingPreferenceOption;

    public function __construct(ShippingPreferences $shippingPreferences, PackingOptions $packingOption, InternationalShippingOptions $internationalShippingOptions, PreferredShipMethod $preferredShipMethod, ProformaInvoiceOptions $proformaInvoiceOptions, LoginOption $loginOption, ShippingPreferenceOption $shippingPreferenceOption)
    {
        $this->shippingPreferences = $shippingPreferences;
        $this->packingOption = $packingOption;
        $this->internationalShippingOptions = $internationalShippingOptions;
        $this->preferredShipMethod = $preferredShipMethod;
        $this->proformaInvoiceOptions = $proformaInvoiceOptions;
        $this->loginOption = $loginOption;
        $this->shippingPreferenceOption = $shippingPreferenceOption;
    }

    /**
     * Get shipping preference for customer
     */
    public function getShippingPreferenceForCustomer($customerId)
    {
        return $this->shippingPreferences->where('customer_id', $customerId)->with('address')->first();
    }

    /**
     * @deprecated Use getShippingPreferenceForCustomer instead
     */
    public function getShippingPreference($userId)
    {
        return $this->getShippingPreferenceForCustomer($userId);
    }
    public function getPackingOption()
    {
        return $this->packingOption->get();
    }

    public function getInternationalShippingOptions()
    {
        return $this->internationalShippingOptions->get();
    }
    public function getInternationalShippingOptionById($id)
    {
        return $this->internationalShippingOptions->where('id', $id)->first();
    }


    public function getPreferredShipMethod()
    {
        return $this->preferredShipMethod->get();
    }

    public function getLoginOptions()
    {
        return $this->loginOption->get();
    }

    public function getProformaInvoiceOptions()
    {
        return $this->proformaInvoiceOptions->get();
    }

    public function shippingPreferenceOptions()
    {
        return $this->shippingPreferenceOption->get();
    }

    /**
     * Update or create shipping preference for customer
     */
    public function updateOrCreateForCustomer($customerId, $data)
    {
        return $this->shippingPreferences->updateOrCreate(['customer_id' => $customerId], $data);
    }

    /**
     * @deprecated Use updateOrCreateForCustomer instead
     */
    public function updateOrCreateShippingPreference($data)
    {
        $customer = Auth::guard('customer')->user();
        return $this->updateOrCreateForCustomer($customer ? $customer->id : null, $data);
    }


    public function sumPackingOption($packingOptionIds)
    {
        // Handle null or empty values
        if (empty($packingOptionIds) || !is_array($packingOptionIds)) {
            return 0;
        }
        return $this->packingOption->whereIn('id', $packingOptionIds)->sum('price');
    }

    public function sumShippingPreferenceOption($shippingPreferenceOptionIds)
    {
        // Handle null or empty values
        if (empty($shippingPreferenceOptionIds) || !is_array($shippingPreferenceOptionIds)) {
            return 0;
        }
        return $this->shippingPreferenceOption->whereIn('id', $shippingPreferenceOptionIds)->sum('price');
    }

    public function getPackingOptionByIds($ids)
    {
        // Handle null or empty values
        if (empty($ids) || !is_array($ids)) {
            return collect([]);
        }
        return $this->packingOption->whereIn('id', $ids)->get();
    }


    public function shippingPreferenceOptionByIds($ids)
    {
        // Handle null or empty values
        if (empty($ids) || !is_array($ids)) {
            return collect([]);
        }
        return $this->shippingPreferenceOption->whereIn('id', $ids)->get();
    }

}
