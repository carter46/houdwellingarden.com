<?php
/**
 * Download default region card images into uploads/regions/{slug}.jpg (for hosts that block hotlinked Unsplash).
 * php scripts/seed-region-images.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$map = [
    'bellevue-hill' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=800&q=80',
    'rose-bay' => 'https://images.unsplash.com/photo-1505142468610-359e7d316be0?auto=format&fit=crop&w=800&q=80',
    'south-yarra' => 'https://images.unsplash.com/photo-1514395462725-fb8496db94a8?auto=format&fit=crop&w=800&q=80',
    'ocean-grove' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=800&q=80',
    'victoria' => 'https://images.unsplash.com/photo-1519864607624-a07bb8c2477e?auto=format&fit=crop&w=800&q=80',
];

$dir = APP_ROOT . '/uploads/regions';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ($map as $slug => $url) {
    $dest = $dir . '/' . $slug . '.jpg';
    if (is_file($dest) && filesize($dest) > 1000) {
        echo "Skip {$slug} (exists)\n";
        continue;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'HouGarden/1.0']]);
    $bin = @file_get_contents($url, false, $ctx);
    if ($bin === false || strlen($bin) < 500) {
        fwrite(STDERR, "Failed: {$slug}\n");
        continue;
    }
    file_put_contents($dest, $bin);
    echo "Wrote uploads/regions/{$slug}.jpg\n";
}

$pdo = db();
if (schema_column_exists($pdo, 'regions', 'image_path')) {
    foreach ($map as $slug => $url) {
        $local = 'uploads/regions/' . $slug . '.jpg';
        if (!is_file(APP_ROOT . '/' . $local)) {
            continue;
        }
        $pdo->prepare('UPDATE regions SET image_path = ? WHERE slug = ? AND (image_path IS NULL OR image_path LIKE \'https://%\' OR image_path = \'\')')
            ->execute([$local, $slug]);
    }
    echo "Updated regions.image_path to local files where applicable.\n";
}
