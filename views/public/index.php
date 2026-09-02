<?php
/** The front page — every set, grouped by range. @var array $catalogue @var array $totals */
?>
<main class="page">

  <div class="page-head">
    <div>
      <div class="kicker">Citadel Miniatures</div>
      <h1>&rsquo;EAVY METAL</h1>
      <p class="page-blurb">My collection of Oldhammer miniatures.</p>
    </div>
    <div class="stat-row">
      <div>
        <div class="stat-num"><?= e(num($totals['miniatures'])) ?></div>
        <div class="stat-lbl">Miniatures</div>
      </div>
      <div>
        <div class="stat-num"><?= e(num($totals['sets'])) ?></div>
        <div class="stat-lbl">Sets</div>
      </div>
      <div class="stat-own">
        <div class="stat-num"><?= e(num($totals['owned'])) ?></div>
        <div class="stat-lbl">Owned</div>
      </div>
    </div>
  </div>

  <?php /* Empty on this page — the filter controls are set-page only. It is here
           for the rule it draws beneath the head. */ ?>
  <div class="filter-bar"></div>

  <?php if (!$catalogue): ?>
    <div class="empty-state">
      <strong>The archive is empty</strong>
      <span>Nothing has been catalogued yet. Sign in as the catalogue owner to add the first range.</span>
    </div>
  <?php endif; ?>

  <?php foreach ($catalogue as $range): ?>
    <details class="range-section" data-range="<?= e($range['slug']) ?>" open>
      <summary class="range-head">
        <span class="range-toggle" aria-hidden="true">
          <span class="msym tog-open">add</span>
          <span class="msym tog-close">remove</span>
        </span>
        <h3><?= e($range['name']) ?></h3>
        <span class="meta">
          <?= e(num(count($range['sets']))) ?> <?= e(plural(count($range['sets']), 'set', 'sets')) ?>
          · <?= e(num($range['mini_count'])) ?> <?= e(plural($range['mini_count'], 'miniature', 'miniatures')) ?>
        </span>
        <span class="range-owned"><?= e(num($range['owned_count'])) ?> / <?= e(num($range['mini_count'])) ?> owned</span>
      </summary>

      <?php if (!$range['sets']): ?>
        <div class="empty-range">
          <strong>No sets in this range yet</strong>
          <span>Nothing has been catalogued under <?= e($range['name']) ?>.</span>
        </div>
      <?php else: ?>
        <div class="set-grid">
          <?php foreach ($range['sets'] as $set): ?>
            <?php $complete = $set['mini_count'] > 0 && $set['owned_count'] === $set['mini_count']; ?>
            <a class="panel panel-hover set-card"
               href="<?= e(url(rawurlencode($range['slug']) . '/' . rawurlencode($set['slug']))) ?>">
              <div class="set-card-top">
                <span class="set-code"><?= e($set['code']) ?></span>
                <span class="set-name"><?= e($set['name']) ?></span>
                <?php if ($complete): ?>
                  <span class="set-complete" title="Fully owned">&#10003;</span>
                <?php endif; ?>
              </div>
              <div class="set-card-foot">
                <div class="set-card-labels">
                  <span><?= e(num($set['mini_count'])) ?> <?= e(plural($set['mini_count'], 'miniature', 'miniatures')) ?></span>
                  <span><?= e(num($set['owned_count'])) ?> owned</span>
                </div>
                <div class="bar"><span style="width:<?= e(number_format(pct($set['owned_count'], $set['mini_count']), 2, '.', '')) ?>%"></span></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </details>
  <?php endforeach; ?>

</main>
