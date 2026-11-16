<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PulseSend API Key
    |--------------------------------------------------------------------------
    |
    | The key used by the mail transport and the PulseSend facade. Create one
    | in the PulseSend dashboard, under API keys. A ps_test_ key accepts every
    | call but delivers nothing, which suits local and CI environments.
    |
    */

    'api_key' => env('PULSESEND_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | PulseSend Base URL
    |--------------------------------------------------------------------------
    |
    | Where the API lives. Leave it empty to use the public PulseSend API.
    |
    */

    'base_url' => env('PULSESEND_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | PulseSend Routes
    |--------------------------------------------------------------------------
    |
    | This option determines whether the package registers its routes, such
    | as the webhook handler. You may disable this when handling incoming
    | webhooks yourself or when the routes are not needed at all.
    |
    */

    'routes' => env('PULSESEND_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | PulseSend Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where the package routes will be accessible from.
    | If the setting is null, the routes reside under the same domain as your
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('PULSESEND_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | PulseSend Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where the package routes, such as the webhook
    | handler, will be available from: POST /pulsesend/webhook by default.
    |
    */

    'path' => env('PULSESEND_PATH', 'pulsesend'),

    /*
    |--------------------------------------------------------------------------
    | PulseSend Webhooks
    |--------------------------------------------------------------------------
    |
    | The signing secret of your webhook endpoint, shown in the dashboard when
    | you create it. When set, every call is checked against its signature.
    | The tolerance is the largest drift accepted, in seconds.
    |
    */

    'webhook' => [
        'secret' => env('PULSESEND_WEBHOOK_SECRET'),
        'tolerance' => env('PULSESEND_WEBHOOK_TOLERANCE', 300),
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
