<?php
/**
 * Every query the app makes.
 *
 * The index needs range -> sets -> counts -> owned counts in one round trip,
 * so repo_catalogue() is a single aggregate query rather than a loop.
 */

/** A range whose category is missing or unrecognised falls back to this. */
const LL_DEFAULT_CATEGORY = 'fantasy';

/**
 * The top level of the archive, in the order it is shown.
 *
 * Deliberately code rather than a table: there are two of them, they change
 * about never, and a table would need a screen of its own to manage. Adding a
 * third is one line here plus a migration default.
 */
function categories(): array
{
    return [
        'fantasy'    => 'Fantasy',
        'scifi'      => 'Sci-fi',
        'specialist' => 'Specialist Games',
    ];
}

function category_label(string $key): string
{
    return categories()[$key] ?? categories()[LL_DEFAULT_CATEGORY];
}

function category_valid(string $key): bool
{
    return isset(categories()[$key]);
}

/**
 * Group a catalogue into its categories, in category order, dropping any that
 * hold no ranges. Each group is ['key', 'label', 'ranges'].
 */
function repo_by_category(array $catalogue): array
{
    $groups = [];
    foreach (categories() as $key => $label) {
        $groups[$key] = ['key' => $key, 'label' => $label, 'ranges' => []];
    }

    foreach ($catalogue as $range) {
        $key = category_valid((string)$range['category']) ? $range['category'] : LL_DEFAULT_CATEGORY;
        $groups[$key]['ranges'][] = $range;
    }

    return array_values(array_filter($groups, static fn(array $g): bool => $g['ranges'] !== []));
}

/**
 * The whole catalogue: ranges alphabetical, sets natural-sorted by code,
 * each set carrying its miniature count and this user's owned count.
 */
function repo_catalogue(): array
{
    $sql = 'SELECT r.id           AS range_id,
                   r.name         AS range_name,
                   r.slug         AS range_slug,
                   r.category     AS range_category,
                   s.id           AS set_id,
                   s.code         AS set_code,
                   s.slug         AS set_slug,
                   s.name         AS set_name,
                   COUNT(DISTINCT m.id)           AS mini_count,
                   COUNT(DISTINCT o.miniature_id) AS owned_count
              FROM ' . tbl('ranges') . ' r
         LEFT JOIN ' . tbl('sets') . ' s        ON s.range_id = r.id
         LEFT JOIN ' . tbl('miniatures') . ' m  ON m.set_id = s.id
         LEFT JOIN ' . tbl('ownership') . ' o   ON o.miniature_id = m.id
          GROUP BY r.id, r.name, r.slug, r.category, s.id, s.code, s.slug, s.name
          ORDER BY r.name ASC, r.id ASC, s.code ASC, s.name ASC, s.id ASC';

    $rows = q($sql)->fetchAll();

    $ranges = [];
    foreach ($rows as $row) {
        $rid = (int)$row['range_id'];
        if (!isset($ranges[$rid])) {
            $ranges[$rid] = [
                'id'          => $rid,
                'name'        => $row['range_name'],
                'slug'        => $row['range_slug'],
                'category'    => $row['range_category'],
                'sets'        => [],
                'mini_count'  => 0,
                'owned_count' => 0,
            ];
        }
        if ($row['set_id'] === null) {
            continue; // a range with no sets yet
        }

        $count = (int)$row['mini_count'];
        $owned = (int)$row['owned_count'];

        $ranges[$rid]['sets'][] = [
            'id'          => (int)$row['set_id'],
            'code'        => $row['set_code'],
            'slug'        => $row['set_slug'],
            'name'        => $row['set_name'],
            'mini_count'  => $count,
            'owned_count' => $owned,
        ];
        $ranges[$rid]['mini_count']  += $count;
        $ranges[$rid]['owned_count'] += $owned;
    }

    foreach ($ranges as &$range) {
        sort_sets_by_code($range['sets']);
    }
    unset($range);

    return array_values($ranges);
}

/**
 * The four headline figures, derived — never hard-coded. Three of them fall
 * out of the catalogue already in hand; the hunt spans it and asks for itself.
 */
function repo_totals(array $catalogue): array
{
    $minis = 0;
    $sets  = 0;
    $owned = 0;
    foreach ($catalogue as $range) {
        $minis += $range['mini_count'];
        $owned += $range['owned_count'];
        $sets  += count($range['sets']);
    }
    return [
        'miniatures' => $minis,
        'sets'       => $sets,
        'owned'      => $owned,
        'wanted'     => repo_wanted_total(),
    ];
}

function repo_range_by_slug(string $slug): ?array
{
    $row = q('SELECT id, name, slug, category FROM ' . tbl('ranges') . ' WHERE slug = ?', [$slug])->fetch();
    return $row ?: null;
}

