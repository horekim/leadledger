<?php /** Admin default — every range and its sets. @var array $catalogue */ ?>
<div class="admin-shell">

  <?php view('admin/_rail', ['catalogue' => $catalogue, 'railAll' => true]); ?>

  <main class="admin-main">

    <div class="section-head is-row">
      <div>
        <h2>All ranges</h2>
      </div>
      <div class="section-actions">
        <button type="button" class="btn btn-primary" data-editor-open
                data-kind="range" data-id="" data-name=""
                data-title="New range" data-submit="Create range">
          <span class="msym" style="font-size:16px">add</span>New range
        </button>
      </div>
    </div>

    <?php if (!$catalogue): ?>
      <div class="empty-state">
        <strong>Nothing catalogued yet</strong>
        <span>Start with a range — Citadel C-Series, Regiments of Renown, whatever you are working through.</span>
      </div>
    <?php endif; ?>

    <?php foreach (repo_by_category($catalogue) as $group): ?>
      <h3 class="admin-category"><?= e($group['label']) ?></h3>

      <?php foreach ($group['ranges'] as $range): ?>
      <section class="admin-range">
        <div class="admin-range-head">
          <h3><a class="plain" href="<?= e(url('admin/ranges/' . (int)$range['id'])) ?>"><?= e($range['name']) ?></a></h3>
          <span class="meta-up">
            <?= e(num(count($range['sets']))) ?> <?= e(plural(count($range['sets']), 'set', 'sets')) ?>
            · <?= e(num($range['mini_count'])) ?> <?= e(plural($range['mini_count'], 'miniature', 'miniatures')) ?>
          </span>
          <div class="section-actions">
            <button type="button" class="btn btn-primary btn-sm" data-editor-open
                    data-kind="set" data-id="" data-range="<?= e((string)$range['id']) ?>"
                    data-title="New set in <?= e($range['name']) ?>" data-submit="Create set">
              <span class="msym" style="font-size:16px">add</span>Add set
            </button>
          </div>
        </div>

        <?php view('admin/_sets', ['range' => $range]); ?>
      </section>
      <?php endforeach; ?>
    <?php endforeach; ?>

  </main>
</div>

<?php view('admin/_dialogs'); ?>
