<?php
/**
 * Homepage: properties grouped by location category (HouGarden-style).
 *
 * @var array<int, array{label: string, slug: string, properties: array}> $locationSections
 */

declare(strict_types=1);

if (empty($locationSections) || !is_array($locationSections)) {
    return;
}

$propertiesBase = base_url('properties.php');
?>
<?php foreach ($locationSections as $section): ?>
  <?php
    $label = (string) ($section['label'] ?? '');
    $props = $section['properties'] ?? [];
    if ($label === '' || $props === []) {
        continue;
    }
    $viewAll = $propertiesBase . '?' . http_build_query(['location' => $label]);
  ?>
  <section class="hg-section hg-location-block" aria-labelledby="hg-loc-<?= e(preg_replace('/[^a-z0-9]+/i', '-', strtolower($label))) ?>">
    <div class="hg-section__head">
      <h3 class="hg-section__title" id="hg-loc-<?= e(preg_replace('/[^a-z0-9]+/i', '-', strtolower($label))) ?>"><?= e($label) ?></h3>
      <a class="hg-section__link" href="<?= e($viewAll) ?>">View all in <?= e($label) ?> →</a>
    </div>
    <?php $properties = array_slice($props, 0, 6); require __DIR__ . '/home-property-grid.php'; ?>
  </section>
<?php endforeach; ?>