function repo_range(int $id): ?array
{
    $row = q('SELECT id, name, slug, category FROM ' . tbl('ranges') . ' WHERE id = ?', [$id])->fetch();
    return $row ?: null;
}

function repo_ranges(): array
{
    return q('SELECT id, name, slug, category FROM ' . tbl('ranges') . ' ORDER BY name ASC')->fetchAll();
}

/**
 * A set plus its range, found the way the public URL addresses it. Codes are
 * not unique within a range, so the URL carries the slug instead.
 */
function repo_set_by_slugs(string $rangeSlug, string $setSlug): ?array
{
    $sql = 'SELECT s.id, s.code, s.slug, s.name, s.range_id,
                   r.name AS range_name, r.slug AS range_slug
              FROM ' . tbl('sets') . ' s
              JOIN ' . tbl('ranges') . ' r ON r.id = s.range_id
             WHERE r.slug = ? AND s.slug = ?';
    $row = q($sql, [$rangeSlug, $setSlug])->fetch();
    return $row ?: null;
}

function repo_set(int $id): ?array
{
    $sql = 'SELECT s.id, s.code, s.slug, s.name, s.range_id,
                   r.name AS range_name, r.slug AS range_slug
              FROM ' . tbl('sets') . ' s
              JOIN ' . tbl('ranges') . ' r ON r.id = s.range_id
             WHERE s.id = ?';
    $row = q($sql, [$id])->fetch();
    return $row ?: null;
}

/** A set's miniatures in their manual order, each flagged owned. */
function repo_miniatures(int $setId): array
{
    $sql = 'SELECT m.id, m.code, m.name, m.photo, m.sort_index,
                   (o.miniature_id IS NOT NULL) AS owned,
                   (w.miniature_id IS NOT NULL) AS wanted
              FROM ' . tbl('miniatures') . ' m
         LEFT JOIN ' . tbl('ownership') . ' o
                ON o.miniature_id = m.id
         LEFT JOIN ' . tbl('wanted') . ' w
                ON w.miniature_id = m.id
             WHERE m.set_id = :sid
          ORDER BY m.sort_index ASC, m.id ASC';

    $rows = q($sql, ['sid' => $setId])->fetchAll();
    foreach ($rows as &$row) {
        $row['id']     = (int)$row['id'];
        $row['owned']  = (bool)$row['owned'];
        // Ticking owned deletes the wanted row, so these should never both be
        // set. Kept as a guard: an owned miniature never reads as wanted.
        $row['wanted'] = !$row['owned'] && (bool)$row['wanted'];
    }
    unset($row);
    return $rows;
}

function repo_miniature(int $id): ?array
{
    $row = q('SELECT id, set_id, code, name, photo, sort_index FROM ' . tbl('miniatures') . ' WHERE id = ?', [$id])->fetch();
    return $row ?: null;
}

/* — ownership — one collection for the whole archive, idempotent both ways — */

function repo_set_owned(int $miniatureId, bool $owned): void
{
    if ($owned) {
        q(
            'INSERT IGNORE INTO ' . tbl('ownership') . ' (miniature_id, created_at) VALUES (?, NOW())',
            [$miniatureId]
        );
    } else {
        q('DELETE FROM ' . tbl('ownership') . ' WHERE miniature_id = ?', [$miniatureId]);
    }
}

/* — the hunt — the same shape as ownership — */

function repo_set_wanted(int $miniatureId, bool $wanted): void
{
    if ($wanted) {
        q(
            'INSERT IGNORE INTO ' . tbl('wanted') . ' (miniature_id, created_at) VALUES (?, NOW())',
            [$miniatureId]
        );
    } else {
        q('DELETE FROM ' . tbl('wanted') . ' WHERE miniature_id = ?', [$miniatureId]);
    }
}

/** Wanted only counts where it is not already owned. */
function repo_wanted_count_in_set(int $setId): int
{
    $row = q(
        'SELECT COUNT(*) AS n FROM ' . tbl('wanted') . ' w
           JOIN ' . tbl('miniatures') . ' m ON m.id = w.miniature_id
      LEFT JOIN ' . tbl('ownership') . ' o ON o.miniature_id = m.id
          WHERE m.set_id = ? AND o.miniature_id IS NULL',
        [$setId]
    )->fetch();
    return (int)$row['n'];
}

/* — the hunt, whole — what the wanted page shows — */

/**
 * Every miniature on the hunt, across the archive, with the set and range it
 * came from. The wanted page is a flat list rather than a walk of the
 * catalogue, so this is one query.
 *
 * Owned rows are excluded here as well as cleared on the tick: a wanted row
 * left behind by an older build never shows as sought.
 *
 * Sorted in PHP, not ORDER BY, for the same reason sets are — set codes need
 * a natural sort (C01 < C06 < C11) and MySQL has none.
 */
