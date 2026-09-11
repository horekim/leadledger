<?php
/** @var string $content @var string $title @var string $screen */
$user    = current_user();
$admin   = is_admin();
$message = flash();

// The nav is the public site's own. Admin swaps it for a way back out, and
// the auth page shows neither — there is one thing to do on it.
$publicNav = in_array($screen, ['browse', 'wanted', 'trade'], true);
$navLink   = static fn(bool $on): string => 'nav-link' . ($on ? ' is-active' : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title !== '' ? $title . ' — ' . config('site_name') : config('site_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/ds/styles.css')) ?>">
<?php /* display=block: the icon glyphs are ligatures, so without it the raw
        ligature text ("chevron_right", "delete") paints as words until the font
        arrives. Blocking hides them instead, then swaps the glyphs in. */ ?>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0&display=block">
<link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-url" content="<?= e(url('')) ?>">
</head>
<body>

<header class="app-header">
  <div class="app-header-in">
    <a class="brand" href="<?= e(url('')) ?>">
      <span class="brand-mark">Lead Ledger</span>
      <span class="brand-sub">Oldhammer archive</span>
    </a>

    <?php if ($publicNav): ?>
      <nav class="app-nav" aria-label="Sections">
        <a class="<?= e($navLink($screen === 'browse')) ?>" href="<?= e(url('')) ?>"
           <?= $screen === 'browse' ? 'aria-current="page"' : '' ?>>Collection</a>
        <?php /* The count travels with the label: what is sought and what is
                 spare are the two numbers worth carrying on every page. */ ?>
        <a class="<?= e($navLink($screen === 'wanted')) ?>" href="<?= e(url('wanted')) ?>"
           <?= $screen === 'wanted' ? 'aria-current="page"' : '' ?>>Wanted<span
           class="nav-count"><?= e(num(repo_wanted_total())) ?></span></a>
        <a class="<?= e($navLink($screen === 'trade')) ?>" href="<?= e(url('for-trade')) ?>"
           <?= $screen === 'trade' ? 'aria-current="page"' : '' ?>>For trade<span
           class="nav-count"><?= e(num(repo_trade_total())) ?></span></a>
        <?php if ($admin): ?>
          <span class="nav-div" aria-hidden="true"></span>
          <a class="nav-link" href="<?= e(url('admin')) ?>">Admin</a>
        <?php endif; ?>
      </nav>
    <?php elseif ($screen === 'admin'): ?>
      <nav class="app-nav" aria-label="Sections">
        <a class="nav-back" href="<?= e(url('')) ?>">
          <span class="msym" style="font-size:15px">arrow_back</span>Public site
        </a>
        <span class="nav-div" aria-hidden="true"></span>
        <a class="nav-link is-active" href="<?= e(url('admin')) ?>" aria-current="page">Admin</a>
      </nav>
    <?php endif; ?>

    <?php /* Signing out is the rarest thing on the site, so it is a quiet
             link rather than a button competing with the nav. */ ?>
    <?php if ($user): ?>
      <form method="post" action="<?= e(url('sign-out')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="link-quiet">Sign out</button>
      </form>
    <?php elseif ($screen !== 'auth'): ?>
      <a class="btn btn-secondary" href="<?= e(url('sign-in')) ?>">Sign in</a>
    <?php endif; ?>
  </div>
</header>

<?php if ($message): ?>
  <div class="flash"><?= e($message) ?></div>
<?php endif; ?>

<?= $content ?>

<script src="<?= e(asset('assets/app.js')) ?>" defer></script>
</body>
</html>
