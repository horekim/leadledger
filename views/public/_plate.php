<?php
/**
 * A miniature's photograph and its two controls — the owned tick and the
 * crosshair. Shared by the set grid and the wanted page, which draw the same
 * card and differ only in the caption beneath it.
 *
 * Expects the card wrapper (.mini-card, .is-owned) to be drawn by the caller;
 * this is only what sits inside it, above the caption.
 *
 * @var array $m       a miniature row, carrying 'owned' and 'wanted'
 * @var bool  $canEdit whether this visitor may change either state
 */
$photo = $m['photo'] ? asset($m['photo']) : null;
$what  = repo_mini_label($m);
$label = ($m['owned'] ? 'Owned — ' : 'Not owned — ') . $what;
?>
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
