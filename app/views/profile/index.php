<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">My Profile</div>
    <div class="page-subtitle">Manage your account settings</div>
  </div>
</div>

<div class="grid-2col">
  <div class="card">
    <div class="card-header"><div class="card-title">Profile Picture</div></div>
    <div class="card-body" style="text-align:center">
      <?php $picUrl = profilePictureUrl($profile['profile_picture'] ?? null); ?>
      <div class="profile-avatar" style="width:120px;height:120px;border-radius:50%;margin:0 auto 16px;overflow:hidden;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;font-weight:700">
        <?php if ($picUrl): ?>
        <img src="<?= e($picUrl) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
        <?= strtoupper(substr($profile['full_name'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <form method="POST" enctype="multipart/form-data" style="margin-bottom:8px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="profile_action" value="update_picture">
        <div class="form-group" style="margin-bottom:8px">
          <input type="file" name="profile_picture" accept="image/jpeg,image/png" class="form-control" style="padding:6px;font-size:.82rem">
          <div class="form-hint">JPEG or PNG, max 10MB</div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= svgIcon('upload') ?> Upload</button>
      </form>
      <?php if ($picUrl): ?>
      <form method="POST" style="margin-top:4px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="profile_action" value="remove_picture">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove profile picture?')"><?= svgIcon('x') ?> Remove</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Account Information</div></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="profile_action" value="update_profile">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" value="<?= e($profile['full_name']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="<?= e($profile['username']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($profile['email']) ?>" required>
          </div>
          <div class="form-group">
            <label>Mobile</label>
            <input type="text" name="mobile" class="form-control" value="<?= e($profile['mobile']) ?>" required>
          </div>
          <div class="form-group">
            <label>Role</label>
            <input type="text" class="form-control" value="<?= e($profile['role']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Partner</label>
            <input type="text" class="form-control" value="<?= e($profile['partner_name'] ?? 'Neilos Internal') ?>" readonly>
          </div>
          <div class="form-group">
            <label>Last Login</label>
            <input type="text" class="form-control" value="<?= $profile['last_login'] ? fmtDateTime($profile['last_login']) : 'Never' ?>" readonly>
          </div>
          <div class="form-group">
            <label>Member Since</label>
            <input type="text" class="form-control" value="<?= fmtDate($profile['created_at']) ?>" readonly>
          </div>
        </div>
        <div class="divider"></div>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check') ?> Save Changes</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Change Password</div></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="profile_action" value="change_password">
        <div class="form-grid">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="8">
            <div class="form-hint">Minimum 8 characters</div>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
          </div>
        </div>
        <div class="divider"></div>
        <button type="submit" class="btn btn-primary"><?= svgIcon('refresh') ?> Update Password</button>
      </form>
    </div>
  </div>
</div>