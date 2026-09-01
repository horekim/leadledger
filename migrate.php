<?php
/**
 * Lead Ledger — schema updates for an archive that is already live.
 *
 * install.php only ever creates tables; this brings an existing database up to
 * the current schema.sql. It is idempotent — running it twice does nothing the
 * second time — and it requires you to be signed in as the catalogue owner.
 *
 * Delete it once it reports nothing left to do.
 */

require __DIR__ . '/src/bootstrap.php';

auth_boot();
require_admin();

$prefix = (string)(config('db_prefix') ?? 'll_');
$minis  = $prefix . 'miniatures';
$steps  = [];
$fatal  = null;
$ran    = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($ran) {
    csrf_guard();
}

/** How a column is declared right now. */
function column(string $table, string $name): ?array
{
    $row = q(
        'SELECT IS_NULLABLE, COLUMN_TYPE
           FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $name]
    )->fetch();
    return $row ?: null;
}

function nullable(string $table, string $name): bool
{
    $col = column($table, $name);
    return $col !== null && $col['IS_NULLABLE'] === 'YES';
}

try {
    if (column($minis, 'photo') === null) {
        throw new RuntimeException($minis . ' does not exist. Run install.php first.');
    }

    /* — 1. code and name become optional — */

    foreach ([['code', 'VARCHAR(60)'], ['name', 'VARCHAR(190)']] as [$col, $type]) {
        if (nullable($minis, $col)) {
            $steps[] = ['skip', $col . ' is already optional.'];
            continue;
        }
        if ($ran) {
            db()->exec('ALTER TABLE `' . $minis . '` MODIFY `' . $col . '` ' . $type . ' NULL DEFAULT NULL');
            $steps[] = ['good', $col . ' is now optional.'];
        } else {
            $steps[] = ['todo', $col . ' will be made optional.'];
        }
    }

    /* — 2. blanks already in the table become real absences — */

    $blank = (int)q('SELECT COUNT(*) AS n FROM `' . $minis . "` WHERE code = '' OR name = ''")->fetch()['n'];
    if ($blank === 0) {
        $steps[] = ['skip', 'No blank codes or names to tidy.'];
    } elseif ($ran) {
        db()->exec('UPDATE `' . $minis . "` SET code = NULLIF(code, ''), name = NULLIF(name, '')");
        $steps[] = ['good', $blank . ' ' . plural($blank, 'row', 'rows') . ' with a blank code or name set to NULL.'];
    } else {
        $steps[] = ['todo', $blank . ' ' . plural($blank, 'row', 'rows') . ' with a blank code or name will be set to NULL.'];
    }

    /* — 3. the photograph becomes required — */

    $photoless = (int)q('SELECT COUNT(*) AS n FROM `' . $minis . "` WHERE photo IS NULL OR photo = ''")->fetch()['n'];

    if (!nullable($minis, 'photo')) {
        $steps[] = ['skip', 'A photograph is already required.'];
    } elseif ($photoless > 0) {
        $steps[] = ['bad',
            $photoless . ' ' . plural($photoless, 'miniature has', 'miniatures have') . ' no photograph, so the ' .
            'column cannot be made required yet. Give them one in the admin screens (or delete them), then run this again. ' .
            'Nothing was changed for them — the app already refuses to save a miniature without a photograph.'];
    } elseif ($ran) {
        db()->exec('ALTER TABLE `' . $minis . '` MODIFY `photo` VARCHAR(255) NOT NULL');
        $steps[] = ['good', 'A photograph is now required at the database level too.'];
    } else {
        $steps[] = ['todo', 'The photograph column will be made required.'];
    }
} catch (Throwable $ex) {
    $fatal = $ex->getMessage();
}

$pending = array_filter($steps, static fn($s) => $s[0] === 'todo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Schema update — Lead Ledger</title>
<link rel="stylesheet" href="<?= e(asset('assets/ds/styles.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<main class="page" style="max-width:720px">
  <div class="kicker">Setup</div>
  <h1 style="font-size:44px;line-height:1">Schema update</h1>
  <hr class="hr">

  <?php if ($fatal !== null): ?>
    <p class="form-error"><?= e($fatal) ?></p>

  <?php else: ?>
    <p class="page-blurb">
      Brings <strong><?= e($minis) ?></strong> in line with the current schema: the code and the name
      become optional, and the photograph becomes the required field.
    </p>

    <?php foreach ($steps as [$tone, $text]): ?>
      <?php
        $mark  = ['good' => '&#10003;', 'skip' => '&middot;', 'todo' => '&rarr;', 'bad' => '&times;'][$tone];
        $color = $tone === 'bad' ? 'var(--color-accent-700)'
               : ($tone === 'skip' ? 'color-mix(in srgb, var(--color-text) 55%, transparent)' : 'var(--color-text)');
      ?>
      <p style="color:<?= $color ?>"><?= $mark ?> <?= e($text) ?></p>
    <?php endforeach; ?>

    <hr class="hr">

    <?php if ($pending): ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="btn btn-primary" type="submit">Apply <?= e(count($pending)) ?> <?= e(plural(count($pending), 'change', 'changes')) ?></button>
      </form>
    <?php else: ?>
      <p class="page-blurb">Nothing left to do. <strong>Delete migrate.php from the server.</strong></p>
      <p><a class="btn btn-primary" href="<?= e(url('admin')) ?>">Back to the admin screens</a></p>
    <?php endif; ?>
  <?php endif; ?>
</main>
</body>
</html>
