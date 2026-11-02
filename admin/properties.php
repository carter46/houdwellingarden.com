<?php
/**
 * Manage Properties — list, search, status filter (Phase 3).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
// Default view = active (non-archived). Explicit status=archived shows archive list.
$viewArchived = ($status === 'archived');
if ($status === '' && !isset($_GET['all'])) {
    // Active list: everything except archived
    $total = property_count_admin_excluding_archived($q);
    $rows = property_list_admin_excluding_archived($q, 50, 0);
} else {
    $total = property_count_admin($q, $status);
    $rows = property_list_admin($q, $status, 50, 0);
}

$flashOk = flash_get('property_ok');
$flashErr = flash_get('property_error');

$adminPageTitle = $viewArchived ? 'Archived Properties' : 'Manage Properties';
$adminActiveNav = 'properties';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<span class="admin-eyebrow">Listings</span>
<h1 class="admin-page-title"><?= e($adminPageTitle) ?></h1>
<p class="admin-page-lead">Showing <?= e((string) count($rows)) ?> of <?= e((string) $total) ?> matching listings.</p>

<?php if ($flashOk): ?><div class="admin-alert admin-alert--ok"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="admin-alert admin-alert--error"><?= e($flashErr) ?></div><?php endif; ?>

<div class="admin-actions">
    <a class="admin-btn" href="<?= e(base_url('admin/property-form.php')) ?>">New Listing</a>
    <?php if ($viewArchived): ?>
      <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/properties.php')) ?>">Active listings</a>
    <?php else: ?>
      <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/properties.php?status=archived')) ?>">Archived listings</a>
    <?php endif; ?>
</div>

<form class="admin-panel" method="get" action="">
    <div class="admin-toolbar">
        <div class="admin-field" style="flex:1;min-width:12rem;margin:0;">
            <label for="q">Search</label>
            <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Address, MLS #, reference, title…">
        </div>
        <?php if ($viewArchived): ?>
          <input type="hidden" name="status" value="archived">
        <?php else: ?>
        <div class="admin-field" style="min-width:10rem;margin:0;">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All active statuses</option>
                <?php foreach (property_statuses() as $st): ?>
                    <?php if ($st === 'archived') { continue; } ?>
                    <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button class="admin-btn" type="submit" style="align-self:end;">Filter</button>
        <a class="admin-btn admin-btn--ghost" style="align-self:end;" href="<?= e(base_url($viewArchived ? 'admin/properties.php?status=archived' : 'admin/properties.php')) ?>">Clear</a>
    </div>
</form>

<?php if ($viewArchived): ?>
  <p class="admin-note admin-note--compact" style="margin-top:-0.5rem;">Not shown on the public site.</p>
<?php endif; ?>

<?php if ($rows === []): ?>
<section class="admin-panel">
    <p class="admin-note" style="margin:0;"><?= $viewArchived ? 'No archived properties.' : 'No properties match these filters.' ?></p>
</section>
<?php else: ?>

<section class="admin-panel admin-panel--flush admin-table-only">
    <table class="admin-table">
        <thead>
            <tr>
                <th></th>
                <th>Property</th>
                <th>Price</th>
                <th>Status</th>
                <th>Featured</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $id = (int) $row['id'];
                $cover = (string) ($row['cover_path'] ?? '');
                $priceLabel = format_price(
                    isset($row['price']) ? (float) $row['price'] : null,
                    !empty($row['price_on_request']),
                    (string) ($row['currency'] ?? 'USD')
                );
                $public = is_property_status_public((string) $row['status']);
                $isArchived = ((string) ($row['status'] ?? '')) === 'archived';
                $listedRaw = trim((string) ($row['listed_at'] ?? ''));
                $listedLabel = '—';
                if ($listedRaw !== '') {
                    $listedTs = strtotime($listedRaw);
                    $listedLabel = $listedTs !== false ? date('j M Y', $listedTs) : $listedRaw;
                }
                ?>
                <tr>
                    <td class="admin-table__thumb">
                        <?php if ($cover !== ''): ?>
                            <img src="<?= e(media_url($cover)) ?>" alt="">
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e((string) $row['title']) ?></strong><br>
                        <span class="admin-muted"><?= e(trim(($row['city'] ?? '') . ' ' . ($row['postal_code'] ?? ''))) ?></span><br>
                        <span class="admin-muted"><?= e((string) ($row['reference_code'] ?? '')) ?><?= !empty($row['mls_number']) ? ' · MLS# ' . e((string) $row['mls_number']) : '' ?></span>
                    </td>
                    <td><?= e($priceLabel) ?></td>
                    <td>
                        <span class="admin-badge"><?= e(str_replace('_', ' ', (string) $row['status'])) ?></span>
                        <span class="admin-muted admin-listed-date"><?= e($listedLabel) ?></span>
                    </td>
                    <td><?= !empty($row['is_featured']) ? 'Yes' : '—' ?></td>
                    <td>
                        <?php require dirname(__DIR__) . '/includes/partials/admin-property-menu.php'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<div class="admin-property-cards">
    <?php foreach ($rows as $row): ?>
        <?php
        $id = (int) $row['id'];
        $cover = (string) ($row['cover_path'] ?? '');
        $priceLabel = format_price(
            isset($row['price']) ? (float) $row['price'] : null,
            !empty($row['price_on_request']),
            (string) ($row['currency'] ?? 'USD')
        );
        $public = is_property_status_public((string) $row['status']);
        $isArchived = ((string) ($row['status'] ?? '')) === 'archived';
        $listedRaw = trim((string) ($row['listed_at'] ?? ''));
        $listedLabel = '—';
        if ($listedRaw !== '') {
            $listedTs = strtotime($listedRaw);
            $listedLabel = $listedTs !== false ? date('j M Y', $listedTs) : $listedRaw;
        }
        $cardId = 'admin-property-card-' . $id;
        ?>
        <article class="admin-property-card">
            <button type="button" class="admin-property-card__head" aria-expanded="false" aria-controls="<?= e($cardId) ?>">
                <span class="admin-property-card__thumb">
                    <?php if ($cover !== ''): ?>
                        <img src="<?= e(media_url($cover)) ?>" alt="">
                    <?php endif; ?>
                </span>
                <span class="admin-property-card__summary">
                    <strong><?= e((string) $row['title']) ?></strong>
                    <span class="admin-muted"><?= e(trim(($row['city'] ?? '') . ' ' . ($row['postal_code'] ?? ''))) ?></span>
                    <span class="admin-property-card__price"><?= e($priceLabel) ?></span>
                </span>
                <span class="material-symbols-outlined admin-property-card__chevron" aria-hidden="true">expand_more</span>
            </button>
            <div class="admin-property-card__body" id="<?= e($cardId) ?>" hidden>
                <p class="admin-property-card__meta">
                    <span class="admin-badge"><?= e(str_replace('_', ' ', (string) $row['status'])) ?></span>
                    <span class="admin-muted admin-listed-date"><?= e($listedLabel) ?></span>
                </p>
                <p class="admin-muted"><?= e((string) ($row['reference_code'] ?? '')) ?><?= !empty($row['is_featured']) ? ' · Featured' : '' ?></p>
                <div class="admin-property-card__actions">
                    <?php require dirname(__DIR__) . '/includes/partials/admin-property-menu.php'; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
require dirname(__DIR__) . '/includes/admin-footer.php';
