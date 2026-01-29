<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Updates Caribbean countries with FedEx-specific state acceptance rules.
 * 
 * FedEx address validation is carrier-specific and unforgiving.
 * Many Caribbean territories reject state/province values even if they have postal codes.
 * 
 * Rule reference:
 * - Countries that REJECT state: VG, KY, TC, BM, AW, CW, SX, AG, GD, LC, VC, KN, DM, MS, AI, etc.
 * - Countries that ACCEPT state: JM (parish), TT, DO, PR
 * - French territories (GP, MQ, MF, BL): Use French postal system, state optional
 */
class FedExCaribbeanRulesSeeder extends Seeder
{
    public function run(): void
    {
        // Countries where FedEx REJECTS state/province values
        // These are small territories without FedEx-recognized administrative divisions
        $countriesRejectingState = [
            'AI', // Anguilla
            'AG', // Antigua and Barbuda
            'AW', // Aruba
            'BS', // Bahamas (island names not valid states)
            'BB', // Barbados (parishes not accepted)
            'BQ', // Caribbean Netherlands (legacy)
            'BQ-BO', // Bonaire
            'BQ-SA', // Saba
            'BQ-SE', // Sint Eustatius
            'VG', // British Virgin Islands - CONFIRMED: rejects "Tortola"
            'KY', // Cayman Islands
            'CW', // Curaçao
            'DM', // Dominica
            'GD', // Grenada
            'KN', // St Kitts and Nevis
            'LC', // St Lucia
            'MS', // Montserrat
            'SX', // Sint Maarten
            'TC', // Turks and Caicos
            'VC', // St Vincent and the Grenadines
            'VI', // US Virgin Islands
            'BM', // Bermuda
        ];

        // Countries where FedEx ACCEPTS state/province values
        // These have recognized administrative divisions
        $countriesAcceptingState = [
            'JM', // Jamaica (parish system accepted)
            'TT', // Trinidad and Tobago
            'DO', // Dominican Republic
            'PR', // Puerto Rico (US state codes)
            'GP', // Guadeloupe (French departments)
            'MQ', // Martinique (French departments)
            'MF', // Saint Martin (French)
            'BL', // Saint Barthélemy
            'HT', // Haiti
        ];

        // Update countries that reject state
        Country::whereIn('code', $countriesRejectingState)
            ->update(['fedex_accepts_state' => false]);

        // Update countries that accept state (explicit, in case default was changed)
        Country::whereIn('code', $countriesAcceptingState)
            ->update(['fedex_accepts_state' => true]);

        $this->command->info('Updated FedEx state acceptance rules for Caribbean countries.');
        $this->command->info('  - ' . count($countriesRejectingState) . ' countries set to reject state');
        $this->command->info('  - ' . count($countriesAcceptingState) . ' countries set to accept state');
    }
}
