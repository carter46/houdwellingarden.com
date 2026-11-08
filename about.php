<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';

$pageTitle = 'About — ' . site_name();
hougarden_use_public_shell('about', 'hg-about-page-shell');
require __DIR__ . '/includes/header-hougarden.php';

$about = about_content_public();
$brand = site_name();
$heroImage = is_file(__DIR__ . '/assets/img/hero-home.jpg')
    ? asset_url('assets/img/hero-home.jpg')
    : asset_url('assets/img/regions/austin.jpg');
?>
<div class="hg-about-page">
  <div class="hg-page-head hg-about-page__head">
    <p class="hg-page-head__eyebrow">About</p>
    <h1 class="hg-page-head__title">About <?= e($brand) ?></h1>
    <p class="hg-page-head__lead"><?= e($about['hero_lead']) ?></p>
  </div>

  <section class="hg-about-banner" aria-label="Our story">
    <div class="hg-about-banner__media">
      <img src="<?= e($heroImage) ?>" alt="" loading="lazy" class="hg-about-banner__img"/>
      <div class="hg-about-banner__scrim" aria-hidden="true"></div>
    </div>
    <div class="hg-about-banner__body">
      <h2 class="hg-about-banner__title"><?= e($about['hero_title']) ?></h2>
      <p class="hg-about-banner__lead"><?= e($about['intro']) ?></p>
    </div>
  </section>

  <section class="hg-about-split hg-about-split--story">
    <div class="hg-about-copy hg-panel">
      <h2 class="hg-content-block__title">Our approach</h2>
      <p><?= e($about['intro']) ?></p>
      <p><?= e($about['mission']) ?></p>
      <a href="<?= e(base_url('agents.php')) ?>" class="hg-text-link"><?= hg_icon('person', 'hg-icon--sm') ?> Meet our agents</a>
    </div>
    <div class="hg-about-stats hg-panel">
      <p class="hg-about-stats__heading">By the numbers</p>
      <div class="hg-about-stats__grid">
        <?php foreach ($about['stats'] as $stat): ?>
          <?php
            $value = trim((string) ($stat['value'] ?? ''));
            $label = trim((string) ($stat['label'] ?? ''));
            if ($value === '' || $label === '') {
                continue;
            }
          ?>
          <div class="hg-about-stat">
            <div class="hg-about-stat__value"><?= e($value) ?></div>
            <div class="hg-about-stat__label"><?= e($label) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="hg-about-pillars" aria-label="What we offer">
    <?php foreach ($about['pillars'] as $pillar): ?>
      <article class="hg-about-pillar hg-panel">
        <div class="hg-about-pillar__icon" aria-hidden="true"><?= hg_icon((string) ($pillar['icon'] ?? 'info')) ?></div>
        <h3><?= e((string) $pillar['title']) ?></h3>
        <p><?= e((string) $pillar['body']) ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="hg-about-cta">
    <h2 class="hg-content-block__title"><?= e($about['cta_title']) ?></h2>
    <p><?= e($about['cta_body']) ?></p>
    <div class="hg-about-cta__actions">
      <a href="<?= e(base_url('properties.php')) ?>" class="hg-btn">View listings</a>
      <a href="<?= e(base_url('contact.php')) ?>" class="hg-btn hg-btn--ghost">Contact us</a>
    </div>
  </section>
</div>
<?php require __DIR__ . '/includes/footer-hougarden.php';
