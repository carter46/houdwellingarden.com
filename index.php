<?php
/**
 * Homepage — HouGarden /au–inspired layout.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';
require_once __DIR__ . '/includes/hougarden-locations.php';

$pageTitle = site_name() . ' — US homes for sale';
$pageDescription = seo_site_description();
hougarden_use_public_shell('home', 'hougarden-home');

$feed = (string) ($_GET['feed'] ?? 'recommend');
if (!in_array($feed, ['recommend', 'watched', 'saved'], true)) {
    $feed = 'recommend';
}

$locationCategories = [];
$feedListings = [];
$feedCounts = ['recommend' => 0, 'watched' => 0, 'saved' => 0];
$dbOk = true;

try {
    foreach (hougarden_location_markets() as $market) {
        $label = (string) $market['label'];
        $filter = $market['filter'];
        $count = property_count_public($filter);
        // Homepage location tiles use the region image from Admin → Regions (not a random listing photo).
        $image = (string) ($market['image'] ?? '');
        if ($image === '') {
            $image = hougarden_location_fallback_image($label);
        }
        $locationCategories[] = [
            'label' => $label,
            'href' => base_url('properties.php?' . http_build_query($filter)),
            'image' => $image,
            'count' => $count,
        ];
    }

    foreach (array_keys($feedCounts) as $key) {
        $feedCounts[$key] = property_count_public(['home_feed' => $key]);
    }
    $feedListings = property_list_public(['home_feed' => $feed], 'newest', 6, 0);
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] home: ' . $e->getMessage());
}

$heroImage = '';
if (is_file(APP_ROOT . '/assets/img/hero-home.jpg')) {
    $heroImage = media_url('assets/img/hero-home.jpg');
} elseif (is_file(APP_ROOT . '/assets/img/regions/austin-metro.jpg')) {
    $heroImage = media_url('assets/img/regions/austin-metro.jpg');
}
if ($locationCategories !== [] && $heroImage === '') {
    $heroImage = (string) ($locationCategories[0]['image'] ?? '');
}
if ($heroImage === '' && is_file(APP_ROOT . '/assets/img/regions/austin-metro.jpg')) {
    $heroImage = media_url('assets/img/regions/austin-metro.jpg');
}

$searchDefaultTab = 'location';
$searchSimpleHome = true;
$feedLabels = [
    'recommend' => 'Recommend',
    'watched' => 'Just watched',
    'saved' => 'Saved',
];

require __DIR__ . '/includes/header-hougarden.php';
?>
<section class="hg-hero" aria-label="Search properties">
  <div class="hg-hero__visual">
    <img class="hg-hero__bg" src="<?= e($heroImage) ?>" alt="" decoding="async"/>
    <div class="hg-hero__scrim hg-hero__scrim--visual" aria-hidden="true"></div>
  </div>
  <div class="hg-hero__body">
    <h1 class="hg-hero__title">Agents. Homes. Loans.</h1>
    <p class="hg-hero__tagline">Search cities and neighborhoods across the United States.</p>
    <div class="hg-search-shell hg-search-shell--hero">
      <?php
      $searchAction = base_url('properties.php');
      require __DIR__ . '/includes/search-form-hougarden.php';
      ?>
    </div>
  </div>
</section>

<section class="hg-section hg-section--locations" aria-labelledby="hg-locations-heading">
  <div class="hg-section__inner">
  <h2 class="hg-section__title hg-section__title--page" id="hg-locations-heading">Explore areas</h2>
  <?php if (!$dbOk): ?>
    <p class="hg-empty">Property data is unavailable. Check your database connection in config.</p>
  <?php elseif ($locationCategories === []): ?>
    <p class="hg-empty">No location listings yet.</p>
  <?php else: ?>
    <?php require __DIR__ . '/includes/partials/home-location-cards.php'; ?>
  <?php endif; ?>
  </div>
</section>

<section class="hg-section hg-section--recommend pb-16" aria-labelledby="hg-recommend-heading">
  <div class="hg-section__inner">
  <div class="hg-recommend-head">
    <h2 class="hg-section__title hg-section__title--page" id="hg-recommend-heading">Recommended homes</h2>
    <div class="hg-pill-tabs" role="tablist" aria-label="Listing filters">
      <?php foreach ($feedLabels as $feedKey => $feedLabel): ?>
        <?php
          $isActive = $feed === $feedKey;
          $pillHref = base_url('index.php?' . http_build_query(['feed' => $feedKey]));
          $count = (int) ($feedCounts[$feedKey] ?? 0);
        ?>
        <a class="hg-pill<?= $isActive ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $isActive ? 'true' : 'false' ?>" href="<?= e($pillHref) ?>">
          <?= e($feedLabel) ?><?= $count > 0 ? ' (' . $count . ')' : '' ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if (!$dbOk): ?>
    <p class="hg-empty">Property data is unavailable.</p>
  <?php elseif ($feedListings === []): ?>
    <p class="hg-empty">No listings in this feed yet. In admin, edit a property and check <strong><?= e($feedLabels[$feed] ?? '') ?></strong> under Homepage feeds.</p>
  <?php else: ?>
    <div class="hg-recommend-grid">
      <?php foreach ($feedListings as $property): ?>
        <?php require __DIR__ . '/includes/property-card-hougarden-recommend.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/partials/home-action-cards.php'; ?>
<?php
require __DIR__ . '/includes/footer-hougarden.php';
