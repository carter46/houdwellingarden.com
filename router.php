<?php
/**
 * Router for PHP built-in server: php -S 127.0.0.1:8765 router.php
 */
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

if (is_file(__DIR__ . $uri . '.php')) {
    require __DIR__ . $uri . '.php';
    return true;
}

http_response_code(404);
echo 'Not found';
return true;
