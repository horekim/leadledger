<?php
/** Sign in / create account. @var string $mode @var string $error @var array $old */
$isUp   = $mode === 'up';
$signup = auth_accepts_signup(); // only while the archive has no owner yet
?>
<div class="auth-shell">

  <div class="auth-form">
    <div class="kicker"><?= $isUp ? 'Join the archive' : 'Account' ?></div>
    <h1><?= $isUp ? 'Create an account' : 'Sign in' ?></h1>
    <p class="auth-blurb">
      <?= $isUp
        ? 'This first account owns the catalogue — it can add ranges, sets and miniatures, and tick what is in the collection.'
        : 'Sign in to maintain the archive: add ranges, sets and miniatures, and tick what is in the collection.' ?>
    </p>

    <?php if ($signup): ?>
      <div class="segmented auth-tabs" role="group" aria-label="Account">
        <a class="<?= $isUp ? '' : 'is-active' ?>" href="<?= e(url('sign-in')) ?>">Sign in</a>
        <a class="<?= $isUp ? 'is-active' : '' ?>" href="<?= e(url('sign-in?mode=up')) ?>">Create account</a>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('sign-in')) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="mode" value="<?= $isUp ? 'up' : 'in' ?>">

      <div class="auth-fields">
        <?php if ($isUp): ?>
          <div class="field">
            <label for="f-name">Collector name</label>
            <input class="input" id="f-name" name="name" autocomplete="name" required
                   value="<?= e($old['name'] ?? '') ?>">
          </div>
        <?php endif; ?>

        <div class="field">
          <label for="f-email">Email</label>
          <input class="input" id="f-email" name="email" type="email" autocomplete="email" required
                 value="<?= e($old['email'] ?? '') ?>">
        </div>

        <div class="field">
          <label for="f-pass">Password</label>
          <input class="input" id="f-pass" name="password" type="password" required
                 autocomplete="<?= $isUp ? 'new-password' : 'current-password' ?>">
          <?php if ($isUp): ?><div class="field-hint">Eight characters or more.</div><?php endif; ?>
        </div>
      </div>

      <?php if ($error !== ''): ?>
        <p class="form-error" role="alert"><?= e($error) ?></p>
      <?php endif; ?>

      <?php if (!$isUp): ?>
        <div class="auth-line">
          <label class="auth-keep">
            <input type="checkbox" name="remember" value="1" checked> Keep me signed in
          </label>
        </div>
      <?php else: ?>
        <input type="hidden" name="remember" value="1">
      <?php endif; ?>

      <div class="auth-submit">
        <button type="submit" class="btn btn-primary btn-block"><?= $isUp ? 'Create account' : 'Sign in' ?></button>
      </div>
    </form>

    <?php if ($signup): ?>
      <p class="auth-switch">
        <?php if ($isUp): ?>
          Already have one? <a href="<?= e(url('sign-in')) ?>">Sign in</a>
        <?php else: ?>
          No account yet? <a href="<?= e(url('sign-in?mode=up')) ?>">Create one</a>
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <aside class="auth-aside">
    <div class="auth-aside-label">What signing in gives you</div>
    <div class="auth-benefit">
      <strong>The catalogue</strong>
      <span>Add and edit ranges, sets and miniatures, reorder a set by hand, and photograph the collection into it.</span>
    </div>
    <div class="auth-benefit">
      <strong>A ledger, not a wishlist</strong>
      <span>Tick a miniature once and it stays ticked — every set page then shows the collection against the full range.</span>
    </div>
    <div class="auth-benefit">
      <strong>The gaps, at a glance</strong>
      <span>Unowned miniatures sit back to a faint grey, so a set page reads as a map of what is still missing.</span>
    </div>
  </aside>

</div>
