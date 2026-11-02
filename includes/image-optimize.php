<?php
/**
 * Property image size normalization (target band for web delivery).
 */

declare(strict_types=1);

function property_image_max_bytes(): int
{
    return (int) app_config('uploads.property_image_max_bytes', 400 * 1024);
}

function property_image_min_bytes(): int
{
    return (int) app_config('uploads.property_image_min_bytes', 200 * 1024);
}

/**
 * @return \GdImage|null
 */
function property_image_load_from_path(string $path)
{
    if (!extension_loaded('gd') || !is_file($path)) {
        return null;
    }

    $info = @getimagesize($path);
    if ($info === false) {
        return null;
    }

    $mime = (string) ($info['mime'] ?? '');
    $img = null;
    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $img = @imagecreatefromjpeg($path);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $img = @imagecreatefrompng($path);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($path);
    }

    return ($img instanceof GdImage) ? $img : null;
}

function property_image_png_has_alpha(\GdImage $img): bool
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0) {
        return false;
    }
    $stepX = max(1, (int) floor($w / 8));
    $stepY = max(1, (int) floor($h / 8));
    for ($y = 0; $y < $h; $y += $stepY) {
        for ($x = 0; $x < $w; $x += $stepX) {
            $rgba = imagecolorat($img, $x, $y);
            $alpha = ($rgba & 0x7F000000) >> 24;
            if ($alpha > 0) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @return string|null JPEG or WebP binary
 */
function property_image_encode_jpeg(\GdImage $img, int $quality): ?string
{
    ob_start();
    $ok = imagejpeg($img, null, max(60, min(100, $quality)));
    $data = ob_get_clean();

    return ($ok && is_string($data) && $data !== '') ? $data : null;
}

/**
 * @return string|null
 */
function property_image_encode_webp(\GdImage $img, int $quality): ?string
{
    if (!function_exists('imagewebp')) {
        return null;
    }
    ob_start();
    $ok = imagewebp($img, null, max(60, min(100, $quality)));
    $data = ob_get_clean();

    return ($ok && is_string($data) && $data !== '') ? $data : null;
}

/**
 * @return \GdImage|null scaled copy (destroys source if new resource created)
 */
function property_image_scale(\GdImage $img, float $scale)
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0 || $scale >= 0.999) {
        return $img;
    }
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($dst === false) {
        return null;
    }
    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($img);

    return $dst;
}

function property_image_cap_long_edge(\GdImage $img, int $maxEdge): \GdImage
{
    $w = imagesx($img);
    $h = imagesy($img);
    $long = max($w, $h);
    if ($long <= $maxEdge) {
        return $img;
    }

    return property_image_scale($img, $maxEdge / $long) ?? $img;
}

/**
 * @return string|null
 */
function property_image_encode_png(\GdImage $img, int $compression): ?string
{
    imagesavealpha($img, true);
    imagealphablending($img, false);
    ob_start();
    $ok = imagepng($img, null, max(0, min(9, $compression)));
    $data = ob_get_clean();

    return ($ok && is_string($data) && $data !== '') ? $data : null;
}

/**
 * Find highest-quality encoding that fits under $maxBytes (prefers larger files within cap).
 *
 * @return array{data:string, quality:int, format:string}|null
 */
function property_image_best_encoding_under_limit(\GdImage $img, int $maxBytes, string $ext): ?array
{
    $best = null;
    $ext = strtolower($ext);
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }

    if ($ext === 'webp') {
        foreach ([94, 92, 90, 88, 86, 84, 82] as $q) {
            $data = property_image_encode_webp($img, $q);
            if ($data === null) {
                continue;
            }
            $len = strlen($data);
            if ($len <= $maxBytes && ($best === null || $len > strlen($best['data']))) {
                $best = ['data' => $data, 'quality' => $q, 'format' => 'webp'];
            }
        }

        return $best;
    }

    if ($ext === 'png') {
        foreach ([6, 7, 8, 5, 4] as $level) {
            $data = property_image_encode_png($img, $level);
            if ($data === null) {
                continue;
            }
            $len = strlen($data);
            if ($len <= $maxBytes && ($best === null || $len > strlen($best['data']))) {
                $best = ['data' => $data, 'quality' => $level, 'format' => 'png'];
            }
        }

        return $best;
    }

    foreach ([94, 92, 90, 88, 86, 84, 82] as $q) {
        $data = property_image_encode_jpeg($img, $q);
        if ($data === null) {
            continue;
        }
        $len = strlen($data);
        if ($len <= $maxBytes && ($best === null || $len > strlen($best['data']))) {
            $best = ['data' => $data, 'quality' => $q, 'format' => 'jpg'];
        }
    }

    return $best;
}

