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

$wantedN = 0;
foreach ($miniatures as $m) {
    if ($m['wanted']) {
        $wantedN++;
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
          <?php
            $photo = $m['photo'] ? asset($m['photo']) : null;
            $what  = repo_mini_label($m);
            $label = ($m['owned'] ? 'Owned — ' : 'Not owned — ') . $what;
          ?>
          <div class="card mini-card<?= $m['owned'] ? ' is-owned' : '' ?>" data-mini="<?= e((string)$m['id']) ?>">
            <?php if ($canEdit): ?>
              <form method="post" action="<?= e(url('api/collection/' . $m['id'])) ?>" data-toggle>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="owned" value="<?= $m['owned'] ? '0' : '1' ?>" data-owned-field>
                <button type="submit" class="plate mini-plate" aria-label="<?= e($label) ?>">
                  <span class="mini-photo<?= $photo ? '' : ' is-blank' ?>"
                        <?= $photo ? 'style="background-image:url(\'' . e($photo) . '\')"' : '' ?>></span>
                  <?php if ($m['wanted']): ?><span class="want-strip" aria-hidden="true">Wanted</span><?php endif; ?>
                </button>
                <button type="submit" class="tick" aria-pressed="<?= $m['owned'] ? 'true' : 'false' ?>"
                        aria-label="<?= e(($m['owned'] ? 'Mark not owned: ' : 'Mark owned: ') . $what) ?>">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="3" stroke-linecap="square" aria-hidden="true">
                    <path d="M5 12.5 10 17.5 19 7"/>
                  </svg>
                </button>
              </form>

              <?php /* Always rendered, hidden by CSS while the card is owned.
                       Rendering it conditionally meant un-ticking had nothing
                       to reveal, since the markup was never there. */ ?>
                <form method="post" action="<?= e(url('api/wanted/' . $m['id'])) ?>" data-want>
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="wanted" value="<?= $m['wanted'] ? '0' : '1' ?>" data-wanted-field>
                  <button type="submit" class="want" aria-pressed="<?= $m['wanted'] ? 'true' : 'false' ?>"
                          title="<?= e($m['wanted'] ? 'Stop looking for this' : 'I am looking for this') ?>"
                          aria-label="<?= e(($m['wanted'] ? 'Stop looking for: ' : 'Look for: ') . $what) ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2.4" stroke-linecap="square" aria-hidden="true">
                    <circle cx="12" cy="12" r="7"/><path d="M12 1v3 M12 20v3 M1 12h3 M20 12h3"/>
                  </svg>
                  </button>
                </form>
            <?php else: ?>
              <?php /* Read-only. The tick shows only where it means something —
                       an empty square nobody can press is an affordance that lies. */ ?>
              <div class="plate mini-plate is-static">
                <span class="mini-photo<?= $photo ? '' : ' is-blank' ?>"
                      <?= $photo ? 'style="background-image:url(\'' . e($photo) . '\')"' : '' ?>></span>
                <?php if ($m['wanted']): ?><span class="want-strip" aria-hidden="true">Wanted</span><?php endif; ?>
              </div>
              <?php if ($m['owned']): ?>
                <span class="tick is-static" role="img" aria-label="Owned">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="3" stroke-linecap="square" aria-hidden="true">
                    <path d="M5 12.5 10 17.5 19 7"/>
                  </svg>
                </span>
              <?php endif; ?>
              <?php if ($m['wanted']): ?>
                <span class="want is-static" role="img" aria-label="Wanted">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2.4" stroke-linecap="square" aria-hidden="true">
                    <circle cx="12" cy="12" r="7"/><path d="M12 1v3 M12 20v3 M1 12h3 M20 12h3"/>
                  </svg>
                </span>
              <?php endif; ?>
            <?php endif; ?>

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
