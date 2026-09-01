<?php
/** Sign in / create account. @var string $mode @var string $error @var array $old */
$isUp = $mode === 'up';
?>
<div class="auth-shell">

  <div class="auth-form">
    <div class="kicker"><?= $isUp ? 'Join the archive' : 'Account' ?></div>
    <h1><?= $isUp ? 'Create an account' : 'Sign in' ?></h1>
    <p class="auth-blurb">
      <?= $isUp
        ? 'An account is what turns the catalogue into your ledger — it remembers which miniatures you own.'
        : 'Sign in to tick what you own. Your collection is yours alone; nothing in the archive shows who owns what.' ?>
    </p>

    <div class="segmented auth-tabs" role="group" aria-label="Account">
      <a class="<?= $isUp ? '' : 'is-active' ?>" href="<?= e(url('sign-in')) ?>">Sign in</a>
      <a class="<?= $isUp ? 'is-active' : '' ?>" href="<?= e(url('sign-in?mode=up')) ?>">Create account</a>
    </div>

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
          <a href="<?= e(url('sign-in?mode=up')) ?>">Forgotten password</a>
        </div>
      <?php else: ?>
        <input type="hidden" name="remember" value="1">
      <?php endif; ?>

      <div class="auth-submit">
        <button type="submit" class="btn btn-primary btn-block"><?= $isUp ? 'Create account' : 'Sign in' ?></button>
      </div>
    </form>

    <p class="auth-switch">
      <?php if ($isUp): ?>
        Already have one? <a href="<?= e(url('sign-in')) ?>">Sign in</a>
      <?php else: ?>
        No account yet? <a href="<?= e(url('sign-in?mode=up')) ?>">Create one</a>
      <?php endif; ?>
    </p>
  </div>

  <aside class="auth-aside">
    <div class="auth-aside-label">What an account gives you</div>
    <div class="auth-benefit">
      <strong>A ledger, not a wishlist</strong>
      <span>Tick a miniature once and it stays ticked — every set page then shows your collection against the full range.</span>
    </div>
    <div class="auth-benefit">
      <strong>The gaps, at a glance</strong>
      <span>Unowned miniatures sit back to a faint grey. A set page reads as a map of what is still missing.</span>
    </div>
    <div class="auth-benefit">
      <strong>Yours and private</strong>
      <span>Ownership is recorded against your account alone. Nothing in the archive exposes who owns what.</span>
    </div>
  </aside>

</div>
