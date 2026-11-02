<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/hougarden-shell.php';

$pageTitle = 'About — ' . site_name();
hougarden_use_public_shell('about');
require __DIR__ . '/includes/header-hougarden.php';

$about = about_content_public();
$brand = site_name();
?>
<div class="hg-page-head">
  <p class="hg-page-head__eyebrow">About</p>
  <h1 class="hg-page-head__title">About <?= e($brand) ?></h1>
  <p class="hg-page-head__lead"><?= e($about['hero_lead']) ?></p>
</div>

<section class="hg-about-hero">
  <h2 class="hg-about-hero__title"><?= e($about['hero_title']) ?></h2>
  <p class="hg-about-hero__lead"><?= e($about['intro']) ?></p>
</section>

<div class="hg-about-split">
  <div class="hg-about-copy">
    <h2 class="hg-content-block__title">Our approach</h2>
    <p><?= e($about['intro']) ?></p>
    <p><?= e($about['mission']) ?></p>
  </div>
  <div class="hg-about-stats">
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

<div class="hg-about-pillars">
  <div class="hg-about-pillar hg-panel">
    <h3>Search-first discovery</h3>
    <p>Natural-language search, location cards, and live filters—every result comes from the same MySQL inventory your team manages in admin.</p>
  </div>
  <div class="hg-about-pillar hg-panel">
    <h3>Local markets</h3>
    <p>Sydney harbour suburbs and Victorian coast &amp; metro listings, grouped for browsing without hard-coded property cards on the homepage.</p>
  </div>
  <div class="hg-about-pillar hg-panel">
    <h3>Transparent enquiries</h3>
    <p>Property and contact forms create inquiry records, notify administrators, and acknowledge clients when mail is configured.</p>
  </div>
</div>

<div class="hg-about-cta">
  <h2 class="hg-content-block__title">Ready to explore?</h2>
  <p>Browse live listings or send a message—our team will respond from the contact details you set in Admin → Settings.</p>
  <a href="<?= e(base_url('properties.php')) ?>" class="hg-btn">View listings</a>
  <a href="<?= e(base_url('contact.php')) ?>" class="hg-btn hg-btn--ghost" style="margin-left:0.5rem;">Contact us</a>
</div>
<?php require __DIR__ . '/includes/footer-hougarden.php';
