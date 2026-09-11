<?php
/**
 * The trade list. Every miniature I have spare, across the whole archive,
 * grouped under its genre — the want ad's counterpart, and pasted into the
 * same forum posts and messages.
 *
 * Everything here is the wanted page with substitutions, per the handoff, so
 * the two share their layout wholesale and differ only in copy and controls.
 *
 * Every card is owned, so it carries .is-owned and the photograph runs at
 * full strength. The plate carries only the swap: no tick and no crosshair,
 * because un-owning something off the trade list is not an action anyone
 * wants by accident.
 *
 * @var array  $trade   the miniatures on offer, already sorted
 * @var string $density the grid density carried over from the set pages
 * @var string $contact the address to write to
 */
$canEdit = is_admin(); // one drawer: everyone reads it, an admin changes it
$total   = count($trade);
?>
<main class="page is-ad">

  <div class="page-head ruled ad-head">
    <div>
      <h1>For trade</h1>
      <p class="page-blurb">
        Duplicates and spares from the drawer, all of them available. Contact me at
        <a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>
        if you want any of these — I trade, and I sell.
      </p>
    </div>
    <div class="ad-tally">
      <div class="ad-tally-num"><?= e(num($total)) ?></div>
      <div class="stat-lbl">Miniatures offered</div>
    </div>
  </div>

  <?php if (!$trade): ?>
    <div class="empty-state ad-empty">
      <strong>Nothing in the trade drawer</strong>
      <span>Tick the swap icon on anything you own and it is listed here as available.</span>
      <a class="btn btn-secondary" href="<?= e(url('')) ?>">Browse the collection</a>
    </div>
  <?php else: ?>
    <?php foreach (repo_minis_by_category($trade) as $group): ?>
      <section class="ad-genre">
        <h2 class="category-head"><?= e($group['label']) ?></h2>

        <div class="mini-grid ad-grid" data-density="<?= e($density) ?>">
          <?php foreach ($group['items'] as $m): ?>
            <div class="card mini-card is-owned" data-mini="<?= e((string)$m['id']) ?>">
              <?php view('public/_plate', ['m' => $m, 'canEdit' => $canEdit, 'controls' => 'trade']) ?>

              <?php /* Code and name are both optional; the set line never is —
                       it is what tells a stranger which casting this is. */ ?>
              <div class="mini-caption">
                <?php if ($m['code'] !== null): ?><span class="mini-code"><?= e($m['code']) ?></span><?php endif; ?>
                <?php if ($m['name'] !== null): ?><span class="mini-name"><?= e($m['name']) ?></span><?php endif; ?>
                <span class="mini-set"><?= e($m['set_code']) ?> · <?= e($m['set_name']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

</main>

<?php /* The same accent band as the want ad — red runs as a field in this one
         place on either page, per the design system. */ ?>
<section class="ad-band">
  <div class="ad-poster">
    <div>
      <h2>Want one of these?</h2>
      <p>Send the code and what you have to swap. Straight sales are fine too —
         everything here is a spare.</p>
    </div>
    <a class="ad-mail" href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>
  </div>
</section>
