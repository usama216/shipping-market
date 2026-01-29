<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Updates British Virgin Islands (VG) to have proper postal codes.
 * BVI uses postal codes in the format VG11## (VG1110-VG1150).
 * 
 * Run with: php artisan db:seed --class=BVIPostalCodeUpdateSeeder
 */
class BVIPostalCodeUpdateSeeder extends Seeder
{
    public function run(): void
    {
        // Update country to indicate it HAS postal codes
        Country::where('code', 'VG')->update([
            'has_postal_code' => true,
            'postal_code_format' => 'VG####',
        ]);

        // BVI postal code mapping by area
        // VG1110 = Road Town (capital), Tortola
        // VG1120 = East End, Tortola  
        // VG1130 = West End, Tortola
        // VG1140 = Virgin Gorda
        // VG1150 = Anegada, Jost Van Dyke
        $postalCodes = [
            // Tortola
            'Road Town' => 'VG1110',
            'Cane Garden Bay' => 'VG1110',
            'East End' => 'VG1120',
            'West End' => 'VG1130',

            // Virgin Gorda
            'Spanish Town' => 'VG1140',
            'The Baths' => 'VG1140',
            'North Sound' => 'VG1140',

            // Other islands
            'The Settlement' => 'VG1150', // Anegada
            'Great Harbour' => 'VG1150',  // Jost Van Dyke
        ];

        foreach ($postalCodes as $cityName => $postalCode) {
            City::where('name', $cityName)
                ->whereHas('state.country', fn($q) => $q->where('code', 'VG'))
                ->update(['postal_code' => $postalCode]);
        }

        $this->command->info('Updated BVI (VG) with postal codes VG1110-VG1150');
    }
}
