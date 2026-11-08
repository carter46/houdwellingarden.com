<?php
/**
 * Zillow-style pathways: Buy · Rent · Sell (official Zillow spot art, stored under assets/img/pathways/).
 */
declare(strict_types=1);

$pathways = [
    [
        'title' => 'Buy a home',
        'text' => 'Find your place with rich photos, prices, and the widest choice of listings.',
        'cta' => 'Search homes',
        'href' => base_url('properties.php'),
        'image' => 'assets/img/pathways/buy.webp',
    ],
    [
        'title' => 'Rent a home',
        'text' => 'Browse rentals by suburb and budget, then enquire when you are ready.',
        'cta' => 'Find rentals',
        'href' => base_url('properties.php'),
        'image' => 'assets/img/pathways/rent.webp',
    ],
    [
        'title' => 'Sell a home',
        'text' => 'List with us and reach buyers across the US with professional presentation.',
        'cta' => 'See your options',
        'href' => base_url('contact.php?subject=' . rawurlencode('Sell / list a property')),
        'image' => 'assets/img/pathways/sell.webp',
    ],
];
?>
<section class="hg-section hg-section--pathways">
  <div class="hg-section__inner hg-section__inner--pathways">
    <div class="hg-pathway-grid">
      <?php foreach ($pathways as $item): ?>
        <article class="hg-pathway-card">
          <div class="hg-pathway-card__art" aria-hidden="true">
            <img
              class="hg-pathway-card__illus"
              src="<?= e(asset_url($item['image'])) ?>"
              alt=""
              width="320"
              height="240"
              loading="lazy"
              decoding="async"
            />
          </div>
          <h3 class="hg-pathway-card__title"><?= e($item['title']) ?></h3>
          <p class="hg-pathway-card__text"><?= e($item['text']) ?></p>
          <a class="hg-pathway-card__btn" href="<?= e($item['href']) ?>"><?= e($item['cta']) ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
