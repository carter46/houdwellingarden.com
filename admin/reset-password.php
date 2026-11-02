<?php
/**
 * Reset an existing admin password when admin.password_reset_enabled is true.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!admin_password_reset_enabled()) {
    flash_set('auth_error', 'Password reset is disabled. Set admin.password_reset_enabled to true in config.local.php.');
    redirect('admin/login.php');
}

if (auth_check()) {
    redirect('admin/index.php');
}

$error = null;
$success = false;
$email = '';

if (is_post()) {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $result = auth_reset_admin_password($email, $password, $confirm);
        if ($result['ok']) {
            $success = true;
        } else {
            $error = $result['error'] ?? 'Reset failed.';
        }
    }
}

$minLen = 12;
$authPageTitle = 'Reset password';
require dirname(__DIR__) . '/includes/admin-auth-head.php';
?>
        <h1>Reset admin password</h1>
        <p class="lead">Updates the password for an existing admin email. Requires <code>admin.password_reset_enabled</code> in config.</p>

        <?php if ($success): ?>
            <div class="admin-alert admin-alert--ok">
                Password updated. Turn off <code>admin.password_reset_enabled</code> in config.local.php, then sign in.
            </div>
            <p style="margin-top:1rem;"><a class="admin-btn" href="<?= e(base_url('admin/login.php')) ?>">Go to admin login</a></p>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="admin-alert admin-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="admin-field">
                <label for="email">Admin email</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="<?= e($email) ?>">
            </div>
            <div class="admin-field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" minlength="<?= (int) $minLen ?>">
            </div>
            <div class="admin-field">
                <label for="password_confirm">Confirm password</label>
                <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password" minlength="<?= (int) $minLen ?>">
            </div>
            <button class="admin-btn" type="submit">Save new password</button>
        </form>
        <?php endif; ?>

        <p class="admin-auth__foot">
            <a href="<?= e(base_url('admin/login.php')) ?>">Back to login</a>
        </p>
<?php require dirname(__DIR__) . '/includes/admin-auth-foot.php'; ?>
