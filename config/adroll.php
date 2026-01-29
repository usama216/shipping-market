<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AdRoll Pixel Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for AdRoll pixel tracking.
    | Get your Pixel ID from AdRoll Dashboard:
    | https://app.adroll.com/
    |
    */

    /*
    |--------------------------------------------------------------------------
    | AdRoll Pixel ID (pix_id)
    |--------------------------------------------------------------------------
    |
    | Your AdRoll pixel ID
    | Found in your AdRoll account settings
    |
    */

    'pix_id' => env('ADROLL_PIX_ID', null),

    /*
    |--------------------------------------------------------------------------
    | AdRoll Advertiser ID (adv_id)
    |--------------------------------------------------------------------------
    |
    | Your AdRoll advertiser ID
    | Found in your AdRoll account settings
    |
    */

    'adv_id' => env('ADROLL_ADV_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Enable AdRoll Pixel
    |--------------------------------------------------------------------------
    |
    | Set to false to disable AdRoll pixel tracking globally.
    | Useful for development or staging environments.
    |
    */

    'enabled' => env('ADROLL_PIXEL_ENABLED', true),
];
