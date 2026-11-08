<?php
/**
 * Property card for homepage grid (recent / featured blocks).
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
$slug = (string) ($property['slug'] ?? '');
$href = $slug !== '' ? base_url('property.php?slug=' . rawurlencode($slug)) : '#';
?>
<a class="hg-grid-card" href="<?= e($href) ?>">
  <?php if ($cover !== ''): ?>
    <img class="hg-grid-card__img" src="<?= e(media_url($cover)) ?>" alt="" loading="lazy"/>
  <?php else: ?>
    <div class="hg-grid-card__img img-placeholder" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="hg-grid-card__body">
    <?php if ($city !== ''): ?>
      <p class="hg-grid-card__loc"><?= e($city) ?></p>
    <?php endif; ?>
    <h3 class="hg-grid-card__title"><?= e($title !== '' ? $title : 'Property listing') ?></h3>
    <p class="hg-grid-card__price"><?= e($priceLabel) ?></p>
  </div>
</a>
