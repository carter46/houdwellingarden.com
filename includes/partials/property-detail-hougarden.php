<?php
/**
 * HouGarden-style property detail (expects $property, $images, $amenities, $byCat, etc.)
 */

declare(strict_types=1);

$agentPhotoUrl = agent_photo_url(
    isset($property['agent_photo']) ? (string) $property['agent_photo'] : null,
    isset($property['agent_slug']) ? (string) $property['agent_slug'] : null
);
$badge = trim((string) ($property['badge'] ?? ''));
$addressFull = trim(
    (string) ($property['address_line'] ?? '') .
    (($property['city'] ?? '') !== '' ? ', ' . (string) $property['city'] : '') .
    (($property['state'] ?? '') !== '' ? ' ' . (string) $property['state'] : '')
);
$listedRaw = trim((string) ($property['listed_at'] ?? ''));
$listedLabel = $listedRaw !== '' && ($ts = strtotime($listedRaw)) ? date('D M j, Y', $ts) : '';
$imageCount = count($images);
$areaLabel = property_floor_area_label($property);
$publicDescription = property_public_description($property);
$showAgent = property_page_show_agent()
    && (!empty($property['agent_id']) || trim((string) ($property['agent_name'] ?? '')) !== '');
$showInquiry = property_page_show_inquiry_form();
$enquireHref = $showInquiry ? '#inquiry' : $contactHref;
?>
<div class="hg-prop pb-16">
  <div class="hg-prop-hero">
    <?php if ($coverUrl !== ''): ?>
      <img class="hg-prop-hero__img" src="<?= e($coverUrl) ?>" alt=""/>
    <?php else: ?>
      <div class="hg-prop-hero__img hg-prop-hero__img--empty"></div>
    <?php endif; ?>
    <div class="hg-prop-hero__scrim" aria-hidden="true"></div>
    <div class="hg-prop-hero__content">
      <?php if ($badge !== ''): ?>
        <span class="hg-prop-hero__badge"><?= e($badge) ?></span>
      <?php endif; ?>
      <p class="hg-prop-hero__loc"><?= e($locationEyebrow !== '' ? $locationEyebrow : (string) ($property['city'] ?? '')) ?></p>
      <h1 class="hg-prop-hero__title"><?= e((string) $property['title']) ?></h1>
      <?php if ($addressFull !== ''): ?>
        <p class="hg-prop-hero__address"><?= e($addressFull) ?></p>
      <?php endif; ?>
      <p class="hg-prop-hero__price"><?= e($priceLabel) ?></p>
      <div class="hg-prop-hero__actions">
        <a href="<?= e($enquireHref) ?>" class="hg-btn<?= $showInquiry ? ' js-scroll-inquiry' : '' ?>">Enquire</a>
        <a href="<?= e($contactHref) ?>" class="hg-btn hg-btn--ghost hg-btn--on-dark">Request details</a>
      </div>
    </div>
  </div>

  <div class="hg-prop-stats">
    <div class="hg-prop-stats__item">
      <?= hg_icon('bed') ?>
      <span><strong><?= e($property['bedrooms'] !== null ? (string) $property['bedrooms'] : '—') ?></strong> beds</span>
    </div>
    <div class="hg-prop-stats__item">
      <?= hg_icon('shower') ?>
      <span><strong><?= e($property['bathrooms'] !== null ? (string) $property['bathrooms'] : '—') ?></strong> baths</span>
    </div>
    <div class="hg-prop-stats__item">
      <?= hg_icon('square_foot') ?>
      <span><strong><?= e($areaLabel !== '' ? $areaLabel : '—') ?></strong></span>
    </div>
    <?php if ($listedLabel !== ''): ?>
      <div class="hg-prop-stats__item hg-prop-stats__item--listed">
        <?= hg_icon('calendar_today') ?>
        <span><?= e($listedLabel) ?></span>
      </div>
    <?php endif; ?>
  </div>

  <div class="hg-prop-layout">
    <div class="hg-prop-main">
      <?php if ($publicDescription !== ''): ?>
        <section class="hg-prop-block">
          <h2 class="hg-prop-block__title">About this property</h2>
          <div class="hg-prop-block__body"><?= nl2br(e($publicDescription)) ?></div>
        </section>
      <?php endif; ?>

      <section class="hg-prop-block">
        <h2 class="hg-prop-block__title">Photos<?= $imageCount > 0 ? ' (' . $imageCount . ')' : '' ?></h2>
        <?php if ($images === []): ?>
          <p class="hg-prop-muted">No gallery images yet.</p>
        <?php else: ?>
          <div class="hg-prop-gallery-wrap">
            <?php require __DIR__ . '/../property-gallery-hougarden.php'; ?>
          </div>
        <?php endif; ?>
      </section>

      <?php if ($byCat !== []): ?>
        <section class="hg-prop-block">
          <h2 class="hg-prop-block__title">Features</h2>
          <div class="hg-prop-amenities">
            <?php foreach ($byCat as $cat => $items): ?>
              <div class="hg-prop-amenity-group">
                <h3 class="hg-prop-amenity-group__title"><?= e((string) $cat) ?></h3>
                <ul class="hg-prop-amenity-list">
                  <?php foreach ($items as $item): ?>
                    <li><?= e((string) $item['name']) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </div>

    <aside class="hg-prop-aside">
      <?php if ($showAgent): ?>
        <div class="hg-prop-agent hg-panel">
          <?php if ($agentPhotoUrl !== ''): ?>
            <img class="hg-prop-agent__photo" src="<?= e($agentPhotoUrl) ?>" alt=""/>
          <?php endif; ?>
          <p class="hg-prop-agent__badge"><?= e((string) ($property['agent_badge'] ?? 'Listing agent')) ?></p>
          <h3 class="hg-prop-agent__name"><?= e((string) ($property['agent_name'] ?? '')) ?></h3>
          <p class="hg-prop-agent__title"><?= e(trim(($property['agent_title'] ?? '') . (($property['agent_region'] ?? '') !== '' ? ', ' . $property['agent_region'] : ''))) ?></p>
          <?php if (!empty($property['agent_quote'])): ?>
            <p class="hg-prop-agent__quote">&ldquo;<?= e((string) $property['agent_quote']) ?>&rdquo;</p>
          <?php endif; ?>
          <a href="<?= e($enquireHref) ?>" class="hg-btn hg-btn--block<?= $showInquiry ? ' js-scroll-inquiry' : '' ?>">Enquire now</a>
          <p class="hg-prop-agent__ref">Ref <?= e((string) $property['reference_code']) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($showInquiry): ?>
      <div id="inquiry" class="hg-prop-inquiry hg-panel">
        <h2 class="hg-prop-inquiry__title">Send an enquiry</h2>
        <?php
          $inquiryIntro = $showAgent && trim((string) ($property['agent_name'] ?? '')) !== ''
              ? 'Same form as Contact us — pre-filled for this listing. Your message goes to our team and listing agent ' . (string) $property['agent_name'] . '.'
              : 'Same form as Contact us — pre-filled for this listing. Your message is stored and emailed to our team.';
          $inquiryFormAction = '#inquiry';
          $inquiryContext = 'property';
          $formHidden = 'property_inquiry';
          $idPrefix = 'inq';
          $submitLabel = 'Send enquiry';
          require __DIR__ . '/inquiry-form-hougarden.php';
        ?>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</div>

<?php if ($showInquiry): ?>
<div class="hg-prop-mobile-cta lg:hidden" id="property-mobile-cta">
  <div class="hg-prop-mobile-cta__inner">
    <span class="hg-prop-mobile-cta__price"><?= e($priceLabel) ?></span>
    <a href="#inquiry" class="hg-btn js-scroll-inquiry">Enquire</a>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  var inquiry = document.getElementById("inquiry");
  if (!inquiry) return;
  function scrollToInquiry(e) {
    if (e) e.preventDefault();
    inquiry.scrollIntoView({ behavior: "smooth", block: "start" });
  }
  document.querySelectorAll(".js-scroll-inquiry").forEach(function (link) {
    link.addEventListener("click", scrollToInquiry);
  });
})();
</script>
