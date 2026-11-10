<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Private administration path
    |--------------------------------------------------------------------------
    |
    | URL prefix of the administration area, including its sign-in page. It is a
    | secret kept only in the local .env file (never committed). Generate one with
    | "php artisan app:admin-path --generate" and show it with "--show".
    |
    | Knowing the path never grants access: every administration route still
    | requires a password, the administrator role, an active verified account and
    | a second factor. The path only hides the sign-in page from casual scanning.
    |
    | When ADMIN_PATH is missing or weaker than the rules in App\Support\AdminPath,
    | a path derived from APP_KEY is used instead, so the area never falls back to
    | a predictable address such as /admin.
    |
    */

    'path' => env('ADMIN_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Second factor (authenticator app, TOTP)
    |--------------------------------------------------------------------------
    |
    | Every administrator must confirm an authenticator app code after the
    | password. The issuer is the account name shown in the authenticator app.
    |
    */

    'two_factor' => [
        'issuer' => env('ADMIN_TWO_FACTOR_ISSUER', env('APP_NAME', 'BreIn').' Admin'),
        'recovery_codes' => 8,
        'pending_login_minutes' => 10,
    ],

];
