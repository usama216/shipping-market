<?php

namespace App\Interfaces;

interface ShippingPreferencesInterface
{
    public function getShippingPreference($userId);
    public function getPackingOption();
    public function getInternationalShippingOptions();
    public function getPreferredShipMethod();

    public function getLoginOptions();

    public function getProformaInvoiceOptions();

    public function shippingPreferenceOptions();

    public function getPackingOptionByIds($ids);

    public function shippingPreferenceOptionByIds($ids);

    public function sumPackingOption($packingOptionIds);
    public function sumShippingPreferenceOption($shippingPreferenceOptionIds);

    public function getInternationalShippingOptionById($id);
}
