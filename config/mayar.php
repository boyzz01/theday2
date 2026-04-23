<?php

return [
    'api_key'       => env('MAYAR_API_KEY', ''),
    'is_production' => env('MAYAR_IS_PRODUCTION', false),
    'base_url'      => env('MAYAR_IS_PRODUCTION', false)
        ? 'https://api.mayar.id/hl/v1'
        : 'https://api.mayar.club/hl/v1',
];
