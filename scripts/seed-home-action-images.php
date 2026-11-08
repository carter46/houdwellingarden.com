<?php
/**
 * Download homepage action card images (buy / rent / sell / find).
 * php scripts/seed-home-action-images.php
 */
declare(strict_types=1);

$dir = dirname(__DIR__) . '/assets/img/home-actions';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create {$dir}\n");
    exit(1);
}

$map = [
    'buy.jpg' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80',
    'rent.jpg' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80',
    'sell.jpg' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80',
    'find.jpg' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=800&q=80',
];

$ctx = stream_context_create(['http' => ['timeout' => 45, 'user_agent' => 'HouGarden/1.0']]);
foreach ($map as $file => $url) {
    $dest = $dir . '/' . $file;
    $bin = @file_get_contents($url, false, $ctx);
    if ($bin === false || strlen($bin) < 500) {
        fwrite(STDERR, "Failed: {$file}\n");
        continue;
    }
    file_put_contents($dest, $bin);
    echo "Wrote assets/img/home-actions/{$file}\n";
}
