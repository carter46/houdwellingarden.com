<?php
declare(strict_types=1);

require_once __DIR__ . '/lib-http-fetch.php';

/** Zillow CDN fetch (adds Referer); falls back to generic HTTP fetch. */
function zillow_fetch_binary(string $url): ?string
{
    if ($url === '') {
        return null;
    }
    if (str_contains($url, 'zillowstatic.com')) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: image/*',
                    'Referer: https://www.zillow.com/',
                ],
            ]);
            $bin = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($bin) && $code >= 200 && $code < 400 && strlen($bin) > 400) {
                return $bin;
            }
        }
    }

    return http_fetch_binary($url);
}
