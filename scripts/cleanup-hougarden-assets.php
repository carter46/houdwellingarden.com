<?php
/**
 * HouGarden AU cleanup:
 *  - Remove property_images pointing at legacy Colorado/cc slug folders (keep hg-{id} only)
 *  - Download any remaining external image URLs to local hg-{id} paths
 *  - Ensure each active property has a cover under hg-{id}
 *  - Delete upload folders not referenced by any property_images row
 *  - Remove DB properties that are not HouGarden AU inventory (optional archive/purge)
 *
 * Usage:
 *   php scripts/cleanup-hougarden-assets.php --dry-run
 *   php scripts/cleanup-hougarden-assets.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/image-optimize.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$pdo = db();
$uploadRoot = rtrim((string) app_config('uploads.properties_dir', APP_ROOT . '/uploads/properties'), '/\\');

function hg_download_image(string $url, string $destPath): bool
{
    if ($url === '') {
        return false;
    }
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 25,
            'header' => "User-Agent: HoudwellLocalImport/1.0\r\nAccept: image/*\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $bin = @file_get_contents($url, false, $ctx);
    if ($bin === false || strlen($bin) < 500) {
        return false;
    }

    return file_put_contents($destPath, $bin) !== false;
}

function path_is_hg_for_property(string $path, int $propertyId): bool
{
    return str_contains($path, '/hg-' . $propertyId . '/');
}

/** Remove a directory and all contents (Windows-safe for empty or nested trees). */
function hg_remove_directory_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    @rmdir($dir);
    if (is_dir($dir) && DIRECTORY_SEPARATOR === '\\') {
        $escaped = escapeshellarg($dir);
        @exec("cmd /c rmdir /s /q {$escaped}");
    }
}

/** @return list<string> */
function referenced_upload_folders(PDO $pdo): array
{
    $folders = [];
    $paths = $pdo->query('SELECT path FROM property_images')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paths as $path) {
        $path = (string) $path;
        if (preg_match('#^uploads/properties/([^/]+)/#', $path, $m)) {
            $folders[$m[1]] = true;
        }
    }

    return array_keys($folders);
}

echo $dryRun ? "DRY RUN\n" : "LIVE RUN\n";

// 1) Drop legacy image rows (wrong folder for property id)
$rows = $pdo->query(
    'SELECT pi.id, pi.property_id, pi.path, pi.is_cover, p.source_name
     FROM property_images pi
     INNER JOIN properties p ON p.id = pi.property_id'
)->fetchAll(PDO::FETCH_ASSOC);

$deletedRows = 0;
$migratedExternal = 0;
$legacyPaths = [];

foreach ($rows as $row) {
    $imageId = (int) $row['id'];
    $propertyId = (int) $row['property_id'];
    $path = (string) $row['path'];

    if (preg_match('#^https?://#i', $path)) {
        $folder = $uploadRoot . DIRECTORY_SEPARATOR . 'hg-' . $propertyId;
        $rel = 'uploads/properties/hg-' . $propertyId . '/cover-migrated.jpg';
        $abs = APP_ROOT . '/' . $rel;
        echo "External → local: property #{$propertyId}\n";
        if (!$dryRun) {
            if (hg_download_image($path, $abs)) {
                property_image_optimize_file($abs);
                $pdo->prepare('UPDATE property_images SET path = ? WHERE id = ?')->execute([$rel, $imageId]);
                $migratedExternal++;
            } else {
                echo "  download failed, deleting row {$imageId}\n";
                $pdo->prepare('DELETE FROM property_images WHERE id = ?')->execute([$imageId]);
                $deletedRows++;
            }
        }
        continue;
    }

    if (!path_is_hg_for_property($path, $propertyId)) {
        $legacyPaths[] = $path;
        echo "Remove legacy image row #{$imageId} property #{$propertyId}: {$path}\n";
        if (!$dryRun) {
            $pdo->prepare('DELETE FROM property_images WHERE id = ?')->execute([$imageId]);
        }
        $deletedRows++;
    }
}

// 2) Ensure cover exists for each public HouGarden property
$props = $pdo->query(
    "SELECT id, title FROM properties
     WHERE source_name IN ('hougarden_au', 'hougarden_au_vic', 'hougarden_au_extra')
        OR source_name IS NULL OR source_name = ''"
)->fetchAll(PDO::FETCH_ASSOC);

