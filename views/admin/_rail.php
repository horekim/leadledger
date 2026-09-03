<?php
/**
 * The admin menu: every range.
 *
 * On a narrow screen it collapses to one row that names where you are; above
 * the breakpoint the button is hidden and the list is the vertical rail it has
 * always been. Deliberately not a <details>: its open state would have to be
 * forced back on when the viewport widens, and a stale closed state there
 * empties the menu.
 *
 * The list ships visible, so with no JavaScript the menu is simply always
 * open rather than permanently shut.
 *
 * @var array $catalogue @var ?int $activeRangeId @var bool $railAll
 */
$current = !empty($railAll) ? 'All ranges' : '';
if ($current === '' && isset($activeRangeId)) {
    foreach ($catalogue as $r) {
        if ((int)$r['id'] === (int)$activeRangeId) {
            $current = $r['name'];
            break;
        }
    }
}
?>
<nav class="admin-rail" aria-label="Ranges">
  <button type="button" class="admin-disc" aria-expanded="true" aria-controls="range-menu">
    <span class="range-toggle" aria-hidden="true">
      <span class="msym tog-open">add</span>
      <span class="msym tog-close">remove</span>
    </span>
    <span class="disc-label">Ranges</span>
    <span class="disc-current"><?= e($current) ?></span>
  </button>

  <div class="admin-rail-list" id="range-menu">
    <a class="<?= !empty($railAll) ? 'is-active' : '' ?>" href="<?= e(url('admin')) ?>">
      <span>All ranges</span>
      <span class="admin-rail-count"><?= e(num(count($catalogue))) ?></span>
    </a>

    <?php foreach (repo_by_category($catalogue) as $group): ?>
      <div class="rail-group"><?= e($group['label']) ?></div>
      <?php foreach ($group['ranges'] as $r): ?>
        <a class="<?= isset($activeRangeId) && (int)$r['id'] === (int)$activeRangeId ? 'is-active' : '' ?>"
           href="<?= e(url('admin/ranges/' . (int)$r['id'])) ?>">
          <span><?= e($r['name']) ?></span>
          <span class="admin-rail-count"><?= e(num(count($r['sets']))) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</nav>
