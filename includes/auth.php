<?php
/**
 * Authentication helpers — Phase 2 / hardened Phase 7.
 */

declare(strict_types=1);

function auth_is_https(): bool
{
    $forced = app_config('security.cookie_secure', null);
    if ($forced === true || $forced === false || $forced === 1 || $forced === 0 || $forced === '1' || $forced === '0') {
        return (bool) $forced;
    }

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!empty(app_config('security.trust_forwarded_proto', false))) {
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }
    }

    return false;
}

function auth_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $name = (string) app_config('security.session_name', 'sdc_re_session');
    session_name($name);
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => auth_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function auth_user(): ?array
{
    auth_session_start();
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

function auth_check(): bool
{
    $user = auth_user();
    if ($user === null || empty($user['id'])) {
        return false;
    }

    $last = (int) ($_SESSION['auth_last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > 28800) {
        auth_logout();
        return false;
    }

    // Re-validate active admin periodically (every 5 minutes).
    $checked = (int) ($_SESSION['auth_user_checked_at'] ?? 0);
    if ($checked === 0 || (time() - $checked) > 300) {
        try {
            $stmt = db()->prepare(
                "SELECT id, email, name, role, is_active
                 FROM users WHERE id = ? LIMIT 1"
            );
            $stmt->execute([(int) $user['id']]);
            $row = $stmt->fetch();
            if (
                !is_array($row)
                || (int) ($row['is_active'] ?? 0) !== 1
                || ($row['role'] ?? '') !== 'admin'
            ) {
                auth_logout();
                return false;
            }
            $_SESSION['auth_user'] = [
                'id' => (int) $row['id'],
                'email' => (string) $row['email'],
                'name' => (string) ($row['name'] ?? ''),
                'role' => (string) $row['role'],
            ];
            $_SESSION['auth_user_checked_at'] = time();
        } catch (Throwable $e) {
            app_log('auth', 'auth_check revalidate failed: ' . $e->getMessage());
            // Fail closed on DB errors for admin pages
            auth_logout();
            return false;
        }
    }

    $_SESSION['auth_last_activity'] = time();
    return true;
}

function auth_require(): void
{
    if (admin_setup_required()) {
        redirect('admin/setup.php');
    }

    if (!auth_check()) {
        flash_set('auth_error', 'Please sign in to continue.');
        redirect('admin/login.php');
    }
}

function admin_setup_enabled(): bool
{
    return (bool) app_config('admin.setup_enabled', false);
}

function admin_password_reset_enabled(): bool
{
    return (bool) app_config('admin.password_reset_enabled', false);
}

/** Setup form: first admin always, or when config allows create/update. */
function admin_setup_page_allowed(): bool
{
    return admin_setup_required() || admin_setup_enabled();
}

/**
 * Whether the one-time admin setup is still required.
 * True when no active admin with a password_hash exists.
 */
function admin_setup_required(): bool
{
    try {
        $stmt = db()->query(
            "SELECT COUNT(*) AS c
             FROM users
             WHERE role = 'admin'
               AND is_active = 1
               AND password_hash IS NOT NULL
               AND password_hash != ''"
        );
        $count = (int) ($stmt->fetchColumn() ?: 0);
        return $count === 0;
    } catch (Throwable $e) {
        return true;
    }
}

/**
 * @return array{ok: bool, error: ?string, user: ?array}
 */
function auth_attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Email and password are required.', 'user' => null];
    }

    $max = (int) app_config('security.login_max_attempts', 5);
    $window = (int) app_config('security.login_lockout_seconds', 900);
    $limit = rate_limit_hit('login', $max, $window);
    if (!$limit['allowed']) {
        return [
            'ok' => false,
            'error' => 'Too many sign-in attempts. Try again in ' . (int) $limit['retry_after'] . ' seconds.',
            'user' => null,
        ];
    }

    $stmt = db()->prepare(
        "SELECT id, email, name, role, password_hash, is_active
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    $hash = is_array($row) ? (string) ($row['password_hash'] ?? '') : '';
    $valid = $hash !== '' && password_verify($password, $hash);

    if (!is_array($row) || !$valid || (int) ($row['is_active'] ?? 0) !== 1 || ($row['role'] ?? '') !== 'admin') {
        return ['ok' => false, 'error' => 'Invalid email or password.', 'user' => null];
    }

    rate_limit_clear('login');

    auth_login_user([
        'id' => (int) $row['id'],
        'email' => (string) $row['email'],
        'name' => (string) ($row['name'] ?? ''),
        'role' => (string) $row['role'],
    ]);

    $upd = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $upd->execute([(int) $row['id']]);

    return ['ok' => true, 'error' => null, 'user' => auth_user()];
}

/**
 * @param array{id:int,email:string,name?:string,role:string} $user
 */
function auth_login_user(array $user): void
{
    auth_session_start();
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'name' => (string) ($user['name'] ?? ''),
        'role' => (string) $user['role'],
    ];
    $_SESSION['auth_last_activity'] = time();
    $_SESSION['auth_user_checked_at'] = time();
    csrf_rotate();
}

function auth_logout(): void
{
    auth_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
    }
    session_destroy();
}

/**
 * Create the first admin (one-time setup).
 *
 * @return array{ok: bool, error: ?string}
 */
/**
 * @return array{ok: bool, error: ?string, min_len?: int}
 */
