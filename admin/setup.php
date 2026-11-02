<?php
/**
 * Admin setup — first account (always) or create/update when admin.setup_enabled is true.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!admin_setup_page_allowed()) {
    if (auth_check()) {
        redirect('admin/index.php');
    }
    flash_set('auth_error', 'Admin setup is disabled. Set admin.setup_enabled to true in config.local.php if you need create/update access.');
    redirect('admin/login.php');
}

$isInitial = admin_setup_required();
$isConfigSetup = admin_setup_enabled();
$title = $isInitial ? 'Create admin account' : 'Create or update admin';
$lead = $isInitial
    ? 'No admin account exists yet. Choose a strong password (12+ characters).'
    : 'Creates a new admin or updates the password for an existing email. Turn off admin.setup_enabled in config after you finish.';

$error = null;
$successAction = null;
$name = '';
$email = '';

if (auth_check() && !$isInitial && !$isConfigSetup) {
    redirect('admin/index.php');
}

if (is_post()) {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = (string) ($_POST['name'] ?? '');
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($isInitial) {
            $result = auth_create_first_admin($name, $email, $password, $confirm);
        } else {
            $result = auth_upsert_admin_account($name, $email, $password, $confirm);
        }

        if ($result['ok']) {
            $successAction = $result['action'] ?? 'created';
            if ($isInitial || ($successAction === 'created' && admin_setup_required())) {
                flash_set('auth_ok', 'Admin account created. Welcome.');
                redirect('admin/index.php');
            }
        } else {
            $error = $result['error'] ?? 'Setup failed.';
        }
    }
}

$minLen = 12;
$authPageTitle = $title;
require dirname(__DIR__) . '/includes/admin-auth-head.php';
?>
        <h1><?= e($title) ?></h1>
        <p class="lead"><?= e($lead) ?></p>

        <?php if ($successAction !== null): ?>
            <div class="admin-alert admin-alert--ok">
                Admin account <?= $successAction === 'updated' ? 'updated' : 'created' ?>.
                Turn off <code>admin.setup_enabled</code> in config.local.php, then sign in.
            </div>
            <p style="margin-top:1rem;"><a class="admin-btn" href="<?= e(base_url('admin/login.php')) ?>">Go to admin login</a></p>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="admin-alert admin-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($isConfigSetup && !$isInitial): ?>
            <p class="admin-note" style="margin:0 0 1rem;">Config: <code>admin.setup_enabled</code> is on.</p>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="admin-field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" required autocomplete="name" value="<?= e($name) ?>">
            </div>
            <div class="admin-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="<?= e($email) ?>">
            </div>
            <div class="admin-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" minlength="<?= (int) $minLen ?>">
            </div>
            <div class="admin-field">
                <label for="password_confirm">Confirm password</label>
                <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password" minlength="<?= (int) $minLen ?>">
            </div>
            <button class="admin-btn" type="submit"><?= $isInitial ? 'Create admin &amp; continue' : 'Save admin account' ?></button>
        </form>
        <?php endif; ?>

        <p class="admin-auth__foot">
            <a href="<?= e(base_url('admin/login.php')) ?>">Back to login</a>
            · <a href="<?= e(base_url('index.php')) ?>">Back to website</a>
        </p>
<?php require dirname(__DIR__) . '/includes/admin-auth-foot.php'; ?>
