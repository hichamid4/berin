<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile Device Tokens
    |--------------------------------------------------------------------------
    |
    | Each sign-in from the BreIn mobile app issues one Sanctum token per device.
    | Tokens expire after this many days; the member then signs in again.
    |
    */

    'token_lifetime_days' => (int) env('MOBILE_TOKEN_LIFETIME_DAYS', 60),

    /*
    |--------------------------------------------------------------------------
    | Real-time Connection (Laravel Reverb)
    |--------------------------------------------------------------------------
    |
    | Where the phone opens its WebSocket. The host is often different from the
    | REVERB_HOST the server publishes to (an emulator reaches the computer as
    | 10.0.2.2, a phone through the LAN IP). Leave it empty to reuse the host of
    | the API base URL the app was configured with. Only the public app key is
    | sent to the phone; the Reverb secret never leaves the server.
    |
    */

    'realtime' => [
        'host' => env('MOBILE_REVERB_HOST') ?: null,
        'port' => (int) env('MOBILE_REVERB_PORT', env('REVERB_PORT', 8080)),
        'scheme' => env('MOBILE_REVERB_SCHEME', env('REVERB_SCHEME', 'http')),

        /*
        | Queue connection used to broadcast chat events. "deferred" sends them right after the HTTP
        | response, without a queue worker and with the lowest latency. Use "database" (or "redis")
        | with `php artisan queue:work --queue=broadcasts` when broadcasts must be retried on failure.
        */
        'broadcast_queue' => env('MOBILE_BROADCAST_QUEUE', 'deferred'),
    ],

];
