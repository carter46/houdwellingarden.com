<?php
declare(strict_types=1);

function http_fetch_binary(string $url): ?string
{
    if ($url === '') {
        return null;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ],
        ]);
        $bin = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (is_string($bin) && $code >= 200 && $code < 400 && strlen($bin) > 800) {
            return $bin;
        }
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 90,
            'header' => "User-Agent: Mozilla/5.0\r\nAccept: image/*\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $bin = @file_get_contents($url, false, $ctx);

    return (is_string($bin) && strlen($bin) > 800) ? $bin : null;
}
