<?php

// The PULSESEND_* variables of pulsesend/pulsesend-laravel are still read when
// the NOBURI_SEND_* ones are missing, so an app can switch packages first.

return [

    /*
    |--------------------------------------------------------------------------
    | Noburi Send API Key
    |--------------------------------------------------------------------------
    |
    | The key used by the mail transport and the Noburi Send facade. Create one
    | in the Noburi Send dashboard, under API keys. A ps_test_ key accepts every
    | call but delivers nothing, which suits local and CI environments.
    |
    */

    'api_key' => env('NOBURI_SEND_API_KEY', env('PULSESEND_API_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Noburi Send Base URL
    |--------------------------------------------------------------------------
    |
    | Where the API lives. Leave it empty to use the public Noburi Send API.
    |
    */

    'base_url' => env('NOBURI_SEND_BASE_URL', env('PULSESEND_BASE_URL')),

    /*
    |--------------------------------------------------------------------------
    | Noburi Send Routes
    |--------------------------------------------------------------------------
    |
    | Whether the package registers its webhook route. By default (null) it
    | does so only when PULSESEND_WEBHOOK_SECRET is set, so an app that
    | doesn't use webhooks exposes nothing. true or false forces it.
    |
    */

    'routes' => env('NOBURI_SEND_ROUTES', env('PULSESEND_ROUTES')),

    /*
    |--------------------------------------------------------------------------
    | Noburi Send Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where the package routes will be accessible from.
    | If the setting is null, the routes reside under the same domain as your
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('NOBURI_SEND_DOMAIN', env('PULSESEND_DOMAIN')),

    /*
    |--------------------------------------------------------------------------
    | Noburi Send Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where the package routes, such as the webhook
    | handler, will be available from: POST /noburi-send/webhook by default.
    |
    */

    'path' => env('NOBURI_SEND_PATH', env('PULSESEND_PATH', 'noburi-send')),

    /*
    |--------------------------------------------------------------------------
    | Noburi Send Webhooks
    |--------------------------------------------------------------------------
    |
    | The signing secret of your webhook endpoint, shown in the dashboard when
    | you create it. When set, every call is checked against its signature.
    | The tolerance is the largest drift accepted, in seconds.
    |
    */

    'webhook' => [
        'secret' => env('NOBURI_SEND_WEBHOOK_SECRET', env('PULSESEND_WEBHOOK_SECRET')),
        'tolerance' => env('NOBURI_SEND_WEBHOOK_TOLERANCE', env('PULSESEND_WEBHOOK_TOLERANCE', 300)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | The list of middlewares that will be assigned to the webhook route.
    |
    */

    'middleware' => [],

];
