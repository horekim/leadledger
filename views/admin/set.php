<?php /** Admin set page — the miniatures table and the edit drawer. @var array $set @var array $miniatures */ ?>
<div class="admin-shell">

  <?php view('admin/_rail', ['catalogue' => $catalogue, 'activeRangeId' => (int)$set['range_id']]); ?>

  <main class="admin-main">

    <div class="section-head">
      <div>
        <div class="breadcrumb">
          <a href="<?= e(url('admin/ranges/' . (int)$set['range_id'])) ?>">&larr; <?= e($set['range_name']) ?></a>
        </div>
        <h2><?= e($set['code']) ?> <?= e($set['name']) ?></h2>
        <div class="count-line"><?= e(num(count($miniatures))) ?> <?= e(plural(count($miniatures), 'miniature', 'miniatures')) ?></div>
      </div>
      <div class="section-actions">
        <button type="button" class="btn btn-secondary" data-editor-open
                data-kind="set" data-id="<?= e((string)$set['id']) ?>"
                data-range="<?= e((string)$set['range_id']) ?>"
                data-code="<?= e($set['code']) ?>" data-name="<?= e($set['name']) ?>"
                data-title="Edit set" data-submit="Save" data-deletable="1"
                data-del-title="Delete set <?= e($set['code']) ?> <?= e($set['name']) ?>?"
                data-del-body="The set and its <?= e(num(count($miniatures))) ?> <?= e(plural(count($miniatures), 'miniature', 'miniatures')) ?> are removed from the catalogue, along with every collector&#39;s record of them. It cannot be undone."
                data-del-submit="Delete set">
          <span class="msym" style="font-size:16px">edit</span>Edit set
        </button>
        <button type="button" class="btn btn-secondary" data-bulk-open>
          <span class="msym" style="font-size:16px">library_add</span>Bulk add
        </button>
        <button type="button" class="btn btn-primary" data-drawer-open
                data-id="" data-code="" data-name="" data-photo="" data-photo-name="">
          <span class="msym" style="font-size:16px">add</span>Add miniature
        </button>
      </div>
    </div>

    <?php if (!$miniatures): ?>
      <div class="empty-state" style="padding:34px 0">
        <strong style="font-size:17px">No miniatures in this set yet</strong>
        <span>Add the first one — a code, a name and a photograph is all a miniature is.</span>
      </div>
    <?php else: ?>
      <div class="tbl-scroll">
        <table class="table" data-reorder data-set="<?= e((string)$set['id']) ?>">
          <thead>
            <tr>
              <th class="col-grip"><span class="sr-only">Order</span></th>
              <th class="col-thumb"></th>
              <th class="col-code">Code</th>
              <th>Name</th>
              <th class="col-act"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($miniatures as $m): ?>
              <?php
                $photo = $m['photo'] ? asset($m['photo']) : '';
                $label = repo_mini_label($m);
              ?>
              <tr draggable="true" data-id="<?= e((string)$m['id']) ?>" tabindex="0"
                  data-drawer-open
                  data-code="<?= e($m['code']) ?>" data-name="<?= e($m['name']) ?>"
                  data-photo="<?= e($photo) ?>" data-photo-name="<?= e($m['photo'] ? basename($m['photo']) : '') ?>">
                <td class="col-grip" aria-hidden="true">&#10262;</td>
                <td class="col-thumb">
                  <div class="plate thumb" <?= $photo ? 'style="background-image:url(\'' . e($photo) . '\')"' : '' ?>></div>
                </td>
                <td class="col-code"><?= $m['code'] !== null ? e($m['code']) : '<span class="no-info">No code</span>' ?></td>
                <td class="col-name"><?= $m['name'] !== null ? e($m['name']) : '<span class="no-info">No name</span>' ?></td>
                <td class="col-act">
                  <button type="button" class="icon-btn danger" data-confirm-open
                          data-kind="miniature" data-id="<?= e((string)$m['id']) ?>"
                          data-title="Delete <?= e($label) ?>?"
                          data-body="This removes <?= e($label) ?> from the set for every collector. It cannot be undone."
                          data-submit="Delete miniature"
                          aria-label="Delete <?= e($label) ?>">
                    <span class="msym">delete</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </main>