function repo_wanted(): array
{
    $sql = 'SELECT m.id, m.code, m.name, m.photo,
                   s.id       AS set_id,
                   s.code     AS set_code,
                   s.slug     AS set_slug,
                   s.name     AS set_name,
                   r.name     AS range_name,
                   r.slug     AS range_slug,
                   r.category AS range_category
              FROM ' . tbl('wanted') . ' w
              JOIN ' . tbl('miniatures') . ' m ON m.id = w.miniature_id
              JOIN ' . tbl('sets') . ' s       ON s.id = m.set_id
              JOIN ' . tbl('ranges') . ' r     ON r.id = s.range_id
         LEFT JOIN ' . tbl('ownership') . ' o  ON o.miniature_id = m.id
             WHERE o.miniature_id IS NULL';

    $rows = q($sql)->fetchAll();

    foreach ($rows as &$row) {
        $row['id']       = (int)$row['id'];
        $row['set_id']   = (int)$row['set_id'];
        // The card markup is shared with the set page, which reads both.
        $row['owned']    = false;
        $row['wanted']   = true;
        $row['category'] = category_valid((string)$row['range_category'])
            ? (string)$row['range_category']
            : LL_DEFAULT_CATEGORY;
    }
    unset($row);

    usort($rows, static function (array $a, array $b): int {
        return strnatcasecmp($a['set_code'], $b['set_code'])
            ?: strnatcasecmp((string)$a['code'], (string)$b['code'])
            ?: ($a['id'] <=> $b['id']);
    });

    return $rows;
}

/**
 * The hunt grouped into its categories, in category order, dropping any that
 * hold nothing. Each group is ['key', 'label', 'items'].
 *
 * Deliberately not repo_by_category(): that one groups ranges, and the wanted
 * page has no range level — the genre heading sits straight above the grid.
 */
function repo_wanted_by_category(array $wanted): array
{
    $groups = [];
    foreach (categories() as $key => $label) {
        $groups[$key] = ['key' => $key, 'label' => $label, 'items' => []];
    }

    foreach ($wanted as $mini) {
        $groups[$mini['category']]['items'][] = $mini;
    }

    return array_values(array_filter($groups, static fn(array $g): bool => $g['items'] !== []));
}

/**
 * How many miniatures are sought. The header's count chip asks for this on
 * every page, so the answer is held for the request.
 */
function repo_wanted_total(): int
{
    static $total = null;
    if ($total === null) {
        $row = q(
            'SELECT COUNT(*) AS n FROM ' . tbl('wanted') . ' w
               JOIN ' . tbl('miniatures') . ' m ON m.id = w.miniature_id
          LEFT JOIN ' . tbl('ownership') . ' o ON o.miniature_id = m.id
              WHERE o.miniature_id IS NULL'
        )->fetch();
        $total = (int)$row['n'];
    }
    return $total;
}

function repo_owned_count_in_set(int $setId): int
{
    $row = q(
        'SELECT COUNT(*) AS n FROM ' . tbl('ownership') . ' o
           JOIN ' . tbl('miniatures') . ' m ON m.id = o.miniature_id
          WHERE m.set_id = ?',
        [$setId]
    )->fetch();
    return (int)$row['n'];
}

/* — admin writes — */

