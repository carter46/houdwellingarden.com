<?php
/**
 * HouGarden-style recommended property card (homepage grid).
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
    (string) ($property['currency'] ?? 'AUD')
);
$title = trim((string) ($property['title'] ?? ''));
$address = trim((string) ($property['address_line'] ?? ''));
$city = trim((string) ($property['city'] ?? ''));
$addressLine = $address !== '' ? ($city !== '' ? $address . ', ' . $city : $address) : $city;
$beds = $property['bedrooms'] ?? null;
$baths = $property['bathrooms'] ?? null;
$sqft = isset($property['sqft']) && is_numeric($property['sqft']) ? (float) $property['sqft'] : null;
$badge = trim((string) ($property['badge'] ?? ''));
$slug = (string) ($property['slug'] ?? '');
$href = $slug !== '' ? base_url('property.php?slug=' . rawurlencode($slug)) : '#';

$listedRaw = trim((string) ($property['listed_at'] ?? ''));
$listedLabel = '';
if ($listedRaw !== '') {
    $ts = strtotime($listedRaw);
    $listedLabel = $ts !== false ? 'Listed on ' . date('D M j', $ts) : '';
}

$areaLabel = '';
if ($sqft !== null && $sqft > 0) {
    $areaLabel = $sqft <= 400 ? (string) (int) round($sqft) . 'm²' : (string) (int) round($sqft / 10.764) . 'm²';
}

$headline = $title !== '' ? $title : (!empty($property['price_on_request']) ? 'P.O.A.' : $priceLabel);
if ($badge !== '' && stripos($badge, 'auction') !== false) {
    $headline = $badge;
}
$showEstimate = !$property['price_on_request'] && $priceLabel !== '' && ($title !== '' || $badge !== '');

$agentName = trim((string) ($property['agent_name'] ?? ''));
$agentPhoto = trim((string) ($property['agent_photo'] ?? ''));
$agentBadge = trim((string) ($property['agent_badge'] ?? ''));
$imageCount = (int) ($property['image_count'] ?? 0);
?>
<article class="hg-rec-card">
  <a class="hg-rec-card__link" href="<?= e($href) ?>">
    <div class="hg-rec-card__media">
      <?php if ($cover !== ''): ?>
        <img class="hg-rec-card__img" src="<?= e(media_url($cover)) ?>" alt="" loading="lazy"/>
      <?php else: ?>
        <div class="hg-rec-card__img hg-rec-card__img--empty" aria-hidden="true"></div>
      <?php endif; ?>
      <?php if ($badge !== '' && stripos($badge, 'open') !== false): ?>
        <div class="hg-rec-card__open-badge">
          <span class="hg-rec-card__open-dot" aria-hidden="true"></span>
          <span><?= e($badge) ?></span>
        </div>
      <?php endif; ?>
      <?php if ($imageCount > 0): ?>
        <div class="hg-rec-card__photo-count" aria-hidden="true">
          <span class="material-symbols-outlined">photo_camera</span>
          <span><?= e((string) $imageCount) ?></span>
        </div>
      <?php endif; ?>
      <div class="hg-rec-card__agent-strip">
        <div class="hg-rec-card__agent-left">
          <?php if ($agentPhoto !== ''): ?>
            <img class="hg-rec-card__agent-photo" src="<?= e(media_url($agentPhoto)) ?>" alt=""/>
          <?php endif; ?>
          <span class="hg-rec-card__agent-name"><?= e($agentName !== '' ? $agentName : site_name_short()) ?></span>
        </div>
        <span class="hg-rec-card__agency"><?= e($agentBadge !== '' ? $agentBadge : 'AU') ?></span>
      </div>
    </div>
    <div class="hg-rec-card__body">
      <div class="hg-rec-card__top">
        <?php if ($addressLine !== ''): ?>
          <p class="hg-rec-card__address"><?= e($addressLine) ?></p>
        <?php endif; ?>
        <h3 class="hg-rec-card__headline"><?= e($headline) ?></h3>
        <?php if ($showEstimate): ?>
          <div class="hg-rec-card__estimate">
            <span class="hg-rec-card__estimate-mark">h</span>
            <span class="hg-rec-card__estimate-label">Estimate</span>
            <span class="hg-rec-card__estimate-price"><?= e($priceLabel) ?></span>
          </div>
        <?php endif; ?>
      </div>
      <div class="hg-rec-card__footer">
        <div class="hg-rec-card__features">
          <?php if ($beds !== null): ?><span><?= e((string) $beds) ?> bed</span><?php endif; ?>
          <?php if ($baths !== null): ?><span><?= e((string) $baths) ?> bath</span><?php endif; ?>
          <?php if ($areaLabel !== ''): ?><span><?= e($areaLabel) ?></span><?php endif; ?>
        </div>
        <?php if ($listedLabel !== ''): ?>
          <span class="hg-rec-card__listed"><?= e($listedLabel) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </a>
</article>
