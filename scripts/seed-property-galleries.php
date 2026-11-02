<?php
/**
 * Add multiple gallery images per property (HouGarden blocks bulk scrape; uses curated pool).
 * Skips download if file exists. Does not remove existing cover.
 *
 * Usage: php scripts/seed-property-galleries.php
 * Optional: php scripts/seed-property-galleries.php --min=8
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$minImages = 8;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--min=')) {
        $minImages = max(2, (int) substr($arg, 6));
    }
}

function hg_gallery_download(string $url, string $destPath): bool
{
    if ($url === '') {
        return false;
    }
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (is_file($destPath) && filesize($destPath) > 500) {
        return true;
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

/** @var list<string> */
$pool = [
    'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600585154340-be616a1a0a1c?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600573472592-401b489a3cdc?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600607687644-c7171b42498f?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1605276374101-dee2a0ed3cd7?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600585154363-67eb9e2e2099?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=80',
];

$pdo = db();
$props = $pdo->query(
    "SELECT p.id, p.title FROM properties p
     WHERE p.status IN ('available','pending','under_contract','sold','private')
     ORDER BY p.id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$ins = $pdo->prepare(
    'INSERT INTO property_images (property_id, path, alt_text, sort_order, is_cover) VALUES (?, ?, ?, ?, ?)'
);
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM property_images WHERE property_id = ?');

$uploadRoot = dirname(__DIR__) . '/uploads/properties';
$added = 0;
$poolSize = count($pool);

foreach ($props as $prop) {
    $pid = (int) $prop['id'];
    $countStmt->execute([$pid]);
    $have = (int) $countStmt->fetchColumn();
    if ($have >= $minImages) {
        continue;
    }

    $folder = $uploadRoot . '/hg-' . $pid;
    $title = (string) ($prop['title'] ?? 'Property');

    for ($i = $have; $i < $minImages; $i++) {
        $url = $pool[($pid * 7 + $i * 3) % $poolSize];
        $file = 'gallery-' . ($i + 1) . '.jpg';
        $rel = 'uploads/properties/hg-' . $pid . '/' . $file;
        $abs = dirname(__DIR__) . '/' . $rel;
        if (!hg_gallery_download($url, $abs)) {
            continue;
        }
        $isCover = ($i === 0 && $have === 0) ? 1 : 0;
        $ins->execute([$pid, $rel, $title . ' — photo ' . ($i + 1), $i, $isCover]);
        $added++;
    }
}

// Ensure exactly one cover per property
$pdo->exec(
    'UPDATE property_images pi
     INNER JOIN (
       SELECT property_id, MIN(id) AS mid FROM property_images GROUP BY property_id
     ) x ON pi.property_id = x.property_id
     SET pi.is_cover = IF(pi.id = x.mid, 1, 0)'
);

echo "Added {$added} gallery images (target min {$minImages} per property).\n";
