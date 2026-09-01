<?php
/**
 * Lead Ledger — one-time installer.
 *
 * Open it in a browser once, after filling in config.php. It creates the `ll_`
 * tables and, if you want, writes the sample catalogue so there is something to
 * look at. Delete this file once the archive is up.
 */

require __DIR__ . '/src/bootstrap.php';

$prefix = (string)(config('db_prefix') ?? 'll_');
$steps  = [];
$fatal  = null;

/** schema.sql is written with the `ll_` prefix; rewrite it if config says otherwise. */
function schema_statements(string $prefix): array
{
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('schema.sql is missing.');
    }
    if ($prefix !== 'll_') {
        $sql = str_replace('ll_', $prefix, $sql);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';

    return array_values(array_filter(array_map('trim', explode(';', $sql)), static fn($s) => $s !== ''));
}

function table_exists(string $table): bool
{
    try {
        db()->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
        return true;
    } catch (PDOException $ex) {
        return false;
    }
}

function row_count(string $table): int
{
    $row = db()->query('SELECT COUNT(*) AS n FROM `' . $table . '`')->fetch();
    return (int)$row['n'];
}

/** Write the prototype's sample catalogue. Only ever runs on an empty archive. */
function seed(string $prefix): int
{
    $data = require __DIR__ . '/src/seed.php';
    $pdo  = db();
    $pdo->beginTransaction();

    try {
        $rangeIds = [];
        foreach ($data['ranges'] as $code => $name) {
            $rangeIds[$code] = repo_create_range($name);
        }

        $setIds = [];
        foreach ($data['sets'] as [$rangeCode, $setCode, $setName]) {
            $setIds[$setCode] = repo_create_set($rangeIds[$rangeCode], $setCode, $setName);
        }

        $photos = $data['photos'];
        $n      = 0;
        $made   = 0;

        foreach ($data['figures'] as $setCode => $names) {
            foreach ($names as $i => $name) {
                $code = $setCode . '-' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
                repo_create_miniature($setIds[$setCode], $code, $name, $photos[$n++ % count($photos)]);
                $made++;
            }
        }

        $pdo->commit();
        return $made;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/* — run — */

$usersTable = $prefix . 'users';
$installed  = false;
$locked     = false;

try {
    db(); // the connection itself, before any table question
    $installed = table_exists($usersTable);
    $locked    = $installed && row_count($usersTable) > 0;
} catch (Throwable $ex) {
    $fatal = $ex->getMessage();
}

$action = $_POST['action'] ?? null;

if ($action !== null && $fatal === null) {
    if ($locked) {
        $steps[] = ['bad', 'There are already accounts in this database. The installer will not touch it — delete install.php.'];
    } else {
        try {
            if ($action === 'create' || $action === 'create_seed') {
                foreach (schema_statements($prefix) as $stmt) {
                    db()->exec($stmt);
                }
                $steps[]   = ['good', 'Tables created (or already present), all prefixed ' . $prefix];
                $installed = true;
            }
            if ($action === 'create_seed') {
                if (row_count($prefix . 'ranges') > 0) {
                    $steps[] = ['bad', 'The catalogue already has ranges — the sample data was not written.'];
                } else {
                    $made    = seed($prefix);
                    $steps[] = ['good', $made . ' sample miniatures written across 12 sets and 5 ranges.'];
                }
            }
        } catch (Throwable $ex) {
            $steps[] = ['bad', $ex->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install — Lead Ledger</title>
<link rel="stylesheet" href="assets/ds/styles.css">
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<main class="page" style="max-width:720px">
  <div class="kicker">Setup</div>
  <h1 style="font-size:44px;line-height:1">Install Lead Ledger</h1>
  <hr class="hr">

  <?php if ($fatal !== null): ?>
    <p class="form-error">Could not reach the database.</p>
    <p class="page-blurb" style="font-family:monospace;font-size:12.5px"><?= e($fatal) ?></p>

    <p class="page-blurb">These are the values <code>config.php</code> is handing to MySQL — the password
       is not shown, but check the other three against the one.com control panel
       (<em>Web hosting &rarr; MySQL/Database</em>). On one.com the database name and the user name are
       usually the same string.</p>
    <div class="tbl-scroll">
      <table class="table">
        <tbody>
          <tr><td class="col-code">Host</td><td><?= e((string)config('db_host')) ?></td></tr>
          <tr><td class="col-code">Database</td><td><?= e((string)config('db_name')) ?></td></tr>
          <tr><td class="col-code">User</td><td><?= e((string)config('db_user')) ?></td></tr>
          <tr><td class="col-code">Password</td><td><?= config('db_pass') === '' ? 'empty' : e(strlen((string)config('db_pass')) . ' characters') ?></td></tr>
        </tbody>
      </table>
    </div>

    <p class="page-blurb">If you are running this from your own machine rather than on one.com, the
       database also has to be switched to external access in the control panel first.</p>

  <?php else: ?>
    <p class="page-blurb">
      Connected to <strong><?= e((string)config('db_name')) ?></strong> on
      <strong><?= e((string)config('db_host')) ?></strong>. Every table this creates is prefixed
      <strong><?= e($prefix) ?></strong>, so it sits alongside anything else already in there.
    </p>

    <?php foreach ($steps as [$tone, $text]): ?>
      <p style="color:<?= $tone === 'good' ? 'var(--color-text)' : 'var(--color-accent-700)' ?>">
        <?= $tone === 'good' ? '&#10003;' : '&times;' ?> <?= e($text) ?>
      </p>
    <?php endforeach; ?>

    <?php if ($locked): ?>
      <hr class="hr">
      <p class="page-blurb">This archive is already live — there are accounts in it. Nothing here will run again.</p>
      <p><strong>Delete install.php from the server.</strong></p>
      <p><a class="btn btn-primary" href="<?= e(url('')) ?>">Open the archive</a></p>

    <?php else: ?>
      <hr class="hr">
      <form method="post" style="display:flex;gap:9px;flex-wrap:wrap">
        <button class="btn btn-primary" name="action" value="create_seed" type="submit">
          Create tables and write the sample catalogue
        </button>
        <button class="btn btn-secondary" name="action" value="create" type="submit">
          Create empty tables only
        </button>
      </form>

      <?php if ($installed && $steps): ?>
        <hr class="hr">
        <p class="page-blurb">
          Now open the archive and create the first account — the first account made owns the
          catalogue and gets the admin screens. Then delete <code>install.php</code>.
        </p>
        <p><a class="btn btn-primary" href="<?= e(url('sign-in?mode=up')) ?>">Create the first account</a></p>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
</main>
</body>
</html>