</div>

<div class="drawer" id="drawer" hidden data-dialog>
  <form class="drawer-panel" method="post" enctype="multipart/form-data"
        action="<?= e(url('admin/miniature/save')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="set_id" value="<?= e((string)$set['id']) ?>">
    <input type="hidden" name="id" value="" data-drawer-id>
    <input type="hidden" name="remove_photo" value="0" data-drawer-remove>

    <div class="drawer-head">
      <div>
        <div class="kicker" data-drawer-kicker>New miniature</div>
        <h3 data-drawer-title>New miniature</h3>
      </div>
      <button type="button" class="btn btn-ghost" data-dialog-close aria-label="Close">&times;</button>
    </div>

    <div class="plate dropzone" data-dropzone tabindex="0" role="button"
         aria-label="Drop a photograph, or click to choose">
      <div class="dropzone-empty" data-dropzone-empty>
        <span class="msym">add_photo_alternate</span>
        <span>Drop a photograph, or click to choose</span>
      </div>
      <div class="dropzone-img" data-dropzone-img hidden></div>
    </div>
    <input type="file" name="photo" accept="image/*" hidden data-drawer-file>

    <div class="photo-row" data-photo-row hidden>
      <span class="photo-name" data-photo-name></span>
      <button type="button" class="icon-btn" data-photo-replace aria-label="Replace photograph">
        <span class="msym">swap_horiz</span>
      </button>
      <button type="button" class="icon-btn danger" data-photo-remove aria-label="Remove photograph">
        <span class="msym">delete</span>
      </button>
    </div>

    <div class="field-row">
      <div class="field">
        <label for="dw-code">Code</label>
        <input class="input" id="dw-code" name="code" data-drawer-code>
      </div>
      <div class="field">
        <label for="dw-name">Name</label>
        <input class="input" id="dw-name" name="name" data-drawer-name>
      </div>
    </div>
    <p class="field-hint">A photograph is required. Code and name are optional.</p>

    <p class="form-error" data-drawer-error hidden>A miniature needs a photograph.</p>

    <div class="form-actions">
      <button type="button" class="btn btn-secondary" data-dialog-close>Cancel</button>
      <button type="submit" class="btn btn-primary">Save miniature</button>
    </div>
  </form>
</div>

<div class="drawer" id="bulk" hidden data-dialog
     data-endpoint="<?= e(url('admin/miniature/save')) ?>"
     data-set="<?= e((string)$set['id']) ?>">
  <div class="drawer-panel">

    <div class="drawer-head">
      <div>
        <div class="kicker">Bulk add</div>
        <h3><?= e($set['code']) ?> <?= e($set['name']) ?></h3>
      </div>
      <button type="button" class="btn btn-ghost" data-dialog-close aria-label="Close">&times;</button>
    </div>

    <div class="plate dropzone" data-bulk-zone tabindex="0" role="button"
         aria-label="Drop photographs, or click to choose">
      <div class="dropzone-empty">
        <span class="msym">add_photo_alternate</span>
        <span>Drop photographs, or click to choose</span>
      </div>
    </div>
    <input type="file" accept="image/*" multiple hidden data-bulk-file>

    <p class="field-hint">
      One miniature per photograph, added to the end of the set. Names are read from the
      filenames where they can be — edit any of them before adding.
    </p>

    <ol class="bulk-list" data-bulk-list></ol>

    <p class="form-error" data-bulk-error hidden></p>

    <div class="form-actions">
      <button type="button" class="btn btn-secondary" data-dialog-close>Cancel</button>
      <button type="button" class="btn btn-primary" data-bulk-start disabled>Add miniatures</button>
    </div>
  </div>
</div>

<?php view('admin/_dialogs'); ?>
