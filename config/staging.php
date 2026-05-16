<?php

return [
    'basic_auth' => [
        'user' => env('STAGING_AUTH_USER', 'staging'),
        'password' => env('STAGING_AUTH_PASSWORD', ''),
    ],
];
