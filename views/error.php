<?php /** @var string $heading @var string $body */ ?>
<main class="page">
  <div class="empty-state" style="margin-top:40px">
    <strong><?= e($heading) ?></strong>
    <span><?= e($body) ?></span>
  </div>
  <p style="margin-top:24px"><a href="<?= e(url('')) ?>">&larr; Back to the archive</a></p>
</main>
