<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= isContractorUser() ? 'My Jobs' : 'Contractor Management' ?></div>
    <div class="page-subtitle"><?= isContractorUser() ? 'View and manage your assigned installation jobs.' : 'Manage contractor assignments across all orders.' ?></div>
  </div>
</div>

<!-- Stat Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px">
  <div class="stat-card"><div class="stat-card-icon" style="background:rgba(255,171,64,.15);color:#ffab40"><?= svgIcon('clock') ?></div><div class="stat-card-value"><?= $counts['Assigned'] ?></div><div class="stat-card-label">New Assignments</div></div>
  <div class="stat-card"><div class="stat-card-icon" style="background:rgba(99,102,241,.15);color:var(--primary)"><?= svgIcon('check') ?></div><div class="stat-card-value"><?= $counts['Accepted'] ?></div><div class="stat-card-label">Accepted / Scheduled</div></div>
  <div class="stat-card"><div class="stat-card-icon" style="background:rgba(99,102,241,.15);color:var(--primary)"><?= svgIcon('project') ?></div><div class="stat-card-value"><?= $counts['In Progress'] ?></div><div class="stat-card-label">In Progress</div></div>
  <div class="stat-card"><div class="stat-card-icon" style="background:rgba(220,53,69,.15);color:var(--danger)"><?= svgIcon('refresh') ?></div><div class="stat-card-value"><?= $counts['Returned'] ?></div><div class="stat-card-label">Returned for Correction</div></div>
  <div class="stat-card"><div class="stat-card-icon" style="background:rgba(34,197,94,.15);color:var(--success)"><?= svgIcon('upload') ?></div><div class="stat-card-value"><?= $counts['Completed Submitted'] ?></div><div class="stat-card-label">Completed Submitted</div></div>
  <div class="stat-card"><div class="stat-card-icon" style="background:rgba(239,68,68,.15);color:var(--danger)"><?= svgIcon('clock') ?></div><div class="stat-card-value"><?= $counts['SLA Due Today'] ?? 0 ?></div><div class="stat-card-label">SLA / Due Today</div></div>
</div>

<!-- Jobs Table -->
<div class="card">
  <div class="card-header"><div class="card-title">All <?= isContractorUser() ? 'My ' : '' ?>Jobs</div></div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th>Service</th>
          <th>Location</th>
          <?php if (!isContractorUser()): ?><th>Contractor</th><?php endif; ?>
          <th>Status</th>
          <th>Assigned</th>
          <th>Due</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon"><?= svgIcon('project') ?></div><div class="empty-state-title">No jobs found</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($assignments as $a): ?>
        <?php
          $statusClass = match($a['status']) {
              'Assigned' => 'badge-warning',
              'Accepted' => 'badge-primary',
              'In Progress' => 'badge-primary',
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
          <?php if (!isContractorUser()): ?><td><?= e($a['contractor_name'] ?? '—') ?></td><?php endif; ?>
          <td><span class="badge <?= $statusClass ?>"><?= e($a['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($a['assigned_at']) ?></td>
          <td class="text-muted font-sm"><?= $a['target_date'] ? fmtDate($a['target_date']) : '—' ?></td>
          <td><a href="<?= APP_URL ?>/?page=contractor&action=job&id=<?= $a['id'] ?>" class="btn btn-sm btn-icon" style="background:transparent;border:none;color:var(--text-primary)" title="View Job"><?= svgIcon('eye') ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
