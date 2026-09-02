<?php
/** Sessions, sign-in / sign-up, admin gating and CSRF tokens. */

const AUTH_PERSIST_COOKIE = 'll_persist';
const AUTH_PERSIST_DAYS   = 30;

function auth_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // "Keep me signed in" is remembered in its own cookie, read here because
    // the session cookie's lifetime has to be decided before the session starts.
    $persist  = ($_COOKIE[AUTH_PERSIST_COOKIE] ?? '') === '1';
    $lifetime = $persist ? AUTH_PERSIST_DAYS * 86400 : 0;

    if ($persist) {
        @ini_set('session.gc_maxlifetime', (string)$lifetime);
    }

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => base_url() === '' ? '/' : base_url() . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('ll_session');
    session_start();
}

function auth_set_persist(bool $on): void
{
    $params = session_get_cookie_params();
    setcookie(AUTH_PERSIST_COOKIE, $on ? '1' : '', [
        'expires'  => $on ? time() + AUTH_PERSIST_DAYS * 86400 : time() - 3600,
        'path'     => $params['path'],
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $params['secure'],
    ]);
}

/**
 * True until the first account exists. Sign-up is open only while this holds —
 * the archive keeps one collection that only an admin edits, so there is
 * nothing for a second account to do.
 */
function auth_accepts_signup(): bool
{
    static $open = null;
    if ($open === null) {
        $open = (int)q('SELECT COUNT(*) AS n FROM ' . tbl('users'))->fetch()['n'] === 0;
    }
    return $open;
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    $id = $_SESSION['uid'] ?? null;
    if (!$id) {
        return $user = null;
    }

    $row = q('SELECT id, name, email, is_admin FROM ' . tbl('users') . ' WHERE id = ?', [$id])->fetch();
    if (!$row) {
        // The account was deleted underneath the session.
        unset($_SESSION['uid']);
        return $user = null;
    }

    $row['id']       = (int)$row['id'];
    $row['is_admin'] = (int)$row['is_admin'] === 1;
    return $user = $row;
}

function user_id(): ?int
{
    $u = current_user();
    return $u ? $u['id'] : null;
}

function is_signed_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['is_admin'];
}

/** Admin screens and admin API routes both funnel through here. */
function require_admin(): void
{
    if (!is_signed_in()) {
        if (is_json_request()) {
            json_fail('Sign in first.', 401);
        }
        redirect('sign-in');
    }
    if (!is_admin()) {
        if (is_json_request()) {
            json_fail('This account cannot edit the catalogue.', 403);
        }
        http_response_code(403);
        exit('This account cannot edit the catalogue.');
    }
}

/**
 * Register. The first account created owns the catalogue — on a fresh install
 * that is whoever sets the site up.
 * @return array{0:bool,1:string} ok, error message
 */
function auth_register(string $name, string $email, string $password, bool $remember): array
{
    $name  = trim($name);
    $email = strtolower(trim($email));

    if ($name === '') {
        return [false, 'Tell us what to call you.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'That does not look like an email address.'];
    }
    if (strlen($password) < 8) {
        return [false, 'Eight characters or more.'];
    }

    $taken = q('SELECT id FROM ' . tbl('users') . ' WHERE email = ?', [$email])->fetch();
    if ($taken) {
        return [false, 'There is already an account on that address.'];
    }

    $first = (int)q('SELECT COUNT(*) AS n FROM ' . tbl('users'))->fetch()['n'] === 0;

    q(
        'INSERT INTO ' . tbl('users') . ' (name, email, password_hash, is_admin, created_at)
         VALUES (?, ?, ?, ?, NOW())',
        [$name, $email, password_hash($password, PASSWORD_DEFAULT), $first ? 1 : 0]
    );

    auth_establish((int)db()->lastInsertId(), $remember);
    return [true, ''];
}

/** @return array{0:bool,1:string} */
function auth_login(string $email, string $password, bool $remember): array
{
    $email = strtolower(trim($email));
    $row   = q('SELECT id, password_hash FROM ' . tbl('users') . ' WHERE email = ?', [$email])->fetch();

    // Same message either way — it does not leak which addresses exist.
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return [false, 'That email and password do not match.'];
    }

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        q(
            'UPDATE ' . tbl('users') . ' SET password_hash = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $row['id']]
        );
    }

    auth_establish((int)$row['id'], $remember);
    return [true, ''];
}

function auth_establish(int $userId, bool $remember): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $userId;
    auth_set_persist($remember);
}

function auth_logout(): void
{
    $_SESSION = [];
    auth_set_persist(false);
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $p['secure'],
        ]);
    }
    session_destroy();
}

/* — CSRF — every state-changing request carries this token. — */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/** Reject the request unless it carries a good token. */
function csrf_guard(): void
{
    $token = $_POST['csrf']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if ($token === null) {
        $token = json_body()['csrf'] ?? null;
    }

    if (!csrf_valid($token)) {
        if (is_json_request()) {
            json_fail('Your session expired. Reload the page and try again.', 419);
        }
        http_response_code(419);
        exit('Your session expired. Reload the page and try again.');
    }
}
