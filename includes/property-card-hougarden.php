<?php
/**
 * HouGarden-style horizontal listing card (homepage carousel).
 *
 * @var array<string, mixed> $property
 */

declare(strict_types=1);

if (!isset($property) || !is_array($property)) {
    return;
}

$cover = (string) ($property['cover_path'] ?? $property['image_path'] ?? '');
$priceLabel = format_price(
    isset($property['price']) ? (float) $property['price'] : null,
    !empty($property['price_on_request']),
    (string) ($property['currency'] ?? 'USD')
);
$title = trim((string) ($property['title'] ?? ''));
$city = trim((string) ($property['city'] ?? ''));
$region = trim((string) ($property['region'] ?? ''));
$locLine = $city !== '' ? $city : $region;
$beds = $property['bedrooms'] ?? null;
$baths = $property['bathrooms'] ?? null;
$slug = (string) ($property['slug'] ?? '');
$href = $slug !== '' ? base_url('property.php?slug=' . rawurlencode($slug)) : '#';
?>
<a class="hg-listing-card" href="<?= e($href) ?>">
  <?php if ($cover !== ''): ?>
    <img class="hg-listing-card__img" src="<?= e(media_url($cover)) ?>" alt="" loading="lazy"/>
  <?php else: ?>
    <div class="hg-listing-card__img img-placeholder" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="hg-listing-card__body">
    <?php if ($locLine !== ''): ?>
      <p class="hg-listing-card__loc"><?= e($locLine) ?></p>
    <?php endif; ?>
    <h3 class="hg-listing-card__title"><?= e($title !== '' ? $title : 'Property listing') ?></h3>
    <div class="hg-listing-card__meta">
      <?php if ($beds !== null): ?><span><?= e((string) $beds) ?> bed</span><?php endif; ?>
      <?php if ($baths !== null): ?><span><?= e((string) $baths) ?> bath</span><?php endif; ?>
    </div>
    <p class="hg-listing-card__price"><?= e($priceLabel) ?></p>
  </div>
</a>
