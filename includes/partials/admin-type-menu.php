<?php
/** @var array $row */
declare(strict_types=1);
?>
<div class="admin-menu">
    <button type="button" class="admin-menu__toggle" aria-label="Actions" aria-haspopup="true">⋯</button>
    <div class="admin-menu__panel" hidden>
        <a href="<?= e(base_url('admin/property-types.php?id=' . (int) $row['id'])) ?>">Edit</a>
        <?php if (!empty($row['is_active'])): ?>
            <form method="post" onsubmit="return confirm('Deactivate this type?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="deactivate">
                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                <button type="submit">Deactivate</button>
            </form>
        <?php else: ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="activate">
                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                <button type="submit">Activate</button>
            </form>
        <?php endif; ?>
    </div>
</div>
