<?php
/**
 * Dynamic robots.txt — Sitemap uses app.url / current host.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');
echo seo_robots_txt_body();
