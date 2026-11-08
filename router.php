<?php
/**
 * Router for PHP built-in server: php -S 127.0.0.1:8765 router.php
 */
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = '/' . trim($uri, '/');
if ($uri === '//') {
    $uri = '/';
}

$rel = ltrim($uri, '/');
$relPath = $rel !== '' ? str_replace('/', DIRECTORY_SEPARATOR, $rel) : '';
$file = $relPath !== '' ? (__DIR__ . DIRECTORY_SEPARATOR . $relPath) : __DIR__;

// Static assets (css, js, images, uploads) — let the built-in server stream the file.
if ($relPath !== '' && is_file($file) && !preg_match('/\.php$/i', $relPath)) {
    return false;
}

if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

if (is_file($file) && preg_match('/\.php$/i', $relPath)) {
    require $file;
    return true;
}

$phpFile = __DIR__ . DIRECTORY_SEPARATOR . $relPath . '.php';
if ($relPath !== '' && is_file($phpFile)) {
    require $phpFile;
    return true;
}

http_response_code(404);
echo 'Not found';
return true;
