<?php
/**
 * Homepage location category tiles — link to filtered listings page.
 *
 * @var list<array{label: string, href: string, image: string, count: int}> $locationCategories
 */

declare(strict_types=1);

if (empty($locationCategories) || !is_array($locationCategories)) {
    return;
}
?>
<div class="hg-loc-grid">
  <?php foreach ($locationCategories as $cat): ?>
    <?php
      $label = (string) ($cat['label'] ?? '');
      $href = (string) ($cat['href'] ?? '#');
      $image = (string) ($cat['image'] ?? '');
      $count = (int) ($cat['count'] ?? 0);
      if ($label === '') {
          continue;
      }
      $caption = $count > 0 ? $label . ' ' . $count : $label;
    ?>
    <a class="hg-loc-tile" href="<?= e($href) ?>">
      <?php if ($image !== ''): ?>
        <img class="hg-loc-tile__img" src="<?= e($image) ?>" alt="" loading="lazy"/>
      <?php else: ?>
        <div class="hg-loc-tile__img hg-loc-tile__img--empty" aria-hidden="true"></div>
      <?php endif; ?>
      <span class="hg-loc-tile__scrim" aria-hidden="true"></span>
      <span class="hg-loc-tile__label"><?= e($caption) ?></span>
    </a>
  <?php endforeach; ?>
</div>
