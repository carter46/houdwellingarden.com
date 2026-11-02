<?php
/**
 * Remove demo/import boilerplate from property descriptions in DB.
 * Usage: php scripts/clean-property-descriptions.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();
$rows = $pdo->query('SELECT id, description FROM properties')->fetchAll(PDO::FETCH_ASSOC);
$cleared = 0;
$stmt = $pdo->prepare('UPDATE properties SET description = NULL WHERE id = ?');

foreach ($rows as $row) {
    $desc = trim((string) ($row['description'] ?? ''));
    if ($desc === '') {
        continue;
    }
    $lower = strtolower($desc);
    if (str_contains($lower, 'sourced for houdwell garden demo')
        || str_contains($lower, 'hougarden /au style')
        || (str_contains($lower, 'listing in ') && str_contains($lower, 'hougarden.com'))) {
        $stmt->execute([(int) $row['id']]);
        $cleared++;
    }
}

echo "Cleared demo descriptions on {$cleared} properties.\n";
