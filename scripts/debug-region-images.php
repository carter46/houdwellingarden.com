<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/hougarden-locations.php';
foreach (hougarden_location_markets() as $m) {
    echo $m['label'] . ' => ' . $m['image'] . PHP_EOL;
}
