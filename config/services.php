<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],


    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'live'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'currency' => env('PAYPAL_CURRENCY', 'MXN'),
    ],

    'zoom' => [
        'account_id' => env('ZOOM_ACCOUNT_ID'),
        'client_id' => env('ZOOM_CLIENT_ID'),
        'client_secret' => env('ZOOM_CLIENT_SECRET'),
        'base_url' => env('ZOOM_BASE_URL', 'https://api.zoom.us/v2'),
        'timezone' => env('ZOOM_TIMEZONE', env('APP_TIMEZONE', 'America/Mexico_City')),
        'default_duration' => (int) env('ZOOM_DEFAULT_DURATION', 50),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
