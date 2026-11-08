<?php
/**
 * Admin layout header — IA matches references/admin_*; token chrome via admin.css.
 *
 * @var string $adminPageTitle
 * @var string $adminActiveNav  overview|properties|inquiries
 */

declare(strict_types=1);

seo_admin_block_indexing();

$adminPageTitle = $adminPageTitle ?? 'Admin';
$adminActiveNav = $adminActiveNav ?? 'overview';
$appName = site_name();
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= e($adminPageTitle) ?> — <?= e($appName) ?></title>
    <?php $favicon = site_favicon_url(); if ($favicon !== ''): ?>
    <link rel="icon" href="<?= e($favicon) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,300,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin-hougarden.css')) ?>">
</head>
<body class="admin-body hougarden-admin">
<header class="admin-topbar">
    <button type="button" class="admin-nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
        <?= hg_icon('menu') ?>
    </button>
    <p class="admin-topbar__title"><?= e($adminPageTitle) ?></p>
</header>
<div class="admin-shell">
<button type="button" class="admin-nav-backdrop" aria-label="Close menu" hidden></button>
<?php require __DIR__ . '/admin-nav.php'; ?>
<main class="admin-main">
