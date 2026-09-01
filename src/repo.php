<?php
/**
 * Every query the app makes.
 *
 * The index needs range -> sets -> counts -> owned counts in one round trip,
 * so repo_catalogue() is a single aggregate query rather than a loop.
 */

/**
 * The whole catalogue: ranges alphabetical, sets natural-sorted by code,
 * each set carrying its miniature count and this user's owned count.
 */
function repo_catalogue(?int $userId): array
{
    $sql = 'SELECT r.id           AS range_id,
                   r.name         AS range_name,
                   r.slug         AS range_slug,
                   s.id           AS set_id,
                   s.code         AS set_code,
                   s.name         AS set_name,
                   COUNT(DISTINCT m.id)           AS mini_count,
                   COUNT(DISTINCT o.miniature_id) AS owned_count
              FROM ' . tbl('ranges') . ' r
         LEFT JOIN ' . tbl('sets') . ' s        ON s.range_id = r.id
         LEFT JOIN ' . tbl('miniatures') . ' m  ON m.set_id = s.id
         LEFT JOIN ' . tbl('ownership') . ' o   ON o.miniature_id = m.id AND o.user_id = :uid
          GROUP BY r.id, r.name, r.slug, s.id, s.code, s.name
          ORDER BY r.name ASC';

    $rows = q($sql, ['uid' => $userId ?? 0])->fetchAll();

    $ranges = [];
    foreach ($rows as $row) {
        $rid = (int)$row['range_id'];
        if (!isset($ranges[$rid])) {
            $ranges[$rid] = [
                'id'          => $rid,
                'name'        => $row['range_name'],
                'slug'        => $row['range_slug'],
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

/** The three headline figures, derived — never hard-coded. */
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
    return ['miniatures' => $minis, 'sets' => $sets, 'owned' => $owned];
}

function repo_range_by_slug(string $slug): ?array
{
    $row = q('SELECT id, name, slug FROM ' . tbl('ranges') . ' WHERE slug = ?', [$slug])->fetch();
    return $row ?: null;
}

function repo_range(int $id): ?array
{
    $row = q('SELECT id, name, slug FROM ' . tbl('ranges') . ' WHERE id = ?', [$id])->fetch();
    return $row ?: null;
}

function repo_ranges(): array
{
    return q('SELECT id, name, slug FROM ' . tbl('ranges') . ' ORDER BY name ASC')->fetchAll();
}

/** A set plus its range, found the way the public URL addresses it. */
function repo_set_by_slug_code(string $rangeSlug, string $code): ?array
{
    $sql = 'SELECT s.id, s.code, s.name, s.range_id,
                   r.name AS range_name, r.slug AS range_slug
              FROM ' . tbl('sets') . ' s
              JOIN ' . tbl('ranges') . ' r ON r.id = s.range_id
             WHERE r.slug = ? AND s.code = ?';
    $row = q($sql, [$rangeSlug, $code])->fetch();
    return $row ?: null;
}

function repo_set(int $id): ?array
{
    $sql = 'SELECT s.id, s.code, s.name, s.range_id,
                   r.name AS range_name, r.slug AS range_slug
              FROM ' . tbl('sets') . ' s
              JOIN ' . tbl('ranges') . ' r ON r.id = s.range_id
             WHERE s.id = ?';
    $row = q($sql, [$id])->fetch();
    return $row ?: null;
}

/** A set's miniatures in their manual order, each flagged owned for this user. */
function repo_miniatures(int $setId, ?int $userId): array
{
    $sql = 'SELECT m.id, m.code, m.name, m.photo, m.sort_index,
                   (o.miniature_id IS NOT NULL) AS owned
              FROM ' . tbl('miniatures') . ' m
         LEFT JOIN ' . tbl('ownership') . ' o
                ON o.miniature_id = m.id AND o.user_id = :uid
             WHERE m.set_id = :sid
          ORDER BY m.sort_index ASC, m.id ASC';

    $rows = q($sql, ['uid' => $userId ?? 0, 'sid' => $setId])->fetchAll();
    foreach ($rows as &$row) {
        $row['id']    = (int)$row['id'];
        $row['owned'] = (bool)$row['owned'];
    }
    unset($row);
    return $rows;
}

function repo_miniature(int $id): ?array
{
    $row = q('SELECT id, set_id, code, name, photo, sort_index FROM ' . tbl('miniatures') . ' WHERE id = ?', [$id])->fetch();
    return $row ?: null;
}

/* — ownership — private per user, and idempotent both ways — */

function repo_set_owned(int $userId, int $miniatureId, bool $owned): void
{
    if ($owned) {
        q(
            'INSERT IGNORE INTO ' . tbl('ownership') . ' (user_id, miniature_id, created_at)
             VALUES (?, ?, NOW())',
            [$userId, $miniatureId]
        );
    } else {
        q(
            'DELETE FROM ' . tbl('ownership') . ' WHERE user_id = ? AND miniature_id = ?',
            [$userId, $miniatureId]
        );
    }
}

/** Tick or clear a whole set in one statement, not N. */
function repo_set_owned_bulk(int $userId, int $setId, bool $owned): int
{
    if ($owned) {
        $stmt = q(
            'INSERT IGNORE INTO ' . tbl('ownership') . ' (user_id, miniature_id, created_at)
             SELECT ?, m.id, NOW() FROM ' . tbl('miniatures') . ' m WHERE m.set_id = ?',
            [$userId, $setId]
        );
    } else {
        $stmt = q(
            'DELETE o FROM ' . tbl('ownership') . ' o
               JOIN ' . tbl('miniatures') . ' m ON m.id = o.miniature_id
              WHERE o.user_id = ? AND m.set_id = ?',
            [$userId, $setId]
        );
    }
    return $stmt->rowCount();
}

function repo_owned_count_in_set(int $userId, int $setId): int
{
    $row = q(
        'SELECT COUNT(*) AS n FROM ' . tbl('ownership') . ' o
           JOIN ' . tbl('miniatures') . ' m ON m.id = o.miniature_id
          WHERE o.user_id = ? AND m.set_id = ?',
        [$userId, $setId]
    )->fetch();
    return (int)$row['n'];
}

/* — admin writes — */

/** Ranges have no code; the slug is derived from the name and kept unique. */
function repo_unique_range_slug(string $name, ?int $ignoreId = null): string
{
    $base = slugify($name);
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

function repo_create_range(string $name): int
{
    q(
        'INSERT INTO ' . tbl('ranges') . ' (name, slug, created_at) VALUES (?, ?, NOW())',
        [$name, repo_unique_range_slug($name)]
    );
    return (int)db()->lastInsertId();
}

function repo_update_range(int $id, string $name): void
{
    q(
        'UPDATE ' . tbl('ranges') . ' SET name = ?, slug = ? WHERE id = ?',
        [$name, repo_unique_range_slug($name, $id), $id]
    );
}

/** Cascades to the range's sets and their miniatures through the foreign keys. */
function repo_delete_range(int $id): void
{
    q('DELETE FROM ' . tbl('ranges') . ' WHERE id = ?', [$id]);
}

function repo_create_set(int $rangeId, string $code, string $name): int
{
    q(
        'INSERT INTO ' . tbl('sets') . ' (range_id, code, name, created_at) VALUES (?, ?, ?, NOW())',
        [$rangeId, $code, $name]
    );
    return (int)db()->lastInsertId();
}

function repo_update_set(int $id, string $code, string $name): void
{
    q('UPDATE ' . tbl('sets') . ' SET code = ?, name = ? WHERE id = ?', [$code, $name, $id]);
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

function repo_create_miniature(int $setId, string $code, string $name, ?string $photo): int
{
    $row  = q('SELECT COALESCE(MAX(sort_index), -1) AS mx FROM ' . tbl('miniatures') . ' WHERE set_id = ?', [$setId])->fetch();
    $next = (int)$row['mx'] + 1;

    q(
        'INSERT INTO ' . tbl('miniatures') . ' (set_id, code, name, photo, sort_index, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())',
        [$setId, $code, $name, $photo, $next]
    );
    return (int)db()->lastInsertId();
}

function repo_update_miniature(int $id, string $code, string $name, ?string $photo): void
{
    q(
        'UPDATE ' . tbl('miniatures') . ' SET code = ?, name = ?, photo = ? WHERE id = ?',
        [$code, $name, $photo, $id]
    );
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
