<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Neilos Partner Portal</title>
  <meta name="description" content="Neilos Partner Portal — Secure Login">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css?v=3">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="<?= APP_URL ?>/assets/img/logo.png?v=2" alt="Neilos" class="login-logo-img">
      <div class="login-subtitle">Sign in to your account</div>
    </div>

    <?php if ($loginError): ?>
    <div class="alert alert-danger">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?= e($loginError) ?></span>
    </div>
    <?php endif; ?>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
      <span><?= e($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <form class="login-form" method="POST" action="<?= APP_URL ?>/?page=login">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="username">Username or Email</label>
        <input type="text" id="username" name="username" class="form-control" required
               value="<?= e($_POST['username'] ?? '') ?>" placeholder="Enter your username"
               autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control" required
                 placeholder="Enter your password" autocomplete="current-password"
                 style="border-radius:var(--radius-sm) 0 0 var(--radius-sm)">
          <button type="button" class="input-addon" onclick="togglePwd()" style="cursor:pointer;border-left:none;border-radius:0 var(--radius-sm) var(--radius-sm) 0" title="Show/hide password">
            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Sign In
      </button>
    </form>

    <div class="divider"></div>
    <div style="text-align:center;font-size:.78rem;color:var(--text-muted)">
      Demo credentials: <strong>admin / password</strong> | <strong>savanna / password</strong>
    </div>
    <div style="text-align:center;font-size:.72rem;color:var(--text-muted);margin-top:20px">
      &copy; <?= date('Y') ?> Neilos Network · Partner Portal v1.0
    </div>
  </div>
</div>
<script>
function togglePwd() {
  var p = document.getElementById('password');
  p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
