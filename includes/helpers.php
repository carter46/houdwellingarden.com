<?php
/**
 * Shared helper functions.
 */

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

/**
 * Same-origin path for links and static assets (InfinityFree-friendly, like /css/styles.css).
 * On the web server this is always relative to the current host — no app.url required.
 */
function base_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        return $path === '' ? '/' : '/' . $path;
    }

    $base = rtrim((string) app_config('app.url', ''), '/');
    if ($base === '') {
        return $path === '' ? '/' : '/' . $path;
    }

    return $path === '' ? $base : $base . '/' . $path;
}

/**
 * Full https? URL for emails, webhooks, and CLI (uses current host on web requests).
 */
function absolute_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $origin = rtrim(app_public_origin(), '/');

    return $path === '' ? $origin . '/' : $origin . '/' . $path;
}

function app_public_origin(): string
{
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
        $scheme = $https ? 'https' : 'http';
        $host = preg_replace('/[^\w.\-:]/', '', (string) $_SERVER['HTTP_HOST']);

        return $scheme . '://' . $host;
    }

    $configured = rtrim((string) app_config('app.url', ''), '/');

    return $configured !== '' ? $configured : 'http://localhost';
}

/**
 * Static asset under the site root with cache-busting filemtime (trust-offshore style).
 */
function asset_url(string $path): string
{
    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $localPath = APP_ROOT . $path;
    if (is_file($localPath)) {
        return $path . '?v=' . (string) filemtime($localPath);
    }

    return $path;
}

/**
 * Redirect only to same-app relative paths (or absolute URLs under app.url).
 * Rejects open redirects via external hosts.
 */
function redirect(string $path): never
{
    $target = redirect_safe_url($path);
    header('Location: ' . $target);
    exit;
}

function redirect_safe_url(string $path): string
{
    $path = trim($path);
    $appBase = rtrim(app_public_origin(), '/');

    if ($path === '') {
        return base_url('');
    }

    // Protocol-relative or absolute external URLs are rejected unless under app.url.
    if (preg_match('#^(https?:)?//#i', $path) === 1) {
        if (preg_match('#^https?://#i', $path) !== 1) {
            return base_url('');
        }
        if ($appBase !== '' && str_starts_with(strtolower($path), strtolower($appBase))) {
            return $path;
        }
        return base_url('');
    }

    // Relative path only
    if (str_contains($path, "\n") || str_contains($path, "\r") || str_starts_with($path, '//')) {
        return base_url('');
    }

    return base_url(ltrim($path, '/'));
}

function flash_set(string $key, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key, bool $clear = true): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    if ($clear && isset($_SESSION['_flash'][$key])) {
        unset($_SESSION['_flash'][$key]);
    }
    return is_string($message) ? $message : null;
}

function format_price(?float $price, bool $onRequest = false, string $currency = 'USD'): string
{
    if ($onRequest || $price === null) {
        return 'Price Upon Request';
    }

    $currency = strtoupper(trim($currency));
    $symbol = in_array($currency, ['USD', 'AUD', 'CAD', 'NZD'], true) ? '$' : $currency . ' ';
    return $symbol . number_format($price, 0, '.', ',');
}

/**
 * Display floor area for listings (DB column is `sqft`; AU listings often store imperial ft²).
 */
function property_floor_area_label(array $property): string
{
    if (!isset($property['sqft']) || !is_numeric($property['sqft'])) {
        return '';
    }
    $raw = (float) $property['sqft'];
    if ($raw <= 0) {
        return '';
    }

    $country = strtoupper(trim((string) ($property['country'] ?? '')));
    $currency = strtoupper(trim((string) ($property['currency'] ?? '')));
    $metric = in_array($country, ['AU', 'AUSTRALIA'], true) || $currency === 'AUD';

    if ($metric) {
        $m2 = $raw <= 400 ? $raw : $raw / 10.764;

        return number_format((int) round($m2)) . ' m²';
    }

    return number_format((int) round($raw)) . ' sq ft';
}

/**
 * Resolve a media path for <img src>.
 * Absolute http(s) only; protocol-relative // rejected. Relative paths use base_url().
 */
function media_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^//#', $path) === 1) {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    // Block path traversal in relative media
    if (str_contains($path, '..')) {
        return '';
    }
    return base_url(ltrim($path, '/'));
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

/**
 * Append a line to storage/logs/app.log (and PHP error_log).
 */
function app_log(string $channel, string $message): void
{
    $line = sprintf('[%s] [%s] %s', date('c'), $channel, $message);
    error_log('[SDC] ' . $channel . ': ' . $message);
    $logDir = APP_ROOT . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logDir . '/app.log', $line . "\n", FILE_APPEND | LOCK_EX);
}
