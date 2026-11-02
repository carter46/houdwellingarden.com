<?php
/**
 * Grid of HouGarden-style property cards.
 *
 * @var array<int, array<string, mixed>> $properties
 */

declare(strict_types=1);

if (empty($properties) || !is_array($properties)) {
    return;
}
?>
<div class="hg-recent-grid">
  <?php foreach ($properties as $property): ?>
    <?php require __DIR__ . '/../property-card-hougarden-grid.php'; ?>
  <?php endforeach; ?>
</div>
