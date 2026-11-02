<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();

$props = $pdo->query('SELECT id, slug, title, status, source_name, city, state FROM properties ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$images = $pdo->query('SELECT property_id, path, is_cover FROM property_images ORDER BY property_id, sort_order')->fetchAll(PDO::FETCH_ASSOC);

$byProp = [];
foreach ($images as $img) {
    $byProp[(int) $img['property_id']][] = $img;
}

$external = [];
$local = [];
$missingLocal = [];

foreach ($props as $p) {
    $id = (int) $p['id'];
    $imgs = $byProp[$id] ?? [];
    foreach ($imgs as $img) {
        $path = (string) $img['path'];
        if (preg_match('#^https?://#i', $path)) {
            $external[] = ['property_id' => $id, 'slug' => $p['slug'], 'path' => $path];
        } else {
            $local[] = $path;
            $abs = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $path), '/');
            if (!is_file($abs)) {
                $missingLocal[] = ['property_id' => $id, 'slug' => $p['slug'], 'path' => $path];
            }
        }
    }
}

echo "Properties in DB: " . count($props) . "\n";
echo "External image URLs: " . count($external) . "\n";
echo "Missing local files: " . count($missingLocal) . "\n\n";

if ($external !== []) {
    echo "=== External URLs (sample) ===\n";
    foreach (array_slice($external, 0, 20) as $row) {
        echo "#{$row['property_id']} {$row['slug']}\n  {$row['path']}\n";
    }
}

echo "\n=== By source_name / status ===\n";
$groups = $pdo->query(
    "SELECT COALESCE(source_name, '(none)') AS src, status, COUNT(*) AS c FROM properties GROUP BY src, status ORDER BY c DESC"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($groups as $g) {
    echo "{$g['src']} / {$g['status']}: {$g['c']}\n";
}

echo "\n=== Slug prefixes ===\n";
$prefixes = [];
foreach ($props as $p) {
    $slug = (string) $p['slug'];
    if (preg_match('#^(hg-|cc-|cc-co|1220|450|88|1240)#', $slug, $m)) {
        $pre = str_starts_with($slug, 'hg-') ? 'hg-*' : (str_starts_with($slug, 'cc-') ? 'cc-*' : 'legacy-seed');
    } elseif (str_contains($slug, '-')) {
        $pre = explode('-', $slug, 2)[0];
    } else {
        $pre = 'other';
    }
    if (str_starts_with($slug, 'cc-')) {
        $pre = 'cc-*';
    } elseif (preg_match('#^\d#', $slug) || in_array($slug, ['450-red-mountain-rd', '1220-vail-valley-dr'], true)) {
        $pre = 'colorado-seed';
    } elseif (preg_match('#-[0-9]+$#', $slug)) {
        $pre = 'hg-au';
    } else {
        $pre = 'other';
    }
    $prefixes[$pre] = ($prefixes[$pre] ?? 0) + 1;
}
foreach ($prefixes as $k => $v) {
    echo "$k: $v\n";
}
