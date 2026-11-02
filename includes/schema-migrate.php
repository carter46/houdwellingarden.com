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
    return 6;
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
    ];
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
