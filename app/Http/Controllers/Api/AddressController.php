<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AddressController - API endpoints for address data
 */
class AddressController extends Controller
{
    private AddressService $addressService;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    /**
     * Get all countries
     * GET /api/address/countries
     */
    public function countries(): JsonResponse
    {
        $countries = $this->addressService->getCountries();

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Get states/provinces for a country
     * GET /api/address/states/{countryCode}
     */
    public function states(string $countryCode): JsonResponse
    {
        $states = $this->addressService->getStates($countryCode);

        return response()->json([
            'success' => true,
            'data' => $states,
            'has_states' => $states->isNotEmpty(),
        ]);
    }

    /**
     * Normalize an address for carrier API
     * POST /api/address/normalize
     */
    public function normalize(Request $request): JsonResponse
    {
        $address = $request->all();
        $normalized = $this->addressService->normalizeForCarrier($address);

        return response()->json([
            'success' => true,
            'data' => $normalized,
        ]);
    }

    /**
     * Validate country code
     * GET /api/address/validate-country/{code}
     */
    public function validateCountry(string $code): JsonResponse
    {
        $isValid = $this->addressService->isValidCountryCode($code);
        $country = $isValid ? $this->addressService->getCountry($code) : null;

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'country' => $country,
        ]);
    }
}
