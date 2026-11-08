<?php
/**
 * Compress public JPEG/PNG/WebP assets into the 300–400 KB band (high quality, web delivery).
 *
 *   php scripts/optimize-all-public-images.php [--dry-run]
 *
 * Scans uploads/properties and assets/img (skips SVG, small pathway WebP).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/image-optimize.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$maxBytes = property_image_max_bytes();
$minBytes = property_image_min_bytes();

$roots = [
    ['path' => APP_ROOT . '/uploads/properties', 'edge' => (int) app_config('uploads.property_image_max_edge', 2560)],
    ['path' => APP_ROOT . '/assets/img/regions', 'edge' => site_marketing_image_max_edge()],
    ['path' => APP_ROOT . '/assets/img/home-actions', 'edge' => site_marketing_image_max_edge()],
    ['path' => APP_ROOT . '/assets/img', 'edge' => site_marketing_image_max_edge(), 'files' => ['hero-home.jpg']],
];

/** @var list<string> */
$skipBasenames = [
    'buy.webp',
    'rent.webp',
    'sell.webp',
    'logo-sdc.svg',
    'logoe.png',
    'favicon.jpg',
];

echo 'Target: ' . round($minBytes / 1024) . '–' . round($maxBytes / 1024) . " KB per file (GD quality 82–94)\n";
if ($dryRun) {
    echo "DRY RUN\n";
}

$scanned = 0;
$changed = 0;
$errors = 0;
$skipped = 0;

/**
 * @param iterable<string> $paths
 */
function optimize_paths(iterable $paths, int $maxBytes, int $minBytes, int $maxEdge, bool $dryRun, int &$scanned, int &$changed, int &$errors): void
{
    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            continue;
        }
        $scanned++;
        $size = (int) filesize($path);
        if ($size <= $maxBytes) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($path, strlen(APP_ROOT) + 1));
        echo $rel . ' — ' . round($size / 1024, 1) . " KB\n";
        if ($dryRun) {
            continue;
        }
        $result = property_image_optimize_file($path, $maxBytes, $minBytes, $maxEdge);
        if (!$result['ok']) {
            echo "  ERROR: {$result['message']}\n";
            $errors++;
            continue;
        }
        if ($result['changed']) {
            $changed++;
            echo '  → ' . round($result['after'] / 1024, 1) . ' KB — ' . $result['message'] . "\n";
        }
    }
}

foreach ($roots as $spec) {
    $base = $spec['path'];
    $edge = (int) $spec['edge'];
    if (isset($spec['files'])) {
        $list = [];
        foreach ($spec['files'] as $name) {
            $list[] = $base . '/' . $name;
        }
        optimize_paths($list, $maxBytes, $minBytes, $edge === site_marketing_image_max_edge() ? site_hero_image_max_edge() : $edge, $dryRun, $scanned, $changed, $errors);
        continue;
    }
    if (!is_dir($base)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );
    $batch = [];
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }
        $name = $file->getFilename();
        if (in_array(strtolower($name), $skipBasenames, true)) {
            $skipped++;
            continue;
        }
        $batch[] = $file->getPathname();
    }
    optimize_paths($batch, $maxBytes, $minBytes, $edge, $dryRun, $scanned, $changed, $errors);
}

// Remaining loose assets/img photos (agents, legacy decor) not under subfolders already scanned
$imgRoot = APP_ROOT . '/assets/img';
if (is_dir($imgRoot)) {
    foreach (glob($imgRoot . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $path) {
        $base = basename($path);
        if (in_array(strtolower($base), $skipBasenames, true)) {
            continue;
        }
        optimize_paths([$path], $maxBytes, $minBytes, site_marketing_image_max_edge(), $dryRun, $scanned, $changed, $errors);
    }
}

echo "\nScanned: {$scanned}, optimized: {$changed}, skipped known-small: {$skipped}, errors: {$errors}\n";
if ($dryRun && $changed === 0) {
    echo "Re-run without --dry-run to apply.\n";
}
