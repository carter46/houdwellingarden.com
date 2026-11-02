<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = db();
$rows = $pdo->query(
    'SELECT pi.id, pi.property_id, p.slug, p.source_name, pi.path, pi.is_cover
     FROM property_images pi
     JOIN properties p ON p.id = pi.property_id
     ORDER BY pi.property_id, pi.sort_order'
)->fetchAll(PDO::FETCH_ASSOC);

$legacy = 0;
$hg = 0;
$external = 0;
foreach ($rows as $r) {
    $path = (string) $r['path'];
    if (preg_match('#^https?://#i', $path)) {
        $external++;
    } elseif (str_contains($path, '/hg-')) {
        $hg++;
    } else {
        $legacy++;
    }
}
echo "Images total: " . count($rows) . " legacy paths: $legacy hg paths: $hg external: $external\n\n";

foreach ($rows as $r) {
    $path = (string) $r['path'];
    if (preg_match('#^https?://#i', $path) || !str_contains($path, '/hg-')) {
        echo "#{$r['property_id']} {$r['slug']} cover={$r['is_cover']} {$path}\n";
    }
}
