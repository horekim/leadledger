<?php
/**
 * Lead Ledger — schema updates for an archive that is already live.
 *
 * install.php only ever creates tables; this brings an existing database up to
 * the current schema.sql. It reports every change before making it, is
 * idempotent, and requires you to be signed in as the catalogue owner.
 *
 * Delete it once it reports nothing left to do.
 */

require __DIR__ . '/src/bootstrap.php';

auth_boot();
require_admin();

$prefix = (string)(config('db_prefix') ?? 'll_');
$minis  = $prefix . 'miniatures';
$sets   = $prefix . 'sets';
$steps  = [];
$fatal  = null;
$ran    = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($ran) {
    csrf_guard();
}

/* — inspection — */

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

function index_exists(string $table, string $index): bool
{
    $row = q(
        'SELECT COUNT(*) AS n
           FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$table, $index]
    )->fetch();
    return (int)$row['n'] > 0;
}

function step(string $tone, string $text): void
{
    global $steps;
    $steps[] = [$tone, $text];
}

/**
 * Give every set a URL slug derived from its code, unique within its range.
 * Runs in PHP rather than SQL because the numeric suffixing is per range.
 */
function backfill_set_slugs(string $sets): int
{
    $rows = q('SELECT id, range_id, code FROM `' . $sets . "` WHERE slug IS NULL OR slug = '' ORDER BY range_id, id")->fetchAll();
    if (!$rows) {
        return 0;
    }

    // What each range already holds, so a backfill cannot collide with it.
    $taken = [];
    foreach (q('SELECT range_id, slug FROM `' . $sets . "` WHERE slug IS NOT NULL AND slug <> ''")->fetchAll() as $r) {
        $taken[(int)$r['range_id']][strtolower($r['slug'])] = true;
    }

    $stmt = db()->prepare('UPDATE `' . $sets . '` SET slug = ? WHERE id = ?');
    foreach ($rows as $row) {
        $rangeId = (int)$row['range_id'];
        $base    = slugify((string)$row['code'], 'set');
        $slug    = $base;
        $n       = 2;
        while (isset($taken[$rangeId][strtolower($slug)])) {
            $slug = $base . '-' . $n++;
        }
        $taken[$rangeId][strtolower($slug)] = true;
        $stmt->execute([$slug, (int)$row['id']]);
    }
    return count($rows);
}

/* — plan, and apply — */

