<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
$paths = db()->query('SELECT path FROM property_images')->fetchAll(PDO::FETCH_COLUMN);
$sample = array_slice($paths, 0, 8);
foreach ($sample as $p) {
    echo $p . "\n";
}
$folders = [];
foreach ($paths as $p) {
    if (preg_match('#^uploads/properties/([^/]+)/#', $p, $m)) {
        $folders[$m[1]] = true;
    }
}
echo 'Referenced folders: ' . count($folders) . "\n";
echo implode(', ', array_keys($folders)) . "\n";
