<?php

namespace App\Config;

class App
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? '1' : '0');

        date_default_timezone_set('Europe/Madrid');
        mb_internal_encoding('UTF-8');

        self::$initialized = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        return $_ENV['APP_DEBUG'] === 'true';
    }

    public static function name(): string
    {
        return $_ENV['APP_NAME'] ?? 'GMAO';
    }

    public static function url(): string
    {
        return rtrim($_ENV['APP_URL'] ?? '', '/');
    }
}
