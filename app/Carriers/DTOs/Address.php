<?php

namespace App\Carriers\DTOs;

/**
 * Address DTO - Unified address structure for all carriers
 */
class Address
{
    public function __construct(
        public readonly string $street1,
        public readonly ?string $street2 = null,
        public readonly string $city = '',
        public readonly string $state = '',
        public readonly string $postalCode = '',
        public readonly string $countryCode = 'US',
        public readonly ?string $residential = null,
    ) {
    }

    /**
     * Convert to array for API requests
     */
    public function toArray(): array
    {
        return array_filter([
            'street1' => $this->street1,
            'street2' => $this->street2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'residential' => $this->residential,
        ]);
    }
}
