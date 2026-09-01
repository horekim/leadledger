<?php
/** @var string $content @var string $title @var string $screen */
$user    = current_user();
$admin   = is_admin();
$message = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title !== '' ? $title . ' — ' . config('site_name') : config('site_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/ds/styles.css')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0">
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

    <?php if ($user): ?>
      <form method="post" action="<?= e(url('sign-out')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="btn btn-ghost">Sign out</button>
      </form>
      <?php if ($admin && $screen === 'admin'): ?>
        <a class="btn btn-secondary" href="<?= e(url('')) ?>">
          <span class="msym" style="font-size:16px">arrow_back</span>Public site
        </a>
      <?php elseif ($admin): ?>
        <a class="btn btn-secondary" href="<?= e(url('admin')) ?>">Admin</a>
      <?php endif; ?>
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
