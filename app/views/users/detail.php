<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($profile['full_name']) ?></div>
    <div class="page-subtitle"><?= e($profile['role']) ?> · <?= e($profile['username']) ?> · <?= $profile['is_active'] ? 'Active' : 'Inactive' ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=users" class="btn btn-secondary"><?= svgIcon('list') ?> All Users</a>
    <a href="<?= APP_URL ?>/?page=users&action=edit&id=<?= $profile['id'] ?>" class="btn btn-primary"><?= svgIcon('edit') ?> Edit User</a>
    <form method="POST" action="<?= APP_URL ?>/?page=users&action=reset_password" style="display:inline" onsubmit="return confirm('Reset password for <?= e($profile['full_name']) ?> to Chang3Me!?')">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $profile['id'] ?>">
      <button type="submit" class="btn btn-secondary"><?= svgIcon('refresh') ?> Reset Password</button>
    </form>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:24px">
  <div class="card">
    <div class="card-header"><div class="card-title">User Information</div></div>
    <div class="card-body">
      <div style="display:flex;gap:20px;align-items:flex-start;margin-bottom:16px">
        <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#fff;font-weight:700;flex-shrink:0">
          <?php $upic = profilePictureUrl($profile['profile_picture'] ?? null); ?>
          <?php if ($upic): ?>
          <img src="<?= e($upic) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
          <?= strtoupper(substr($profile['full_name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div>
          <div style="font-weight:700;font-size:1.1rem"><?= e($profile['full_name']) ?></div>
          <div style="font-size:.85rem;color:var(--text-secondary);margin-top:2px"><?= e($profile['role']) ?> · <?= $profile['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?></div>
          <div style="font-size:.82rem;color:var(--text-muted);margin-top:2px"><?= e($profile['username']) ?> · <?= e($profile['email']) ?></div>
        </div>
      </div>
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Mobile</label><div><?= e($profile['mobile'] ?: '—') ?></div></div>
        <div class="form-group"><label>Partner</label><div><?= e($profile['partner_name'] ?: '— (Internal)') ?></div></div>
        <div class="form-group"><label>First Login</label><div><?= $profile['is_first_login'] ? '<span class="badge badge-warning">Required</span>' : '<span class="badge badge-success">Completed</span>' ?></div></div>
        <div class="form-group"><label>OTP Verified</label><div><?= $profile['otp_verified'] ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-secondary">Not Verified</span>' ?></div></div>
        <div class="form-group"><label>Last Login</label><div><?= $profile['last_login'] ? fmtDateTime($profile['last_login']) : 'Never' ?></div></div>
        <div class="form-group"><label>Created By</label><div><?= e($profile['created_by_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Created At</label><div><?= fmtDateTime($profile['created_at']) ?></div></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Activity Log</div></div>
    <div class="card-body" style="padding:0">
      <?php if (empty($auditLogs)): ?>
      <div class="empty-state" style="padding:32px"><div class="empty-state-title">No activity recorded</div></div>
      <?php else: ?>
      <div style="max-height:400px;overflow-y:auto">
        <?php foreach ($auditLogs as $log): ?>
        <div style="padding:10px 16px;border-bottom:1px solid var(--border);font-size:.82rem">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span><?= e($log['action']) ?></span>
            <span class="text-muted" style="font-size:.75rem"><?= fmtDateTime($log['created_at']) ?></span>
          </div>
          <?php if ($log['module']): ?>
          <div style="color:var(--text-muted);margin-top:2px">
            Module: <?= e($log['module']) ?> · Record: <?= (int)$log['record_id'] ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
