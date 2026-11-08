<?php
/**
 * Idempotent schema upgrades (run once per version, tracked in settings.schema_version).
 *
 * Fresh deploy: import database/schema.sql via phpMyAdmin — no separate migration SQL needed.
 * Existing servers: first admin or public page load applies any pending steps automatically.
 *
 * Adding a change from 2025-11-01 onward:
 *   1. Add schema_migrate_vN_*() below and register it in app_schema_migrations().
 *   2. Bump app_schema_target_version().
 *   3. Update database/schema.sql for new installs so it matches.
 */

declare(strict_types=1);

function app_schema_target_version(): int
{
    return 10;
}

function schema_table_exists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$table]);

    return (int) $st->fetchColumn() > 0;
}

function schema_column_exists(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $column]);

    return (int) $st->fetchColumn() > 0;
}

function schema_add_column(PDO $pdo, string $table, string $column, string $definition, string $after): void
{
    if (schema_column_exists($pdo, $table, $column)) {
        return;
    }
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition} AFTER `{$after}`");
}

/**
 * Run pending schema migrations (safe to call every request; work stops after caught up).
 */
function app_run_schema_migrations(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    try {
        $pdo = db();
    } catch (Throwable $e) {
        return;
    }

    if (!schema_table_exists($pdo, 'settings')) {
        return;
    }

    $current = (int) (setting_get('schema_version') ?? '0');
    $target = app_schema_target_version();
    if ($current >= $target) {
        return;
    }

    $steps = app_schema_migrations();
    ksort($steps);

    foreach ($steps as $version => $callable) {
        if ($version <= $current) {
            continue;
        }
        if ($version > $target) {
            break;
        }
        try {
            $callable($pdo);
            setting_set('schema_version', (string) $version);
        } catch (Throwable $e) {
            app_log('schema_migrate', "v{$version}: " . $e->getMessage());
            break;
        }
    }
}

/**
 * @return array<int, callable(PDO): void>
 */
function app_schema_migrations(): array
{
    return [
        1 => 'schema_migrate_v1_home_feed_columns',
        2 => 'schema_migrate_v2_regions_featured',
        3 => 'schema_migrate_v3_property_source_columns',
        4 => 'schema_migrate_v4_taxonomy_is_active',
        5 => 'schema_migrate_v5_property_image_caption',
        6 => 'schema_migrate_v6_default_settings_keys',
        7 => 'schema_migrate_v7_hougarden_home_locations',
        8 => 'schema_migrate_v8_purge_legacy_market_regions',
        9 => 'schema_migrate_v9_us_home_locations',
        10 => 'schema_migrate_v10_us_region_local_images',
    ];
}

function schema_migrate_v10_us_region_local_images(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'regions')) {
        return;
    }
    $rows = [
        'austin-metro' => 'assets/img/regions/austin-metro.jpg',
        'phoenix-metro' => 'assets/img/regions/phoenix-metro.jpg',
        'atlanta-metro' => 'assets/img/regions/atlanta-metro.jpg',
        'denver-metro' => 'assets/img/regions/denver-metro.jpg',
        'chicago-metro' => 'assets/img/regions/chicago-metro.jpg',
    ];
    $upd = $pdo->prepare('UPDATE regions SET image_path = ? WHERE slug = ?');
    foreach ($rows as $slug => $path) {
        if (is_file(APP_ROOT . '/' . $path)) {
            $upd->execute([$path, $slug]);
        }
    }
}

