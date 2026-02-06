<?php

declare(strict_types=1);

return [
    'host' => env('DB_HOST', 'mysql'),
    'port' => (int) env('DB_PORT', '3306'),
    'name' => env('DB_NAME', 'zhelpdesk'),
    'user' => env('DB_USER', 'zhelpdesk'),
    'pass' => env('DB_PASS', 'secret'),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
];