try {
    if (column($minis, 'photo') === null) {
        throw new RuntimeException($minis . ' does not exist. Run install.php first.');
    }

    /* ── miniatures: the photograph is the required field ── */

    foreach ([['code', 'VARCHAR(60)'], ['name', 'VARCHAR(190)']] as [$col, $type]) {
        if (nullable($minis, $col)) {
            step('skip', 'Miniature ' . $col . ' is already optional.');
        } elseif ($ran) {
            db()->exec('ALTER TABLE `' . $minis . '` MODIFY `' . $col . '` ' . $type . ' NULL DEFAULT NULL');
            step('good', 'Miniature ' . $col . ' is now optional.');
        } else {
            step('todo', 'Miniature ' . $col . ' will be made optional.');
        }
    }

    $blank = (int)q('SELECT COUNT(*) AS n FROM `' . $minis . "` WHERE code = '' OR name = ''")->fetch()['n'];
    if ($blank === 0) {
        step('skip', 'No blank miniature codes or names to tidy.');
    } elseif ($ran) {
        db()->exec('UPDATE `' . $minis . "` SET code = NULLIF(code, ''), name = NULLIF(name, '')");
        step('good', $blank . ' blank ' . plural($blank, 'value', 'values') . ' set to NULL.');
    } else {
        step('todo', $blank . ' blank miniature ' . plural($blank, 'value', 'values') . ' will be set to NULL.');
    }

    $photoless = (int)q('SELECT COUNT(*) AS n FROM `' . $minis . "` WHERE photo IS NULL OR photo = ''")->fetch()['n'];
    if (!nullable($minis, 'photo')) {
        step('skip', 'A photograph is already required.');
    } elseif ($photoless > 0) {
        step('bad', $photoless . ' ' . plural($photoless, 'miniature has', 'miniatures have') . ' no photograph, so that '
            . 'column cannot be made required yet. Give them one (or delete them) and run this again. The app already '
            . 'refuses to save a miniature without a photograph.');
    } elseif ($ran) {
        db()->exec('ALTER TABLE `' . $minis . '` MODIFY `photo` VARCHAR(255) NOT NULL');
        step('good', 'A photograph is now required at the database level too.');
    } else {
        step('todo', 'The photograph column will be made required.');
    }

    /* ── sets: two in a range may share a code, so the URL uses a slug ── */

    $hasSlug = column($sets, 'slug') !== null;

    if (!$hasSlug) {
        if ($ran) {
            db()->exec('ALTER TABLE `' . $sets . '` ADD COLUMN `slug` VARCHAR(60) NULL DEFAULT NULL AFTER `code`');
            step('good', 'Added a slug column to ' . $sets . '.');
            $hasSlug = true;
        } else {
            step('todo', 'A slug column will be added to ' . $sets . '.');
        }
    } else {
        step('skip', $sets . ' already has a slug column.');
    }

    if ($hasSlug) {
        $missing = (int)q('SELECT COUNT(*) AS n FROM `' . $sets . "` WHERE slug IS NULL OR slug = ''")->fetch()['n'];
        if ($missing === 0) {
            step('skip', 'Every set already has a slug.');
        } elseif ($ran) {
            step('good', backfill_set_slugs($sets) . ' ' . plural($missing, 'set', 'sets') . ' given a URL slug.');
            $missing = 0; // so the NOT NULL step below can run in this same pass
        } else {
            step('todo', $missing . ' ' . plural($missing, 'set', 'sets') . ' will be given a URL slug from their code.');
        }

        if (!nullable($sets, 'slug')) {
            step('skip', 'The set slug is already required.');
        } elseif ($ran && $missing === 0) {
            db()->exec('ALTER TABLE `' . $sets . '` MODIFY `slug` VARCHAR(60) NOT NULL');
            step('good', 'The set slug is now required.');
        } elseif (!$ran) {
            step('todo', 'The set slug will be made required.');
        }
    }

    if (index_exists($sets, 'uq_sets_range_slug')) {
        step('skip', 'The unique key on (range, slug) is already in place.');
    } elseif ($ran && $hasSlug && !nullable($sets, 'slug')) {
        db()->exec('ALTER TABLE `' . $sets . '` ADD UNIQUE KEY `uq_sets_range_slug` (`range_id`, `slug`)');
        step('good', 'Added the unique key on (range, slug).');
    } elseif (!$ran) {
        step('todo', 'A unique key on (range, slug) will be added.');
    }

    if (index_exists($sets, 'ix_sets_range_code')) {
        step('skip', 'The (range, code) lookup index is already in place.');
    } elseif ($ran) {
        db()->exec('ALTER TABLE `' . $sets . '` ADD KEY `ix_sets_range_code` (`range_id`, `code`)');
        step('good', 'Added the (range, code) lookup index.');
    } else {
        step('todo', 'A (range, code) lookup index will be added.');
    }

    if (index_exists($sets, 'uq_sets_range_code')) {
        if ($ran) {
            db()->exec('ALTER TABLE `' . $sets . '` DROP INDEX `uq_sets_range_code`');
            step('good', 'Dropped the unique key on (range, code) — codes may now repeat within a range.');
        } else {
            step('todo', 'The unique key on (range, code) will be dropped, so codes may repeat within a range.');
        }
    } else {
        step('skip', 'Codes may already repeat within a range.');
    }
} catch (Throwable $ex) {
    $fatal = $ex->getMessage();
}

$pending = array_filter($steps, static fn($s) => $s[0] === 'todo');
$blocked = array_filter($steps, static fn($s) => $s[0] === 'bad');
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
      Brings the tables in line with the current schema: a miniature's photograph becomes its one
      required field, and two sets in the same range become free to share a code.
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
        <button class="btn btn-primary" type="submit">
          Apply <?= e(count($pending)) ?> <?= e(plural(count($pending), 'change', 'changes')) ?>
        </button>
      </form>
      <?php if ($blocked): ?>
        <p class="page-blurb" style="margin-top:12px">Anything marked &times; is skipped until you deal with it.</p>
      <?php endif; ?>
    <?php elseif ($blocked): ?>
      <p class="page-blurb">Deal with the items marked &times;, then run this again.</p>
      <p><a class="btn btn-primary" href="<?= e(url('admin')) ?>">Back to the admin screens</a></p>
    <?php else: ?>
      <p class="page-blurb">Nothing left to do. <strong>Delete migrate.php from the server.</strong></p>
      <p><a class="btn btn-primary" href="<?= e(url('admin')) ?>">Back to the admin screens</a></p>
    <?php endif; ?>
  <?php endif; ?>
</main>
</body>
</html>
