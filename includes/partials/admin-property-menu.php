<?php
/**
 * @var int $id
 * @var array $row
 * @var bool $public
 * @var bool $isArchived
 */
declare(strict_types=1);
?>
<div class="admin-menu">
    <button type="button" class="admin-menu__toggle" aria-label="Actions" aria-haspopup="true">⋯</button>
    <div class="admin-menu__panel" hidden>
        <a href="<?= e(base_url('admin/property-form.php?id=' . $id)) ?>">Edit</a>
        <?php if ($public): ?>
            <a href="<?= e(base_url('property.php?slug=' . rawurlencode((string) $row['slug']))) ?>" target="_blank" rel="noopener">View on site</a>
        <?php endif; ?>
        <?php if (!$isArchived): ?>
            <form method="post" action="<?= e(base_url('admin/property-archive.php')) ?>" onsubmit="return confirm('Archive this listing?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit">Archive</button>
            </form>
        <?php else: ?>
            <form method="post" action="<?= e(base_url('admin/property-delete.php')) ?>" onsubmit="var t=prompt('This permanently deletes the property and all images. Type DELETE to confirm.'); if(t!=='DELETE'){return false;} this.querySelector('[name=confirm]').value=t; return true;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="confirm" value="">
                <button type="submit">Delete permanently</button>
            </form>
        <?php endif; ?>
    </div>
</div>
