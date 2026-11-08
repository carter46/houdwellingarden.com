<?php
/**
 * HouGarden-style shell: left sidebar (desktop) + main column.
 *
 * @var string|null $activeNav
 */

declare(strict_types=1);

require_once __DIR__ . '/hougarden-shell.php';

$activeNav = $activeNav ?? 'home';
$sidebarItems = hougarden_sidebar_items();
$topNavItems = hougarden_topnav_items();
require __DIR__ . '/stitch-head.php';
?>
<div class="hg-app" data-hg-suggest-url="<?= e(base_url('api/search-suggest.php')) ?>">
  <header class="hg-site-header" role="banner">
    <div class="hg-topnav hidden lg:flex" aria-label="Primary">
      <nav class="hg-topnav__links hg-topnav__links--start" aria-label="Browse">
        <?php foreach ($topNavItems as $item): ?>
          <a class="hg-topnav__link" href="<?= e((string) $item['href']) ?>"><?= e((string) $item['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <a class="hg-topnav__brand<?= site_has_logo() ? ' hg-topnav__brand--logo' : '' ?>" href="<?= e(base_url('index.php')) ?>">
        <?php $brandSlot = 'topnav'; require __DIR__ . '/partials/site-brand.php'; ?>
      </a>
      <div class="hg-topnav__end">
        <a class="hg-topnav__link hg-topnav__link--quiet" href="<?= e(base_url('admin/login.php')) ?>">Sign in</a>
        <a class="hg-topnav__cta" href="<?= e(base_url('properties.php')) ?>">Search homes</a>
      </div>
    </div>
    <div class="hg-mobile-bar lg:hidden">
      <a href="<?= e(base_url('index.php')) ?>" class="hg-mobile-bar__brand<?= site_has_logo() ? ' hg-mobile-bar__brand--logo' : '' ?>">
        <?php $brandSlot = 'mobile'; require __DIR__ . '/partials/site-brand.php'; ?>
      </a>
      <input type="checkbox" id="hg-mobile-nav" class="sr-only" autocomplete="off"/>
      <label for="hg-mobile-nav" class="hg-mobile-bar__menu" aria-label="Open menu"><?= hg_icon('menu', 'hg-icon--lg') ?></label>
      <div class="hg-mobile-drawer">
        <label for="hg-mobile-nav" class="hg-mobile-drawer__backdrop" aria-hidden="true"></label>
        <div class="hg-mobile-drawer__panel">
          <div class="hg-mobile-drawer__head">
            <a href="<?= e(base_url('index.php')) ?>" class="hg-mobile-drawer__brand<?= site_has_logo() ? ' hg-mobile-drawer__brand--logo' : '' ?>">
              <?php $brandSlot = 'mobile-drawer'; require __DIR__ . '/partials/site-brand.php'; ?>
            </a>
            <label for="hg-mobile-nav" class="hg-mobile-drawer__close" aria-label="Close menu"><?= hg_icon('close') ?></label>
          </div>
          <div class="hg-mobile-drawer__scroll">
            <p class="hg-mobile-drawer__section">Browse</p>
            <?php foreach ($topNavItems as $item): ?>
              <a class="hg-mobile-drawer__link" href="<?= e((string) $item['href']) ?>"><?= e((string) $item['label']) ?></a>
            <?php endforeach; ?>
            <p class="hg-mobile-drawer__section">Shortcuts</p>
            <?php foreach ($sidebarItems as $item): ?>
              <a class="hg-mobile-drawer__link" href="<?= e((string) $item['href']) ?>">
                <?= hg_icon((string) ($item['icon'] ?? 'search')) ?>
                <span><?= e((string) $item['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <div class="hg-mobile-drawer__footer">
            <a class="hg-mobile-drawer__cta" href="<?= e(base_url('properties.php')) ?>">Search homes</a>
            <a class="hg-mobile-drawer__cta hg-mobile-drawer__cta--ghost" href="<?= e(base_url('admin/login.php')) ?>">Sign in</a>
          </div>
        </div>
      </div>
    </div>
  </header>
  <div class="hg-app__frame">
  <aside class="hg-sidebar" aria-label="Site menu">
    <div class="hg-sidebar__top">
      <a class="hg-sidebar__logo" href="<?= e(base_url('index.php')) ?>" aria-label="<?= e(site_name()) ?>">
        <?php $brandSlot = 'sidebar'; require __DIR__ . '/partials/site-brand.php'; ?>
      </a>
      <nav class="hg-sidebar__nav">
        <?php foreach ($sidebarItems as $item): ?>
          <?php
            $key = (string) ($item['key'] ?? '');
            $active = ($activeNav === $key)
              || ($key === 'search' && $activeNav === 'properties')
              || ($key === 'saved' && $activeNav === 'home' && (($_GET['feed'] ?? '') === 'saved'))
              || ($key === 'about' && $activeNav === 'about');
            $cls = 'hg-sidebar__link' . ($active ? ' is-active' : '');
          ?>
          <a class="<?= e($cls) ?>" href="<?= e((string) $item['href']) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
            <?= hg_icon((string) $item['icon'], 'hg-sidebar__icon') ?>
            <span class="hg-sidebar__label"><?= e((string) $item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
    <div class="hg-sidebar__bottom">
      <a class="hg-sidebar__link hg-sidebar__link--signin" href="<?= e(base_url('admin/login.php')) ?>">
        <span class="hg-sidebar__signin-avatar" aria-hidden="true">
          <?= hg_icon('person') ?>
        </span>
        <span class="hg-sidebar__label">Sign in</span>
      </a>
      <a class="hg-sidebar__pin" href="<?= e(base_url('properties.php')) ?>" aria-label="Browse properties">
        <?= hg_icon('location_on') ?>
      </a>
    </div>
  </aside>

  <div class="hg-main">
    <main class="hg-content">