function auth_validate_admin_password(string $password, string $passwordConfirm): array
{
    if ($password === '') {
        return ['ok' => false, 'error' => 'Password is required.', 'min_len' => 12];
    }
    $minLen = 12;
    if (strlen($password) < $minLen) {
        return ['ok' => false, 'error' => "Password must be at least {$minLen} characters.", 'min_len' => $minLen];
    }
    if (!hash_equals($password, $passwordConfirm)) {
        return ['ok' => false, 'error' => 'Password confirmation does not match.', 'min_len' => $minLen];
    }

    return ['ok' => true, 'error' => null, 'min_len' => $minLen];
}

function auth_hash_admin_password(string $password): ?string
{
    $algo = app_config('security.password_algo', PASSWORD_DEFAULT);
    $hash = password_hash($password, is_int($algo) || is_string($algo) ? $algo : PASSWORD_DEFAULT);

    return $hash !== false ? $hash : null;
}

/**
 * Create the first admin (no active admin with password yet).
 *
 * @return array{ok: bool, error: ?string, action?: string}
 */
function auth_create_first_admin(string $name, string $email, string $password, string $passwordConfirm): array
{
    if (!admin_setup_required()) {
        return ['ok' => false, 'error' => 'Admin setup is already complete. Sign in, or enable admin.setup_enabled in config to create or update an account.'];
    }

    $result = auth_persist_admin_account($name, $email, $password, $passwordConfirm, true);
    if ($result['ok']) {
        $result['action'] = $result['action'] ?? 'created';
    }

    return $result;
}

/**
 * Create or update an admin when admin.setup_enabled is true (config.local.php).
 *
 * @return array{ok: bool, error: ?string, action?: string|null}
 */
function auth_upsert_admin_account(string $name, string $email, string $password, string $passwordConfirm): array
{
    if (!admin_setup_enabled()) {
        return [
            'ok' => false,
            'error' => 'Create/update admin is disabled. Set admin.setup_enabled to true in config.local.php (or config.php).',
        ];
    }

    return auth_persist_admin_account($name, $email, $password, $passwordConfirm, admin_setup_required());
}

/**
 * Reset password for an existing admin when admin.password_reset_enabled is true.
 *
 * @return array{ok: bool, error: ?string}
 */
function auth_reset_admin_password(string $email, string $password, string $passwordConfirm): array
{
    if (!admin_password_reset_enabled()) {
        return [
            'ok' => false,
            'error' => 'Password reset is disabled. Set admin.password_reset_enabled to true in config.local.php.',
        ];
    }

    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Enter a valid admin email address.'];
    }

    $pw = auth_validate_admin_password($password, $passwordConfirm);
    if (!$pw['ok']) {
        return ['ok' => false, 'error' => $pw['error']];
    }

    $hash = auth_hash_admin_password($password);
    if ($hash === null) {
        return ['ok' => false, 'error' => 'Unable to hash password.'];
    }

    try {
        $stmt = db()->prepare(
            "SELECT id FROM users WHERE email = ? AND role = 'admin' AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return [
                'ok' => false,
                'error' => 'No active admin account with that email. Use setup (if setup_enabled) to create one.',
            ];
        }

        db()->prepare(
            'UPDATE users SET password_hash = ?, setup_completed_at = COALESCE(setup_completed_at, NOW()), updated_at = NOW() WHERE id = ?'
        )->execute([$hash, (int) $row['id']]);

        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        app_log('auth', 'admin password reset failed: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Could not update password. Check database connection.'];
    }
}

/**
 * @return array{ok: bool, error: ?string, action?: string}
 */
function auth_persist_admin_account(
    string $name,
    string $email,
    string $password,
    string $passwordConfirm,
    bool $signInAfter
): array {
    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === '' || $email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Name, email, and password are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Enter a valid email address.'];
    }

    $pw = auth_validate_admin_password($password, $passwordConfirm);
    if (!$pw['ok']) {
        return ['ok' => false, 'error' => $pw['error']];
    }

    $hash = auth_hash_admin_password($password);
    if ($hash === null) {
        return ['ok' => false, 'error' => 'Unable to hash password.'];
    }

    try {
        $check = db()->prepare('SELECT id, role FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        $existing = $check->fetch();
        $action = 'created';

        if (is_array($existing)) {
            $action = 'updated';
            db()->prepare(
                "UPDATE users
                 SET name = ?, password_hash = ?, role = 'admin', is_active = 1,
                     setup_completed_at = COALESCE(setup_completed_at, NOW()), updated_at = NOW()
                 WHERE id = ?"
            )->execute([$name, $hash, (int) $existing['id']]);
            $userId = (int) $existing['id'];
        } else {
            db()->prepare(
                "INSERT INTO users (email, password_hash, name, role, is_active, setup_completed_at)
                 VALUES (?, ?, ?, 'admin', 1, NOW())"
            )->execute([$email, $hash, $name]);
            $userId = (int) db()->lastInsertId();
        }

        if ($signInAfter) {
            auth_login_user([
                'id' => $userId,
                'email' => $email,
                'name' => $name,
                'role' => 'admin',
            ]);
        }

        return ['ok' => true, 'error' => null, 'action' => $action];
    } catch (Throwable $e) {
        app_log('auth', 'admin persist failed: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Could not save admin account. Check database connection.'];
    }
}
