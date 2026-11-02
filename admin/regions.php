<?php
/**
 * Regions CMS — view / add / edit / deactivate + homepage featured + image.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/hougarden-locations.php';
auth_require();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = $editId > 0 ? taxonomy_region_find($editId) : null;
$errors = [];
$ok = flash_get('region_ok');
$flashErr = flash_get('region_error');
$openModal = $editing !== null || $errors !== [];

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token.';
        $openModal = true;
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'deactivate') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $result = taxonomy_region_deactivate_safe($id);
                if ($result['ok']) {
                    flash_set('region_ok', 'Region deactivated.');
                } else {
                    flash_set('region_error', (string) $result['error']);
                }
            }
            redirect('admin/regions.php');
        }
        if ($action === 'activate') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                db()->prepare('UPDATE regions SET is_active = 1 WHERE id = ?')->execute([$id]);
                flash_set('region_ok', 'Region activated.');
            }
            redirect('admin/regions.php');
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $result = taxonomy_region_delete_permanent($id);
                if ($result['ok']) {
                    flash_set('region_ok', 'Region permanently deleted.');
                } else {
                    flash_set('region_error', (string) $result['error']);
                }
            }
            redirect('admin/regions.php');
        }

        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $sortRaw = trim((string) ($_POST['sort_order'] ?? ''));
        $sortOrder = $sortRaw === '' ? null : (int) $sortRaw;
        $isFeatured = !empty($_POST['is_featured']);
        // New regions always go live on Markets; edits keep the Active checkbox.
        $isActive = $id === null ? true : !empty($_POST['is_active']);
        $clearImage = !empty($_POST['clear_image']);
        $imagePath = null;

        $prevImage = '';
        $prevActive = true;
        if ($id) {
            $prev = taxonomy_region_find($id);
            $prevImage = is_array($prev) ? (string) ($prev['image_path'] ?? '') : '';
            $prevActive = is_array($prev) ? !empty($prev['is_active']) : true;
            // Cannot deactivate via edit form while listings still use this region.
            if ($prevActive && !$isActive && taxonomy_region_usage_count($id) > 0) {
                $errors[] = 'Cannot deactivate: listings still use this region. Reassign or remove those listings first.';
                $isActive = true;
            }
        }

        $file = $_FILES['image'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $up = region_upload_image($file, str_starts_with($prevImage, 'uploads/regions/') ? $prevImage : null);
            if (!$up['ok']) {
                $errors[] = (string) $up['error'];
            } else {
                $imagePath = (string) $up['path'];
                $clearImage = false;
            }
        }

        if ($errors === []) {
            $result = taxonomy_region_save(
                $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['slug'] ?? ''),
                $isActive,
                $isFeatured,
                $sortOrder,
                $imagePath,
                $clearImage,
                (string) ($_POST['home_filter_key'] ?? ''),
                (string) ($_POST['home_filter_value'] ?? '')
            );
            if (!$result['ok']) {
                $errors[] = (string) $result['error'];
            } else {
                if ($id) {
                    flash_set(
                        'region_ok',
                        $isActive
                            ? 'Location updated. Homepage and Markets will reflect changes immediately.'
                            : 'Location updated. It is inactive and hidden from the site.'
                    );
                } else {
                    flash_set('region_ok', 'Location created.');
                }
                redirect('admin/regions.php');
            }
        }

        if ($errors !== []) {
            $editing = [
                'id' => $id,
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'sort_order' => $sortOrder ?? ($editing['sort_order'] ?? 0),
                'is_active' => $isActive ? 1 : 0,
                'is_featured' => $isFeatured ? 1 : 0,
                'image_path' => $imagePath ?? ($editing['image_path'] ?? $prevImage),
                'home_filter_key' => $_POST['home_filter_key'] ?? '',
                'home_filter_value' => $_POST['home_filter_value'] ?? '',
            ];
            $openModal = true;
        }
    }
}

$rows = regions_all(false);
$adminPageTitle = 'Homepage locations';
$adminActiveNav = 'regions';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-page-head">
  <div>
    <span class="admin-eyebrow">Homepage</span>
    <h1 class="admin-page-title">Homepage locations</h1>
    <p class="admin-page-lead">These rows power the <strong>Locations</strong> section on the homepage and search filters. Turn on <strong>Show on homepage</strong> for each suburb or market. Upload a card image optional — otherwise a default photo is used. Active locations also appear on the Markets page.</p>
  </div>
  <div class="admin-actions" style="gap:0.5rem;">
    <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('markets.php')) ?>" target="_blank" rel="noopener">View Markets page</a>
    <button type="button" class="admin-btn" data-modal-open="region-modal">Add location</button>
  </div>
</div>

<?php if ($ok): ?><div class="admin-alert admin-alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="admin-alert admin-alert--error"><?= e($flashErr) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="admin-alert admin-alert--error"><?= e($err) ?></div><?php endforeach; ?>

<div class="admin-panel">
  <h2>All locations</h2>
  <?php if ($rows === []): ?>
    <p class="admin-note">No locations yet. Use “Add location” or import <code>database/hougarden-deploy.sql</code>.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Sort</th><th>Homepage</th><th>Filter</th><th>Listings</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <?php
          $thumb = trim((string) ($row['image_path'] ?? ''));
          $thumbUrl = $thumb !== '' ? media_url($thumb) : '';
          $usage = taxonomy_region_listing_count($row);
          $filter = hougarden_region_search_filter($row);
          $filterLabel = $filter === [] ? '—' : http_build_query($filter);
          $isActiveRow = !empty($row['is_active']);
        ?>
        <tr>
          <td style="width:3.5rem;">
            <?php if ($thumbUrl !== ''): ?>
              <img src="<?= e($thumbUrl) ?>" alt="" style="width:2.75rem;height:2.75rem;object-fit:cover;border-radius:4px;background:#eee;">
            <?php else: ?>
              <span class="admin-note">—</span>
            <?php endif; ?>
          </td>
          <td><?= e((string) $row['name']) ?></td>
          <td><?= e((string) $row['sort_order']) ?></td>
          <td><?= !empty($row['is_featured']) ? 'Yes' : '—' ?></td>
          <td><code style="font-size:0.75rem;"><?= e($filterLabel) ?></code></td>
          <td><?= (int) $usage ?></td>
          <td><?= $isActiveRow ? 'Active' : 'Inactive' ?></td>
          <td>
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
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="admin-modal<?= $openModal ? ' is-open' : '' ?>" id="region-modal" role="dialog" aria-modal="true" aria-labelledby="region-modal-title">
  <div class="admin-modal__backdrop" data-modal-close></div>
  <div class="admin-modal__dialog">
    <div class="admin-modal__head">
      <h2 id="region-modal-title"><?= $editing ? 'Edit location' : 'Add location' ?></h2>
      <button type="button" class="admin-modal__close" data-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editing && !empty($editing['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $editing['id']) ?>">
      <?php endif; ?>
      <div class="admin-field">
        <label for="name">Name *</label>
        <input id="name" name="name" required value="<?= e((string) ($editing['name'] ?? '')) ?>">
      </div>
      <div class="admin-field">
        <label for="slug">Slug</label>
        <input id="slug" name="slug" value="<?= e((string) ($editing['slug'] ?? '')) ?>" placeholder="auto from name">
      </div>
      <div class="admin-field">
        <label for="sort_order">Display order</label>
        <input id="sort_order" name="sort_order" type="number" value="<?= e((string) ($editing['sort_order'] ?? '')) ?>" placeholder="auto on create">
        <p class="admin-note">Lower numbers appear first in the homepage Locations row.</p>
      </div>
      <div class="admin-field">
        <label><input type="checkbox" name="is_featured" value="1" <?= !empty($editing['is_featured']) ? 'checked' : '' ?>> Show on homepage (Locations section)</label>
      </div>
      <div class="admin-grid-2">
        <div class="admin-field">
          <label for="home_filter_key">Search filter type</label>
          <select id="home_filter_key" name="home_filter_key">
            <?php
              $fk = (string) ($editing['home_filter_key'] ?? 'location');
              if ($fk === '' && !empty($editing['name']) && strcasecmp((string) $editing['name'], 'Victoria') === 0) {
                  $fk = 'market';
              }
            ?>
            <option value="location" <?= $fk === 'location' ? 'selected' : '' ?>>Suburb / location</option>
            <option value="market" <?= $fk === 'market' ? 'selected' : '' ?>>Victoria market</option>
          </select>
        </div>
        <div class="admin-field">
          <label for="home_filter_value">Filter value</label>
          <input id="home_filter_value" name="home_filter_value" value="<?= e((string) ($editing['home_filter_value'] ?? $editing['name'] ?? '')) ?>" placeholder="e.g. Rose Bay or victoria">
          <p class="admin-note">Must match listing <strong>city</strong> (or <code>market=victoria</code> for Victoria).</p>
        </div>
      </div>
      <div class="admin-field">
        <label for="image">Collection image</label>
        <?php
          $editImg = trim((string) ($editing['image_path'] ?? ''));
          if ($editImg !== ''):
        ?>
          <p style="margin:0 0 0.5rem;"><img src="<?= e(media_url($editImg)) ?>" alt="" style="max-height:96px;border-radius:4px;"></p>
          <label><input type="checkbox" name="clear_image" value="1"> Remove current image</label>
        <?php endif; ?>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <p class="admin-note">JPEG, PNG, or WebP. Recommended wide landscape photo.</p>
      </div>
      <div class="admin-field">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || !empty($editing['is_active']) ? 'checked' : '' ?><?= empty($editing['id']) ? ' disabled' : '' ?>> Active (shown on Markets page and in listing filters)</label>
        <?php if (empty($editing['id'])): ?>
          <input type="hidden" name="is_active" value="1">
          <p class="admin-note">New regions are always Active and appear on the Markets page immediately. You can deactivate later only if no listings use the region.</p>
        <?php else: ?>
          <p class="admin-note">Uncheck Active only when this region has no listings. Deactivated regions can then be permanently deleted from the list.</p>
        <?php endif; ?>
      </div>
      <div class="admin-actions">
        <button class="admin-btn" type="submit"><?= $editing ? 'Update' : 'Create' ?></button>
        <?php if ($editing): ?>
          <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/regions.php')) ?>">Cancel</a>
        <?php else: ?>
          <button type="button" class="admin-btn admin-btn--ghost" data-modal-close>Close</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php';
