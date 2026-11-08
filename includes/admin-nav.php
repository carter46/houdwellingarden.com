<?php
/**
 * Admin sidebar — labels match references/admin_* IA.
 *
 * @var string $adminActiveNav
 */

declare(strict_types=1);

$adminActiveNav = $adminActiveNav ?? 'overview';
$appName = site_name();
$user = auth_user();

$items = [
    'overview' => ['label' => 'Dashboard', 'href' => 'admin/index.php', 'icon' => 'dashboard'],
    'properties' => ['label' => 'Properties', 'href' => 'admin/properties.php', 'icon' => 'home_work'],
    'types' => ['label' => 'Property types', 'href' => 'admin/property-types.php', 'icon' => 'category'],
    'regions' => ['label' => 'Regions', 'href' => 'admin/regions.php', 'icon' => 'map'],
    'amenities' => ['label' => 'Amenities', 'href' => 'admin/amenities.php', 'icon' => 'checklist'],
    'inquiries' => ['label' => 'Inquiries', 'href' => 'admin/inquiries.php', 'icon' => 'mail'],
    'settings' => ['label' => 'Settings', 'href' => 'admin/settings.php', 'icon' => 'settings'],
    'profile' => ['label' => 'Account', 'href' => 'admin/profile.php', 'icon' => 'person'],
];
?>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin">
    <div class="admin-sidebar__head">
        <div class="admin-topbar__mark" aria-hidden="true">h</div>
        <div class="admin-sidebar__head-text">
            <span class="admin-eyebrow">Admin</span>
            <span class="admin-sidebar__site"><?= e($appName) ?></span>
        </div>
    </div>
    <nav class="admin-sidebar__nav">
        <?php foreach ($items as $key => $item): ?>
            <a class="admin-sidebar__link<?= $adminActiveNav === $key ? ' is-active' : '' ?>"
               href="<?= e(base_url($item['href'])) ?>">
                <?= hg_icon((string) $item['icon']) ?>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
        <a class="admin-sidebar__link admin-sidebar__link--exit" href="<?= e(base_url('index.php')) ?>">
            <?= hg_icon('public') ?>
            <span>View site</span>
        </a>
    </nav>
    <div class="admin-sidebar__foot">
        <?php if ($user): ?>
            <span class="admin-sidebar__user"><?= e((string) ($user['name'] ?: $user['email'])) ?></span>
        <?php endif; ?>
        <form class="admin-logout-form" method="post" action="<?= e(base_url('admin/logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="admin-sidebar__signout" type="submit">Sign out</button>
        </form>
    </div>
</aside>
