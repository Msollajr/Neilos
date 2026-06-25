<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password — Neilos Partner Portal</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="<?= APP_URL ?>/assets/img/logo.png?v=2" alt="Neilos" class="login-logo-img">
      <div class="login-title">Set Your Password</div>
      <div class="login-subtitle">This is required before you can continue.</div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div><?php foreach($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    </div>
    <?php endif; ?>

    <form class="login-form" method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label>New Password <span class="required">*</span></label>
        <input type="password" name="new_password" class="form-control" required
               placeholder="Min 8 chars, 1 uppercase, 1 number" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Confirm New Password <span class="required">*</span></label>
        <input type="password" name="confirm_password" class="form-control" required
               placeholder="Re-enter password" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary w-100">Set Password &amp; Continue</button>
    </form>
  </div>
</div>
</body>
</html>
