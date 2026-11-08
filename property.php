<?php
/**
 * Dynamic property detail — Glass House section template for any public property.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    $pageTitle = 'Property not found — ' . site_name();
    $pageMetaRobots = 'noindex,follow';
    $activeNav = 'properties';
    hougarden_use_public_shell('search');
    require __DIR__ . '/includes/header-hougarden.php';
    echo '<p class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-24 font-body-lg text-on-surface-variant">Property not found.</p>';
    require __DIR__ . '/includes/footer-hougarden.php';
    exit;
}

$property = null;
$images = [];
$amenities = [];
try {
    $property = property_find_public_by_slug($slug);
    if ($property) {
        $images = property_images((int) $property['id']);
        $amenities = property_amenities_for((int) $property['id']);
    }
} catch (Throwable $e) {
    error_log('[SDC] property detail: ' . $e->getMessage());
}

if ($property) {
    $property = property_enrich_listing_agent($property);
}

if (!$property) {
    http_response_code(404);
    $pageTitle = 'Property not found — ' . site_name();
    $pageMetaRobots = 'noindex,follow';
    $activeNav = 'properties';
    hougarden_use_public_shell('search');
    require __DIR__ . '/includes/header-hougarden.php';
    echo '<p class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-24 font-body-lg text-on-surface-variant">This property is not available.</p>';
    require __DIR__ . '/includes/footer-hougarden.php';
    exit;
}

$cover = '';
foreach ($images as $img) {
    if (!empty($img['is_cover'])) {
        $cover = (string) $img['path'];
        break;
    }
}
if ($cover === '' && $images !== []) {
    $cover = (string) ($images[0]['path'] ?? '');
}

$priceLabel = format_price(
    isset($property['price']) ? (float) $property['price'] : null,
    !empty($property['price_on_request']),
    (string) ($property['currency'] ?? 'USD')
);
$locationEyebrow = trim(($property['city'] ?? '') . (($property['state'] ?? '') !== '' ? ', ' . $property['state'] : ''));
$pageTitle = (string) $property['title'] . ' — ' . site_name();
hougarden_use_public_shell('search', 'property-detail-page');

$byCat = [];
foreach ($amenities as $am) {
    $byCat[$am['category']][] = $am;
}

$inquiryErrors = [];
$inquirySuccess = false;
$inquiryForm = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'interest' => 'Buy Now',
    'message' => inquiry_default_property_message($property),
];

if (is_post() && ($_POST['form'] ?? '') === 'property_inquiry') {
    if (!csrf_verify()) {
        $inquiryErrors[] = 'Invalid security token. Please try again.';
    } elseif (honeypot_tripped()) {
        $inquirySuccess = true;
    } else {
        $max = (int) app_config('security.inquiry_max_per_hour', 5);
        $limit = rate_limit_hit('inquiry', $max, 3600);
        if (!$limit['allowed']) {
            $inquiryErrors[] = 'Too many messages sent. Please try again later.';
        } else {
            $inquiryForm = [
                'first_name' => (string) ($_POST['first_name'] ?? ''),
                'last_name' => (string) ($_POST['last_name'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'phone' => (string) ($_POST['phone'] ?? ''),
                'interest' => (string) ($_POST['interest'] ?? 'Buy Now'),
                'message' => (string) ($_POST['message'] ?? ''),
                'property_id' => (int) $property['id'],
            ];
            $validated = inquiry_validate($inquiryForm, 'property_inquiry');
            $inquiryErrors = $validated['errors'];
            if ($validated['ok']) {
                try {
                    $id = inquiry_create($validated['data']);
                    $inquiry = inquiry_find($id) ?? array_merge($validated['data'], ['id' => $id]);
                    inquiry_notify_admin($inquiry);
                    inquiry_ack_client($inquiry);
                    $inquirySuccess = true;
                    $inquiryForm = [
                        'first_name' => '',
                        'last_name' => '',
                        'email' => '',
                        'phone' => '',
                        'interest' => 'Buy Now',
                        'message' => inquiry_default_property_message($property),
                    ];
                } catch (Throwable $e) {
                    error_log('[SDC] property inquiry: ' . $e->getMessage());
                    $inquiryErrors[] = 'Could not submit your inquiry. Please try again.';
                }
            }
        }
    }
}

$coverUrl = $cover !== '' ? media_url($cover) : '';
$contactHref = base_url('contact.php?property=' . rawurlencode((string) $property['slug']));

$descRaw = property_public_description($property);
$pageDescription = $descRaw !== ''
    ? seo_truncate_description($descRaw)
    : seo_truncate_description(
        (string) $property['title'] . ' for sale in ' . trim((string) ($property['city'] ?? '') . ', ' . (string) ($property['state'] ?? '')) . ' — ' . site_name()
    );
$pageOgType = 'article';
$pageOgImage = $coverUrl !== ''
    ? (str_starts_with($coverUrl, 'http') ? $coverUrl : absolute_url(ltrim($coverUrl, '/')))
    : null;
$seoJsonLdExtra = [
    seo_jsonld_listing(
        $property,
        absolute_url('property.php?slug=' . rawurlencode((string) $property['slug'])),
        is_string($pageOgImage) ? $pageOgImage : ''
    ),
];

require __DIR__ . '/includes/header-hougarden.php';
require __DIR__ . '/includes/partials/property-detail-hougarden.php';
?>
<div class="pb-16"></div>
<?php
require __DIR__ . '/includes/footer-hougarden.php';
