<?php
/**
 * Public SEO helpers (meta, canonical, JSON-LD) + admin noindex.
 */

declare(strict_types=1);

function seo_site_description(): string
{
    $custom = trim((string) (setting_get('seo_meta_description') ?? ''));
    if ($custom !== '') {
        return $custom;
    }
    $about = about_content_public();
    $intro = trim((string) ($about['intro'] ?? ''));
    if ($intro !== '') {
        return seo_truncate_description($intro);
    }

    return seo_truncate_description(
        site_name() . ' — search US homes for sale across featured cities. Listings and site name are managed in admin.'
    );
}

function seo_truncate_description(string $text, int $max = 160): string
{
    $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
    if ($text === '') {
        return '';
    }
    if ((function_exists('mb_strlen') ? mb_strlen($text) : strlen($text)) <= $max) {
        return $text;
    }
    $cut = function_exists('mb_substr') ? mb_substr($text, 0, $max - 1) : substr($text, 0, $max - 1);

    return rtrim((string) $cut) . '…';
}

function seo_page_title_string(): string
{
    global $pageTitle;
    $title = trim((string) ($pageTitle ?? ''));
    if ($title === '') {
        return site_name();
    }

    return $title;
}

/**
 * Preferred canonical URL for the current public page.
 */
function seo_current_canonical(): string
{
    global $seoCanonicalUrl;
    if (!empty($seoCanonicalUrl) && is_string($seoCanonicalUrl)) {
        return $seoCanonicalUrl;
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
  if ($script === 'index.php') {
        return rtrim(app_public_origin(), '/') . '/';
    }
    if ($script === 'property.php') {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        if ($slug !== '') {
            return absolute_url('property.php?slug=' . rawurlencode($slug));
        }
    }

    $indexable = [
        'about.php',
        'contact.php',
        'faq.php',
        'agents.php',
        'markets.php',
        'properties.php',
    ];
    if (in_array($script, $indexable, true)) {
        if ($script === 'properties.php') {
            return absolute_url('properties.php');
        }

        return absolute_url($script);
    }

    return absolute_url($script);
}

function seo_default_og_image(): string
{
    global $pageOgImage;
    if (!empty($pageOgImage) && is_string($pageOgImage)) {
        if (str_starts_with($pageOgImage, 'http://') || str_starts_with($pageOgImage, 'https://')) {
            return $pageOgImage;
        }

        return absolute_url(ltrim($pageOgImage, '/'));
    }
    $logo = site_logo_url();
    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
        return $logo;
    }

    return absolute_url(ltrim($logo, '/'));
}

/**
 * @return list<array<string, mixed>>
 */
function seo_jsonld_organization(): array
{
    $origin = rtrim(app_public_origin(), '/');
    $data = [
        '@type' => 'RealEstateAgent',
        '@id' => $origin . '#organization',
        'name' => site_name(),
        'url' => $origin . '/',
        'logo' => seo_default_og_image(),
    ];
    $email = site_email();
    if ($email !== '') {
        $data['email'] = $email;
    }
    $phone = site_phone();
    if ($phone !== '') {
        $data['telephone'] = $phone;
    }

    return $data;
}

/**
 * @return array<string, mixed>
 */
function seo_jsonld_website(): array
{
    $origin = rtrim(app_public_origin(), '/');

    return [
        '@type' => 'WebSite',
        '@id' => $origin . '#website',
        'name' => site_name(),
        'url' => $origin . '/',
        'publisher' => ['@id' => $origin . '#organization'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => $origin . '/properties.php?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/**
 * @param array<string, mixed> $property
 */
function seo_jsonld_listing(array $property, string $pageUrl, string $imageUrl = ''): array
{
    $data = [
        '@type' => 'RealEstateListing',
        'name' => (string) ($property['title'] ?? 'Property'),
        'url' => $pageUrl,
        'description' => seo_truncate_description(property_public_description($property), 500),
    ];
    if ($imageUrl !== '') {
        $data['image'] = $imageUrl;
    }
    $city = trim((string) ($property['city'] ?? ''));
    $state = trim((string) ($property['state'] ?? ''));
    if ($city !== '' || $state !== '') {
        $data['address'] = [
            '@type' => 'PostalAddress',
            'addressLocality' => $city,
            'addressRegion' => $state,
            'addressCountry' => (string) ($property['country'] ?? 'US'),
        ];
    }
    if (empty($property['price_on_request']) && isset($property['price']) && is_numeric($property['price'])) {
        $data['offers'] = [
            '@type' => 'Offer',
            'price' => (float) $property['price'],
            'priceCurrency' => (string) ($property['currency'] ?? 'USD'),
            'availability' => 'https://schema.org/InStock',
        ];
    }

    return $data;
}

/**
 * @return list<array<string, mixed>>
 */
function seo_page_jsonld_graph(): array
{
    global $seoJsonLdExtra;
    $graph = [seo_jsonld_organization(), seo_jsonld_website()];
    if (!empty($seoJsonLdExtra) && is_array($seoJsonLdExtra)) {
        foreach ($seoJsonLdExtra as $node) {
            if (is_array($node) && $node !== []) {
                $graph[] = $node;
            }
        }
    }

    return $graph;
}

/** Block search indexing for admin / auth surfaces (meta + HTTP header). */
function seo_admin_block_indexing(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
}

function seo_robots_txt_body(): string
{
    $lines = [
        'User-agent: *',
        'Disallow: /admin/',
        'Disallow: /api/',
        'Disallow: /storage/',
        'Disallow: /database/',
        'Disallow: /vendor/',
        'Disallow: /bin/',
        'Disallow: /docs/',
        'Disallow: /check.php',
        'Disallow: /health.php',
        'Disallow: /config.php',
        'Disallow: /config.local.php',
        'Allow: /',
        'Sitemap: ' . absolute_url('sitemap.php'),
    ];

    return implode("\n", $lines) . "\n";
}
