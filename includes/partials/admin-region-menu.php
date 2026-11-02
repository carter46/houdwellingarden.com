<?php
/**
 * @var array $row
 * @var int $usage
 * @var bool $isActiveRow
 */
declare(strict_types=1);
?>
<div class="admin-menu">
    <button type="button" class="admin-menu__toggle" aria-label="Actions" aria-haspopup="true">⋯</button>
    <div class="admin-menu__panel" hidden>
        <a href="<?= e(base_url('admin/regions.php?id=' . (int) $row['id'])) ?>">Edit</a>
        <?php if ($isActiveRow): ?>
            <?php if ($usage === 0): ?>
                <form method="post" onsubmit="return confirm('Deactivate this region?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                    <button type="submit">Deactivate</button>
                </form>
            <?php else: ?>
                <span class="admin-note" style="display:block;padding:0.5rem 0.85rem;">Has listings — cannot deactivate</span>
            <?php endif; ?>
        <?php else: ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="activate">
                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                <button type="submit">Activate</button>
            </form>
            <?php if ($usage === 0): ?>
                <form method="post" onsubmit="return confirm('Permanently delete this deactivated region? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                    <button type="submit">Delete permanently</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