function schema_migrate_v9_us_home_locations(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'regions')) {
        return;
    }

    $removeSlugs = [
        'bellevue-hill', 'rose-bay', 'south-yarra', 'ocean-grove', 'victoria',
    ];
    $placeholders = implode(',', array_fill(0, count($removeSlugs), '?'));
    $pdo->prepare("DELETE FROM regions WHERE slug IN ($placeholders)")->execute($removeSlugs);

    $locations = [
        ['austin-metro', 'Austin', 10, 'location', 'Austin', 'https://images.unsplash.com/photo-1531218150217-54595bc27324?auto=format&fit=crop&w=800&q=80'],
        ['phoenix-metro', 'Phoenix', 20, 'location', 'Phoenix', 'https://images.unsplash.com/photo-1516734215434-ccad973a74fe?auto=format&fit=crop&w=800&q=80'],
        ['atlanta-metro', 'Atlanta', 30, 'location', 'Atlanta', 'https://images.unsplash.com/photo-1467226632440-45d075e5c8fe?auto=format&fit=crop&w=800&q=80'],
        ['denver-metro', 'Denver', 40, 'location', 'Denver', 'https://images.unsplash.com/photo-1612892487469-05827b57269b?auto=format&fit=crop&w=800&q=80'],
        ['chicago-metro', 'Chicago', 50, 'location', 'Chicago', 'https://images.unsplash.com/photo-1494522855154-9297ac83324f?auto=format&fit=crop&w=800&q=80'],
    ];

    $upsert = $pdo->prepare(
        'INSERT INTO regions (slug, name, sort_order, is_active, is_featured, image_path, home_filter_key, home_filter_value)
         VALUES (?, ?, ?, 1, 1, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           sort_order = VALUES(sort_order),
           is_active = 1,
           is_featured = 1,
           home_filter_key = VALUES(home_filter_key),
           home_filter_value = VALUES(home_filter_value),
           image_path = COALESCE(NULLIF(TRIM(regions.image_path), \'\'), VALUES(image_path))'
    );
    foreach ($locations as $loc) {
        $upsert->execute([$loc[0], $loc[1], $loc[2], $loc[5], $loc[3], $loc[4]]);
    }
}

function schema_migrate_v8_purge_legacy_market_regions(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'regions')) {
        return;
    }
    $legacySlugs = [
        'aspen', 'vail', 'beaver-creek', 'telluride', 'snowmass', 'steamboat', 'denver-metro',
        'phoenix-metro', 'austin-metro', 'central-florida', 'atlanta-metro',
    ];
    $placeholders = implode(',', array_fill(0, count($legacySlugs), '?'));
    $pdo->prepare("DELETE FROM regions WHERE slug IN ($placeholders)")->execute($legacySlugs);
}

function schema_migrate_v1_home_feed_columns(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'properties')) {
        return;
    }
    schema_add_column($pdo, 'properties', 'is_home_recommend', 'TINYINT(1) NOT NULL DEFAULT 0', 'is_featured');
    schema_add_column($pdo, 'properties', 'is_home_watched', 'TINYINT(1) NOT NULL DEFAULT 0', 'is_home_recommend');
    schema_add_column($pdo, 'properties', 'is_home_saved', 'TINYINT(1) NOT NULL DEFAULT 0', 'is_home_watched');
}

function schema_migrate_v2_regions_featured(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'regions')) {
        return;
    }
    schema_add_column($pdo, 'regions', 'is_featured', 'TINYINT(1) NOT NULL DEFAULT 0', 'is_active');
    schema_add_column($pdo, 'regions', 'image_path', 'VARCHAR(500) NULL DEFAULT NULL', 'is_featured');
}

function schema_migrate_v3_property_source_columns(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'properties')) {
        return;
    }
    schema_add_column($pdo, 'properties', 'source_name', 'VARCHAR(80) NULL DEFAULT NULL', 'listed_at');
    schema_add_column($pdo, 'properties', 'source_url', 'VARCHAR(500) NULL DEFAULT NULL', 'source_name');
    schema_add_column($pdo, 'properties', 'source_reference', 'VARCHAR(120) NULL DEFAULT NULL', 'source_url');
}

