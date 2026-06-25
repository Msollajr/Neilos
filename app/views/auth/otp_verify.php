<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification — Neilos Partner Portal</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/portal.css">
  <link rel="icon" type="image/png" href="<?= APP_URL ?>/favicon.ico?v=2">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="<?= APP_URL ?>/assets/img/logo.png?v=2" alt="Neilos" class="login-logo-img">
      <div class="login-title">OTP Verification</div>
      <div class="login-subtitle">Enter the 6-digit code sent to your mobile number.</div>
    </div>

    <!-- Simulated OTP display for MVP -->
    <div class="alert alert-info" style="text-align:center">
      <div style="width:100%">
        <div style="font-size:.78rem;margin-bottom:4px">📱 <strong>Simulation Mode</strong> — Your OTP is:</div>
        <div style="font-size:2rem;font-weight:800;letter-spacing:8px;color:var(--primary)"><?= e($_SESSION['current_otp'] ?? '------') ?></div>
        <div style="font-size:.72rem;margin-top:4px;color:var(--text-secondary)">In production, this will be sent via SMS/WhatsApp.</div>
      </div>
    </div>

    <?php if (!empty($otpError)): ?>
    <div class="alert alert-danger"><?= e($otpError) ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label>Enter OTP Code <span class="required">*</span></label>
        <input type="text" name="otp_code" class="form-control" required maxlength="6"
               placeholder="6-digit code" autocomplete="one-time-code"
               style="text-align:center;font-size:1.4rem;letter-spacing:8px;font-weight:700">
      </div>
      <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
    </form>

    <div style="text-align:center;margin-top:16px">
      <a href="<?= APP_URL ?>/?page=otp_verify" style="font-size:.8rem;color:var(--primary)">Resend OTP</a>
      &nbsp;·&nbsp;
      <a href="<?= APP_URL ?>/?page=logout" style="font-size:.8rem;color:var(--text-muted)">Sign Out</a>
    </div>
  </div>
</div>
</body>
</html>
