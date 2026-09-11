<?php
/**
 * The want ad. Every miniature on the hunt, across the whole archive, grouped
 * under its genre — a page made to be pasted into a forum post, so it reads
 * to someone who has never seen the collection.
 *
 * Nothing here is dimmed: every card is unowned by definition, so the set
 * page's recessive treatment would push the entire page back. The photographs
 * are the ask.
 *
 * Its mirror is views/public/trade.php, which shares this page's layout and
 * differs only in what it is asking for.
 *
 * @var array  $wanted  the miniatures sought, already sorted
 * @var string $density the grid density carried over from the set pages
 * @var string $contact the address to write to
 */
$canEdit = is_admin(); // one hunt: everyone reads it, an admin changes it
$total   = count($wanted);
?>
<main class="page is-ad">

  <div class="page-head ruled ad-head">
    <div>
      <h1>Wanted</h1>
      <p class="page-blurb">
        Miniatures I am still missing, across every range. Contact me at
        <a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>
        if you have any of these for sale or trade.
      </p>
    </div>
    <div class="ad-tally">
      <div class="ad-tally-num"><?= e(num($total)) ?></div>
      <div class="stat-lbl">Miniatures sought</div>
    </div>
  </div>

  <?php if (!$wanted): ?>
    <div class="empty-state ad-empty">
      <strong>Nothing on the hunt</strong>
      <span>Mark a miniature with the crosshair anywhere in the collection and it turns up here.</span>
      <a class="btn btn-secondary" href="<?= e(url('')) ?>">Browse the collection</a>
    </div>
  <?php else: ?>
    <?php foreach (repo_minis_by_category($wanted) as $group): ?>
      <section class="ad-genre">
        <h2 class="category-head"><?= e($group['label']) ?></h2>

        <div class="mini-grid ad-grid" data-density="<?= e($density) ?>">
          <?php foreach ($group['items'] as $m): ?>
            <div class="card mini-card" data-mini="<?= e((string)$m['id']) ?>">
              <?php view('public/_plate', ['m' => $m, 'canEdit' => $canEdit]) ?>

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

<?php /* The one place the accent runs as a field, per the design system. */ ?>
<section class="ad-band">
  <div class="ad-poster">
    <div>
      <h2>Got one of these?</h2>
      <p>Send the code and a photograph. I will pay postage either way, and I
         trade from the duplicates drawer.</p>
    </div>
    <a class="ad-mail" href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>
  </div>
</section>
