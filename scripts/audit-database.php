<?php
/**
 * Local/staging DB integrity audit. Usage: php scripts/audit-database.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/hougarden-shell.php';
require_once dirname(__DIR__) . '/includes/hougarden-locations.php';

hougarden_ensure_home_feed_columns();

$pdo = db();
$issues = [];
$ok = [];

function audit_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $col]);

    return (int) $st->fetchColumn() > 0;
}

$requiredCols = [
    'properties' => [
        'is_home_recommend', 'is_home_watched', 'is_home_saved',
        'source_name', 'source_url', 'listing_purpose', 'currency',
    ],
    'settings' => ['setting_key', 'setting_value'],
    'property_images' => ['is_cover', 'sort_order'],
];

foreach ($requiredCols as $table => $cols) {
    foreach ($cols as $col) {
        if (audit_col($pdo, $table, $col)) {
            $ok[] = "column {$table}.{$col}";
        } else {
            $issues[] = "MISSING column {$table}.{$col}";
        }
    }
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();
$public = (int) $pdo->query(
    "SELECT COUNT(*) FROM properties WHERE status IN ('available','pending','under_contract','sold','private')"
)->fetchColumn();
$noTitle = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE TRIM(title) = ''")->fetchColumn();
$demoDesc = (int) $pdo->query(
    "SELECT COUNT(*) FROM properties WHERE description LIKE '%Sourced for Houdwell Garden demo%'"
)->fetchColumn();

$imgStats = $pdo->query(
    'SELECT p.id, p.slug, p.title,
            COUNT(pi.id) AS img_count,
            SUM(pi.is_cover) AS cover_count
     FROM properties p
     LEFT JOIN property_images pi ON pi.property_id = p.id
     GROUP BY p.id
     HAVING img_count = 0 OR cover_count = 0'
)->fetchAll(PDO::FETCH_ASSOC);

echo "=== HouGarden DB audit ===\n";
echo "Properties total: {$total}, public-status: {$public}\n";
echo "Empty titles: {$noTitle}\n";
echo "Demo import descriptions remaining: {$demoDesc}\n\n";

if ($issues !== []) {
    echo "Issues:\n";
    foreach ($issues as $i) {
        echo "  - {$i}\n";
    }
} else {
    echo "Required columns: OK\n";
}

if ($imgStats !== []) {
    echo "\nListings missing images or cover (" . count($imgStats) . "):\n";
    foreach (array_slice($imgStats, 0, 15) as $row) {
        echo "  #{$row['id']} {$row['slug']} images={$row['img_count']} cover={$row['cover_count']}\n";
    }
    if (count($imgStats) > 15) {
        echo '  ... and ' . (count($imgStats) - 15) . " more\n";
    }
} else {
    echo "\nAll properties have at least one image and a cover.\n";
}

$sample = $pdo->query(
    "SELECT id, slug, title, bedrooms, bathrooms, sqft, price, currency, status
     FROM properties WHERE status = 'available' ORDER BY id LIMIT 3"
)->fetchAll(PDO::FETCH_ASSOC);
echo "\nSample available listings:\n";
foreach ($sample as $s) {
    echo "  #{$s['id']} beds={$s['bedrooms']} baths={$s['bathrooms']} area={$s['sqft']} {$s['currency']} {$s['price']} — {$s['title']}\n";
}

echo "\nSettings keys (public): ";
$keys = $pdo->query("SELECT setting_key FROM settings WHERE setting_key IN ('site_name','site_phone','site_email','smartsupp_key','property_show_agent','property_show_inquiry') ORDER BY setting_key")->fetchAll(PDO::FETCH_COLUMN);
echo $keys === [] ? "(none)\n" : implode(', ', $keys) . "\n";

$schemaVer = setting_get('schema_version');
$targetVer = app_schema_target_version();
echo "Schema version (DB): " . ($schemaVer ?? 'not set') . " · target: {$targetVer}\n";
if ((int) ($schemaVer ?? 0) < $targetVer) {
    echo "  → Load any page once to apply pending PHP migrations.\n";
}

echo "\nHomepage locations (Admin → Homepage locations):\n";
$legacyRegionSlugs = [
    'aspen', 'vail', 'beaver-creek', 'telluride', 'snowmass', 'steamboat',
    'central-florida', 'bellevue-hill', 'rose-bay', 'south-yarra', 'ocean-grove', 'victoria',
];
$badRegions = $pdo->query(
    "SELECT slug, name FROM regions WHERE slug IN ('" . implode("','", $legacyRegionSlugs) . "')"
)->fetchAll(PDO::FETCH_ASSOC);
if ($badRegions !== []) {
    echo "  ISSUE: Legacy Colorado/US regions still in DB — load admin once (migration v7) or re-import hougarden-deploy.sql\n";
    foreach ($badRegions as $br) {
        echo "    - {$br['slug']}\n";
    }
}
foreach (hougarden_location_markets() as $market) {
    $label = (string) $market['label'];
    $n = property_count_public($market['filter']);
    echo "  {$label}: {$n} public listing(s)\n";
}

$inactiveTypes = (int) $pdo->query(
    "SELECT COUNT(*) FROM property_types WHERE slug IN ('estate','ranch','chalet') AND is_active = 1"
)->fetchColumn();
if ($inactiveTypes > 0) {
    echo "\nISSUE: Legacy property types still active (estate/ranch/chalet). Run migration v7 or deactivate in admin.\n";
} else {
    echo "\nProperty types: OK (legacy estate/ranch/chalet inactive).\n";
}

echo "\nDone.\n";
