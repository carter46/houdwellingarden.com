<?php
/**
 * XML sitemap for public pages + listings (submit URL to Google Search Console).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex', true);

$urls = [
    ['loc' => absolute_url(''), 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => absolute_url('properties.php'), 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => absolute_url('markets.php'), 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => absolute_url('about.php'), 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => absolute_url('contact.php'), 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => absolute_url('agents.php'), 'priority' => '0.6', 'changefreq' => 'weekly'],
    ['loc' => absolute_url('faq.php'), 'priority' => '0.5', 'changefreq' => 'monthly'],
];

try {
    [$in, $statuses] = public_status_sql_in();
    $stmt = db()->prepare(
        "SELECT slug, updated_at FROM properties WHERE status IN ($in) ORDER BY updated_at DESC"
    );
    $stmt->execute($statuses);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $urls[] = [
            'loc' => absolute_url('property.php?slug=' . rawurlencode($slug)),
            'lastmod' => substr((string) ($row['updated_at'] ?? ''), 0, 10),
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ];
    }
} catch (Throwable $e) {
    error_log('[SDC] sitemap: ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $entry): ?>
  <url>
    <loc><?= e((string) $entry['loc']) ?></loc>
<?php if (!empty($entry['lastmod'])): ?>
    <lastmod><?= e((string) $entry['lastmod']) ?></lastmod>
<?php endif; ?>
<?php if (!empty($entry['changefreq'])): ?>
    <changefreq><?= e((string) $entry['changefreq']) ?></changefreq>
<?php endif; ?>
<?php if (!empty($entry['priority'])): ?>
    <priority><?= e((string) $entry['priority']) ?></priority>
<?php endif; ?>
  </url>
<?php endforeach; ?>
</urlset>
