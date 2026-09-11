<?php
/**
 * A miniature's photograph and its controls — the owned tick, the crosshair
 * and the swap. Shared by the set grid, the wanted page and the for-trade
 * page, which draw the same card and differ only in the caption beneath it.
 *
 * The crosshair and the swap never show together: the hunt only means
 * something while a miniature is unowned, an offer only while it is owned. So
 * they share one slot above the tick, and ownership decides which is in it.
 *
 * Expects the card wrapper (.mini-card, .is-owned) to be drawn by the caller;
 * this is only what sits inside it, above the caption.
 *
 * @var array  $m        a miniature row, carrying 'owned', 'wanted' and 'trade'
 * @var bool   $canEdit  whether this visitor may change any of them
 * @var string $controls 'all', or 'trade' for the for-trade page — see below
 */
$photo    = $m['photo'] ? asset($m['photo']) : null;
$what     = repo_mini_label($m);
$label    = ($m['owned'] ? 'Owned — ' : 'Not owned — ') . $what;
$trade    = !empty($m['trade']);
$controls = $controls ?? 'all';

/* Static markup, so built once here rather than repeated down the branches. */
$svgTick  = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
          . ' stroke-width="3" stroke-linecap="square" aria-hidden="true">'
          . '<path d="M5 12.5 10 17.5 19 7"/></svg>';
$svgCross = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
          . ' stroke-width="2.4" stroke-linecap="square" aria-hidden="true">'
          . '<circle cx="12" cy="12" r="7"/><path d="M12 1v3 M12 20v3 M1 12h3 M20 12h3"/></svg>';
$svgSwap  = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
          . ' stroke-width="2.4" stroke-linecap="square" aria-hidden="true">'
          . '<path d="M3 7h15l-4-4M21 17H6l4 4"/></svg>';

$photoSpan = '<span class="mini-photo' . ($photo ? '' : ' is-blank') . '"'
           . ($photo ? ' style="background-image:url(\'' . e($photo) . '\')"' : '') . '></span>';
$tradeBar  = $trade ? '<span class="trade-strip" aria-hidden="true">For trade</span>' : '';
$wantBar   = $m['wanted'] ? '<span class="want-strip" aria-hidden="true">Wanted</span>' : '';
?>
<?php if ($controls === 'trade'): ?>
  <?php /* The for-trade page carries the swap and nothing else. The plate is
           not pressable there and there is no tick: un-owning something off
           the trade list is not an action anyone wants by accident. */ ?>
  <div class="plate mini-plate is-static"><?= $photoSpan . $tradeBar ?></div>
  <?php if ($canEdit): ?>
    <form method="post" action="<?= e(url('api/trade/' . $m['id'])) ?>" data-trade>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="trade" value="<?= $trade ? '0' : '1' ?>" data-trade-field>
      <button type="submit" class="trade" aria-pressed="<?= $trade ? 'true' : 'false' ?>"
              title="<?= e($trade ? 'Not for trade any more' : 'I have this for trade') ?>"
              aria-label="<?= e(($trade ? 'Not for trade: ' : 'Offer for trade: ') . $what) ?>">
        <?= $svgSwap ?>
      </button>
    </form>
  <?php elseif ($trade): ?>
    <span class="trade is-static" role="img" aria-label="For trade"><?= $svgSwap ?></span>
  <?php endif; ?>

<?php elseif ($canEdit): ?>
  <form method="post" action="<?= e(url('api/collection/' . $m['id'])) ?>" data-toggle>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="owned" value="<?= $m['owned'] ? '0' : '1' ?>" data-owned-field>
    <button type="submit" class="plate mini-plate" aria-label="<?= e($label) ?>">
      <?= $photoSpan . $wantBar . $tradeBar ?>
    </button>
    <button type="submit" class="tick" aria-pressed="<?= $m['owned'] ? 'true' : 'false' ?>"
            aria-label="<?= e(($m['owned'] ? 'Mark not owned: ' : 'Mark owned: ') . $what) ?>">
      <?= $svgTick ?>
    </button>
  </form>

  <?php /* Both are always rendered, hidden by CSS on the wrong side of the
           owned tick. Rendering them conditionally meant ticking had nothing
           to reveal, since the markup was never there. */ ?>
  <form method="post" action="<?= e(url('api/wanted/' . $m['id'])) ?>" data-want>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="wanted" value="<?= $m['wanted'] ? '0' : '1' ?>" data-wanted-field>
    <button type="submit" class="want" aria-pressed="<?= $m['wanted'] ? 'true' : 'false' ?>"
            title="<?= e($m['wanted'] ? 'Stop looking for this' : 'I am looking for this') ?>"
            aria-label="<?= e(($m['wanted'] ? 'Stop looking for: ' : 'Look for: ') . $what) ?>">
      <?= $svgCross ?>
    </button>
  </form>

  <form method="post" action="<?= e(url('api/trade/' . $m['id'])) ?>" data-trade>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="trade" value="<?= $trade ? '0' : '1' ?>" data-trade-field>
    <button type="submit" class="trade" aria-pressed="<?= $trade ? 'true' : 'false' ?>"
            title="<?= e($trade ? 'Not for trade any more' : 'I have this for trade') ?>"
            aria-label="<?= e(($trade ? 'Not for trade: ' : 'Offer for trade: ') . $what) ?>">
      <?= $svgSwap ?>
    </button>
  </form>

<?php else: ?>
  <?php /* Read-only. A badge shows only where it means something —
           an empty square nobody can press is an affordance that lies. */ ?>
  <div class="plate mini-plate is-static"><?= $photoSpan . $wantBar . $tradeBar ?></div>
  <?php if ($m['owned']): ?>
    <span class="tick is-static" role="img" aria-label="Owned"><?= $svgTick ?></span>
  <?php endif; ?>
  <?php if ($m['wanted']): ?>
    <span class="want is-static" role="img" aria-label="Wanted"><?= $svgCross ?></span>
  <?php endif; ?>
  <?php if ($trade): ?>
    <span class="trade is-static" role="img" aria-label="For trade"><?= $svgSwap ?></span>
  <?php endif; ?>
<?php endif; ?>
