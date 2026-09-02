<?php
/**
 * One range's sets — the table shared by the all-ranges list and a range page.
 * @var array $range
 */
?>
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
