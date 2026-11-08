<?php
/**
 * Shared site branding — logo in header bars; sidebar always uses letter mark.
 *
 * @var string $brandSlot topnav|mobile|mobile-drawer|sidebar
 */

declare(strict_types=1);

$brandSlot = $brandSlot ?? 'topnav';
$hasLogo = site_has_logo();
$logoUrl = $hasLogo ? site_logo_url() : '';
$name = site_name();
$short = site_name_short();
$initial = function_exists('mb_substr')
    ? mb_substr($short, 0, 1)
    : substr($short, 0, 1);

if ($brandSlot === 'sidebar'): ?>
  <span class="hg-sidebar__logo-mark" aria-hidden="true"><?= e($initial) ?></span>
  <?php
    return;
endif;

if ($hasLogo && $logoUrl !== ''):
    ?>
    <img
      class="hg-brand-img hg-brand-img--<?= e($brandSlot) ?>"
      src="<?= e($logoUrl) ?>"
      alt="<?= e($name) ?>"
      decoding="async"
      loading="eager"
    />
    <?php
    return;
endif;

if ($brandSlot === 'topnav'): ?>
  <span class="hg-topnav__brand-mark" aria-hidden="true"><?= e($initial) ?></span>
  <span class="hg-topnav__brand-text"><?= e($short) ?></span>
<?php elseif ($brandSlot === 'mobile' || $brandSlot === 'mobile-drawer'): ?>
  <span class="hg-mobile-bar__logo" aria-hidden="true"><?= e(strtolower($initial)) ?></span>
  <span class="hg-mobile-bar__name"><?= e($short) ?></span>
<?php endif; ?>
