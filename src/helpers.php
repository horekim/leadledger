<?php
/** Small shared helpers: escaping, URLs, slugs, sorting, JSON. */

/** Escape for HTML text and attribute contexts. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * The directory the front controller lives in, so the app works whether it is
 * dropped at the web root or inside a subfolder of the one.com webspace.
 */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
    }
    return $base;
}

/**
 * A URL for a static file — stylesheets, scripts, uploaded photographs.
 * Never goes through the front controller.
 */
function asset(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/**
 * A URL for a route.
 *
 * With pretty_urls on (the default) this needs mod_rewrite to fold everything
 * into index.php. Turn it off in config.php and routes address index.php
 * directly instead — /leadledger/index.php/sign-in — which needs no rewriting
 * at all, and so cannot be intercepted by a WordPress .htaccess further up.
 */
function url(string $path = ''): string
{
    $prefix = base_url();
    if (config('pretty_urls') === false) { // absent means on
        $prefix .= '/index.php';
    }
    $path = ltrim($path, '/');
    return $path === '' ? ($prefix === '' ? '/' : $prefix) : $prefix . '/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path), true, 302);
    exit;
}

/** URL-safe slug from a name or a code. Falls back so it is never empty. */
function slugify(string $s, string $fallback = 'item'): string
{
    $s = trim($s);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($t !== false) {
            $s = $t;
        }
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s === '' ? $fallback : substr($s, 0, 120);
}

/**
 * Sets sort by code, numeric-aware: C01 < C06 < C11, RT01 < RT101.
 * MySQL cannot do this in ORDER BY, so it happens here.
 *
 * Two sets in a range may share a code, so the comparison falls through to the
 * name and finally the id. Without that the order of a tied pair is whatever
 * the database happened to return, which differs between queries — the same
 * two sets would list one way on the index and the other way in the rail.
 */
function sort_sets_by_code(array &$sets): void
{
    usort($sets, static function (array $a, array $b): int {
        return strnatcasecmp($a['code'], $b['code'])
            ?: strnatcasecmp($a['name'], $b['name'])
            ?: ((int)$a['id'] <=> (int)$b['id']);
    });
}

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function json_fail(string $message, int $status = 400): void
{
    json_out(['ok' => false, 'error' => $message], $status);
}

/** Body of a JSON request, for the fetch() calls in app.js. */
function json_body(): array
{
    // Cached: csrf_guard() reads the body before the route handler does.
    static $body = null;
    if ($body !== null) {
        return $body;
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $body = [];
    }
    $data = json_decode($raw, true);
    return $body = (is_array($data) ? $data : []);
}

function is_json_request(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json') || $xrw === 'fetch';
}

/** 1,840 rather than 1840 — the design shows grouped thousands. */
function num(int $n): string
{
    return number_format($n, 0, '.', ',');
}

function pct(int $part, int $whole): float
{
    return $whole > 0 ? ($part / $whole) * 100 : 0.0;
}

function plural(int $n, string $one, string $many): string
{
    return $n === 1 ? $one : $many;
}
