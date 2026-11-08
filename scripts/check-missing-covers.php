<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$pdo = db();
$rows = $pdo->query(
    'SELECT p.id, p.title, p.city FROM properties p
     LEFT JOIN property_images i ON i.property_id = p.id AND i.is_cover = 1
     WHERE i.id IS NULL ORDER BY p.id'
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "#{$r['id']} {$r['city']} — {$r['title']}\n";
}
echo 'Missing covers: ' . count($rows) . "\n";
