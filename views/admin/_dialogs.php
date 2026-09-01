<?php /** The range/set editor and the delete confirmation, shared by both admin screens. */ ?>

<div class="dialog-backdrop" id="editor" hidden data-dialog>
  <form class="dialog" method="post" action="<?= e(url('admin/save')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="kind" value="range" data-editor-kind>
    <input type="hidden" name="id" value="" data-editor-id>
    <input type="hidden" name="range_id" value="" data-editor-range>

    <div class="dialog-title" data-editor-title>Edit range</div>

    <div class="field" data-editor-code-field hidden>
      <label for="ed-code">Code</label>
      <input class="input" id="ed-code" name="code" data-editor-code>
    </div>

    <div class="field">
      <label for="ed-name">Name</label>
      <input class="input" id="ed-name" name="name" data-editor-name required>
    </div>

    <div class="dialog-danger" data-editor-danger hidden>
      <button type="button" class="btn btn-ghost" data-editor-delete>
        <span class="msym" style="font-size:16px">delete</span>Delete
      </button>
    </div>

    <div class="dialog-actions">
      <button type="button" class="btn btn-secondary" data-dialog-close>Cancel</button>
      <button type="submit" class="btn btn-primary" data-editor-submit>Save</button>
    </div>
  </form>
</div>

<div class="dialog-backdrop confirm" id="confirm" hidden data-dialog>
  <form class="dialog" method="post" action="<?= e(url('admin/delete')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="kind" value="" data-confirm-kind>
    <input type="hidden" name="id" value="" data-confirm-id>

    <div class="dialog-title" data-confirm-title>Delete?</div>
    <div class="dialog-body" data-confirm-body></div>

    <div class="dialog-actions">
      <button type="button" class="btn btn-secondary" data-dialog-close>Cancel</button>
      <button type="submit" class="btn btn-primary" data-confirm-submit>Delete</button>
    </div>
  </form>
</div>