/**
 * Optimize file in place if over max size. Returns change summary.
 *
 * @return array{ok:bool, changed:bool, before:int, after:int, message:string, path:string}
 */
function property_image_optimize_file(string $absolutePath, ?int $maxBytes = null, ?int $minBytes = null): array
{
    $maxBytes ??= property_image_max_bytes();
    $minBytes ??= property_image_min_bytes();

    if (!is_file($absolutePath)) {
        return ['ok' => false, 'changed' => false, 'before' => 0, 'after' => 0, 'message' => 'File not found', 'path' => $absolutePath];
    }

    $before = (int) filesize($absolutePath);
    if ($before <= $maxBytes) {
        return ['ok' => true, 'changed' => false, 'before' => $before, 'after' => $before, 'message' => 'Already within limit', 'path' => $absolutePath];
    }

    if (!extension_loaded('gd')) {
        return ['ok' => false, 'changed' => false, 'before' => $before, 'after' => $before, 'message' => 'GD extension required', 'path' => $absolutePath];
    }

    $img = property_image_load_from_path($absolutePath);
    if ($img === null) {
        return ['ok' => false, 'changed' => false, 'before' => $before, 'after' => $before, 'message' => 'Could not load image', 'path' => $absolutePath];
    }

    $img = property_image_cap_long_edge($img, 2560);
    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

    $candidates = [];
    for ($attempt = 0; $attempt < 14; $attempt++) {
        $candidate = property_image_best_encoding_under_limit($img, $maxBytes, $ext);
        if ($candidate !== null) {
            $candidate['bytes'] = strlen($candidate['data']);
            $candidates[] = $candidate;
        }
        $next = property_image_scale($img, 0.92);
        if ($next === null) {
            break;
        }
        $img = $next;
    }

    imagedestroy($img);

    if ($candidates === []) {
        return ['ok' => false, 'changed' => false, 'before' => $before, 'after' => $before, 'message' => 'Could not fit under size limit', 'path' => $absolutePath];
    }

    $inBand = array_values(array_filter(
        $candidates,
        static fn (array $c): bool => $c['bytes'] >= $minBytes && $c['bytes'] <= $maxBytes
    ));
    if ($inBand !== []) {
        usort($inBand, static fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);
        $best = $inBand[0];
    } else {
        usort($candidates, static fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);
        $best = $candidates[0];
    }

    $targetPath = $absolutePath;
    $tmp = $targetPath . '.opt.tmp';
    if (@file_put_contents($tmp, $best['data']) === false) {
        return ['ok' => false, 'changed' => false, 'before' => $before, 'after' => $before, 'message' => 'Write failed', 'path' => $absolutePath];
    }

    if (!@rename($tmp, $targetPath)) {
        @unlink($tmp);

        return ['ok' => false, 'changed' => false, 'before' => $before, 'after' => $before, 'message' => 'Replace failed', 'path' => $absolutePath];
    }

    $after = (int) filesize($targetPath);
    $kb = round($after / 1024, 1);
    $msg = "Optimized to {$kb} KB (quality {$best['quality']}, {$best['format']})";
    if ($after < $minBytes) {
        $msg .= ' — under preferred minimum but within max cap';
    }

    return ['ok' => true, 'changed' => true, 'before' => $before, 'after' => $after, 'message' => $msg, 'path' => $targetPath];
}
