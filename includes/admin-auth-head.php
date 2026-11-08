<?php
/**
 * Shared head + open body for admin login / setup / reset.
 *
 * @var string $authPageTitle
 */

declare(strict_types=1);

seo_admin_block_indexing();

$authPageTitle = $authPageTitle ?? 'Admin';
$appName = site_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= e($authPageTitle) ?> — <?= e($appName) ?></title>
    <?php $favicon = site_favicon_url(); if ($favicon !== ''): ?>
    <link rel="icon" href="<?= e($favicon) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,300,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin-hougarden.css')) ?>">
</head>
<body class="admin-body hougarden-admin">
<div class="admin-auth">
    <div class="admin-auth__card">
        <div class="admin-auth__logo">
            <span class="admin-auth__logo-mark" aria-hidden="true">h</span>
            <span class="admin-auth__logo-text"><?= e($appName) ?></span>
        </div>
