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
require __DIR__ . '/stitch-head.php';
?>
<div class="hg-app" data-hg-suggest-url="<?= e(base_url('api/search-suggest.php')) ?>">
  <aside class="hg-sidebar" aria-label="Site menu">
    <div class="hg-sidebar__top">
      <a class="hg-sidebar__logo" href="<?= e(base_url('index.php')) ?>" aria-label="<?= e(site_name()) ?>">
        <span class="hg-sidebar__logo-mark">h</span>
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
            <span class="material-symbols-outlined hg-sidebar__icon" aria-hidden="true"><?= e((string) $item['icon']) ?></span>
            <span class="hg-sidebar__label"><?= e((string) $item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
    <div class="hg-sidebar__bottom">
      <a class="hg-sidebar__link hg-sidebar__link--signin" href="<?= e(base_url('admin/login.php')) ?>">
        <span class="hg-sidebar__signin-avatar" aria-hidden="true">
          <span class="material-symbols-outlined">person</span>
        </span>
        <span class="hg-sidebar__label">Sign in</span>
      </a>
      <a class="hg-sidebar__pin" href="<?= e(base_url('properties.php')) ?>" aria-label="Browse properties">
        <span class="material-symbols-outlined">location_on</span>
      </a>
    </div>
  </aside>

  <div class="hg-main">
    <header class="hg-mobile-bar lg:hidden">
      <a href="<?= e(base_url('index.php')) ?>" class="hg-mobile-bar__brand">
        <span class="hg-mobile-bar__logo">h</span>
        <?= e(site_name_short()) ?>
      </a>
      <input type="checkbox" id="hg-mobile-nav" class="sr-only" autocomplete="off"/>
      <label for="hg-mobile-nav" class="material-symbols-outlined hg-mobile-bar__menu" aria-label="Open menu">menu</label>
      <div class="hg-mobile-drawer">
        <label for="hg-mobile-nav" class="hg-mobile-drawer__backdrop" aria-hidden="true"></label>
        <div class="hg-mobile-drawer__panel">
          <a href="<?= e(base_url('index.php')) ?>" class="hg-mobile-drawer__link">Home</a>
          <?php foreach ($sidebarItems as $item): ?>
            <a href="<?= e((string) $item['href']) ?>" class="hg-mobile-drawer__link"><?= e((string) $item['label']) ?></a>
          <?php endforeach; ?>
          <a href="<?= e(base_url('properties.php')) ?>" class="hg-mobile-drawer__link">All properties</a>
          <a href="<?= e(base_url('contact.php')) ?>" class="hg-mobile-drawer__link">Contact</a>
        </div>
      </div>
    </header>
    <main class="hg-content">
