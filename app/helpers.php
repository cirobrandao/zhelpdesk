<?php

declare(strict_types=1);

function env(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null ? $default : (string) $value;
}

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . $path;
}

function storage_path(string $path = ''): string
{
    $base = base_path('storage');
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . $path;
}

function t(string $key, array $params = [], ?string $locale = null): string
{
    static $cache = [];

    $locale = $locale ?: ($_SESSION['locale'] ?? 'pt_BR');
    $fallbacks = [$locale];
    if (strpos($locale, '_') !== false) {
        $fallbacks[] = substr($locale, 0, 2);
    }
    $fallbacks[] = 'pt_BR';
    $fallbacks[] = 'pt';

    foreach ($fallbacks as $loc) {
        if (!isset($cache[$loc])) {
            $file = base_path('app/i18n/' . $loc . '.php');
            $cache[$loc] = file_exists($file) ? require $file : [];
        }
        if (array_key_exists($key, $cache[$loc])) {
            $text = $cache[$loc][$key];
            foreach ($params as $param => $value) {
                $text = str_replace('{' . $param . '}', (string) $value, $text);
            }
            return $text;
        }
    }

    return $key;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
