<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($asset['asset_type']) ?> — <?= e($asset['serial_number']) ?></div>
    <div class="page-subtitle">Asset inventory detail</div>
  </div>
  <div class="page-header-actions">
    <a href="?page=assets" class="btn btn-secondary"><?= svgIcon('list') ?> All Assets</a>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Asset Details</div></div>
  <div class="card-body">
    <div class="form-grid form-grid-2">
      <div class="form-group"><label>Type</label><div><span class="badge badge-secondary"><?= e($asset['asset_type']) ?></span></div></div>
      <div class="form-group"><label>Serial Number</label><div class="font-600"><?= e($asset['serial_number']) ?></div></div>
      <div class="form-group"><label>Model</label><div><?= e($asset['model'] ?: '—') ?></div></div>
      <div class="form-group"><label>Status</label><div><span class="badge <?= $asset['status'] === 'Deployed' ? 'badge-success' : ($asset['status'] === 'Faulty' ? 'badge-danger' : 'badge-info') ?>"><?= e($asset['status']) ?></span></div></div>
      <div class="form-group"><label>Customer</label><div><?= e($asset['customer_name'] ?: '—') ?></div></div>
      <div class="form-group"><label>Partner</label><div><?= e($asset['partner_name'] ?: '—') ?></div></div>
      <div class="form-group"><label>Site Location</label><div><?= e($asset['site_location'] ?: '—') ?></div></div>
      <div class="form-group"><label>Linked Service</label><div><?= e($asset['svc_id'] ?: '—') ?></div></div>
      <?php if ($asset['notes']): ?>
      <div class="form-group form-col-full"><label>Notes</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;font-size:.875rem"><?= e($asset['notes']) ?></div></div>
      <?php endif; ?>
      <div class="form-group"><label>Created</label><div class="text-muted font-sm"><?= fmtDateTime($asset['created_at']) ?></div></div>
      <div class="form-group"><label>Last Updated</label><div class="text-muted font-sm"><?= fmtDateTime($asset['updated_at']) ?></div></div>
    </div>
  </div>
</div>
