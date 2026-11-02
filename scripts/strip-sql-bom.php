<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    $root . '/database/schema.sql',
    $root . '/database/hougarden-deploy.sql',
];

foreach ($files as $path) {
    if (!is_file($path)) {
        echo "Skip (missing): {$path}\n";
        continue;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo "Read failed: {$path}\n";
        continue;
    }
    $had = str_starts_with($raw, "\xEF\xBB\xBF");
    $clean = $had ? substr($raw, 3) : $raw;
    // Also strip accidental BOM mid-file (concatenation bug)
    $clean = str_replace("\xEF\xBB\xBF", '', $clean);
    if ($clean !== $raw) {
        file_put_contents($path, $clean);
        echo "Stripped BOM: {$path}\n";
    } else {
        echo "OK (no BOM): {$path}\n";
    }
}
