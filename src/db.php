<?php
/** Configuration loading, the PDO handle, and the `ll_` table-name helper. */

function config(?string $key = null)
{
    static $config = null;
    if ($config === null) {
        $file = dirname(__DIR__) . '/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            exit(
                "Lead Ledger is not configured yet.\n\n" .
                "Copy config.example.php to config.php and fill in the four\n" .
                "connection values from the one.com control panel, then reload.\n"
            );
        }
        $config = require $file;
        if (!is_array($config)) {
            throw new RuntimeException('config.php must return an array.');
        }
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? null;
}

/** Prefix a bare table name: tbl('sets') -> `ll_sets`. */
function tbl(string $name): string
{
    $prefix = (string)(config('db_prefix') ?? 'll_');
    // Only ever built from literals in this codebase, but keep it safe to
    // interpolate into SQL regardless.
    $safe = preg_replace('/[^A-Za-z0-9_]/', '', $prefix . $name) ?? '';
    return '`' . $safe . '`';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        (string)config('db_host'),
        (string)config('db_name')
    );

    // Throws PDOException on failure — callers decide what the visitor sees.
    // index.php turns it into a page; install.php prints the real reason.
    $pdo = new PDO($dsn, (string)config('db_user'), (string)config('db_pass'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

/**
 * Last-resort handler for a database that will not answer. Shows the real
 * reason when config.php has debug on, and points at the installer either way.
 */
function db_unavailable(Throwable $ex): void
{
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    if (config('debug')) {
        echo "Database error\n\n" . $ex->getMessage() . "\n\n";
        echo 'host: ' . (string)config('db_host') . "\n";
        echo 'name: ' . (string)config('db_name') . "\n";
        echo 'user: ' . (string)config('db_user') . "\n";
        exit;
    }

    exit(
        "The archive is unavailable right now.\n\n" .
        "If you are setting this up: open install.php, which reports the real\n" .
        "reason, or set 'debug' => true in config.php to see it here.\n"
    );
}

/** Run a statement and hand back the PDOStatement, ready to fetch from. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
