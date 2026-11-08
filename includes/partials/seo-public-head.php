<?php
/**
 * Public SEO tags — included from stitch-head.php
 *
 * @var string|null $pageDescription
 * @var string|null $pageMetaRobots
 * @var string|null $pageOgType
 */

declare(strict_types=1);

$metaDescription = trim((string) ($pageDescription ?? ''));
if ($metaDescription === '') {
    $metaDescription = seo_site_description();
}
$robots = trim((string) ($pageMetaRobots ?? 'index,follow,max-image-preview:large'));
$canonical = seo_current_canonical();
$ogTitle = seo_page_title_string();
$ogType = trim((string) ($pageOgType ?? 'website'));
$ogImage = seo_default_og_image();
$siteLabel = site_name();
?>
<meta name="description" content="<?= e($metaDescription) ?>"/>
<meta name="robots" content="<?= e($robots) ?>"/>
<link rel="canonical" href="<?= e($canonical) ?>"/>
<meta property="og:locale" content="en_US"/>
<meta property="og:site_name" content="<?= e($siteLabel) ?>"/>
<meta property="og:title" content="<?= e($ogTitle) ?>"/>
<meta property="og:description" content="<?= e($metaDescription) ?>"/>
<meta property="og:url" content="<?= e($canonical) ?>"/>
<meta property="og:type" content="<?= e($ogType) ?>"/>
<meta property="og:image" content="<?= e($ogImage) ?>"/>
<meta name="twitter:card" content="summary_large_image"/>
<meta name="twitter:title" content="<?= e($ogTitle) ?>"/>
<meta name="twitter:description" content="<?= e($metaDescription) ?>"/>
<meta name="twitter:image" content="<?= e($ogImage) ?>"/>
<link rel="sitemap" type="application/xml" title="Sitemap" href="<?= e(absolute_url('sitemap.php')) ?>"/>
<?php
$graph = seo_page_jsonld_graph();
if ($graph !== []) {
    $json = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    if (is_string($json) && $json !== '') {
        echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }
}
