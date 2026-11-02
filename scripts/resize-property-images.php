<?php
/**
 * Resize/compress property images over the configured max (default 400 KB).
 * Usage: php scripts/resize-property-images.php [--dry-run]
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/image-optimize.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$root = realpath(APP_ROOT . '/uploads/properties');
if ($root === false || !is_dir($root)) {
    echo "No uploads/properties directory.\n";
    exit(1);
}

$maxBytes = property_image_max_bytes();
$minBytes = property_image_min_bytes();
echo 'Target band: ' . round($minBytes / 1024) . '–' . round($maxBytes / 1024) . " KB (only files over max are changed)\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$scanned = 0;
$over = 0;
$changed = 0;
$errors = 0;

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        continue;
    }
    $scanned++;
    $path = $file->getPathname();
    $size = $file->getSize();
    if ($size <= $maxBytes) {
        continue;
    }
    $over++;
    $rel = str_replace('\\', '/', substr($path, strlen(APP_ROOT) + 1));
    echo $rel . ' — ' . round($size / 1024, 1) . " KB\n";

    if ($dryRun) {
        continue;
    }

    $result = property_image_optimize_file($path, $maxBytes, $minBytes);
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

echo "\nScanned: {$scanned}, over max: {$over}, optimized: {$changed}, errors: {$errors}\n";
if ($dryRun && $over > 0) {
    echo "Re-run without --dry-run to apply.\n";
}
