<?php /** One range and its sets. @var array $catalogue @var array $range */ ?>
<div class="admin-shell">

  <?php view('admin/_rail', ['catalogue' => $catalogue, 'activeRangeId' => (int)$range['id']]); ?>

  <main class="admin-main">

    <div class="section-head">
      <div>
        <div class="breadcrumb">
          <a href="<?= e(url('admin')) ?>">&larr; All ranges</a>
        </div>
        <h2><?= e($range['name']) ?></h2>
        <div class="count-line">
          <?= e(num(count($range['sets']))) ?> <?= e(plural(count($range['sets']), 'set', 'sets')) ?>
          · <?= e(num($range['mini_count'])) ?> <?= e(plural($range['mini_count'], 'miniature', 'miniatures')) ?>
        </div>
      </div>
      <div class="section-actions">
        <button type="button" class="btn btn-secondary" data-editor-open
                data-kind="range" data-id="<?= e((string)$range['id']) ?>"
                data-name="<?= e($range['name']) ?>"
                data-category="<?= e((string)$range['category']) ?>"
                data-title="Edit range" data-submit="Save" data-deletable="1"
                data-del-title="Delete <?= e($range['name']) ?>?"
                data-del-body="This removes <?= e(num(count($range['sets']))) ?> <?= e(plural(count($range['sets']), 'set', 'sets')) ?> and <?= e(num($range['mini_count'])) ?> <?= e(plural($range['mini_count'], 'miniature', 'miniatures')) ?> from the catalogue, along with every record of them. It cannot be undone."
                data-del-submit="Delete range">
          <span class="msym" style="font-size:16px">edit</span>Edit range
        </button>
        <button type="button" class="btn btn-primary" data-editor-open
                data-kind="set" data-id="" data-range="<?= e((string)$range['id']) ?>"
                data-title="New set in <?= e($range['name']) ?>" data-submit="Create set">
          <span class="msym" style="font-size:16px">add</span>Add set
        </button>
      </div>
    </div>

    <?php view('admin/_sets', ['range' => $range]); ?>

  </main>
</div>

<?php view('admin/_dialogs'); ?>