/** Ranges have no code; the slug is derived from the name and kept unique. */
function repo_unique_range_slug(string $name, ?int $ignoreId = null): string
{
    $base = slugify($name, 'range');
    $slug = $base;
    $n    = 2;
    while (true) {
        $sql    = 'SELECT id FROM ' . tbl('ranges') . ' WHERE slug = ?' . ($ignoreId ? ' AND id <> ?' : '');
        $params = $ignoreId ? [$slug, $ignoreId] : [$slug];
        if (!q($sql, $params)->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n++;
    }
}

function repo_create_range(string $name, string $category): int
{
    q(
        'INSERT INTO ' . tbl('ranges') . ' (name, slug, category, created_at) VALUES (?, ?, ?, NOW())',
        [$name, repo_unique_range_slug($name), category_valid($category) ? $category : LL_DEFAULT_CATEGORY]
    );
    return (int)db()->lastInsertId();
}

function repo_update_range(int $id, string $name, string $category): void
{
    q(
        'UPDATE ' . tbl('ranges') . ' SET name = ?, slug = ?, category = ? WHERE id = ?',
        [$name, repo_unique_range_slug($name, $id), category_valid($category) ? $category : LL_DEFAULT_CATEGORY, $id]
    );
}

/** Cascades to the range's sets and their miniatures through the foreign keys. */
function repo_delete_range(int $id): void
{
    q('DELETE FROM ' . tbl('ranges') . ' WHERE id = ?', [$id]);
}

/**
 * The URL slug for a set. Derived from its code, and suffixed when another set
 * in the same range already holds that slug — which is allowed, because two
 * sets in a range may share a code.
 */
function repo_unique_set_slug(int $rangeId, string $code, ?int $ignoreId = null): string
{
    $base = slugify($code, 'set');
    $slug = $base;
    $n    = 2;
    while (true) {
        $sql    = 'SELECT id FROM ' . tbl('sets') . ' WHERE range_id = ? AND slug = ?'
                . ($ignoreId ? ' AND id <> ?' : '');
        $params = $ignoreId ? [$rangeId, $slug, $ignoreId] : [$rangeId, $slug];
        if (!q($sql, $params)->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n++;
    }
}

function repo_create_set(int $rangeId, string $code, string $name): int
{
    q(
        'INSERT INTO ' . tbl('sets') . ' (range_id, code, slug, name, created_at)
         VALUES (?, ?, ?, ?, NOW())',
        [$rangeId, $code, repo_unique_set_slug($rangeId, $code), $name]
    );
    return (int)db()->lastInsertId();
}

/** Moving a set to another range carries its sets, photographs and ticks. */
function repo_update_set(int $id, int $rangeId, string $code, string $name): void
{
    $set = repo_set($id);
    if (!$set) {
        return;
    }

    $moved   = (int)$set['range_id'] !== $rangeId;
    $recoded = strcasecmp($set['code'], $code) !== 0;

    // Slugs are unique within a range, so a move has to re-check even when the
    // code is untouched. Otherwise the slug is kept, so live URLs hold.
    $slug = ($moved || $recoded)
        ? repo_unique_set_slug($rangeId, $code, $id)
        : $set['slug'];

    q(
        'UPDATE ' . tbl('sets') . ' SET range_id = ?, code = ?, slug = ?, name = ? WHERE id = ?',
        [$rangeId, $code, $slug, $name, $id]
    );
}

function repo_delete_set(int $id): void
{
    q('DELETE FROM ' . tbl('sets') . ' WHERE id = ?', [$id]);
}

function repo_count_in_set(int $setId): int
{
    $row = q('SELECT COUNT(*) AS n FROM ' . tbl('miniatures') . ' WHERE set_id = ?', [$setId])->fetch();
    return (int)$row['n'];
}

function repo_range_blast_radius(int $rangeId): array
{
    $row = q(
        'SELECT COUNT(DISTINCT s.id) AS sets, COUNT(m.id) AS minis
           FROM ' . tbl('sets') . ' s
      LEFT JOIN ' . tbl('miniatures') . ' m ON m.set_id = s.id
          WHERE s.range_id = ?',
        [$rangeId]
    )->fetch();
    return ['sets' => (int)$row['sets'], 'miniatures' => (int)$row['minis']];
}

/** Blank code or name is absence, not an empty string. */
function repo_blank_to_null(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

/** The photograph is required; the code and name are not. */
function repo_create_miniature(int $setId, string $code, string $name, string $photo): int
{
    $row  = q('SELECT COALESCE(MAX(sort_index), -1) AS mx FROM ' . tbl('miniatures') . ' WHERE set_id = ?', [$setId])->fetch();
    $next = (int)$row['mx'] + 1;

    q(
        'INSERT INTO ' . tbl('miniatures') . ' (set_id, code, name, photo, sort_index, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())',
        [$setId, repo_blank_to_null($code), repo_blank_to_null($name), $photo, $next]
    );
    return (int)db()->lastInsertId();
}

function repo_update_miniature(int $id, string $code, string $name, string $photo): void
{
    q(
        'UPDATE ' . tbl('miniatures') . ' SET code = ?, name = ?, photo = ? WHERE id = ?',
        [repo_blank_to_null($code), repo_blank_to_null($name), $photo, $id]
    );
}

/**
 * What to call a miniature that has neither. Used wherever one is referred to
 * in prose — confirmations, page titles, accessible labels.
 */
function repo_mini_label(array $m): string
{
    $parts = array_filter([$m['code'] ?? null, $m['name'] ?? null]);
    return $parts ? implode(' ', $parts) : 'this miniature';
}

function repo_delete_miniature(int $id): void
{
    q('DELETE FROM ' . tbl('miniatures') . ' WHERE id = ?', [$id]);
}

/** Persist a drag-reorder: the set's miniature ids in their new order. */
function repo_reorder_miniatures(int $setId, array $orderedIds): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE ' . tbl('miniatures') . ' SET sort_index = ? WHERE id = ? AND set_id = ?'
        );
        foreach (array_values($orderedIds) as $i => $id) {
            $stmt->execute([$i, (int)$id, $setId]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
