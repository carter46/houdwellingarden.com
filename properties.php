<?php
/**
 * Property listings — HouGarden shell + DB data.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';
require_once __DIR__ . '/includes/hougarden-locations.php';

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'location' => trim((string) ($_GET['location'] ?? '')),
    'market' => trim((string) ($_GET['market'] ?? '')),
    'region' => trim((string) ($_GET['region'] ?? '')),
    'type' => trim((string) ($_GET['type'] ?? '')),
    'price' => trim((string) ($_GET['price'] ?? '')),
    'agent_id' => max(0, (int) ($_GET['agent_id'] ?? 0)),
];
$sort = (string) ($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'price_asc', 'price_desc'], true)) {
    $sort = 'newest';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$agentFilter = null;
if ($filters['agent_id'] > 0) {
    try {
        $stmt = db()->prepare('SELECT id, name FROM agents WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$filters['agent_id']]);
        $row = $stmt->fetch();
        $agentFilter = is_array($row) ? $row : null;
        if ($agentFilter === null) {
            $filters['agent_id'] = 0;
        }
    } catch (Throwable $e) {
        $filters['agent_id'] = 0;
        $agentFilter = null;
    }
}

$total = 0;
$rows = [];
$types = [];
$dbOk = true;
try {
    $total = property_count_public($filters);
    $rows = property_list_public($filters, $sort, $perPage, $offset);
    $types = property_types_all(true);
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] listings: ' . $e->getMessage());
}

$locationFilter = trim($filters['location']);
$marketFilter = trim($filters['market']);
if ($marketFilter === 'victoria') {
    $pageTitle = 'Victoria — ' . site_name();
    $listHeading = 'Properties in Victoria';
    $listSub = 'Melbourne and regional Victoria (excluding South Yarra and Ocean Grove collections).';
    $eyebrow = 'Victoria';
} elseif ($locationFilter !== '') {
    $pageTitle = $locationFilter . ' — ' . site_name();
    $listHeading = 'Properties in ' . $locationFilter;
    $listSub = 'Browse every listing we have in ' . $locationFilter . '.';
    $eyebrow = $locationFilter;
} else {
    $pageTitle = 'Properties for sale — ' . site_name();
    $listHeading = 'Properties for sale';
    $listSub = 'Search and filter live inventory.';
    $eyebrow = 'Search';
}

hougarden_use_public_shell('properties');
$regionOptions = regions_all(true);
require __DIR__ . '/includes/header-hougarden.php';
?>
<div class="hg-page-head">
  <p class="hg-page-head__eyebrow"><?= e($eyebrow) ?></p>
  <h1 class="hg-page-head__title"><?= e($listHeading) ?></h1>
  <?php if ($agentFilter): ?>
    <p class="hg-page-head__lead">
      Listings with <strong><?= e((string) $agentFilter['name']) ?></strong>.
      <a href="<?= e(base_url('properties.php')) ?>">Clear agent filter</a>
    </p>
  <?php else: ?>
    <p class="hg-page-head__lead"><?= e($listSub) ?></p>
  <?php endif; ?>
</div>

<form method="get" action="<?= e(base_url('properties.php')) ?>" class="hg-toolbar">
  <?php if ($marketFilter !== ''): ?><input type="hidden" name="market" value="<?= e($marketFilter) ?>"><?php endif; ?>
  <div class="hg-search-input-wrap hg-search-input-wrap--toolbar" data-hg-autocomplete>
    <label for="location">Location</label>
    <input type="search" id="location" name="location" value="<?= e($filters['location']) ?>" placeholder="Suburb or address" autocomplete="off" data-hg-location-input/>
    <div class="hg-suggest" data-hg-suggest-list hidden></div>
  </div>
  <div>
    <label for="region">Region</label>
    <select id="region" name="region">
      <option value="">All regions</option>
      <?php foreach ($regionOptions as $destRow): ?>
        <?php $dest = (string) ($destRow['name'] ?? ''); ?>
        <option value="<?= e($dest) ?>" <?= $filters['region'] === $dest ? 'selected' : '' ?>><?= e($dest) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="price">Price</label>
    <select id="price" name="price">
      <option value="">Any price</option>
      <option value="2-5" <?= $filters['price'] === '2-5' ? 'selected' : '' ?>>$2M–$5M</option>
      <option value="5-10" <?= $filters['price'] === '5-10' ? 'selected' : '' ?>>$5M–$10M</option>
      <option value="10+" <?= $filters['price'] === '10+' ? 'selected' : '' ?>>$10M+</option>
    </select>
  </div>
  <div>
    <label for="type">Type</label>
    <select id="type" name="type">
      <option value="">All types</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= e((string) $t['slug']) ?>" <?= $filters['type'] === $t['slug'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
  <?php if ($filters['agent_id'] > 0): ?><input type="hidden" name="agent_id" value="<?= e((string) $filters['agent_id']) ?>"><?php endif; ?>
  <button type="submit" class="hg-btn">Apply filters</button>
</form>

<div class="hg-meta-row">
  <p>Showing <strong><?= e((string) $total) ?></strong> properties</p>
  <form method="get" class="hg-meta-row">
    <?php foreach ($filters as $k => $v): ?>
      <?php if ($v === '' || $v === 0 || $v === null) {
          continue;
      } ?>
      <input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>">
    <?php endforeach; ?>
    <label>
      Sort
      <select name="sort" onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price high → low</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price low → high</option>
      </select>
    </label>
  </form>
</div>

<div class="pb-16">
  <?php if (!$dbOk): ?>
    <p class="hg-empty">Listings unavailable.</p>
  <?php elseif ($rows === []): ?>
    <p class="hg-empty">
      <?php if ($agentFilter): ?>
        No public listings are assigned to <?= e((string) $agentFilter['name']) ?> yet.
      <?php else: ?>
        No properties match these filters.
      <?php endif; ?>
    </p>
  <?php else: ?>
    <div class="hg-recommend-grid">
      <?php foreach ($rows as $property): ?>
        <?php require __DIR__ . '/includes/property-card-hougarden-recommend.php'; ?>
      <?php endforeach; ?>
    </div>
    <?php if ($offset + count($rows) < $total): ?>
      <div style="margin-top:2rem;text-align:center;">
        <a class="hg-btn hg-btn--ghost"
           href="<?= e(base_url('properties.php?' . http_build_query(array_filter(array_merge($filters, ['sort' => $sort, 'page' => $page + 1]), static fn ($v) => $v !== '' && $v !== 0 && $v !== null)))) ?>">
          Load more
        </a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<script src="<?= e(base_url('assets/js/hg-search-autocomplete.js')) ?>?v=1" defer></script>
<?php require __DIR__ . '/includes/footer-hougarden.php';
