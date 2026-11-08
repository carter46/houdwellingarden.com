<?php
/**
 * Real city skyline/neighborhood photos (Pexels — free license, demo placeholders).
 * Replaces Zillow pathway illustrations on homepage location tiles.
 *
 * php scripts/seed-pexels-region-photos.php
 * php scripts/seed-pexels-region-photos.php --force
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/lib-http-fetch.php';

$force = in_array('--force', $argv ?? [], true);

/** City-related Pexels stills (skyline / streetscape — not illustrations). */
$map = [
    'austin-metro' => 'https://images.pexels.com/photos/3411136/pexels-photo-3411136.jpeg?auto=compress&cs=tinysrgb&w=1600',
    'phoenix-metro' => 'https://images.pexels.com/photos/1796724/pexels-photo-1796724.jpeg?auto=compress&cs=tinysrgb&w=1600',
    'atlanta-metro' => 'https://images.pexels.com/photos/2404843/pexels-photo-2404843.jpeg?auto=compress&cs=tinysrgb&w=1600',
    'denver-metro' => 'https://images.pexels.com/photos/1366919/pexels-photo-1366919.jpeg?auto=compress&cs=tinysrgb&w=1600',
    'chicago-metro' => 'https://images.pexels.com/photos/2901209/pexels-photo-2901209.jpeg?auto=compress&cs=tinysrgb&w=1600',
];

$dir = APP_ROOT . '/assets/img/regions';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create {$dir}\n");
    exit(1);
}

$pdo = db();
$upd = $pdo->prepare('UPDATE regions SET image_path = ? WHERE slug = ?');

foreach ($map as $slug => $url) {
    $rel = 'assets/img/regions/' . $slug . '.jpg';
    $dest = APP_ROOT . '/' . $rel;
    if (!$force && is_file($dest) && filesize($dest) > 2000) {
        echo "Skip {$rel}\n";
    } else {
        $bin = http_fetch_binary($url);
        if ($bin === null) {
            fwrite(STDERR, "Download failed: {$slug}\n");
            continue;
        }
        file_put_contents($dest, $bin);
        echo "Wrote {$rel}\n";
        foreach (['webp', 'png', 'jpeg'] as $old) {
            $legacy = APP_ROOT . '/assets/img/regions/' . $slug . '.' . $old;
            if ($old !== 'jpeg' && is_file($legacy)) {
                @unlink($legacy);
            }
        }
        $webpLegacy = APP_ROOT . '/assets/img/regions/' . $slug . '.webp';
        if (is_file($webpLegacy)) {
            @unlink($webpLegacy);
        }
    }
    if (is_file($dest)) {
        $upd->execute([$rel, $slug]);
    }
}

$heroUrl = 'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg?auto=compress&cs=tinysrgb&w=1920';
$heroRel = 'assets/img/hero-home.jpg';
$heroDest = APP_ROOT . '/' . $heroRel;
if ($force || !is_file($heroDest) || filesize($heroDest) < 2000) {
    $bin = http_fetch_binary($heroUrl);
    if ($bin !== null) {
        file_put_contents($heroDest, $bin);
        echo "Wrote {$heroRel} (hero background)\n";
    }
}

echo "Region tiles now use Pexels city photos (local JPG).\n";
