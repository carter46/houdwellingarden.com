<?php
/**
 * Dashboard Overview — protected admin shell with live counts.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$pdo = db();

$totalListings = (int) $pdo->query('SELECT COUNT(*) FROM properties WHERE status != \'archived\'')->fetchColumn();
$available = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'available'")->fetchColumn();
$sold = (int) $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'sold'")->fetchColumn();
$totalInquiries = (int) $pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
$newInquiries = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")->fetchColumn();
$activeAgents = (int) $pdo->query('SELECT COUNT(*) FROM agents WHERE is_active = 1')->fetchColumn();

$flashOk = flash_get('auth_ok');

$adminPageTitle = 'Dashboard';
$adminActiveNav = 'overview';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<span class="admin-eyebrow">Overview</span>
<h1 class="admin-page-title">Dashboard</h1>

<?php if ($flashOk): ?>
    <div class="admin-alert admin-alert--ok"><?= e($flashOk) ?></div>
<?php endif; ?>

<div class="admin-actions">
    <a class="admin-btn" href="<?= e(base_url('admin/property-form.php')) ?>">Add Property</a>
    <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/inquiries.php')) ?>">View Inquiries</a>
</div>

<div class="admin-kpi-grid">
    <div class="admin-kpi">
        <div class="admin-kpi__label">Total Listings</div>
        <p class="admin-kpi__value"><?= e((string) $totalListings) ?></p>
        <div class="admin-kpi__meta">Active listings</div>
    </div>
    <div class="admin-kpi">
        <div class="admin-kpi__label">Total Inquiries</div>
        <p class="admin-kpi__value"><?= e((string) $totalInquiries) ?></p>
        <div class="admin-kpi__meta"><?= e((string) $newInquiries) ?> new</div>
    </div>
    <div class="admin-kpi">
        <div class="admin-kpi__label">Active Agents</div>
        <p class="admin-kpi__value"><?= e((string) $activeAgents) ?></p>
        <div class="admin-kpi__meta">On site</div>
    </div>
    <div class="admin-kpi">
        <div class="admin-kpi__label">Listing status</div>
        <p class="admin-kpi__value"><?= e((string) $sold) ?> / <?= e((string) $available) ?></p>
        <div class="admin-kpi__meta">Sold / Available</div>
    </div>
</div>

<?php
require dirname(__DIR__) . '/includes/admin-footer.php';
