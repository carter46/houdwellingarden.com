<?php
/**
 * Admin login.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (admin_setup_required()) {
    redirect('admin/setup.php');
}

if (auth_check()) {
    redirect('admin/index.php');
}

$error = flash_get('auth_error');
$ok = flash_get('auth_ok');
$email = '';

if (is_post()) {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $result = auth_attempt_login($email, $password);
        if ($result['ok']) {
            redirect('admin/index.php');
        }
        $error = $result['error'] ?? 'Sign-in failed.';
    }
}

$authPageTitle = 'Sign in';
require dirname(__DIR__) . '/includes/admin-auth-head.php';
?>
        <h1>Sign in</h1>
        <p class="lead">Manage listings, inquiries, and site settings.</p>

        <?php if ($ok): ?>
            <div class="admin-alert admin-alert--ok"><?= e($ok) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="admin-alert admin-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="admin-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="<?= e($email) ?>">
            </div>
            <div class="admin-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="admin-btn" type="submit">Sign in</button>
        </form>

        <?php if (admin_setup_enabled() || admin_password_reset_enabled()): ?>
            <div class="admin-auth__links" style="margin-top:1.25rem;display:flex;flex-direction:column;gap:0.5rem;font-size:0.875rem;">
                <?php if (admin_setup_enabled()): ?>
                    <a href="<?= e(base_url('admin/setup.php')) ?>">Create or update admin account</a>
                <?php endif; ?>
                <?php if (admin_password_reset_enabled()): ?>
                    <a href="<?= e(base_url('admin/reset-password.php')) ?>">Reset admin password</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p class="admin-auth__foot"><a href="<?= e(base_url('index.php')) ?>">Back to website</a></p>
<?php require dirname(__DIR__) . '/includes/admin-auth-foot.php'; ?>
