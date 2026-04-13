<?php

return [
    'token' => env('TOKEN'),
    'key' => env('NBA_API_KEY'),
    'base_uri' => env('NBA_API_URL'),
    'timeout' => env('API_BASKETBALL_TIMEOUT', 1000),
];