$coversFixed = 0;
foreach ($props as $prop) {
    $pid = (int) $prop['id'];
    $cover = $pdo->prepare('SELECT id, path FROM property_images WHERE property_id = ? AND is_cover = 1 LIMIT 1');
    $cover->execute([$pid]);
    $coverRow = $cover->fetch(PDO::FETCH_ASSOC);

    $any = $pdo->prepare('SELECT id, path FROM property_images WHERE property_id = ? ORDER BY is_cover DESC, sort_order ASC LIMIT 1');
    $any->execute([$pid]);
    $anyRow = $any->fetch(PDO::FETCH_ASSOC);

    if ($coverRow && path_is_hg_for_property((string) $coverRow['path'], $pid)) {
        continue;
    }

    if ($anyRow && path_is_hg_for_property((string) $anyRow['path'], $pid)) {
        echo "Set cover from gallery property #{$pid}\n";
        if (!$dryRun) {
            $pdo->prepare('UPDATE property_images SET is_cover = 0 WHERE property_id = ?')->execute([$pid]);
            $pdo->prepare('UPDATE property_images SET is_cover = 1 WHERE id = ?')->execute([(int) $anyRow['id']]);
        }
        $coversFixed++;
        continue;
    }

    $relCover = 'uploads/properties/hg-' . $pid . '/cover.jpg';
    $absCover = APP_ROOT . '/' . $relCover;
    if (is_file($absCover)) {
        echo "Register cover file for property #{$pid}\n";
        if (!$dryRun) {
            $pdo->prepare('UPDATE property_images SET is_cover = 0 WHERE property_id = ?')->execute([$pid]);
            $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM property_images WHERE property_id = ' . $pid)->fetchColumn();
            $pdo->prepare(
                'INSERT INTO property_images (property_id, path, alt_text, sort_order, is_cover) VALUES (?, ?, ?, ?, 1)'
            )->execute([$pid, $relCover, (string) $prop['title'], $maxSort + 1]);
        }
        $coversFixed++;
    }
}

// 3) Delete orphan upload directories
$referenced = referenced_upload_folders($pdo);
$referencedSet = array_fill_keys($referenced, true);
$removedDirs = 0;

if (is_dir($uploadRoot)) {
    foreach (scandir($uploadRoot) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $dir = $uploadRoot . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($dir)) {
            continue;
        }
        if (isset($referencedSet[$entry])) {
            continue;
        }
        echo "Delete orphan folder: uploads/properties/{$entry}\n";
        if (!$dryRun) {
            hg_remove_directory_tree($dir);
        }
        $removedDirs++;
    }
}

// 4) Remove non-HouGarden properties from DB (legacy seed / parent company) if present
$legacyProps = $pdo->query(
    "SELECT id, slug, source_name FROM properties
     WHERE source_name IS NOT NULL
       AND source_name NOT IN ('hougarden_au', 'hougarden_au_vic', 'hougarden_au_extra')"
)->fetchAll(PDO::FETCH_ASSOC);

$purgedProps = 0;
foreach ($legacyProps as $lp) {
    echo "Purge legacy property #{$lp['id']} {$lp['slug']} (source={$lp['source_name']})\n";
    if (!$dryRun) {
        $pid = (int) $lp['id'];
        $imgs = property_images($pid);
        foreach ($imgs as $img) {
            property_delete_image_file((string) ($img['path'] ?? ''));
        }
        $pdo->prepare('DELETE FROM property_amenity WHERE property_id = ?')->execute([$pid]);
        $pdo->prepare('DELETE FROM property_images WHERE property_id = ?')->execute([$pid]);
        $pdo->prepare('UPDATE inquiries SET property_id = NULL WHERE property_id = ?')->execute([$pid]);
        $pdo->prepare('DELETE FROM properties WHERE id = ?')->execute([$pid]);
    }
    $purgedProps++;
}

echo "\nSummary:\n";
echo "  Legacy image rows removed: {$deletedRows}\n";
echo "  External URLs migrated: {$migratedExternal}\n";
echo "  Covers fixed: {$coversFixed}\n";
echo "  Orphan upload folders removed: {$removedDirs}\n";
echo "  Legacy properties purged: {$purgedProps}\n";

if ($dryRun) {
    echo "\nRe-run without --dry-run to apply.\n";
}
