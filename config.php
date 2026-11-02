<?php
/**
 * Central application configuration.
 *
 * Local dev: copy config.local.php.example → config.local.php (gitignored; overrides below).
 * InfinityFree / trust-offshore style: put MySQL credentials in config.php on the server,
 *   OR copy config.infinityfree.php.example → config.local.php on the server only.
 * Do NOT upload your PC's config.local.php to InfinityFree — it overrides config.php.
 */

declare(strict_types=1);

$config = [
    'app' => [
        'name' => 'Sunview Development and Consultancy (SDC)',
        // Safe defaults for deploy; override to local/debug only in config.local.php.
        'env' => 'production', // local | staging | production
        'debug' => false,
        'url' => 'http://localhost/real-estate-app',
        'timezone' => 'America/Denver',
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'real_estate',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        // brevo (API, primary) | mail (PHP) | log | smtp (legacy, not Brevo API)
        'driver' => 'log',
        'from_email' => 'noreply@example.com',
        'from_name' => 'SDC',
        'admin_notify_email' => 'admin@example.com',
        // Brevo Transactional API — set in config.local.php for staging/production
        'brevo_api_key' => '',
        // Optional SMTP (used when mail.driver=smtp, or as fallback when driver=brevo
        // and the Brevo API key is missing / Brevo send fails)
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls', // tls | ssl | ''
        'smtp_user' => '',
        'smtp_pass' => '',
    ],

    // Smartsupp live chat — set key in config.local.php (Settings → Chat box → Chat code)
    'smartsupp' => [
        'key' => '',
    ],

    /**
     * Admin account recovery (web forms). Turn off in production after setup.
     * admin.setup_enabled          → login shows link; /admin/setup.php create or update admin by email
     * admin.password_reset_enabled → login shows link; /admin/reset-password.php reset existing admin only
     * If no admin exists yet, /admin/setup.php is always allowed until the first account is created.
     */
    'admin' => [
        'setup_enabled' => false,
        'password_reset_enabled' => false,
    ],

    'security' => [
        'session_name' => 'sdc_re_session',
        'csrf_token_key' => '_csrf_token',
        'password_algo' => PASSWORD_DEFAULT,
        // null = auto-detect HTTPS; set true behind TLS; set false only for plain HTTP local.
        'cookie_secure' => null,
        // Only enable if a trusted reverse proxy sets X-Forwarded-Proto.
        'trust_forwarded_proto' => false,
        'login_max_attempts' => 5,
        'login_lockout_seconds' => 900,
        'inquiry_max_per_hour' => 5,
        // Optional shared secret for web health checks (leave empty to require admin or CLI).
        'health_token' => '',
    ],

    'uploads' => [
        'properties_dir' => __DIR__ . '/uploads/properties',
        'max_bytes' => 10 * 1024 * 1024, // 10MB upload cap; stored files normalized to property_image_max_bytes
        'property_image_max_bytes' => 400 * 1024,
        'property_image_min_bytes' => 200 * 1024,
        'allowed_mime' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /**
     * Public visibility rules (status => visible on public site).
     * under_contract / sold follow design/business; default public with badge support.
     */
    'visibility' => [
        'draft' => false,
        'private' => false,
        'available' => true,
        'pending' => true,
        'under_contract' => true,
        'sold' => true,
        'archived' => false,
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_readable($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

// Production safety: never leave debug on when env is production.
if (($config['app']['env'] ?? '') === 'production') {
    $config['app']['debug'] = false;
}

// If config.local.php still has a local dev app.url, asset links break on the public host
// (browser may prompt for "local network" access when loading http://127.0.0.1:8765/...).
if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $configuredUrl = rtrim((string) ($config['app']['url'] ?? ''), '/');
    $looksLocal = $configuredUrl === ''
        || preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?(/|$)#i', $configuredUrl) === 1;
    if ($looksLocal) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
        $scheme = $https ? 'https' : 'http';
        $config['app']['url'] = $scheme . '://' . preg_replace('/[^\w.\-:]/', '', (string) $_SERVER['HTTP_HOST']);
    }
}

return $config;
