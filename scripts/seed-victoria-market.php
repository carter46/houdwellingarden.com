<?php
/**
 * Ensure Victoria market has demo VIC listings (Melbourne metro, not South Yarra / Ocean Grove).
 * Usage: php scripts/seed-victoria-market.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/hougarden-shell.php';

hougarden_ensure_home_feed_columns();

$pdo = db();
$existing = (int) $pdo->query(
    "SELECT COUNT(*) FROM properties WHERE status IN ('available','pending','under_contract','sold','private')
     AND state = 'VIC' AND city NOT IN ('South Yarra', 'Ocean Grove')"
)->fetchColumn();

if ($existing >= 4) {
    echo "Victoria market already has {$existing} listings.\n";
    exit(0);
}

$demos = [
    ['title' => 'Apartment on Collins Street', 'address' => '4202/220 Collins Street', 'city' => 'Melbourne', 'region' => 'Melbourne CBD', 'postal' => '3000', 'price' => 1250000],
    ['title' => 'Terrace near Fitzroy Gardens', 'address' => '18 Lansdowne Street', 'city' => 'East Melbourne', 'region' => 'Inner Melbourne', 'postal' => '3002', 'price' => 2890000],
    ['title' => 'Family home, Brighton Beach', 'address' => '14 Middle Crescent', 'city' => 'Brighton', 'region' => 'Bayside', 'postal' => '3186', 'price' => 4100000],
    ['title' => 'Townhouse, Geelong West', 'address' => '2/45 Pakington Street', 'city' => 'Geelong West', 'region' => 'Geelong', 'postal' => '3218', 'price' => 895000],
    ['title' => 'Apartment, Docklands harbour', 'address' => '801/88 Waterfront Way', 'city' => 'Docklands', 'region' => 'Melbourne', 'postal' => '3008', 'price' => 720000],
    ['title' => 'Period home, Albert Park', 'address' => '56 St Vincent Place', 'city' => 'Albert Park', 'region' => 'Inner Melbourne', 'postal' => '3206', 'price' => 3650000],
];

$typeId = (int) ($pdo->query("SELECT id FROM property_types WHERE slug = 'condo' LIMIT 1")->fetchColumn() ?: 0);
if ($typeId === 0) {
    $typeId = (int) ($pdo->query('SELECT id FROM property_types ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 1);
}

$need = 6 - $existing;
$idx = 0;
$updated = 0;

$ids = $pdo->query(
    "SELECT id FROM properties WHERE state = 'NSW' AND city IN ('Bellevue Hill', 'Rose Bay')
     ORDER BY id DESC LIMIT " . (int) max(1, $need)
)->fetchAll(PDO::FETCH_COLUMN);

if (count($ids) < $need) {
    $more = $pdo->query(
        "SELECT id FROM properties ORDER BY id DESC LIMIT " . (int) $need
    )->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_values(array_unique(array_merge($ids, $more)));
}

$stmt = $pdo->prepare(
    "UPDATE properties SET
      title = ?, slug = ?, reference_code = ?, description = ?,
      property_type_id = ?, status = 'available', price = ?, price_on_request = 0, currency = 'AUD',
      address_line = ?, city = ?, region = ?, state = 'VIC', postal_code = ?, country = 'Australia',
      bedrooms = ?, bathrooms = ?, badge = ?, source_name = 'hougarden_au_vic', listed_at = CURDATE()
     WHERE id = ?"
);

foreach (array_slice($ids, 0, $need) as $id) {
    $d = $demos[$idx % count($demos)];
    $idx++;
    $pid = (int) $id;
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($d['address'] . '-' . $d['city'])) . '-' . $pid;
    $ref = 'HG-VIC-' . str_pad((string) $pid, 3, '0', STR_PAD_LEFT);
    $stmt->execute([
        $d['title'],
        $slug,
        $ref,
        'Listing in ' . $d['city'] . ', Victoria. Demo Victoria market for HouGarden-style site.',
        $typeId,
        $d['price'],
        $d['address'],
        $d['city'],
        $d['region'],
        $d['postal'],
        3,
        2,
        'Just Listed',
        $pid,
    ]);
    $updated++;
}

echo "Victoria market: updated {$updated} listings (total VIC excl. SY/OG: " .
    (int) $pdo->query(
        "SELECT COUNT(*) FROM properties WHERE state = 'VIC' AND city NOT IN ('South Yarra', 'Ocean Grove')"
    )->fetchColumn() . ").\n";
