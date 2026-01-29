<?php

/**
 * Carrier API Configuration
 * 
 * Add the following to your .env file:
 * 
 * # FedEx REST API
 * FEDEX_CLIENT_ID=your_client_id
 * FEDEX_CLIENT_SECRET=your_secret
 * FEDEX_ACCOUNT_NUMBER=your_account
 * FEDEX_SANDBOX=true
 * 
 * # DHL MyDHL API
 * DHL_API_KEY=your_api_key
 * DHL_API_SECRET=your_secret
 * DHL_ACCOUNT_NUMBER=your_account
 * DHL_SANDBOX=true
 * 
 * # UPS (Optional)
 * UPS_CLIENT_ID=
 * UPS_CLIENT_SECRET=
 * UPS_ACCOUNT_NUMBER=
 * UPS_SANDBOX=true
 * 
 * # MyUS API
 * MYUS_BASE_URL=https://gateway.myus.com
 * MYUS_API_KEY=your_api_key
 * MYUS_BEARER_TOKEN=your_bearer_token
 * MYUS_AFFILIATE_ID=your_affiliate_id
 * MYUS_MEMBER_ID=your_member_id
 * MYUS_SUITE=your_suite
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Carrier
    |--------------------------------------------------------------------------
    |
    | The default carrier to use when not specified.
    |
    */
    'default' => env('DEFAULT_CARRIER', 'fedex'),

    /*
    |--------------------------------------------------------------------------
    | Label Format
    |--------------------------------------------------------------------------
    |
    | Default format for shipping labels: PDF, PNG, ZPL (thermal)
    |
    */
    'label_format' => env('CARRIER_LABEL_FORMAT', 'PDF'),

    /*
    |--------------------------------------------------------------------------
    | Default Sender Information
    |--------------------------------------------------------------------------
    |
    | Used when package doesn't have a sender address
    |
    */
    'default_sender_name' => env('DEFAULT_SENDER_NAME', 'Marketz Warehouse'),
    'default_sender_company' => env('DEFAULT_SENDER_COMPANY', 'Marketz LLC'),
    'default_sender_phone' => env('DEFAULT_SENDER_PHONE', '3051234567'),
    'default_sender_email' => env('DEFAULT_SENDER_EMAIL', 'shipping@marketz.com'),
    'default_sender_address' => env('DEFAULT_SENDER_ADDRESS', '7900 NW 25th St'),
    'default_sender_city' => env('DEFAULT_SENDER_CITY', 'Miami'),
    'default_sender_state' => env('DEFAULT_SENDER_STATE', 'FL'),
    'default_sender_zip' => env('DEFAULT_SENDER_ZIP', '33122'),
    'default_sender_country' => env('DEFAULT_SENDER_COUNTRY', 'US'),
    'default_sender_ein' => env('DEFAULT_SENDER_EIN', ''), // EIN (Tax ID) for EEI filing support

    /*
    |--------------------------------------------------------------------------
    | FedEx Configuration
    |--------------------------------------------------------------------------
    |
    | FedEx REST API (Web Services deprecated Aug 2024)
    | Get credentials: https://developer.fedex.com/
    |
    */
    'fedex' => [
        'client_id' => env('FEDEX_CLIENT_ID', ''),
        'client_secret' => env('FEDEX_CLIENT_SECRET', ''),
        'account_number' => env('FEDEX_ACCOUNT_NUMBER', ''),
        'sandbox' => env('FEDEX_SANDBOX', true),
        'base_url' => env('FEDEX_SANDBOX', true)
            ? 'https://apis-sandbox.fedex.com'
            : 'https://apis.fedex.com',

        // Default service type
        'default_service' => env('FEDEX_DEFAULT_SERVICE', 'FEDEX_INTERNATIONAL_PRIORITY'),

        // Packaging options
        'packaging_types' => [
            'YOUR_PACKAGING',
            'FEDEX_BOX',
            'FEDEX_ENVELOPE',
            'FEDEX_PAK',
            'FEDEX_TUBE',
            'FEDEX_10KG_BOX',
            'FEDEX_25KG_BOX',
        ],

        // Available services
        'services' => [
            'FEDEX_INTERNATIONAL_PRIORITY' => 'FedEx International Priority',
            'FEDEX_INTERNATIONAL_ECONOMY' => 'FedEx International Economy',
            'INTERNATIONAL_FIRST' => 'FedEx International First',
            'FEDEX_GROUND' => 'FedEx Ground',
            'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DHL Configuration
    |--------------------------------------------------------------------------
    |
    | DHL MyDHL API (legacy XML deprecated late 2024)
    | Get credentials: https://developer.dhl.com/
    |
    */
    'dhl' => [
        'api_key' => env('DHL_API_KEY', ''),
        'api_secret' => env('DHL_API_SECRET', ''),
        'account_number' => env('DHL_ACCOUNT_NUMBER', ''),
        'sandbox' => env('DHL_SANDBOX', true),
        'base_url' => env('DHL_SANDBOX', true)
            ? 'https://express.api.dhl.com/mydhlapi/test'
            : 'https://express.api.dhl.com/mydhlapi',

        // Default service type
        'default_service' => env('DHL_DEFAULT_SERVICE', 'EXPRESS_WORLDWIDE'),

        // Available services
        'services' => [
            'EXPRESS_WORLDWIDE' => 'DHL Express Worldwide',
            'EXPRESS_12_00' => 'DHL Express 12:00',
            'EXPRESS_9_00' => 'DHL Express 9:00',
            'ECONOMY_SELECT' => 'DHL Economy Select',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UPS Configuration
    |--------------------------------------------------------------------------
    |
    | UPS Developer API
    | Get credentials: https://developer.ups.com/
    |
    */
    'ups' => [
        'client_id' => env('UPS_CLIENT_ID', ''),
        'client_secret' => env('UPS_CLIENT_SECRET', ''),
        'account_number' => env('UPS_ACCOUNT_NUMBER', ''),
        'sandbox' => env('UPS_SANDBOX', true),
        'base_url' => env('UPS_SANDBOX', true)
            ? 'https://wwwcie.ups.com'
            : 'https://onlinetools.ups.com',

        // Default service type
        'default_service' => env('UPS_DEFAULT_SERVICE', '65'), // UPS Worldwide Saver

        // Available services (service codes)
        'services' => [
            '07' => 'UPS Worldwide Express',
            '08' => 'UPS Worldwide Expedited',
            '11' => 'UPS Standard',
            '54' => 'UPS Worldwide Express Plus',
            '65' => 'UPS Worldwide Saver',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MyUS Configuration
    |--------------------------------------------------------------------------
    |
    | MyUS Package Forwarding Service API
    | Base URL: gateway.myus.com
    |
    */
    'myus' => [
        'api_key' => env('MYUS_API_KEY', ''),
        'bearer_token' => env('MYUS_BEARER_TOKEN', ''),
        'affiliate_id' => env('MYUS_AFFILIATE_ID', ''),
        'registration_options_id' => env('MYUS_REGISTRATION_OPTIONS_ID', ''),
        'member_id' => env('MYUS_MEMBER_ID', ''),
        'suite' => env('MYUS_SUITE', ''),
        'password' => env('MYUS_PASSWORD', ''),
        'base_url' => env('MYUS_BASE_URL', 'https://gateway.myus.com'),
        'sandbox' => env('MYUS_SANDBOX', false),

        // Default service type
        'default_service' => env('MYUS_DEFAULT_SERVICE', 'STANDARD'),

        // Available services
        'services' => [
            'STANDARD' => 'MyUS Standard Shipping',
            'EXPRESS' => 'MyUS Express Shipping',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping Option Mapping
    |--------------------------------------------------------------------------
    |
    | Maps InternationalShippingOptions.id to carrier and service
    |
    */
    'option_mapping' => [
        // Format: option_id => ['carrier' => 'name', 'service' => 'SERVICE_CODE']
        1 => ['carrier' => 'fedex', 'service' => 'FEDEX_INTERNATIONAL_PRIORITY'],
        2 => ['carrier' => 'fedex', 'service' => 'FEDEX_INTERNATIONAL_ECONOMY'],
        3 => ['carrier' => 'dhl', 'service' => 'EXPRESS_WORLDWIDE'],
        4 => ['carrier' => 'dhl', 'service' => 'ECONOMY_SELECT'],
        5 => ['carrier' => 'ups', 'service' => '65'], // Worldwide Saver
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Fetching Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for live rate API calls with caching and fallback
    |
    */
    'rates' => [
        // Cache TTL in seconds (default 5 minutes)
        'cache_ttl' => env('CARRIER_RATE_CACHE_TTL', 300),

        // API timeout in seconds
        'timeout' => env('CARRIER_RATE_TIMEOUT', 5),

        // NOTE: fallback_enabled removed - errors now show refresh button in UI
    ],
];
