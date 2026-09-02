<?php
/**
 * The admin menu: every range, with New range above them.
 * @var array $catalogue @var ?int $activeRangeId @var bool $railAll
 */
?>
<nav class="admin-rail" aria-label="Ranges">
  <div class="admin-rail-top">
    <button type="button" class="btn btn-primary btn-block" data-editor-open
            data-kind="range" data-id="" data-name=""
            data-title="New range" data-submit="Create range">
      <span class="msym" style="font-size:16px">add</span>New range
    </button>
  </div>

  <a class="<?= !empty($railAll) ? 'is-active' : '' ?>" href="<?= e(url('admin')) ?>">
    <span>All ranges</span>
    <span class="admin-rail-count"><?= e(num(count($catalogue))) ?></span>
  </a>

  <?php foreach ($catalogue as $r): ?>
    <a class="<?= isset($activeRangeId) && (int)$r['id'] === (int)$activeRangeId ? 'is-active' : '' ?>"
       href="<?= e(url('admin/ranges/' . (int)$r['id'])) ?>">
      <span><?= e($r['name']) ?></span>
      <span class="admin-rail-count"><?= e(num(count($r['sets']))) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
