<?php
$caps = [
    ['cap_ftth_install',       'FTTH Installation'],
    ['cap_sme_install',        'SME Installation'],
    ['cap_enterprise_install', 'Enterprise Installation'],
    ['cap_site_survey',        'Site Survey'],
    ['cap_maintenance',        'Maintenance & Support'],
    ['cap_remote_support',     'Remote Hands Support'],
];
$capabilities = array_filter($caps, fn($c) => !empty($contractor[$c[0]]));
?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($contractor['name']) ?></div>
    <div class="page-subtitle">Contractor · <?= e($contractor['partner_type']) ?> · Since <?= fmtDate($contractor['created_at']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=contractors" class="btn btn-secondary"><?= svgIcon('list') ?> All Contractors</a>
    <a href="<?= APP_URL ?>/?page=contractors&action=edit&id=<?= $contractor['id'] ?>" class="btn btn-primary"><?= svgIcon('edit') ?> Edit Contractor</a>
    <?php if (isAdmin() || hasRole('Management')): ?>
    <form method="POST" action="<?= APP_URL ?>/?page=contractors&action=delete" style="display:inline" data-confirm="Permanently delete contractor <?= e($contractor['name']) ?>?">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $contractor['id'] ?>">
      <button type="submit" class="btn btn-danger"><?= svgIcon('trash') ?> Delete Contractor</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Stats row -->
<div class="stats-grid mb-24">
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= $stats['total'] ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Total Jobs</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--warning, #f59e0b)"><?= $stats['active'] ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Active Jobs</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--success)"><?= $stats['completed'] ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Completed</div>
    </div>
  </div>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:2rem;font-weight:700;color:var(--primary)"><?= count($users) ?></div>
      <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px">Linked Users</div>
    </div>
  </div>
</div>

<div class="grid-2col mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title">General Information</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Company Name</label><div class="font-600"><?= e($contractor['name']) ?></div></div>
        <div class="form-group"><label>Trading Name</label><div><?= e($contractor['trading_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Type</label><div><span class="badge badge-primary"><?= e($contractor['partner_type']) ?></span></div></div>
        <div class="form-group"><label>Status</label><div>
          <?php if ($contractor['status'] === 'Active'): ?>
          <span class="badge badge-success">Active</span>
          <?php elseif ($contractor['status'] === 'Inactive'): ?>
          <span class="badge badge-secondary">Inactive</span>
          <?php else: ?>
          <span class="badge badge-danger">Suspended</span>
          <?php endif; ?>
        </div></div>
        <div class="form-group"><label>Registration Number</label><div><?= e($contractor['registration_number'] ?: '—') ?></div></div>
        <div class="form-group"><label>TIN</label><div><?= e($contractor['tin'] ?: '—') ?></div></div>
        <div class="form-group"><label>VAT / VRN</label><div><?= e($contractor['vat_vrn'] ?: '—') ?></div></div>
        <?php if ($contractor['industry_sector']): ?><div class="form-group"><label>Industry Sector</label><div><?= e($contractor['industry_sector']) ?></div></div><?php endif; ?>
        <?php if ($contractor['customer_category']): ?><div class="form-group"><label>Customer Category</label><div><?= e($contractor['customer_category']) ?></div></div><?php endif; ?>
      </div>
      <?php if ($contractor['nature_of_business']): ?>
      <div class="divider"></div>
      <div class="form-group"><label>Nature of Business</label><div><?= e($contractor['nature_of_business']) ?></div></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Location & KYC</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Address</label><div><?= e($contractor['address'] ?: '—') ?></div></div>
        <div class="form-group"><label>City / Region</label><div><?= e($contractor['city_region'] ?: '—') ?></div></div>
        <div class="form-group"><label>Country</label><div><?= e($contractor['country']) ?></div></div>
        <div class="form-group"><label>KYC Status</label><div>
          <?php $kycClass = match($contractor['kyc_status'] ?? '') {
              'Approved' => 'badge-success',
              'Submitted', 'Under Review' => 'badge-info',
              'Rejected' => 'badge-danger',
              default => 'badge-warning'
          }; ?>
          <span class="badge <?= $kycClass ?>"><?= e($contractor['kyc_status'] ?? 'Draft') ?></span>
        </div></div>
      </div>
      <?php if (!empty($contractor['review_notes'])): ?>
      <div class="divider"></div>
      <div class="form-group"><label>KYC Review Notes</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;white-space:pre-wrap"><?= e($contractor['review_notes']) ?></div></div>
      <?php endif; ?>
      <?php if ($capabilities): ?>
      <div class="divider"></div>
      <div class="form-group"><label>Capabilities</label><div style="display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach ($capabilities as $c): ?>
        <span class="badge badge-success"><?= e($c[1]) ?></span>
        <?php endforeach; ?>
      </div></div>
      <?php endif; ?>
      <?php if ($contractor['service_regions']): ?>
      <div class="divider"></div>
      <div class="form-group"><label>Service Regions</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;white-space:pre-wrap"><?= e($contractor['service_regions']) ?></div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="grid-2col mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title">Contacts</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Main Contact</label><div class="font-600"><?= e($contractor['main_contact_name'] ?: '—') ?></div>
          <?php if ($contractor['main_contact_phone'] || $contractor['main_contact_email']): ?>
          <div class="text-muted font-sm"><?= e($contractor['main_contact_phone'] ?: '') ?><?= $contractor['main_contact_phone'] && $contractor['main_contact_email'] ? ' · ' : '' ?><?= e($contractor['main_contact_email'] ?: '') ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group"><label>Operations Contact</label><div class="font-600"><?= e($contractor['ops_contact_name'] ?: '—') ?></div>
          <?php if ($contractor['ops_contact_phone'] || $contractor['ops_contact_email']): ?>
          <div class="text-muted font-sm"><?= e($contractor['ops_contact_phone'] ?: '') ?><?= $contractor['ops_contact_phone'] && $contractor['ops_contact_email'] ? ' · ' : '' ?><?= e($contractor['ops_contact_email'] ?: '') ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group"><label>Technical Supervisor</label><div class="font-600"><?= e($contractor['tech_supervisor_name'] ?: '—') ?></div>
          <?php if ($contractor['tech_supervisor_phone'] || $contractor['tech_supervisor_email']): ?>
          <div class="text-muted font-sm"><?= e($contractor['tech_supervisor_phone'] ?: '') ?><?= $contractor['tech_supervisor_phone'] && $contractor['tech_supervisor_email'] ? ' · ' : '' ?><?= e($contractor['tech_supervisor_email'] ?: '') ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group"><label>Escalation Contact</label><div class="font-600"><?= e($contractor['escalation_contact_name'] ?: '—') ?></div>
          <?php if ($contractor['escalation_contact_phone'] || $contractor['escalation_contact_email']): ?>
          <div class="text-muted font-sm"><?= e($contractor['escalation_contact_phone'] ?: '') ?><?= $contractor['escalation_contact_phone'] && $contractor['escalation_contact_email'] ? ' · ' : '' ?><?= e($contractor['escalation_contact_email'] ?: '') ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Banking Details</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Bank</label><div class="font-600"><?= e($contractor['bank_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Branch</label><div><?= e($contractor['bank_branch'] ?: '—') ?></div></div>
        <div class="form-group"><label>Account Name</label><div><?= e($contractor['bank_account_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Account Number</label><div><?= e($contractor['bank_account_number'] ?: '—') ?></div></div>
        <div class="form-group"><label>Payment Terms</label><div><?= e($contractor['bank_payment_terms'] ?: '—') ?></div></div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($assignments)): ?>
<div class="card">
  <div class="card-header"><div class="card-title">Recent Job Assignments</div></div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th>Service</th>
          <th>Location</th>
          <th>Status</th>
          <th>Assigned</th>
          <th>Due</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $a): ?>
        <?php
          $aClass = match($a['status']) {
              'Assigned' => 'badge-warning',
              'Accepted', 'In Progress' => 'badge-primary',
              'Completed Submitted' => 'badge-info',
              'Completed' => 'badge-success',
              'Returned' => 'badge-danger',
              default => 'badge-secondary'
          };
        ?>
        <tr>
          <td class="font-600"><?= e($a['order_number']) ?></td>
          <td><?= e($a['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($a['service_type']) ?></span></td>
          <td class="text-muted font-sm"><?= e($a['customer_location'] ?: '—') ?></td>
          <td><span class="badge <?= $aClass ?>"><?= e($a['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($a['assigned_at']) ?></td>
          <td class="text-muted font-sm"><?= $a['target_date'] ? fmtDate($a['target_date']) : '—' ?></td>
          <td><a href="<?= APP_URL ?>/?page=contractor&action=job&id=<?= $a['id'] ?>" class="btn btn-sm btn-icon" style="background:transparent;border:none;color:var(--text-primary)" title="View Job"><?= svgIcon('eye') ?></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
