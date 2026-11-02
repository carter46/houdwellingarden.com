<?php
/**
 * HouGarden-style “explore Sydney” category tiles — links into property search.
 *
 * @var string $sydneyHub defaults to Sydney
 */

declare(strict_types=1);

$sydneyHub = $sydneyHub ?? 'Sydney';
$base = base_url('properties.php');

$tiles = [
    [
        'label' => 'Just listed',
        'sub' => 'New on market',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub, 'sort' => 'newest']),
        'bg' => 'linear-gradient(135deg, #5b8def, #3d6fd6)',
    ],
    [
        'label' => 'Open homes this week',
        'sub' => 'Inspect in person',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub]),
        'bg' => 'linear-gradient(135deg, #6aab73, #4a8f52)',
    ],
    [
        'label' => 'Recently sold',
        'sub' => 'Market activity',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub]),
        'bg' => 'linear-gradient(135deg, #9a8b7a, #7a6b5a)',
    ],
    [
        'label' => 'Near transport',
        'sub' => 'Rail & metro',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub]),
        'bg' => 'linear-gradient(135deg, #5c7a8a, #3d5a6a)',
    ],
    [
        'label' => 'Brand new',
        'sub' => 'New builds',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub, 'type' => 'condo']),
        'bg' => 'linear-gradient(135deg, #7b8cff, #5a6ae8)',
    ],
    [
        'label' => 'Auctions',
        'sub' => 'This week',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub]),
        'bg' => 'linear-gradient(135deg, #e07a5f, #c85a3f)',
    ],
    [
        'label' => 'Most popular',
        'sub' => 'Trending',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub]),
        'bg' => 'linear-gradient(135deg, #f4a261, #e76f51)',
    ],
    [
        'label' => 'Price reduced',
        'sub' => 'Value picks',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub, 'sort' => 'price_asc']),
        'bg' => 'linear-gradient(135deg, #2a9d8f, #21867a)',
    ],
    [
        'label' => 'Luxury',
        'sub' => 'Premium homes',
        'href' => $base . '?' . http_build_query(['location' => $sydneyHub, 'price' => '10+']),
        'bg' => 'linear-gradient(135deg, #264653, #1a3038)',
    ],
    [
        'label' => 'Waterfront',
        'sub' => 'Harbour & coast',
        'href' => $base . '?' . http_build_query(['location' => 'Rose Bay']),
        'bg' => 'linear-gradient(135deg, #48cae4, #0096c7)',
    ],
];
?>
<section class="hg-section" aria-labelledby="hg-sydney-title">
  <div class="hg-section__head">
    <h2 class="hg-section__title" id="hg-sydney-title">More about <?= e($sydneyHub) ?></h2>
    <a class="hg-section__link" href="<?= e($base . '?' . http_build_query(['location' => $sydneyHub])) ?>">View all in <?= e($sydneyHub) ?> →</a>
  </div>
  <div class="hg-sydney-grid">
    <?php foreach ($tiles as $tile): ?>
      <a class="hg-sydney-tile" href="<?= e($tile['href']) ?>" style="background-image: <?= e($tile['bg']) ?>;">
        <span class="hg-sydney-tile__scrim" aria-hidden="true"></span>
        <span class="hg-sydney-tile__label">
          <?= e($tile['label']) ?>
          <span class="hg-sydney-tile__count"><?= e($tile['sub']) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
