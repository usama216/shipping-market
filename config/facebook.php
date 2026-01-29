<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facebook Pixel Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Facebook Pixel tracking.
    | Get your Pixel ID from Facebook Business Manager:
    | https://business.facebook.com/events_manager
    |
    */

    'pixel_id' => env('FACEBOOK_PIXEL_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Enable Facebook Pixel
    |--------------------------------------------------------------------------
    |
    | Set to false to disable Facebook Pixel tracking globally.
    | Useful for development or staging environments.
    |
    */

    'enabled' => env('FACEBOOK_PIXEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Advanced Matching
    |--------------------------------------------------------------------------
    |
    | Enable advanced matching to improve ad delivery and measurement.
    | This sends hashed customer data (email, phone, etc.) to Facebook.
    |
    */

    'advanced_matching' => env('FACEBOOK_PIXEL_ADVANCED_MATCHING', true),
];
