<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($partner['name']) ?></div>
    <div class="page-subtitle"><?= e($partner['partner_type']) ?> · <?= e($partner['status']) ?> · Created <?= fmtDate($partner['created_at']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary"><?= svgIcon('list') ?> All Partners</a>
    <a href="<?= APP_URL ?>/?page=partners&action=edit&id=<?= $partner['id'] ?>" class="btn btn-primary"><?= svgIcon('edit') ?> Edit Partner</a>
    <?php if (isAdmin() || hasRole('Management')): ?>
    <form method="POST" action="<?= APP_URL ?>/?page=partners&action=delete" style="display:inline" data-confirm="Permanently delete partner <?= e($partner['name']) ?>?">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $partner['id'] ?>">
      <button type="submit" class="btn btn-danger"><?= svgIcon('trash') ?> Delete Partner</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Stats row -->
<div class="stats-grid mb-24">
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= $userCount ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Linked Users</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= $orderCount ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Service Orders</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= e($partner['partner_type']) ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Partner Type</div>
    </div>
  </div>
</div>

<div class="grid-2col mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title">General Information</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Partner Name</label><div class="font-600"><?= e($partner['name']) ?></div></div>
        <div class="form-group"><label>Trading Name</label><div><?= e($partner['trading_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Partner Type</label><div><span class="badge badge-primary"><?= e($partner['partner_type']) ?></span></div></div>
        <div class="form-group"><label>Assigned KAM</label><div class="font-600" style="color:var(--primary)"><?= e($partner['assigned_kam_name'] ?: 'Not Assigned') ?></div></div>
        <div class="form-group"><label>Status</label><div>
          <?php if ($partner['status'] === 'Active'): ?>
          <span class="badge badge-success">Active</span>
          <?php elseif ($partner['status'] === 'Inactive'): ?>
          <span class="badge badge-secondary">Inactive</span>
          <?php else: ?>
          <span class="badge badge-danger">Suspended</span>
          <?php endif; ?>
        </div></div>
        <div class="form-group"><label>Registration Number</label><div><?= e($partner['registration_number'] ?: '—') ?></div></div>
        <div class="form-group"><label>TIN</label><div><?= e($partner['tin'] ?: '—') ?></div></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Location</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Address</label><div><?= e($partner['address'] ?: '—') ?></div></div>
        <div class="form-group"><label>City / Region</label><div><?= e($partner['city_region'] ?: '—') ?></div></div>
        <div class="form-group"><label>Country</label><div><?= e($partner['country']) ?></div></div>
      </div>
    </div>
  </div>
</div>

<?php if ($partner['created_at'] !== $partner['updated_at']): ?>
<div class="card">
  <div class="card-body" style="padding:14px 18px;font-size:.82rem;color:var(--text-muted)">
    Created <?= fmtDateTime($partner['created_at']) ?> · Last updated <?= fmtDateTime($partner['updated_at']) ?>
  </div>
</div>
<?php endif; ?>
