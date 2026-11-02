<?php
/**
 * Homepage — HouGarden /au–inspired layout.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';
require_once __DIR__ . '/includes/hougarden-locations.php';

$pageTitle = site_name() . ' — Australian property';
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
        $rows = property_list_public($filter, 'newest', 1, 0);
        $image = (string) ($market['image'] ?? '');
        if ($rows !== []) {
            $cover = trim((string) ($rows[0]['cover_path'] ?? $rows[0]['image_path'] ?? ''));
            if ($cover !== '' && !preg_match('#^https?://#i', $cover)) {
                $rel = ltrim(str_replace('\\', '/', $cover), '/');
                if (is_file(APP_ROOT . '/' . $rel)) {
                    $image = media_url($cover);
                }
            } elseif ($cover !== '' && preg_match('#^https?://#i', $cover)) {
                $image = $cover;
            }
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

$heroImage = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1600&q=80';
if ($locationCategories !== []) {
    $heroImage = (string) ($locationCategories[0]['image'] ?? $heroImage);
}

$searchDefaultTab = 'location';
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
    <h1 class="hg-hero__title">Search the way you talk</h1>
    <div class="hg-search-shell">
      <?php
      $searchAction = base_url('properties.php');
      require __DIR__ . '/includes/search-form-hougarden.php';
      ?>
    </div>
  </div>
</section>

<section class="hg-section hg-section--locations" aria-labelledby="hg-locations-heading">
  <h2 class="hg-section__title hg-section__title--page" id="hg-locations-heading">Locations</h2>
  <?php if (!$dbOk): ?>
    <p class="hg-empty">Property data is unavailable. Check your database connection in config.</p>
  <?php elseif ($locationCategories === []): ?>
    <p class="hg-empty">No location listings yet.</p>
  <?php else: ?>
    <?php require __DIR__ . '/includes/partials/home-location-cards.php'; ?>
  <?php endif; ?>
</section>

<section class="hg-section hg-section--recommend pb-16" aria-labelledby="hg-recommend-heading">
  <div class="hg-recommend-head">
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
  <h2 class="sr-only" id="hg-recommend-heading"><?= e($feedLabels[$feed] ?? 'Listings') ?></h2>
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
</section>
<?php
require __DIR__ . '/includes/footer-hougarden.php';
