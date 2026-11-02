<?php
/**
 * HouGarden property gallery — contained grid, no horizontal overflow.
 *
 * @var list<array<string, mixed>> $images
 */

declare(strict_types=1);

$images = $images ?? [];
$tiles = [];
foreach ($images as $image) {
    $src = media_url((string) ($image['path'] ?? ''));
    if ($src === '') {
        continue;
    }
    $tiles[] = [
        'src' => $src,
        'alt' => (string) ($image['alt_text'] ?? $image['caption'] ?? ''),
    ];
}

if ($tiles === []) {
    echo '<p class="hg-prop-muted">No images yet.</p>';
    return;
}
?>
<div class="hg-prop-gallery">
  <?php foreach ($tiles as $tile): ?>
    <figure class="hg-prop-gallery__cell">
      <img src="<?= e($tile['src']) ?>" alt="<?= e($tile['alt']) ?>" loading="lazy"/>
    </figure>
  <?php endforeach; ?>
</div>
