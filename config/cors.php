<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '/login', '/logout'],
    'allowed_origins' => [
        'http://localhost:5173',
        'https://permissively-real-weimaraner-winlanem.cloudpub.ru',
        'https://convincingly-special-barbel.cloudpub.ru'
    ],
    'supports_credentials' => true,
    'allowed_methods' => ['*'],
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,
];
