<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zalo Mini App / integration API
    |--------------------------------------------------------------------------
    |
    | Key must stay identical to the old system so existing Mini App and
    | partner clients keep working.
    |
    */
    'fixed_key' => 'sunny_zma_hanngusunny_online_2026',

    'key' => env('API_KEY', 'sunny_zma_hanngusunny_online_2026'),

    'public_base_url' => rtrim((string) env('API_PUBLIC_BASE_URL', 'https://online.hanngusunny.edu.vn'), '/'),

    'token_ttl_days' => max(1, (int) env('API_TOKEN_TTL_DAYS', 30)),
];
