<?php
/**
 * JSON suggest: locations + properties while typing (Search by location).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/hougarden-locations.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 1) {
    echo json_encode(['locations' => [], 'properties' => []], JSON_THROW_ON_ERROR);
    exit;
}

$qLower = mb_strtolower($q);
$locations = [];
foreach (hougarden_location_markets() as $market) {
    $label = (string) $market['label'];
    if (!str_contains(mb_strtolower($label), $qLower)) {
        continue;
    }
    $filter = $market['filter'];
    try {
        $count = property_count_public($filter);
    } catch (Throwable $e) {
        $count = 0;
    }
    $href = base_url('properties.php?' . http_build_query($filter));
    $locations[] = [
        'label' => $label,
        'count' => $count,
        'href' => $href,
        'type' => 'location',
    ];
}

try {
    $pdo = db();
    [$in, $statuses] = public_status_sql_in();
    $like = '%' . property_like_escape($q) . '%';
    $cityStmt = $pdo->prepare(
        "SELECT city, COUNT(*) AS c FROM properties p
         WHERE p.status IN ($in) AND p.city LIKE ? ESCAPE '\\\\'
         GROUP BY city ORDER BY c DESC, city ASC LIMIT 8"
    );
    $cityStmt->execute(array_merge($statuses, [$like]));
    while ($row = $cityStmt->fetch(PDO::FETCH_ASSOC)) {
        $city = trim((string) ($row['city'] ?? ''));
        if ($city === '') {
            continue;
        }
        $skip = false;
        foreach ($locations as $loc) {
            if (strcasecmp($loc['label'], $city) === 0) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }
        $locations[] = [
            'label' => $city,
            'count' => (int) ($row['c'] ?? 0),
            'href' => base_url('properties.php?' . http_build_query(['location' => $city])),
            'type' => 'city',
        ];
    }
} catch (Throwable $e) {
    error_log('[SDC] search-suggest cities: ' . $e->getMessage());
}

$properties = [];
try {
    $rows = property_list_public(['q' => $q], 'newest', 8, 0);
    foreach ($rows as $row) {
        $slug = (string) ($row['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $properties[] = [
            'title' => (string) ($row['title'] ?? ''),
            'address' => trim((string) ($row['address_line'] ?? '') . ', ' . (string) ($row['city'] ?? ''), ', '),
            'href' => base_url('property.php?slug=' . rawurlencode($slug)),
            'price' => format_price(
                isset($row['price']) ? (float) $row['price'] : null,
                !empty($row['price_on_request']),
                (string) ($row['currency'] ?? 'USD')
            ),
        ];
    }
} catch (Throwable $e) {
    error_log('[SDC] search-suggest properties: ' . $e->getMessage());
}

echo json_encode([
    'locations' => array_slice($locations, 0, 10),
    'properties' => $properties,
], JSON_THROW_ON_ERROR);
