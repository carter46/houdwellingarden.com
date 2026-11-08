<?php
/**
 * Download default region card images into uploads/regions/{slug}.jpg (for hosts that block hotlinked Unsplash).
 * php scripts/seed-region-images.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$map = [
    'austin-metro' => 'https://images.unsplash.com/photo-1531218150217-54595bc27324?auto=format&fit=crop&w=800&q=80',
    'phoenix-metro' => 'https://images.unsplash.com/photo-1516734215434-ccad973a74fe?auto=format&fit=crop&w=800&q=80',
    'atlanta-metro' => 'https://images.unsplash.com/photo-1467226632440-45d075e5c8fe?auto=format&fit=crop&w=800&q=80',
    'denver-metro' => 'https://images.unsplash.com/photo-1612892487469-05827b57269b?auto=format&fit=crop&w=800&q=80',
    'chicago-metro' => 'https://images.unsplash.com/photo-1494522855154-9297ac83324f?auto=format&fit=crop&w=800&q=80',
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
