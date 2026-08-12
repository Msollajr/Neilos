<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Contractors Management</h1>
    <div class="page-subtitle"><?= $total ?> contractor(s) found</div>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="padding:14px 18px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:0">
      <input type="hidden" name="page" value="contractors">
      <div class="form-group" style="margin:0;flex:1;min-width:220px">
        <input type="text" name="q" class="form-control" placeholder="Search by name, trading name or registration number..." value="<?= e($search) ?>">
      </div>
      <div class="form-group" style="margin:0">
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach (['Active', 'Inactive', 'Suspended'] as $st): ?>
          <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><?= svgIcon('search') ?> Filter</button>
      <?php if ($search !== '' || $statusFilter !== ''): ?>
      <a href="<?= APP_URL ?>/?page=contractors" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Trading Name</th>
          <th>Type</th>
          <th>KYC</th>
          <th>Status</th>
          <th>Jobs</th>
          <th>Active Jobs</th>
          <th>Completed</th>
          <th>Users</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($contractors)): ?>
        <tr><td colspan="10"><div class="empty-state"><?= svgIcon('building', 32) ?><div class="empty-state-title">No contractors found</div><div class="empty-state-text">Contractors appear here once they are registered as partners with type "Contractor".</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($contractors as $c): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $c['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($c['name']) ?></a></td>
          <td class="font-sm"><?= e($c['trading_name'] ?: '—') ?></td>
          <td><span class="badge badge-primary"><?= e($c['partner_type']) ?></span></td>
          <td>
            <?php $kycClass = match($c['kyc_status'] ?? '') {
                'Approved' => 'badge-success',
                'Submitted', 'Under Review' => 'badge-info',
                'Rejected' => 'badge-danger',
                default => 'badge-warning'
            }; ?>
            <span class="badge <?= $kycClass ?>"><?= e($c['kyc_status'] ?? 'Draft') ?></span>
          </td>
          <td>
            <?php if ($c['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
            <?php elseif ($c['status'] === 'Inactive'): ?>
            <span class="badge badge-secondary">Inactive</span>
            <?php else: ?>
            <span class="badge badge-danger">Suspended</span>
            <?php endif; ?>
          </td>
          <td class="font-600"><?= (int)$c['total_jobs'] ?></td>
          <td class="font-sm"><?= (int)$c['active_jobs'] ?></td>
          <td class="font-sm text-muted"><?= (int)$c['completed_jobs'] ?></td>
          <td class="font-sm"><?= (int)$c['user_count'] ?></td>
          <td>
            <div class="actions">
              <a href="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><?= svgIcon('eye') ?></a>
              <a href="<?= APP_URL ?>/?page=contractors&action=edit&id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit"><?= svgIcon('edit') ?></a>
              <?php if (isAdmin() || hasRole('Management')): ?>
              <form method="POST" action="<?= APP_URL ?>/?page=contractors&action=delete" style="display:inline" data-confirm="Permanently delete contractor <?= e($c['name']) ?>?">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Contractor"><?= svgIcon('trash') ?></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;gap:8px;align-items:center;justify-content:center">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
