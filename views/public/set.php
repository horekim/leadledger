<?php
/**
 * A set: the range rail on the left, the photo grid on the right.
 * @var array $catalogue @var array $range @var array $set
 * @var array $miniatures @var string $filter @var string $density
 */
$canEdit = is_admin(); // one collection: everyone sees it, an admin changes it
$total   = count($miniatures);
$ownedN  = 0;
foreach ($miniatures as $m) {
    if ($m['owned']) {
        $ownedN++;
    }
}

$shown = array_values(array_filter($miniatures, static function (array $m) use ($filter) {
    if ($filter === 'owned')   { return $m['owned']; }
    if ($filter === 'missing') { return !$m['owned']; }
    if ($filter === 'wanted')  { return $m['wanted']; }
    return true;
}));

$setUrl = static fn(array $r, array $s): string => url(rawurlencode($r['slug']) . '/' . rawurlencode($s['slug']));
$here   = $setUrl(['slug' => $range['slug']], ['slug' => $set['slug']]);
/**
 * Links for the filter bar.
 *
 * `show` is dropped when it is All, because the filter is read fresh from the
 * query string each request and All is what its absence already means.
 *
 * `density` is always carried, even when it is the default. Density persists
 * in the session, so an absent parameter means "keep what you had", not
 * "compact" — dropping it would make the Compact button unable to select
 * itself once another density was chosen.
 */
$qs = static function (array $over) use ($here, $filter, $density): string {
    $params = array_merge(['show' => $filter, 'density' => $density], $over);
    if ($params['show'] === 'all') {
        unset($params['show']);
    }
    return $here . ($params ? '?' . http_build_query($params) : '');
};
?>
<div class="shell">

  <nav class="rail" aria-label="Ranges">
    <?php foreach (repo_by_category($catalogue) as $group): ?>
      <div class="rail-group"><?= e($group['label']) ?></div>
      <?php foreach ($group['ranges'] as $r): ?>
      <details class="rail-range" data-range="<?= e($r['slug']) ?>" <?= $r['id'] === (int)$range['id'] ? 'open' : '' ?>>
        <summary>
          <span class="rail-caret msym" style="font-size:14px">chevron_right</span>
          <span><?= e($r['name']) ?></span>
          <span class="rail-total"><?= e(num($r['mini_count'])) ?></span>
        </summary>
        <?php foreach ($r['sets'] as $s): ?>
          <?php $active = (int)$s['id'] === (int)$set['id']; ?>
          <a class="rail-set<?= $active ? ' is-active' : '' ?>"
             href="<?= e($active ? url('') : $setUrl($r, $s)) ?>"
             <?= $active ? 'aria-current="page" title="Back to all sets"' : '' ?>>
            <span class="code"><?= e($s['code']) ?></span>
            <span class="name"><?= e($s['name']) ?></span>
            <span class="own"><?= e($s['owned_count']) ?>/<?= e($s['mini_count']) ?></span>
          </a>
        <?php endforeach; ?>
      </details>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <main class="page-main">

    <div class="page-head ruled">
      <div>
        <div class="breadcrumb">
          <a href="<?= e(url('')) ?>">&larr; All sets</a>
          <span class="sep">/</span>
          <span class="tail"><?= e($range['name']) ?> · <?= e($set['code']) ?></span>
        </div>
        <h1><?= e($set['name']) ?></h1>
        <div class="count-line"><?= e(num($ownedN)) ?> of <?= e(num($total)) ?> owned</div>
      </div>
    </div>

    <div class="filter-bar">
      <div class="segmented" role="group" aria-label="Ownership filter">
        <a class="<?= $filter === 'all' ? 'is-active' : '' ?>"     href="<?= e($qs(['show' => 'all'])) ?>">All</a>
        <a class="<?= $filter === 'owned' ? 'is-active' : '' ?>"   href="<?= e($qs(['show' => 'owned'])) ?>">Owned</a>
        <a class="<?= $filter === 'missing' ? 'is-active' : '' ?>" href="<?= e($qs(['show' => 'missing'])) ?>">Missing</a>
        <a class="<?= $filter === 'wanted' ? 'is-active' : '' ?>"  href="<?= e($qs(['show' => 'wanted'])) ?>">Wanted</a>
      </div>

      <span class="result-count">Showing <?= e(num(count($shown))) ?> of <?= e(num($total)) ?></span>

      <div class="density" role="group" aria-label="Grid density">
        <a class="<?= $density === 'contact' ? 'is-active' : '' ?>"     href="<?= e($qs(['density' => 'contact'])) ?>"     title="Contact sheet">&#9638;</a>
        <a class="<?= $density === 'compact' ? 'is-active' : '' ?>"     href="<?= e($qs(['density' => 'compact'])) ?>"     title="Compact">&#9636;</a>
        <a class="<?= $density === 'comfortable' ? 'is-active' : '' ?>" href="<?= e($qs(['density' => 'comfortable'])) ?>" title="Comfortable">&#9634;</a>
      </div>
    </div>

    <?php if (!$shown): ?>
      <div class="empty-state">
        <?php if ($total === 0): ?>
          <strong>No miniatures in this set yet</strong>
          <span>The set is catalogued, but nothing has been photographed into it.</span>
        <?php elseif ($filter === 'wanted'): ?>
          <strong>Nothing on the hunt in this set</strong>
          <span>Mark a miniature with the crosshair to start looking for it.</span>
        <?php else: ?>
          <strong>Nothing matches this filter</strong>
          <span>Switch back to All to see the rest of the set.</span>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="mini-grid" data-density="<?= e($density) ?>">
        <?php foreach ($shown as $m): ?>
          <div class="card mini-card<?= $m['owned'] ? ' is-owned' : '' ?>" data-mini="<?= e((string)$m['id']) ?>">
            <?php view('public/_plate', ['m' => $m, 'canEdit' => $canEdit]) ?>

            <?php /* Code and name are both optional; a card may carry neither. */ ?>
            <div class="mini-caption">
              <?php if ($m['code'] === null && $m['name'] === null): ?>
                <span class="no-info">No info</span>
              <?php else: ?>
                <?php if ($m['code'] !== null): ?><span class="mini-code"><?= e($m['code']) ?></span><?php endif; ?>
                <?php if ($m['name'] !== null): ?><span class="mini-name"><?= e($m['name']) ?></span><?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>
</div>
