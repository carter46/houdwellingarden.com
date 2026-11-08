<?php
/**
 * Real residential photos for listing covers (Pexels — demo placeholders).
 *
 * php scripts/seed-pexels-property-photos.php
 * php scripts/seed-pexels-property-photos.php --all
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/lib-http-fetch.php';

$refreshAll = in_array('--all', $argv ?? [], true);

/** @var list<string> House / condo exteriors & interiors (unique Pexels IDs). */
$pool = [
    'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2102587/pexels-photo-2102587.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/323775/pexels-photo-323775.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/186077/pexels-photo-186077.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/259588/pexels-photo-259588.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/280222/pexels-photo-280222.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/462235/pexels-photo-462235.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/534151/pexels-photo-534151.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/111580/pexels-photo-111580.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/691582/pexels-photo-691582.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/4386437/pexels-photo-4386437.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/3559488/pexels-photo-3559488.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1438832/pexels-photo-1438832.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1571468/pexels-photo-1571468.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2132126/pexels-photo-2132126.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2581922/pexels-photo-2581922.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1387037/pexels-photo-1387037.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/259962/pexels-photo-259962.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/189349/pexels-photo-189349.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/53610/pexels-photo-53610.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1582280/pexels-photo-1582280.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/209296/pexels-photo-209296.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1170584/pexels-photo-1170584.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/2121121/pexels-photo-2121121.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/1080721/pexels-photo-1080721.jpeg?auto=compress&cs=tinysrgb&w=1400',
    'https://images.pexels.com/photos/379799/pexels-photo-379799.jpeg?auto=compress&cs=tinysrgb&w=1400',
];

$pdo = db();
$sql = $refreshAll
    ? 'SELECT id, title FROM properties ORDER BY id ASC'
    : 'SELECT p.id, p.title FROM properties p ORDER BY p.id ASC';
// --all replaces every cover (removes Zillow illustration webp, etc.)
$props = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$delAll = $pdo->prepare('DELETE FROM property_images WHERE property_id = ?');
$insImg = $pdo->prepare(
    'INSERT INTO property_images (property_id, path, alt_text, sort_order, is_cover) VALUES (?, ?, ?, 0, 1)'
);

$ok = 0;
$fail = 0;
foreach ($props as $i => $row) {
    $id = (int) $row['id'];
    $url = $pool[$i % count($pool)];
    $folder = APP_ROOT . '/uploads/properties/hg-' . $id;
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    $rel = 'uploads/properties/hg-' . $id . '/cover.jpg';
    $dest = APP_ROOT . '/' . $rel;
    $bin = null;
    for ($try = 0; $try < count($pool); $try++) {
        $tryUrl = $pool[($i + $try) % count($pool)];
        $bin = http_fetch_binary($tryUrl);
        if ($bin !== null) {
            $url = $tryUrl;
            break;
        }
    }
    if ($bin === null) {
        $fail++;
        fwrite(STDERR, "Failed property #{$id}\n");
        continue;
    }
    file_put_contents($dest, $bin);
    if ($refreshAll) {
        $delAll->execute([$id]);
    } else {
        $pdo->prepare('DELETE FROM property_images WHERE property_id = ? AND is_cover = 1')->execute([$id]);
    }
    $insImg->execute([$id, $rel, (string) $row['title'] . ' — demo photo']);
    $ok++;
}

echo "Listing covers (Pexels): {$ok} ok, {$fail} failed.\n";
