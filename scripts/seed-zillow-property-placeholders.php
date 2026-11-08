<?php
/** @deprecated Use seed-pexels-property-photos.php */
declare(strict_types=1);
fwrite(STDERR, "Use: php scripts/seed-pexels-property-photos.php --all\n");
array_unshift($argv, 'seed-pexels-property-photos.php');
require __DIR__ . '/seed-pexels-property-photos.php';
