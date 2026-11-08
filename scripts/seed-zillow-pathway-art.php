<?php
/**
 * Download Zillow homepage pathway illustrations for local dev reference.
 * Source: zillowstatic.com (Zillow-owned assets — replace with your own licensed art before commercial launch).
 *
 * php scripts/seed-zillow-pathway-art.php
 */
declare(strict_types=1);

$dir = dirname(__DIR__) . '/assets/img/pathways';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create {$dir}\n");
    exit(1);
}

$map = [
    'buy.webp' => 'https://www.zillowstatic.com/bedrock/app/uploads/sites/55/2025/04/homepage-spot-agent-lg.webp',
    'rent.webp' => 'https://www.zillowstatic.com/bedrock/app/uploads/sites/55/2025/04/homepage-spot-rent-lg.webp',
    'sell.webp' => 'https://www.zillowstatic.com/bedrock/app/uploads/sites/55/2025/04/homepage-spot-sell-lg.webp',
];

$ctx = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'Mozilla/5.0 (compatible; HouGarden-setup/1.0)']]);
foreach ($map as $file => $url) {
    $dest = $dir . '/' . $file;
    $bin = @file_get_contents($url, false, $ctx);
    if ($bin === false || strlen($bin) < 200) {
        fwrite(STDERR, "Failed: {$file}\n");
        continue;
    }
    file_put_contents($dest, $bin);
    echo "Wrote assets/img/pathways/{$file}\n";
}
