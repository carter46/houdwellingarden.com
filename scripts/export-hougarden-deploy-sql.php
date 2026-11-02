<?php
/**
 * Build database/hougarden-deploy.sql — schema + full HouGarden AU data for phpMyAdmin.
 * Run locally after manifest/galleries are applied: php scripts/export-hougarden-deploy-sql.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$outPath = dirname(__DIR__) . '/database/hougarden-deploy.sql';
$schemaPath = dirname(__DIR__) . '/database/schema.sql';

if (!is_readable($schemaPath)) {
    fwrite(STDERR, "Missing {$schemaPath}\n");
    exit(1);
}

$pdo = db();

$sqlQuote = static function (?string $v) use ($pdo): string {
    if ($v === null) {
        return 'NULL';
    }

    return $pdo->quote($v);
};

$tablesData = [
    'property_types',
    'regions',
    'amenities',
    'agents',
    'offices',
    'settings',
    'properties',
    'property_images',
    'property_amenity',
];

$propCount = (int) $pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();
if ($propCount < 1) {
    fwrite(STDERR, "No properties in local DB. Run apply-hougarden-manifest.php first.\n");
    exit(1);
}

$buf = "-- HouGarden full deploy (schema + data). Import in phpMyAdmin (database already selected).\n";
$buf .= "-- Generated: " . date('c') . "\n";
$buf .= "-- Properties: {$propCount}\n";
$buf .= "-- No admin user — create via admin/setup.php after import.\n\n";
$buf .= "SET NAMES utf8mb4;\n";
$buf .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
$schemaSql = file_get_contents($schemaPath);
if ($schemaSql === false) {
    fwrite(STDERR, "Could not read {$schemaPath}\n");
    exit(1);
}
$schemaSql = preg_replace('/^\xEF\xBB\xBF/', '', $schemaSql) ?? $schemaSql;
$schemaSql = str_replace("\xEF\xBB\xBF", '', $schemaSql);
$buf .= $schemaSql;
$buf .= "\n\n-- ========== DATA (HouGarden AU) ==========\n\n";

foreach ($tablesData as $table) {
    $exists = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table)
    )->fetchColumn();
    if (!(int) $exists) {
        continue;
    }

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        continue;
    }

    $cols = array_keys($rows[0]);
    $colList = implode(', ', array_map(static fn ($c) => '`' . $c . '`', $cols));

    $buf .= "DELETE FROM `{$table}`;\n";
    $buf .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";

    $valueSets = [];
    foreach ($rows as $row) {
        $vals = [];
        foreach ($cols as $c) {
            $v = $row[$c];
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (is_int($v) || (is_string($v) && preg_match('/^-?\d+$/', $v) && $c !== 'postal_code' && $c !== 'reference_code' && $c !== 'mls_number' && $c !== 'source_reference')) {
                // numeric columns — keep simple; strings that look numeric handled below
                if (in_array($c, ['price', 'bedrooms', 'bathrooms', 'sqft', 'lot_acres', 'year_built', 'sort_order', 'is_active', 'is_featured', 'is_home_recommend', 'is_home_watched', 'is_home_saved', 'price_on_request', 'is_cover', 'property_type_id', 'agent_id', 'created_by', 'property_id', 'amenity_id', 'office_id', 'region_id'], true)) {
                    $vals[] = (string) $v;
                } elseif (is_int($v)) {
                    $vals[] = (string) $v;
                } else {
                    $vals[] = $sqlQuote((string) $v);
                }
            } else {
                $vals[] = $sqlQuote(is_string($v) ? $v : (string) $v);
            }
        }
        $valueSets[] = '(' . implode(', ', $vals) . ')';
    }

    $buf .= implode(",\n", $valueSets) . ";\n\n";
}

$buf .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($outPath, $buf);
$mb = round(filesize($outPath) / 1024 / 1024, 2);
echo "Wrote {$outPath} ({$mb} MB, {$propCount} properties)\n";
echo "InfinityFree: phpMyAdmin → Import hougarden-deploy.sql (one file).\n";
