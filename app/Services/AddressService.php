<?php

namespace App\Services;

    use App\Models\Country;
    use CommerceGuys\Addressing\Country\CountryRepository;
    use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Cache;

    /**
     * AddressService - Provides worldwide country/state data and validation
     * 
     * Uses Google's Address Data via commerceguys/addressing
     */
    class AddressService
    {
        private CountryRepository $countryRepository;
        private SubdivisionRepository $subdivisionRepository;

        public function __construct()
        {
            $this->countryRepository = new CountryRepository();
            $this->subdivisionRepository = new SubdivisionRepository();
        }

        /**
         * Get all countries with ISO codes
         * 
         * @return Collection [['code' => 'US', 'name' => 'United States'], ...]
         */
        public function getCountries(): Collection
        {
            return Cache::remember('address_countries', 86400, function () {
                $countries = $this->countryRepository->getAll();

                return collect($countries)->map(function ($country) {
                    return [
                        'code' => $country->getCountryCode(),
                        'name' => $country->getName(),
                        'three_letter_code' => $country->getThreeLetterCode(),
                        'numeric_code' => $country->getNumericCode(),
                    ];
                })->sortBy('name')->values();
            });
        }

        /**
         * Get states/provinces for a country
         * 
         * @param string $countryCode ISO 2-letter country code (e.g., 'US', 'CA', 'PK')
         * @return Collection [['code' => 'CA', 'name' => 'California'], ...]
         */
        public function getStates(string $countryCode): Collection
        {
            $countryCode = strtoupper($countryCode);

            return Cache::remember("address_states_{$countryCode}", 86400, function () use ($countryCode) {
                $subdivisions = $this->subdivisionRepository->getAll([$countryCode]);

                return collect($subdivisions)->map(function ($subdivision) {
                    return [
                        'code' => $subdivision->getCode(),
                        'name' => $subdivision->getName(),
                        'local_code' => $subdivision->getLocalCode(),
                    ];
                })->sortBy('name')->values();
            });
        }

        /**
         * Get country by code
         */
        public function getCountry(string $countryCode): ?array
        {
            try {
                $country = $this->countryRepository->get($countryCode);
                return [
                    'code' => $country->getCountryCode(),
                    'name' => $country->getName(),
                    'three_letter_code' => $country->getThreeLetterCode(),
                ];
            } catch (\Exception $e) {
                return null;
            }
        }

        /**
         * Convert country name to ISO code
         * 
         * @param string $countryName Full country name (e.g., "United States", "Pakistan")
         * @return string|null ISO 2-letter code or null if not found
         */
        public function countryNameToCode(string $countryName): ?string
        {
            $countryName = strtolower(trim($countryName));

            // If already a 2-letter code, return uppercase
            if (strlen($countryName) === 2) {
                return strtoupper($countryName);
            }

            $countries = $this->getCountries();

            $match = $countries->first(function ($country) use ($countryName) {
                return strtolower($country['name']) === $countryName;
            });

            return $match ? $match['code'] : null;
        }

        /**
         * Convert state name to code for a given country
         * 
         * @param string $stateName Full state name (e.g., "California")
         * @param string $countryCode ISO 2-letter country code
         * @return string|null State code or original name if not found
         */
        public function stateNameToCode(string $stateName, string $countryCode): ?string
        {
            $stateName = trim($stateName);

            // If already a short code (2-3 chars), return uppercase
            if (strlen($stateName) <= 3) {
                return strtoupper($stateName);
            }

            $states = $this->getStates($countryCode);

            $match = $states->first(function ($state) use ($stateName) {
                return strtolower($state['name']) === strtolower($stateName);
            });

            // Return the local_code (e.g., "CA" instead of "US-CA")
            if ($match) {
                $code = $match['local_code'] ?? $match['code'];
                // If code contains country prefix like "US-CA", extract just "CA"
                if (str_contains($code, '-')) {
                    $code = explode('-', $code)[1];
                }
                return $code;
            }

            return $stateName; // Return original if not found
        }

        /**
         * Normalize an address for carrier API
         * Converts country names to ISO codes and state names to state codes
         */
        public function normalizeForCarrier(array $address): array
        {
            $countryCode = $address['country'] ?? $address['country_code'] ?? 'US';

            // Convert country name to code if needed
            if (strlen($countryCode) > 2 && !str_contains($countryCode, '-')) {
                $countryCode = $this->countryNameToCode($countryCode) ?? 'US';
            } else {
                $countryCode = strtoupper($countryCode);
            }

            // Handle internal country codes (e.g., BQ-BO for Bonaire)
            // Get the carrier_code for API calls
            if (str_contains($countryCode, '-')) {
                $country = Country::where('code', $countryCode)->first();
                if ($country && $country->carrier_code) {
                    $countryCode = $country->carrier_code; // Use BQ instead of BQ-BO
                }
            }

            // Handle US territories - carriers treat these as US states
            // The territory code becomes the "state" and country becomes "US"
            $usTerritoryCodes = ['PR', 'VI', 'GU', 'AS', 'MP']; // Puerto Rico, Virgin Islands, Guam, American Samoa, Northern Mariana
            $state = $address['state'] ?? '';

            if (in_array($countryCode, $usTerritoryCodes)) {
                // For US territories, use the territory code as state
                $state = $countryCode;
                // Carrier APIs expect country to be 'US' for these territories
                // (some carriers may want the territory code, so we keep countryCode as-is)
            } elseif (strlen($state) > 3) {
                // Convert state name to code for other countries
                $state = $this->stateNameToCode($state, $countryCode);
            }

            // Get ZIP code - let carrier handle countries without postal codes
            $zip = trim($address['zip'] ?? $address['postal_code'] ?? '');
            
            // For countries without postal codes, default to "00000" if empty
            // This is standard for Caribbean islands that don't have postal codes
            if (empty($zip) && !$this->countryRequiresPostalCode($countryCode)) {
                $zip = '00000';
            }

            return [
                'street1' => $address['street1'] ?? $address['address_line_1'] ?? '',
                'street2' => $address['street2'] ?? $address['address_line_2'] ?? null,
                'city' => $address['city'] ?? '',
                'state' => $state,
                'zip' => $zip,
                'country' => $countryCode,
            ];
        }

        /**
         * Check if a country requires a state/province
         */
        public function countryRequiresState(string $countryCode): bool
        {
            $states = $this->getStates($countryCode);
            return $states->isNotEmpty();
        }

        /**
         * Validate country code
         */
        public function isValidCountryCode(string $code): bool
        {
            try {
                $this->countryRepository->get($code);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * Check if country requires postal code for carrier APIs
         * Queries database Country model for has_postal_code field
         * Falls back to requiring postal code if country not found
         * Handles internal codes like BQ-BO by checking carrier_code
         */
        public function countryRequiresPostalCode(string $countryCode): bool
        {
            $countryCode = strtoupper($countryCode);
            
            // Try to find country by code (handles BQ-BO, BQ-SA, etc.)
            $country = Country::where('code', $countryCode)->first();
            
            // If not found, try by carrier_code (for cases where code might be just "BQ")
            if (!$country && strlen($countryCode) === 2) {
                $country = Country::where('carrier_code', $countryCode)->first();
            }

            // If country exists in DB, use its has_postal_code setting
            // Otherwise default to requiring postal code (safer default)
            return $country ? $country->has_postal_code : true;
        }

        /**
         * Get destination key for rate caching
         * Uses ZIP-country for countries with postal codes
         * Uses CITY-country for countries without postal codes
         */
        public function getDestinationKey(array $address): string
        {
            $country = strtoupper($address['country'] ?? $address['country_code'] ?? 'US');
            $zip = trim($address['zip'] ?? $address['postal_code'] ?? '');
            $city = trim($address['city'] ?? '');

            // For countries without postal codes, use city as key
            if (!$this->countryRequiresPostalCode($country) || empty($zip)) {
                $cityKey = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $city));
                return $cityKey . '-' . $country;
            }

            // For countries with postal codes, use ZIP
            return strtoupper($zip) . '-' . $country;
        }

        /**
         * Get list of countries without postal codes from database
         */
        public function getCountriesWithoutPostalCodes(): array
        {
            return Country::where('has_postal_code', false)
                ->pluck('code')
                ->toArray();
        }
    }

