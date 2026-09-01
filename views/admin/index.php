<?php /** Admin landing — ranges and their sets. @var array $catalogue */ ?>
<div class="admin-shell">

  <nav class="admin-rail" aria-label="Admin sections">
    <a class="is-active" href="<?= e(url('admin')) ?>">Ranges &amp; sets</a>
  </nav>

  <main class="admin-main">

    <div class="section-head">
      <div>
        <div class="kicker" style="color:color-mix(in srgb, var(--color-text) 50%, transparent)">Manage</div>
        <h2>Ranges &amp; sets</h2>
      </div>
      <div class="section-actions">
        <button type="button" class="btn btn-primary" data-editor-open
                data-kind="range" data-id="" data-name="" data-title="New range" data-submit="Create range">
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

    <?php foreach ($catalogue as $range): ?>
      <section class="admin-range">
        <div class="admin-range-head">
          <h3><?= e($range['name']) ?></h3>
          <span class="meta-up"><?= e(num(count($range['sets']))) ?> <?= e(plural(count($range['sets']), 'set', 'sets')) ?></span>
          <div class="section-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-editor-open
                    data-kind="range" data-id="<?= e((string)$range['id']) ?>"
                    data-name="<?= e($range['name']) ?>"
                    data-title="Edit range" data-submit="Save" data-deletable="1"
                    data-del-title="Delete <?= e($range['name']) ?>?"
                    data-del-body="This removes <?= e(num(count($range['sets']))) ?> <?= e(plural(count($range['sets']), 'set', 'sets')) ?> and <?= e(num($range['mini_count'])) ?> <?= e(plural($range['mini_count'], 'miniature', 'miniatures')) ?> from the catalogue, along with every collector&#39;s record of them. It cannot be undone."
                    data-del-submit="Delete range">
              <span class="msym" style="font-size:16px">edit</span>Edit range
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-editor-open
                    data-kind="set" data-id="" data-range="<?= e((string)$range['id']) ?>"
                    data-title="New set in <?= e($range['name']) ?>" data-submit="Create set">
              <span class="msym" style="font-size:16px">add</span>Add set
            </button>
          </div>
        </div>

        <?php if (!$range['sets']): ?>
          <div class="empty-range">
            <strong>No sets in this range yet</strong>
            <span>Add a set to start listing miniatures under <?= e($range['name']) ?>.</span>
          </div>
        <?php else: ?>
          <div class="tbl-scroll">
            <table class="table">
              <thead>
                <tr><th class="col-code">Code</th><th>Set</th><th class="col-count">Miniatures</th></tr>
              </thead>
              <tbody>
                <?php foreach ($range['sets'] as $set): ?>
                  <tr data-href="<?= e(url('admin/sets/' . (int)$set['id'])) ?>" tabindex="0">
                    <td class="col-code"><?= e($set['code']) ?></td>
                    <td class="col-name"><?= e($set['name']) ?></td>
                    <td class="col-count"><?= e(num($set['mini_count'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

  </main>
</div>

<?php view('admin/_dialogs'); ?>
