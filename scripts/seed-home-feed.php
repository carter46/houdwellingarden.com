<?php
/**
 * Assign demo home-feed flags (recommend / watched / saved) from existing listings.
 * Usage: php scripts/seed-home-feed.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/hougarden-shell.php';

hougarden_ensure_home_feed_columns();

$pdo = db();
$pdo->exec('UPDATE properties SET is_home_recommend = 1 WHERE is_featured = 1');

$ids = $pdo->query(
    "SELECT id FROM properties WHERE status IN ('available','pending','under_contract','sold','private')
     ORDER BY listed_at DESC, id DESC"
)->fetchAll(PDO::FETCH_COLUMN);

$n = count($ids);
foreach ($ids as $i => $id) {
    $pid = (int) $id;
    if ($i < 6) {
        $pdo->prepare('UPDATE properties SET is_home_recommend = 1 WHERE id = ?')->execute([$pid]);
    } elseif ($i < 9) {
        $pdo->prepare('UPDATE properties SET is_home_watched = 1 WHERE id = ?')->execute([$pid]);
    } elseif ($i < 12) {
        $pdo->prepare('UPDATE properties SET is_home_saved = 1 WHERE id = ?')->execute([$pid]);
    }
}

echo "Home feed seeded for {$n} properties.\n";
echo 'Recommend: ' . (int) $pdo->query('SELECT COUNT(*) FROM properties WHERE is_home_recommend = 1')->fetchColumn() . PHP_EOL;
echo 'Watched: ' . (int) $pdo->query('SELECT COUNT(*) FROM properties WHERE is_home_watched = 1')->fetchColumn() . PHP_EOL;
echo 'Saved: ' . (int) $pdo->query('SELECT COUNT(*) FROM properties WHERE is_home_saved = 1')->fetchColumn() . PHP_EOL;
