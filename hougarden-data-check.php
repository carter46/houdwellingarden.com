<?php
/**
 * Live/staging HouGarden data diagnostic (plain text).
 * Upload, open in browser, then DELETE this file.
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$root = __DIR__;
require_once $root . '/includes/bootstrap.php';
require_once $root . '/includes/hougarden-locations.php';
require_once $root . '/includes/hougarden-shell.php';

app_run_schema_migrations();

echo "=== HouGarden data check ===\n\n";

try {
    $pdo = db();
} catch (Throwable $e) {
    echo "DB connect FAILED: " . $e->getMessage() . "\n";
    exit;
}

$schemaVer = setting_get('schema_version') ?? 'not set';
echo 'schema_version: ' . $schemaVer . ' (target ' . app_schema_target_version() . ")\n\n";

$total = (int) $pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();
[$in, $statuses] = public_status_sql_in();
$st = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE status IN ($in)");
$st->execute($statuses);
$public = (int) $st->fetchColumn();

$draft = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'draft'")->fetchColumn();

$stRec = $pdo->prepare(
    "SELECT COUNT(*) FROM properties p WHERE p.is_home_recommend = 1 AND p.status IN ($in)"
);
$stRec->execute($statuses);
$recommendPublic = (int) $stRec->fetchColumn();

echo "Properties total: {$total}\n";
echo "Public-status (visible on site): {$public}\n";
echo "Draft (hidden until published): {$draft}\n";
echo "Recommend tab (is_home_recommend=1 AND public status): {$recommendPublic}\n\n";

if ($public === 0 && $total > 0) {
    echo ">>> FIX: Listings exist but NONE are public. Set status to Available (not Draft) and Publish.\n\n";
}

echo "--- Location filters (homepage cards) ---\n";
foreach (hougarden_location_markets() as $market) {
    $label = (string) $market['label'];
    $filter = $market['filter'];
    try {
        $n = property_count_public($filter);
    } catch (Throwable $e) {
        $n = -1;
        echo "{$label}: ERROR " . $e->getMessage() . "\n";
        continue;
    }
    echo "{$label}: {$n} matching listing(s)\n";
}

echo "\n--- Cities in database (top 15) ---\n";
$cities = $pdo->query(
    "SELECT city, state, COUNT(*) AS c FROM properties GROUP BY city, state ORDER BY c DESC LIMIT 15"
)->fetchAll(PDO::FETCH_ASSOC);
if ($cities === []) {
    echo "(no rows — database empty or wrong DB)\n";
} else {
    foreach ($cities as $row) {
        echo "  {$row['city']}, {$row['state']}: {$row['c']}\n";
    }
}

echo "\n--- Images ---\n";
$imgTotal = (int) $pdo->query('SELECT COUNT(*) FROM property_images')->fetchColumn();
$external = (int) $pdo->query(
    "SELECT COUNT(*) FROM property_images WHERE path LIKE 'http%'"
)->fetchColumn();
$hgPaths = (int) $pdo->query(
    "SELECT COUNT(*) FROM property_images WHERE path LIKE 'uploads/properties/hg-%'"
)->fetchColumn();
echo "property_images rows: {$imgTotal}\n";
echo "external URL paths: {$external}\n";
echo "hg-* local paths: {$hgPaths}\n";

$missingFiles = 0;
$sample = $pdo->query(
    'SELECT path FROM property_images ORDER BY id ASC LIMIT 50'
)->fetchAll(PDO::FETCH_COLUMN);
foreach ($sample as $path) {
    $path = (string) $path;
    if (preg_match('#^https?://#i', $path)) {
        continue;
    }
    $rel = ltrim(str_replace('\\', '/', $path), '/');
    if (!is_file($root . '/' . $rel)) {
        $missingFiles++;
    }
}
echo "Missing files on disk (first 50 image rows checked): {$missingFiles}\n";
if ($missingFiles > 0) {
    echo ">>> FIX: Upload uploads/properties/hg-* folders to match the DB, or re-run manifest on server.\n";
}

echo "\n--- Sample public listings ---\n";
$sampleList = $pdo->query(
    "SELECT id, status, city, is_home_recommend, title FROM properties ORDER BY id ASC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($sampleList as $s) {
    echo "  #{$s['id']} [{$s['status']}] city={$s['city']} recommend={$s['is_home_recommend']} — {$s['title']}\n";
}

echo "\nDone. Delete hougarden-data-check.php when finished.\n";
