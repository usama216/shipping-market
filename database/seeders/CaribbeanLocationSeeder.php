<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Seeds Caribbean location data for 29 target territories.
 * 
 * Data structure: Country → States/Parishes → Cities
 * Postal codes included where applicable (some islands don't use them).
 */
class CaribbeanLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = $this->getCaribbeanLocations();

        foreach ($locations as $countryData) {
            $country = Country::firstOrCreate(
                ['code' => $countryData['code']],
                [
                    'name' => $countryData['name'],
                    'code' => $countryData['code'],
                    'carrier_code' => $countryData['carrier_code'] ?? null, // ISO code for carrier APIs
                    'phone_prefix' => $countryData['phone_prefix'] ?? null,
                    'has_postal_code' => $countryData['has_postal_code'] ?? false,
                    'postal_code_format' => $countryData['postal_code_format'] ?? null,
                    'is_active' => true,
                    'sort_order' => $countryData['sort_order'] ?? 0,
                ]
            );

            foreach ($countryData['states'] as $stateName => $stateData) {
                $state = State::firstOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $stateName,
                    ],
                    [
                        'country_id' => $country->id,
                        'name' => $stateName,
                        'code' => $stateData['code'] ?? null,
                    ]
                );

                foreach ($stateData['cities'] as $cityName => $cityData) {
                    City::firstOrCreate(
                        [
                            'state_id' => $state->id,
                            'name' => $cityName,
                        ],
                        [
                            'state_id' => $state->id,
                            'name' => $cityName,
                            'postal_code' => is_array($cityData) ? ($cityData['postal_code'] ?? null) : null,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Caribbean location data organized by country.
     * For islands with simple structure, state = main island/area
     */
    private function getCaribbeanLocations(): array
    {
        return [
            // ANGUILLA (AI) - British Overseas Territory, no postal codes
            [
                'name' => 'Anguilla',
                'code' => 'AI',
                'phone_prefix' => '+1-264',
                'has_postal_code' => false,
                'sort_order' => 1,
                'states' => [
                    'The Valley' => [
                        'code' => 'TV',
                        'cities' => [
                            'The Valley' => [],
                            'North Hill' => [],
                            'South Hill' => [],
                            'Sandy Ground' => [],
                            'Blowing Point' => [],
                        ],
                    ],
                    'East End' => [
                        'code' => 'EE',
                        'cities' => [
                            'Island Harbour' => [],
                            'Shoal Bay' => [],
                        ],
                    ],
                    'West End' => [
                        'code' => 'WE',
                        'cities' => [
                            'West End' => [],
                            'Meads Bay' => [],
                            'Long Bay' => [],
                        ],
                    ],
                ],
            ],

            // ANTIGUA AND BARBUDA (AG)
            [
                'name' => 'Antigua and Barbuda',
                'code' => 'AG',
                'phone_prefix' => '+1-268',
                'has_postal_code' => false, // System exists but not widely used
                'sort_order' => 2,
                'states' => [
                    "Saint John's" => [
                        'code' => 'SJ',
                        'cities' => [
                            "St. John's" => [],
                            'Piggotts' => [],
                            'Cedar Grove' => [],
                        ],
                    ],
                    'Saint Peter' => [
                        'code' => 'SP',
                        'cities' => [
                            'Parham' => [],
                            'Pares' => [],
                        ],
                    ],
                    'Saint Philip' => [
                        'code' => 'PH',
                        'cities' => [
                            'Freetown' => [],
                            'Willikies' => [],
                        ],
                    ],
                    'Saint Paul' => [
                        'code' => 'PA',
                        'cities' => [
                            "English Harbour" => [],
                            "Falmouth" => [],
                        ],
                    ],
                    'Saint Mary' => [
                        'code' => 'SM',
                        'cities' => [
                            "Bolans" => [],
                            "Old Road" => [],
                        ],
                    ],
                    'Saint George' => [
                        'code' => 'SG',
                        'cities' => [
                            "Fitches Creek" => [],
                        ],
                    ],
                    'Barbuda' => [
                        'code' => 'BB',
                        'cities' => [
                            'Codrington' => [],
                        ],
                    ],
                ],
            ],

            // ARUBA (AW) - No postal code system
            [
                'name' => 'Aruba',
                'code' => 'AW',
                'phone_prefix' => '+297',
                'has_postal_code' => false,
                'sort_order' => 3,
                'states' => [
                    'Oranjestad' => [
                        'code' => 'OR',
                        'cities' => [
                            'Oranjestad' => [],
                            'Eagle Beach' => [],
                            'Palm Beach' => [],
                        ],
                    ],
                    'Noord' => [
                        'code' => 'NO',
                        'cities' => [
                            'Noord' => [],
                            'Malmok' => [],
                        ],
                    ],
                    'Paradera' => [
                        'code' => 'PA',
                        'cities' => [
                            'Paradera' => [],
                        ],
                    ],
                    'San Nicolas' => [
                        'code' => 'SN',
                        'cities' => [
                            'San Nicolas' => [],
                            'Savaneta' => [],
                        ],
                    ],
                    'Santa Cruz' => [
                        'code' => 'SC',
                        'cities' => [
                            'Santa Cruz' => [],
                        ],
                    ],
                ],
            ],

            // BAHAMAS (BS) - Uses P.O. Box system, no street postal codes
            [
                'name' => 'Bahamas',
                'code' => 'BS',
                'phone_prefix' => '+1-242',
                'has_postal_code' => false,
                'sort_order' => 4,
                'states' => [
                    'New Providence' => [
                        'code' => 'NP',
                        'cities' => [
                            'Nassau' => [],
                            'Cable Beach' => [],
                            'Paradise Island' => [],
                        ],
                    ],
                    'Grand Bahama' => [
                        'code' => 'GB',
                        'cities' => [
                            'Freeport' => [],
                            'Lucaya' => [],
                            'Eight Mile Rock' => [],
                        ],
                    ],
                    'Abaco' => [
                        'code' => 'AB',
                        'cities' => [
                            'Marsh Harbour' => [],
                            'Hope Town' => [],
                            'Treasure Cay' => [],
                        ],
                    ],
                    'Eleuthera' => [
                        'code' => 'EL',
                        'cities' => [
                            "Governor's Harbour" => [],
                            'Harbour Island' => [],
                            'Rock Sound' => [],
                        ],
                    ],
                    'Exuma' => [
                        'code' => 'EX',
                        'cities' => [
                            'George Town' => [],
                            'Staniel Cay' => [],
                        ],
                    ],
                    'Andros' => [
                        'code' => 'AN',
                        'cities' => [
                            'Andros Town' => [],
                            'Fresh Creek' => [],
                        ],
                    ],
                    'Long Island' => [
                        'code' => 'LI',
                        'cities' => [
                            'Clarence Town' => [],
                            'Deadmans Cay' => [],
                        ],
                    ],
                    'Cat Island' => [
                        'code' => 'CI',
                        'cities' => [
                            'Arthur Town' => [],
                            'New Bight' => [],
                        ],
                    ],
                    'San Salvador' => [
                        'code' => 'SS',
                        'cities' => [
                            'Cockburn Town' => [],
                        ],
                    ],
                    'Bimini' => [
                        'code' => 'BI',
                        'cities' => [
                            'Alice Town' => [],
                        ],
                    ],
                ],
            ],

            // BARBADOS (BB) - Has postal codes (BBXXXXX format)
            [
                'name' => 'Barbados',
                'code' => 'BB',
                'phone_prefix' => '+1-246',
                'has_postal_code' => true,
                'postal_code_format' => 'BB#####',
                'sort_order' => 5,
                'states' => [
                    'Christ Church' => [
                        'code' => 'CC',
                        'cities' => [
                            'Oistins' => ['postal_code' => 'BB15000'],
                            'Hastings' => ['postal_code' => 'BB15156'],
                            'Worthing' => ['postal_code' => 'BB15006'],
                            'Rockley' => ['postal_code' => 'BB15138'],
                        ],
                    ],
                    'Saint Michael' => [
                        'code' => 'SM',
                        'cities' => [
                            'Bridgetown' => ['postal_code' => 'BB11000'],
                            'The Garrison' => ['postal_code' => 'BB14038'],
                            'Belleville' => ['postal_code' => 'BB11114'],
                        ],
                    ],
                    'Saint James' => [
                        'code' => 'SJ',
                        'cities' => [
                            'Holetown' => ['postal_code' => 'BB24017'],
                            'Speightstown' => ['postal_code' => 'BB26000'],
                            'Sandy Lane' => ['postal_code' => 'BB24024'],
                        ],
                    ],
                    'Saint Philip' => [
                        'code' => 'SP',
                        'cities' => [
                            'Six Cross Roads' => ['postal_code' => 'BB18000'],
                            'Crane' => ['postal_code' => 'BB18000'],
                        ],
                    ],
                    'Saint Peter' => [
                        'code' => 'PT',
                        'cities' => [
                            'Speightstown' => ['postal_code' => 'BB26000'],
                            'Six Mens' => ['postal_code' => 'BB26000'],
                        ],
                    ],
                    'Saint Joseph' => [
                        'code' => 'SJ',
                        'cities' => [
                            'Bathsheba' => ['postal_code' => 'BB21000'],
                        ],
                    ],
                    'Saint John' => [
                        'code' => 'JN',
                        'cities' => [
                            "St. John's" => ['postal_code' => 'BB22000'],
                        ],
                    ],
                    'Saint George' => [
                        'code' => 'SG',
                        'cities' => [
                            'Edgecliff' => ['postal_code' => 'BB17000'],
                        ],
                    ],
                    'Saint Andrew' => [
                        'code' => 'SA',
                        'cities' => [
                            'Belleplaine' => ['postal_code' => 'BB23000'],
                        ],
                    ],
                    'Saint Thomas' => [
                        'code' => 'ST',
                        'cities' => [
                            'Welchman Hall' => ['postal_code' => 'BB19000'],
                        ],
                    ],
                    'Saint Lucy' => [
                        'code' => 'SL',
                        'cities' => [
                            'Crab Hill' => ['postal_code' => 'BB27000'],
                        ],
                    ],
                ],
            ],

            // BONAIRE (BQ-BO) - Part of Caribbean Netherlands BES Islands
            [
                'name' => 'Bonaire',
                'code' => 'BQ-BO',
                'carrier_code' => 'BQ', // Use BQ for carrier APIs (FedEx, DHL, UPS)
                'phone_prefix' => '+599-7',
                'has_postal_code' => false,
                'sort_order' => 6,
                'states' => [
                    'Bonaire' => [
                        'code' => 'BO',
                        'cities' => [
                            'Kralendijk' => [],
                            'Rincon' => [],
                            'Antriol' => [],
                        ],
                    ],
                ],
            ],

            // SABA (BQ-SA) - Part of Caribbean Netherlands BES Islands
            [
                'name' => 'Saba',
                'code' => 'BQ-SA',
                'carrier_code' => 'BQ', // Use BQ for carrier APIs (FedEx, DHL, UPS)
                'phone_prefix' => '+599-4',
                'has_postal_code' => false,
                'sort_order' => 7,
                'states' => [
                    'Saba' => [
                        'code' => 'SA',
                        'cities' => [
                            'The Bottom' => [],
                            'Windwardside' => [],
                            "Hell's Gate" => [],
                            "St. John's" => [],
                        ],
                    ],
                ],
            ],

            // SINT EUSTATIUS (BQ-SE) - Part of Caribbean Netherlands BES Islands
            [
                'name' => 'Sint Eustatius',
                'code' => 'BQ-SE',
                'carrier_code' => 'BQ', // Use BQ for carrier APIs (FedEx, DHL, UPS)
                'phone_prefix' => '+599-3',
                'has_postal_code' => false,
                'sort_order' => 8,
                'states' => [
                    'Sint Eustatius' => [
                        'code' => 'SE',
                        'cities' => [
                            'Oranjestad' => [],
                        ],
                    ],
                ],
            ],

            // BRITISH VIRGIN ISLANDS (VG) - Has postal codes (VG1110-VG1150)
            [
                'name' => 'British Virgin Islands',
                'code' => 'VG',
                'phone_prefix' => '+1-284',
                'has_postal_code' => true,
                'postal_code_format' => 'VG####',
                'sort_order' => 7,
                'states' => [
                    'Tortola' => [
                        'code' => 'TO',
                        'cities' => [
                            'Road Town' => ['postal_code' => 'VG1110'],
                            'East End' => ['postal_code' => 'VG1120'],
                            'West End' => ['postal_code' => 'VG1130'],
                            'Cane Garden Bay' => ['postal_code' => 'VG1110'],
                        ],
                    ],
                    'Virgin Gorda' => [
                        'code' => 'VG',
                        'cities' => [
                            'Spanish Town' => ['postal_code' => 'VG1140'],
                            'The Baths' => ['postal_code' => 'VG1140'],
                            'North Sound' => ['postal_code' => 'VG1140'],
                        ],
                    ],
                    'Anegada' => [
                        'code' => 'AN',
                        'cities' => [
                            'The Settlement' => ['postal_code' => 'VG1150'],
                        ],
                    ],
                    'Jost Van Dyke' => [
                        'code' => 'JD',
                        'cities' => [
                            'Great Harbour' => ['postal_code' => 'VG1150'],
                        ],
                    ],
                ],
            ],

            // CAYMAN ISLANDS (KY) - Has postal codes (KYN-NNNN format)
            [
                'name' => 'Cayman Islands',
                'code' => 'KY',
                'phone_prefix' => '+1-345',
                'has_postal_code' => true,
                'postal_code_format' => 'KY#-####',
                'sort_order' => 8,
                'states' => [
                    'Grand Cayman' => [
                        'code' => 'GC',
                        'cities' => [
                            'George Town' => ['postal_code' => 'KY1-1000'],
                            'West Bay' => ['postal_code' => 'KY1-1200'],
                            'Bodden Town' => ['postal_code' => 'KY1-1600'],
                            'East End' => ['postal_code' => 'KY1-1700'],
                            'North Side' => ['postal_code' => 'KY1-1800'],
                            'Seven Mile Beach' => ['postal_code' => 'KY1-1200'],
                        ],
                    ],
                    'Cayman Brac' => [
                        'code' => 'CB',
                        'cities' => [
                            'West End' => ['postal_code' => 'KY2-2000'],
                            'Spot Bay' => ['postal_code' => 'KY2-2001'],
                        ],
                    ],
                    'Little Cayman' => [
                        'code' => 'LC',
                        'cities' => [
                            'Blossom Village' => ['postal_code' => 'KY3-2500'],
                        ],
                    ],
                ],
            ],

            // CURAÇAO (CW) - No postal codes
            [
                'name' => 'Curaçao',
                'code' => 'CW',
                'phone_prefix' => '+599',
                'has_postal_code' => false,
                'sort_order' => 9,
                'states' => [
                    'Willemstad' => [
                        'code' => 'WI',
                        'cities' => [
                            'Willemstad' => [],
                            'Punda' => [],
                            'Otrobanda' => [],
                            'Pietermaai' => [],
                        ],
                    ],
                    'Bandabou' => [
                        'code' => 'BD',
                        'cities' => [
                            'Westpunt' => [],
                            'Sint Willibrordus' => [],
                            'Barber' => [],
                        ],
                    ],
                    'Banda Riba' => [
                        'code' => 'BR',
                        'cities' => [
                            'Jan Thiel' => [],
                            'Spanish Water' => [],
                        ],
                    ],
                ],
            ],

            // DOMINICA (DM) - No postal codes
            [
                'name' => 'Dominica',
                'code' => 'DM',
                'phone_prefix' => '+1-767',
                'has_postal_code' => false,
                'sort_order' => 10,
                'states' => [
                    'Saint George' => [
                        'code' => 'SG',
                        'cities' => [
                            'Roseau' => [],
                            'Loubiere' => [],
                        ],
                    ],
                    'Saint John' => [
                        'code' => 'SJ',
                        'cities' => [
                            'Portsmouth' => [],
                            'Picard' => [],
                        ],
                    ],
                    'Saint Patrick' => [
                        'code' => 'SP',
                        'cities' => [
                            'Grand Bay' => [],
                        ],
                    ],
                    'Saint Andrew' => [
                        'code' => 'SA',
                        'cities' => [
                            'Marigot' => [],
                            'Castle Bruce' => [],
                        ],
                    ],
                    'Saint David' => [
                        'code' => 'SD',
                        'cities' => [
                            'Castle Bruce' => [],
                        ],
                    ],
                    'Saint Joseph' => [
                        'code' => 'SO',
                        'cities' => [
                            'Salisbury' => [],
                            'Coulibistrie' => [],
                        ],
                    ],
                    'Saint Luke' => [
                        'code' => 'SL',
                        'cities' => [
                            'Pointe Michel' => [],
                        ],
                    ],
                    'Saint Mark' => [
                        'code' => 'SM',
                        'cities' => [
                            'Soufriere' => [],
                        ],
                    ],
                    'Saint Paul' => [
                        'code' => 'PA',
                        'cities' => [
                            'Massacre' => [],
                        ],
                    ],
                    'Saint Peter' => [
                        'code' => 'PT',
                        'cities' => [
                            'Colihaut' => [],
                        ],
                    ],
                ],
            ],

            // DOMINICAN REPUBLIC (DO) - Has postal codes but not widely used
            [
                'name' => 'Dominican Republic',
                'code' => 'DO',
                'phone_prefix' => '+1-809',
                'has_postal_code' => true,
                'postal_code_format' => '#####',
                'sort_order' => 11,
                'states' => [
                    'Distrito Nacional' => [
                        'code' => 'DN',
                        'cities' => [
                            'Santo Domingo' => ['postal_code' => '10100'],
                            'Bella Vista' => ['postal_code' => '10109'],
                            'Gazcue' => ['postal_code' => '10205'],
                        ],
                    ],
                    'Santo Domingo' => [
                        'code' => 'SD',
                        'cities' => [
                            'Santo Domingo Este' => ['postal_code' => '11506'],
                            'Santo Domingo Norte' => ['postal_code' => '11103'],
                            'Santo Domingo Oeste' => ['postal_code' => '11205'],
                            'Boca Chica' => ['postal_code' => '11601'],
                        ],
                    ],
                    'Santiago' => [
                        'code' => 'ST',
                        'cities' => [
                            'Santiago de los Caballeros' => ['postal_code' => '51000'],
                            'Tamboril' => ['postal_code' => '51200'],
                        ],
                    ],
                    'La Altagracia' => [
                        'code' => 'LA',
                        'cities' => [
                            'Punta Cana' => ['postal_code' => '23000'],
                            'Higüey' => ['postal_code' => '23001'],
                            'Bávaro' => ['postal_code' => '23301'],
                        ],
                    ],
                    'Puerto Plata' => [
                        'code' => 'PP',
                        'cities' => [
                            'Puerto Plata' => ['postal_code' => '57000'],
                            'Sosúa' => ['postal_code' => '57601'],
                            'Cabarete' => ['postal_code' => '57604'],
                        ],
                    ],
                    'La Romana' => [
                        'code' => 'LR',
                        'cities' => [
                            'La Romana' => ['postal_code' => '22000'],
                            'Casa de Campo' => ['postal_code' => '22000'],
                        ],
                    ],
                    'Samaná' => [
                        'code' => 'SM',
                        'cities' => [
                            'Santa Bárbara de Samaná' => ['postal_code' => '32000'],
                            'Las Terrenas' => ['postal_code' => '32100'],
                        ],
                    ],
                ],
            ],

            // GRENADA (GD) - No postal codes
            [
                'name' => 'Grenada',
                'code' => 'GD',
                'phone_prefix' => '+1-473',
                'has_postal_code' => false,
                'sort_order' => 12,
                'states' => [
                    'Saint George' => [
                        'code' => 'SG',
                        'cities' => [
                            "St. George's" => [],
                            "Grand Anse" => [],
                            "Morne Rouge" => [],
                        ],
                    ],
                    'Saint Andrew' => [
                        'code' => 'SA',
                        'cities' => [
                            'Grenville' => [],
                            'Marquis' => [],
                        ],
                    ],
                    'Saint David' => [
                        'code' => 'SD',
                        'cities' => [
                            "St. David's" => [],
                            'La Sagesse' => [],
                        ],
                    ],
                    'Saint John' => [
                        'code' => 'SJ',
                        'cities' => [
                            'Gouyave' => [],
                        ],
                    ],
                    'Saint Mark' => [
                        'code' => 'SM',
                        'cities' => [
                            'Victoria' => [],
                        ],
                    ],
                    'Saint Patrick' => [
                        'code' => 'SP',
                        'cities' => [
                            'Sauteurs' => [],
                        ],
                    ],
                    'Carriacou and Petite Martinique' => [
                        'code' => 'CP',
                        'cities' => [
                            'Hillsborough' => [],
                            'Petite Martinique' => [],
                        ],
                    ],
                ],
            ],

            // GUADELOUPE (GP) - French overseas, uses French postal codes
            [
                'name' => 'Guadeloupe',
                'code' => 'GP',
                'phone_prefix' => '+590',
                'has_postal_code' => true,
                'postal_code_format' => '971##',
                'sort_order' => 13,
                'states' => [
                    'Basse-Terre' => [
                        'code' => 'BT',
                        'cities' => [
                            'Basse-Terre' => ['postal_code' => '97100'],
                            'Saint-Claude' => ['postal_code' => '97120'],
                            'Trois-Rivières' => ['postal_code' => '97114'],
                        ],
                    ],
                    'Grande-Terre' => [
                        'code' => 'GT',
                        'cities' => [
                            'Pointe-à-Pitre' => ['postal_code' => '97110'],
                            'Le Gosier' => ['postal_code' => '97190'],
                            'Sainte-Anne' => ['postal_code' => '97180'],
                            'Saint-François' => ['postal_code' => '97118'],
                            'Le Moule' => ['postal_code' => '97160'],
                        ],
                    ],
                    'Marie-Galante' => [
                        'code' => 'MG',
                        'cities' => [
                            'Grand-Bourg' => ['postal_code' => '97112'],
                        ],
                    ],
                    'Les Saintes' => [
                        'code' => 'LS',
                        'cities' => [
                            'Terre-de-Haut' => ['postal_code' => '97137'],
                        ],
                    ],
                ],
            ],

            // JAMAICA (JM) - Postal codes suspended, not in use
            [
                'name' => 'Jamaica',
                'code' => 'JM',
                'phone_prefix' => '+1-876',
                'has_postal_code' => false, // System suspended since 2007
                'sort_order' => 14,
                'states' => [
                    'Kingston' => [
                        'code' => 'KN',
                        'cities' => [
                            'Kingston' => [],
                            'Downtown Kingston' => [],
                            'New Kingston' => [],
                        ],
                    ],
                    'Saint Andrew' => [
                        'code' => 'SA',
                        'cities' => [
                            'Half Way Tree' => [],
                            'Liguanea' => [],
                            'Constant Spring' => [],
                            'Stony Hill' => [],
                            'Papine' => [],
                        ],
                    ],
                    'Saint Catherine' => [
                        'code' => 'SC',
                        'cities' => [
                            'Spanish Town' => [],
                            'Portmore' => [],
                            'Old Harbour' => [],
                            'Linstead' => [],
                        ],
                    ],
                    'Clarendon' => [
                        'code' => 'CL',
                        'cities' => [
                            'May Pen' => [],
                            'Chapelton' => [],
                        ],
                    ],
                    'Manchester' => [
                        'code' => 'MN',
                        'cities' => [
                            'Mandeville' => [],
                            'Christiana' => [],
                        ],
                    ],
                    'Saint Elizabeth' => [
                        'code' => 'SE',
                        'cities' => [
                            'Black River' => [],
                            'Santa Cruz' => [],
                            'Junction' => [],
                        ],
                    ],
                    'Westmoreland' => [
                        'code' => 'WM',
                        'cities' => [
                            'Savanna-la-Mar' => [],
                            'Negril' => [],
                            'Whitehouse' => [],
                        ],
                    ],
                    'Hanover' => [
                        'code' => 'HN',
                        'cities' => [
                            'Lucea' => [],
                            'Green Island' => [],
                        ],
                    ],
                    'Saint James' => [
                        'code' => 'SJ',
                        'cities' => [
                            'Montego Bay' => [],
                            'Rose Hall' => [],
                            'Ironshore' => [],
                        ],
                    ],
                    'Trelawny' => [
                        'code' => 'TR',
                        'cities' => [
                            'Falmouth' => [],
                            'Duncans' => [],
                        ],
                    ],
                    'Saint Ann' => [
                        'code' => 'AN',
                        'cities' => [
                            "St. Ann's Bay" => [],
                            'Ocho Rios' => [],
                            'Runaway Bay' => [],
                            'Brown\'s Town' => [],
                        ],
                    ],
                    'Saint Mary' => [
                        'code' => 'SM',
                        'cities' => [
                            'Port Maria' => [],
                            'Oracabessa' => [],
                        ],
                    ],
                    'Portland' => [
                        'code' => 'PD',
                        'cities' => [
                            'Port Antonio' => [],
                            'Buff Bay' => [],
                        ],
                    ],
                    'Saint Thomas' => [
                        'code' => 'ST',
                        'cities' => [
                            'Morant Bay' => [],
                            'Yallahs' => [],
                        ],
                    ],
                ],
            ],

            // MARTINIQUE (MQ) - French overseas, uses French postal codes
            [
                'name' => 'Martinique',
                'code' => 'MQ',
                'phone_prefix' => '+596',
                'has_postal_code' => true,
                'postal_code_format' => '972##',
                'sort_order' => 15,
                'states' => [
                    'Fort-de-France' => [
                        'code' => 'FF',
                        'cities' => [
                            'Fort-de-France' => ['postal_code' => '97200'],
                            'Schoelcher' => ['postal_code' => '97233'],
                            'Le Lamentin' => ['postal_code' => '97232'],
                        ],
                    ],
                    'Le Marin' => [
                        'code' => 'LM',
                        'cities' => [
                            'Le Marin' => ['postal_code' => '97290'],
                            'Sainte-Anne' => ['postal_code' => '97227'],
                            'Les Trois-Îlets' => ['postal_code' => '97229'],
                        ],
                    ],
                    'Saint-Pierre' => [
                        'code' => 'SP',
                        'cities' => [
                            'Saint-Pierre' => ['postal_code' => '97250'],
                            'Le Carbet' => ['postal_code' => '97221'],
                        ],
                    ],
                    'La Trinité' => [
                        'code' => 'TR',
                        'cities' => [
                            'La Trinité' => ['postal_code' => '97220'],
                            'Sainte-Marie' => ['postal_code' => '97230'],
                        ],
                    ],
                ],
            ],

            // MONTSERRAT (MS) - No postal codes
            [
                'name' => 'Montserrat',
                'code' => 'MS',
                'phone_prefix' => '+1-664',
                'has_postal_code' => false,
                'sort_order' => 16,
                'states' => [
                    'Saint Peter' => [
                        'code' => 'SP',
                        'cities' => [
                            'Brades' => [],
                            'Little Bay' => [],
                        ],
                    ],
                    'Saint John' => [
                        'code' => 'SJ',
                        'cities' => [
                            "St. John's" => [],
                        ],
                    ],
                ],
            ],

            // PUERTO RICO (PR) - US postal codes (5-digit)
            [
                'name' => 'Puerto Rico',
                'code' => 'PR',
                'phone_prefix' => '+1-787',
                'has_postal_code' => true,
                'postal_code_format' => '#####',
                'sort_order' => 17,
                'states' => [
                    'San Juan' => [
                        'code' => 'SJ',
                        'cities' => [
                            'San Juan' => ['postal_code' => '00901'],
                            'Condado' => ['postal_code' => '00907'],
                            'Old San Juan' => ['postal_code' => '00901'],
                            'Santurce' => ['postal_code' => '00909'],
                            'Hato Rey' => ['postal_code' => '00917'],
                        ],
                    ],
                    'Bayamón' => [
                        'code' => 'BY',
                        'cities' => [
                            'Bayamón' => ['postal_code' => '00956'],
                        ],
                    ],
                    'Carolina' => [
                        'code' => 'CR',
                        'cities' => [
                            'Carolina' => ['postal_code' => '00979'],
                            'Isla Verde' => ['postal_code' => '00979'],
                        ],
                    ],
                    'Ponce' => [
                        'code' => 'PN',
                        'cities' => [
                            'Ponce' => ['postal_code' => '00716'],
                            'La Playa' => ['postal_code' => '00731'],
                        ],
                    ],
                    'Caguas' => [
                        'code' => 'CG',
                        'cities' => [
                            'Caguas' => ['postal_code' => '00725'],
                        ],
                    ],
                    'Mayagüez' => [
                        'code' => 'MG',
                        'cities' => [
                            'Mayagüez' => ['postal_code' => '00680'],
                        ],
                    ],
                    'Aguadilla' => [
                        'code' => 'AG',
                        'cities' => [
                            'Aguadilla' => ['postal_code' => '00603'],
                        ],
                    ],
                    'Arecibo' => [
                        'code' => 'AR',
                        'cities' => [
                            'Arecibo' => ['postal_code' => '00612'],
                        ],
                    ],
                    'Fajardo' => [
                        'code' => 'FJ',
                        'cities' => [
                            'Fajardo' => ['postal_code' => '00738'],
                        ],
                    ],
                    'Guaynabo' => [
                        'code' => 'GY',
                        'cities' => [
                            'Guaynabo' => ['postal_code' => '00965'],
                        ],
                    ],
                    'Humacao' => [
                        'code' => 'HM',
                        'cities' => [
                            'Humacao' => ['postal_code' => '00791'],
                        ],
                    ],
                    'Vieques' => [
                        'code' => 'VQ',
                        'cities' => [
                            'Isabel Segunda' => ['postal_code' => '00765'],
                        ],
                    ],
                    'Culebra' => [
                        'code' => 'CL',
                        'cities' => [
                            'Dewey' => ['postal_code' => '00775'],
                        ],
                    ],
                ],
            ],

            // Note: Saba is now part of Caribbean Netherlands (BQ) above

            // SAINT BARTHÉLEMY (BL) - French, uses French postal codes
            [
                'name' => 'Saint Barthélemy',
                'code' => 'BL',
                'phone_prefix' => '+590',
                'has_postal_code' => true,
                'postal_code_format' => '97133',
                'sort_order' => 19,
                'states' => [
                    'Saint Barthélemy' => [
                        'code' => 'SB',
                        'cities' => [
                            'Gustavia' => ['postal_code' => '97133'],
                            "St. Jean" => ['postal_code' => '97133'],
                            'Lorient' => ['postal_code' => '97133'],
                        ],
                    ],
                ],
            ],

            // SAINT KITTS AND NEVIS (KN) - No postal codes
            [
                'name' => 'Saint Kitts and Nevis',
                'code' => 'KN',
                'phone_prefix' => '+1-869',
                'has_postal_code' => false,
                'sort_order' => 20,
                'states' => [
                    'Saint George Basseterre' => [
                        'code' => 'GB',
                        'cities' => [
                            'Basseterre' => [],
                            'Bird Rock' => [],
                        ],
                    ],
                    'Christ Church Nichola Town' => [
                        'code' => 'CC',
                        'cities' => [
                            'Nichola Town' => [],
                        ],
                    ],
                    'Saint Anne Sandy Point' => [
                        'code' => 'AS',
                        'cities' => [
                            'Sandy Point' => [],
                        ],
                    ],
                    'Saint Thomas Middle Island' => [
                        'code' => 'TM',
                        'cities' => [
                            'Middle Island' => [],
                        ],
                    ],
                    'Saint George Gingerland' => [
                        'code' => 'GG',
                        'cities' => [
                            'Gingerland' => [],
                        ],
                    ],
                    'Saint John Figtree' => [
                        'code' => 'JF',
                        'cities' => [
                            'Figtree' => [],
                        ],
                    ],
                    'Saint James Windward' => [
                        'code' => 'JW',
                        'cities' => [
                            'Newcastle' => [],
                        ],
                    ],
                    'Saint Paul Charlestown' => [
                        'code' => 'PC',
                        'cities' => [
                            'Charlestown' => [],
                        ],
                    ],
                ],
            ],

            // SAINT LUCIA (LC) - Has postal codes (LC## ### format)
            [
                'name' => 'Saint Lucia',
                'code' => 'LC',
                'phone_prefix' => '+1-758',
                'has_postal_code' => true,
                'postal_code_format' => 'LC## ###',
                'sort_order' => 21,
                'states' => [
                    'Castries' => [
                        'code' => 'CA',
                        'cities' => [
                            'Castries' => ['postal_code' => 'LC01 101'],
                            'Gros Islet' => ['postal_code' => 'LC01 301'],
                            'Marisule' => ['postal_code' => 'LC01 102'],
                        ],
                    ],
                    'Gros Islet' => [
                        'code' => 'GI',
                        'cities' => [
                            'Gros Islet' => ['postal_code' => 'LC01 301'],
                            'Rodney Bay' => ['postal_code' => 'LC01 305'],
                            'Cap Estate' => ['postal_code' => 'LC01 304'],
                        ],
                    ],
                    'Soufrière' => [
                        'code' => 'SF',
                        'cities' => [
                            'Soufrière' => ['postal_code' => 'LC07 101'],
                        ],
                    ],
                    'Vieux Fort' => [
                        'code' => 'VF',
                        'cities' => [
                            'Vieux Fort' => ['postal_code' => 'LC04 101'],
                        ],
                    ],
                    'Micoud' => [
                        'code' => 'MC',
                        'cities' => [
                            'Micoud' => ['postal_code' => 'LC03 101'],
                        ],
                    ],
                    'Dennery' => [
                        'code' => 'DN',
                        'cities' => [
                            'Dennery' => ['postal_code' => 'LC02 101'],
                        ],
                    ],
                    'Anse la Raye' => [
                        'code' => 'AR',
                        'cities' => [
                            'Anse la Raye' => ['postal_code' => 'LC05 101'],
                        ],
                    ],
                    'Canaries' => [
                        'code' => 'CN',
                        'cities' => [
                            'Canaries' => ['postal_code' => 'LC06 101'],
                        ],
                    ],
                    'Choiseul' => [
                        'code' => 'CH',
                        'cities' => [
                            'Choiseul' => ['postal_code' => 'LC08 101'],
                        ],
                    ],
                    'Laborie' => [
                        'code' => 'LB',
                        'cities' => [
                            'Laborie' => ['postal_code' => 'LC09 101'],
                        ],
                    ],
                ],
            ],

            // SAINT MARTIN (MF) - French side, uses French postal codes
            [
                'name' => 'Saint Martin',
                'code' => 'MF',
                'phone_prefix' => '+590',
                'has_postal_code' => true,
                'postal_code_format' => '97150',
                'sort_order' => 22,
                'states' => [
                    'Saint Martin' => [
                        'code' => 'SM',
                        'cities' => [
                            'Marigot' => ['postal_code' => '97150'],
                            'Grand Case' => ['postal_code' => '97150'],
                            'Orient Bay' => ['postal_code' => '97150'],
                        ],
                    ],
                ],
            ],

            // SAINT VINCENT AND THE GRENADINES (VC) - No postal codes
            [
                'name' => 'Saint Vincent and the Grenadines',
                'code' => 'VC',
                'phone_prefix' => '+1-784',
                'has_postal_code' => false,
                'sort_order' => 23,
                'states' => [
                    'Charlotte' => [
                        'code' => 'CH',
                        'cities' => [
                            'Kingstown' => [],
                            'Georgetown' => [],
                        ],
                    ],
                    'Saint George' => [
                        'code' => 'SG',
                        'cities' => [
                            'Kingstown' => [],
                        ],
                    ],
                    'Saint Andrew' => [
                        'code' => 'SA',
                        'cities' => [
                            'Layou' => [],
                        ],
                    ],
                    'Saint David' => [
                        'code' => 'SD',
                        'cities' => [
                            'Chateaubelair' => [],
                        ],
                    ],
                    'Saint Patrick' => [
                        'code' => 'SP',
                        'cities' => [
                            'Barrouallie' => [],
                        ],
                    ],
                    'Grenadines' => [
                        'code' => 'GR',
                        'cities' => [
                            'Bequia' => [],
                            'Union Island' => [],
                            'Mustique' => [],
                            'Canouan' => [],
                        ],
                    ],
                ],
            ],

            // Note: Sint Eustatius is now part of Caribbean Netherlands (BQ) above

            // SINT MAARTEN (SX) - Dutch side, no postal codes
            [
                'name' => 'Sint Maarten',
                'code' => 'SX',
                'phone_prefix' => '+1-721',
                'has_postal_code' => false,
                'sort_order' => 25,
                'states' => [
                    'Sint Maarten' => [
                        'code' => 'SM',
                        'cities' => [
                            'Philipsburg' => [],
                            'Simpson Bay' => [],
                            'Maho' => [],
                            'Cole Bay' => [],
                        ],
                    ],
                ],
            ],

            // TRINIDAD AND TOBAGO (TT) - Has postal codes (6-digit)
            [
                'name' => 'Trinidad and Tobago',
                'code' => 'TT',
                'phone_prefix' => '+1-868',
                'has_postal_code' => true,
                'postal_code_format' => '######',
                'sort_order' => 26,
                'states' => [
                    'Port of Spain' => [
                        'code' => 'POS',
                        'cities' => [
                            'Port of Spain' => ['postal_code' => '100101'],
                            'Woodbrook' => ['postal_code' => '100104'],
                            "St. Clair" => ['postal_code' => '100103'],
                            'Newtown' => ['postal_code' => '100105'],
                        ],
                    ],
                    'San Fernando' => [
                        'code' => 'SF',
                        'cities' => [
                            'San Fernando' => ['postal_code' => '500101'],
                            'Gulf View' => ['postal_code' => '500104'],
                        ],
                    ],
                    'Chaguanas' => [
                        'code' => 'CH',
                        'cities' => [
                            'Chaguanas' => ['postal_code' => '500501'],
                        ],
                    ],
                    'Arima' => [
                        'code' => 'AR',
                        'cities' => [
                            'Arima' => ['postal_code' => '300101'],
                        ],
                    ],
                    'Point Fortin' => [
                        'code' => 'PF',
                        'cities' => [
                            'Point Fortin' => ['postal_code' => '700101'],
                        ],
                    ],
                    'Diego Martin' => [
                        'code' => 'DM',
                        'cities' => [
                            'Diego Martin' => ['postal_code' => '110101'],
                            'Westmoorings' => ['postal_code' => '110105'],
                        ],
                    ],
                    'San Juan-Laventille' => [
                        'code' => 'SJL',
                        'cities' => [
                            'San Juan' => ['postal_code' => '120101'],
                            'Barataria' => ['postal_code' => '120201'],
                        ],
                    ],
                    'Tunapuna-Piarco' => [
                        'code' => 'TP',
                        'cities' => [
                            'Tunapuna' => ['postal_code' => '130101'],
                            'Piarco' => ['postal_code' => '130201'],
                            'Trincity' => ['postal_code' => '130102'],
                        ],
                    ],
                    'Couva-Tabaquite-Talparo' => [
                        'code' => 'CTT',
                        'cities' => [
                            'Couva' => ['postal_code' => '520101'],
                        ],
                    ],
                    'Princes Town' => [
                        'code' => 'PT',
                        'cities' => [
                            'Princes Town' => ['postal_code' => '530101'],
                        ],
                    ],
                    'Penal-Debe' => [
                        'code' => 'PD',
                        'cities' => [
                            'Penal' => ['postal_code' => '540101'],
                        ],
                    ],
                    'Siparia' => [
                        'code' => 'SI',
                        'cities' => [
                            'Siparia' => ['postal_code' => '550101'],
                        ],
                    ],
                    'Sangre Grande' => [
                        'code' => 'SG',
                        'cities' => [
                            'Sangre Grande' => ['postal_code' => '310101'],
                        ],
                    ],
                    'Mayaro-Rio Claro' => [
                        'code' => 'MRC',
                        'cities' => [
                            'Mayaro' => ['postal_code' => '320101'],
                            'Rio Claro' => ['postal_code' => '320201'],
                        ],
                    ],
                    'Tobago' => [
                        'code' => 'TOB',
                        'cities' => [
                            'Scarborough' => ['postal_code' => '850101'],
                            'Crown Point' => ['postal_code' => '850201'],
                            'Plymouth' => ['postal_code' => '850301'],
                        ],
                    ],
                ],
            ],

            // TURKS AND CAICOS ISLANDS (TC) - Has postal codes (TKCA 1ZZ format)
            [
                'name' => 'Turks and Caicos Islands',
                'code' => 'TC',
                'phone_prefix' => '+1-649',
                'has_postal_code' => true,
                'postal_code_format' => 'TKCA 1ZZ',
                'sort_order' => 27,
                'states' => [
                    'Providenciales' => [
                        'code' => 'PV',
                        'cities' => [
                            'Providenciales' => ['postal_code' => 'TKCA 1ZZ'],
                            'Grace Bay' => ['postal_code' => 'TKCA 1ZZ'],
                            'Turtle Cove' => ['postal_code' => 'TKCA 1ZZ'],
                        ],
                    ],
                    'Grand Turk' => [
                        'code' => 'GT',
                        'cities' => [
                            'Cockburn Town' => ['postal_code' => 'TKCA 1ZZ'],
                        ],
                    ],
                    'South Caicos' => [
                        'code' => 'SC',
                        'cities' => [
                            'Cockburn Harbour' => ['postal_code' => 'TKCA 1ZZ'],
                        ],
                    ],
                    'North Caicos' => [
                        'code' => 'NC',
                        'cities' => [
                            'Bottle Creek' => ['postal_code' => 'TKCA 1ZZ'],
                        ],
                    ],
                    'Middle Caicos' => [
                        'code' => 'MC',
                        'cities' => [
                            'Conch Bar' => ['postal_code' => 'TKCA 1ZZ'],
                        ],
                    ],
                    'Salt Cay' => [
                        'code' => 'SL',
                        'cities' => [
                            'Balfour Town' => ['postal_code' => 'TKCA 1ZZ'],
                        ],
                    ],
                ],
            ],

            // U.S. VIRGIN ISLANDS (VI) - US postal codes (5-digit, 008xx)
            [
                'name' => 'U.S. Virgin Islands',
                'code' => 'VI',
                'phone_prefix' => '+1-340',
                'has_postal_code' => true,
                'postal_code_format' => '008##',
                'sort_order' => 28,
                'states' => [
                    'Saint Thomas' => [
                        'code' => 'STT',
                        'cities' => [
                            'Charlotte Amalie' => ['postal_code' => '00802'],
                            'Frenchtown' => ['postal_code' => '00802'],
                            'Red Hook' => ['postal_code' => '00802'],
                            "Havensight" => ['postal_code' => '00802'],
                        ],
                    ],
                    'Saint Croix' => [
                        'code' => 'STX',
                        'cities' => [
                            'Christiansted' => ['postal_code' => '00820'],
                            'Frederiksted' => ['postal_code' => '00840'],
                            'Kingshill' => ['postal_code' => '00850'],
                        ],
                    ],
                    'Saint John' => [
                        'code' => 'STJ',
                        'cities' => [
                            'Cruz Bay' => ['postal_code' => '00830'],
                            'Coral Bay' => ['postal_code' => '00830'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
