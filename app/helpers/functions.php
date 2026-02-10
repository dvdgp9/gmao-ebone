<?php

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url($path);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('clearOld')) {
    function clearOld(): void
    {
        unset($_SESSION['old']);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool
    {
        $token = $_POST['_token'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

if (!function_exists('flash')) {
    function flash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}

if (!function_exists('is_active')) {
    function is_active(string $path): string
    {
        $currentUrl = trim($_GET['url'] ?? '', '/');
        return str_starts_with($currentUrl, trim($path, '/')) ? 'active' : '';
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y'): string
    {
        if (!$date) return '-';
        $dt = new DateTime($date);
        return $dt->format($format);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): void
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die();
    }
}
