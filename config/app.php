<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ZHelpdesk'),
    'env' => env('APP_ENV', 'local'),
    'url' => env('APP_URL', 'http://localhost:8080'),
    'debug' => env('APP_DEBUG', '0') === '1',
    'default_locale' => env('APP_DEFAULT_LOCALE', 'pt_BR'),
    'public_key' => env('APP_PUBLIC_KEY', ''),
    'update_endpoint' => env('UPDATE_ENDPOINT', ''),
    'register_enabled' => env('REGISTER_ENABLED', '0') === '1',
    'email_confirmation_enabled' => env('EMAIL_CONFIRMATION_ENABLED', '0') === '1',
    'session' => [
        'secure' => env('SESSION_SECURE', '0') === '1',
        'samesite' => env('SESSION_SAMESITE', 'Lax'),
    ],
];
