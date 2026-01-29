<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\JsonResponse;

/**
 * LocationController - API endpoints for cascading address dropdowns
 * 
 * Provides country → state → city hierarchy for address forms.
 * Used by registration, address book, and checkout pages.
 */
class LocationController extends Controller
{
    /**
     * Get all active countries.
     * 
     * GET /api/locations/countries
     */
    public function countries(): JsonResponse
    {
        $countries = Country::active()
            ->ordered()
            ->select(['id', 'name', 'code', 'phone_prefix', 'has_postal_code'])
            ->get();

        return response()->json($countries);
    }

    /**
     * Get all states/parishes for a country.
     * 
     * GET /api/locations/states/{country}
     * 
     * @param int|string $country Country ID or ISO code
     */
    public function states($country): JsonResponse
    {
        // Support both ID and ISO code lookup
        $countryModel = is_numeric($country)
            ? Country::find($country)
            : Country::where('code', strtoupper($country))->first();

        if (!$countryModel) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        $states = $countryModel->states()
            ->select(['id', 'name', 'code', 'country_id'])
            ->get();

        return response()->json($states);
    }

    /**
     * Get all cities for a state, including postal codes.
     * 
     * GET /api/locations/cities/{state}
     * 
     * @param int $state State ID
     */
    public function cities($state): JsonResponse
    {
        $stateModel = State::find($state);

        if (!$stateModel) {
            return response()->json(['error' => 'State not found'], 404);
        }

        $cities = $stateModel->cities()
            ->select(['id', 'name', 'postal_code', 'state_id'])
            ->get();

        return response()->json($cities);
    }

    /**
     * Get full location hierarchy (for pre-populating edit forms).
     * 
     * GET /api/locations/lookup?country={code}&state={id}&city={id}
     */
    public function lookup(): JsonResponse
    {
        $result = [];

        if (request()->has('country')) {
            $country = Country::where('code', request('country'))
                ->orWhere('id', request('country'))
                ->first();

            if ($country) {
                $result['country'] = $country;
                $result['states'] = $country->states()->select(['id', 'name', 'code'])->get();
            }
        }

        if (request()->has('state')) {
            $state = State::find(request('state'));
            if ($state) {
                $result['state'] = $state;
                $result['cities'] = $state->cities()->select(['id', 'name', 'postal_code'])->get();
            }
        }

        if (request()->has('city')) {
            $city = City::find(request('city'));
            if ($city) {
                $result['city'] = $city;
            }
        }

        return response()->json($result);
    }
}