function schema_migrate_v4_taxonomy_is_active(PDO $pdo): void
{
    if (schema_table_exists($pdo, 'property_types')) {
        schema_add_column($pdo, 'property_types', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1', 'sort_order');
    }
    if (schema_table_exists($pdo, 'amenities')) {
        schema_add_column($pdo, 'amenities', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1', 'sort_order');
    }
}

function schema_migrate_v5_property_image_caption(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'property_images')) {
        return;
    }
    schema_add_column($pdo, 'property_images', 'caption', 'VARCHAR(160) NULL DEFAULT NULL', 'alt_text');
}

function schema_migrate_v7_hougarden_home_locations(PDO $pdo): void
{
    if (!schema_table_exists($pdo, 'regions')) {
        return;
    }
    schema_add_column($pdo, 'regions', 'home_filter_key', "VARCHAR(16) NULL DEFAULT NULL COMMENT 'location|market'", 'image_path');
    schema_add_column($pdo, 'regions', 'home_filter_value', 'VARCHAR(120) NULL DEFAULT NULL', 'home_filter_key');

    // Replace legacy Colorado market rows with HouGarden AU homepage locations.
    $legacySlugs = [
        'aspen', 'vail', 'beaver-creek', 'telluride', 'snowmass', 'steamboat', 'denver-metro',
        'phoenix-metro', 'austin-metro', 'central-florida', 'atlanta-metro',
    ];
    $placeholders = implode(',', array_fill(0, count($legacySlugs), '?'));
    $pdo->prepare("DELETE FROM regions WHERE slug IN ($placeholders)")->execute($legacySlugs);

    $locations = [
        ['bellevue-hill', 'Bellevue Hill', 10, 'location', 'Bellevue Hill', 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80'],
        ['rose-bay', 'Rose Bay', 20, 'location', 'Rose Bay', 'https://images.unsplash.com/photo-1505142468610-359e7d316be0?auto=format&fit=crop&w=800&q=80'],
        ['south-yarra', 'South Yarra', 30, 'location', 'South Yarra', 'https://images.unsplash.com/photo-1514395462725-fb8496db94a8?auto=format&fit=crop&w=800&q=80'],
        ['ocean-grove', 'Ocean Grove', 40, 'location', 'Ocean Grove', 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=800&q=80'],
        ['victoria', 'Victoria', 50, 'market', 'victoria', 'https://images.unsplash.com/photo-1519864607624-a07bb8c2477e?auto=format&fit=crop&w=800&q=80'],
    ];

    $upsert = $pdo->prepare(
        'INSERT INTO regions (slug, name, sort_order, is_active, is_featured, image_path, home_filter_key, home_filter_value)
         VALUES (?, ?, ?, 1, 1, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           sort_order = VALUES(sort_order),
           is_active = 1,
           is_featured = 1,
           home_filter_key = VALUES(home_filter_key),
           home_filter_value = VALUES(home_filter_value),
           image_path = COALESCE(regions.image_path, VALUES(image_path))'
    );
    foreach ($locations as $loc) {
        $upsert->execute([$loc[0], $loc[1], $loc[2], $loc[5], $loc[3], $loc[4]]);
    }

    if (schema_table_exists($pdo, 'property_types')) {
        $pdo->exec("UPDATE property_types SET is_active = 0 WHERE slug IN ('estate','ranch','chalet')");
        $typeUpsert = $pdo->prepare(
            'INSERT INTO property_types (slug, name, sort_order, is_active) VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1'
        );
        foreach ([
            ['single_family', 'House', 10],
            ['condo', 'Apartment', 20],
            ['townhouse', 'Townhouse', 30],
            ['land', 'Land', 40],
        ] as $t) {
            $typeUpsert->execute([$t[0], $t[1], $t[2]]);
        }
    }
}

function schema_migrate_v6_default_settings_keys(PDO $pdo): void
{
    $keys = [
        'site_logo_path' => null,
        'site_favicon_path' => null,
        'mail_from_name' => null,
    ];
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_key = setting_key'
    );
    foreach ($keys as $key => $val) {
        $stmt->execute([$key, $val]);
    }
}
