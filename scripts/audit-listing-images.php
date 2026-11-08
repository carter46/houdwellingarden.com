<?php
/**
 * Report duplicate property_images paths and small gallery pools.
 * php scripts/audit-listing-images.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();

echo "=== Listing image audit ===\n\n";

$dupes = $pdo->query(
    'SELECT path, COUNT(DISTINCT property_id) AS props, COUNT(*) AS img_rows
     FROM property_images
     GROUP BY path
     HAVING props > 1
     ORDER BY props DESC, img_rows DESC
     LIMIT 25'
)->fetchAll(PDO::FETCH_ASSOC);

if ($dupes === []) {
    echo "No duplicate file paths across different properties.\n";
} else {
    echo "Paths reused on multiple properties (gallery pool / same download):\n";
    foreach ($dupes as $row) {
        echo sprintf("  %dx properties · %s\n", (int) $row['props'], (string) $row['path']);
    }
}

$stats = $pdo->query(
    'SELECT COUNT(DISTINCT property_id) AS properties,
            COUNT(DISTINCT path) AS unique_paths,
            COUNT(*) AS image_rows
     FROM property_images'
)->fetch(PDO::FETCH_ASSOC);

echo "\nTotals: " . (int) $stats['properties'] . " properties, "
    . (int) $stats['image_rows'] . " image rows, "
    . (int) $stats['unique_paths'] . " unique paths.\n";

$perProp = $pdo->query(
    'SELECT property_id, COUNT(*) c FROM property_images GROUP BY property_id ORDER BY c ASC LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);
echo "\nFewest images on a property:\n";
foreach ($perProp as $row) {
    echo '  #' . (int) $row['property_id'] . ': ' . (int) $row['c'] . " images\n";
}

$root = dirname(__DIR__);
$hashes = [];
$rows = $pdo->query('SELECT property_id, path FROM property_images ORDER BY property_id')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $rel = ltrim(str_replace('\\', '/', (string) $row['path']), '/');
    $abs = $root . '/' . $rel;
    if (!is_file($abs)) {
        continue;
    }
    $hash = md5_file($abs);
    if ($hash === false) {
        continue;
    }
    $hashes[$hash][] = (int) $row['property_id'];
}
$visualDupes = 0;
foreach ($hashes as $hash => $pids) {
    $pids = array_values(array_unique($pids));
    if (count($pids) > 1) {
        $visualDupes++;
    }
}
echo "\nSame image file content reused across properties: {$visualDupes} distinct photos (hash collisions).\n";
echo "(Demo galleries pull from a small Unsplash pool in seed-property-galleries.php.)\n";

echo "\nDone.\n";
