<?php // Active Service Detail View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($service['service_id']) ?></div>
    <div class="page-subtitle"><?= e($service['service_type']) ?> &middot; <?= e($service['customer_name']) ?> &middot; <?= e($service['partner_name']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=active_services" class="btn btn-secondary"><?= svgIcon('list') ?> All Services</a>
    <a href="<?= APP_URL ?>/?page=tickets&action=create&service_id=<?= $service['id'] ?>" class="btn btn-primary"><?= svgIcon('ticket') ?> New Ticket</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;margin-bottom:24px">
  <div class="card">
    <div class="card-header"><div class="card-title">Service Information</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Service ID</label><div class="font-600"><?= e($service['service_id']) ?></div></div>
        <div class="form-group"><label>Status</label><div><span class="badge badge-<?= $service['status'] === 'Active' ? 'success' : ($service['status'] === 'Suspended' ? 'warning' : 'secondary') ?>" style="font-size:.85rem;padding:6px 14px"><?= e($service['status']) ?></span></div></div>
        <div class="form-group"><label>Customer</label><div><?= e($service['customer_name']) ?></div></div>
        <div class="form-group"><label>Service Type</label><div><span class="badge badge-primary"><?= e($service['service_type']) ?></span></div></div>
        <div class="form-group"><label>Partner</label><div><?= e($service['partner_name']) ?></div></div>
        <div class="form-group"><label>Assigned KAM</label><div><?= e($service['kam_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Circuit ID</label><div><?= e($service['circuit_id'] ?: '—') ?></div></div>
        <div class="form-group"><label>Bandwidth / Capacity</label><div><?= e($service['bandwidth_capacity'] ?: '—') ?></div></div>
        <div class="form-group"><label>Location</label><div><?= e($service['location'] ?: '—') ?></div></div>
        <div class="form-group"><label>Building</label><div><?= e($service['building_name'] ?: '—') ?></div></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Health & Monitoring</div></div>
    <div class="card-body">
      <div style="text-align:center;padding:12px 0">
        <div style="font-size:2rem;font-weight:800;color:<?= $service['monitoring_status'] === 'Online' ? 'var(--success)' : ($service['monitoring_status'] === 'Offline' ? 'var(--danger)' : ($service['monitoring_status'] === 'Degraded' ? 'var(--warning)' : 'var(--text-muted)')) ?>">
          <?= e($service['monitoring_status']) ?>
        </div>
        <div class="text-secondary font-sm" style="margin-top:4px">Monitoring Status</div>
      </div>
      <div class="divider"></div>
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Activation Date</label><div><?= fmtDate($service['activation_date']) ?></div></div>
        <div class="form-group"><label>Billing Start</label><div><?= fmtDate($service['billing_start_date']) ?></div></div>
        <div class="form-group"><label>ONU Serial</label><div class="font-sm"><?= e($service['onu_serial'] ?: '—') ?></div></div>
        <div class="form-group"><label>Router Serial</label><div class="font-sm"><?= e($service['router_serial'] ?: '—') ?></div></div>
        <div class="form-group"><label>IP Address</label><div class="font-sm"><?= e($service['ip_address'] ?: '—') ?></div></div>
      </div>
      <div class="divider"></div>
      <div class="form-group">
        <label>Linked Tickets</label>
        <div class="font-600" style="font-size:1.2rem"><?= $ticketCount ?></div>
      </div>
    </div>
  </div>
</div>

<?php if ($service['notes']): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">Notes</div></div>
  <div class="card-body">
    <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;font-size:.875rem;white-space:pre-wrap"><?= e($service['notes']) ?></div>
  </div>
</div>
<?php endif; ?>

<div class="tabs" data-group="service">
  <button class="tab-btn active" data-tab="assets" data-tab-group="service">Assets (<?= count($assets) ?>)</button>
</div>

<div class="tab-panel active" data-tab-panel="assets" data-tab-group="service">
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Asset Type</th>
            <th>Serial Number</th>
            <th>Model</th>
            <th>Status</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($assets)): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No assets linked</div></div></td></tr>
          <?php else: ?>
          <?php foreach ($assets as $a): ?>
          <tr>
            <td><span class="badge badge-primary"><?= e($a['asset_type']) ?></span></td>
            <td class="font-sm"><?= e($a['serial_number'] ?: '—') ?></td>
            <td class="font-sm"><?= e($a['model'] ?: '—') ?></td>
            <td><span class="badge badge-<?= $a['status'] === 'Deployed' ? 'success' : ($a['status'] === 'Faulty' ? 'danger' : ($a['status'] === 'Returned' ? 'secondary' : 'info')) ?>"><?= e($a['status']) ?></span></td>
            <td class="text-muted font-sm"><?= fmtDate($a['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
