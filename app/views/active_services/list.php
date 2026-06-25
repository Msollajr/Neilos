<?php // Active Services List View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Active Services</div>
    <div class="page-subtitle"><?= $total ?> service(s) found</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="">
      <input type="hidden" name="page" value="active_services">
      <div class="filter-bar">
        <div class="search-box" style="flex:1;max-width:280px">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search service ID, customer, circuit..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="service_type" class="form-control">
          <option value="">All Types</option>
          <?php foreach ($serviceTypes as $t): ?>
          <option value="<?= e($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach ($statusOptions as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="monitoring_status" class="form-control">
          <option value="">All Monitoring</option>
          <?php foreach ($monitoringOptions as $m): ?>
          <option value="<?= e($m) ?>" <?= $filterMon === $m ? 'selected' : '' ?>><?= e($m) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=active_services" class="btn btn-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Service ID</th>
          <th>Customer</th>
          <th>Service Type</th>
          <th>Circuit ID</th>
          <th>Bandwidth</th>
          <th>Monitoring</th>
          <th>Status</th>
          <th>Tickets</th>
          <th>Activated</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($services)): ?>
        <tr><td colspan="10"><div class="empty-state"><div class="empty-state-title">No services found</div><div class="empty-state-text">Try adjusting your filters.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($services as $s):
          $tc = $ticketCounts[(int)$s['id']] ?? 0;
        ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=active_services&action=detail&id=<?= $s['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($s['service_id']) ?></a></td>
          <td><?= e($s['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($s['service_type']) ?></span></td>
          <td class="font-sm"><?= e($s['circuit_id'] ?: '—') ?></td>
          <td class="font-sm"><?= e($s['bandwidth_capacity'] ?: '—') ?></td>
          <td>
            <span class="badge badge-<?= $s['monitoring_status'] === 'Online' ? 'success' : ($s['monitoring_status'] === 'Offline' ? 'danger' : ($s['monitoring_status'] === 'Degraded' ? 'warning' : 'secondary')) ?>">
              <?= e($s['monitoring_status']) ?>
            </span>
          </td>
          <td><span class="badge badge-<?= $s['status'] === 'Active' ? 'success' : ($s['status'] === 'Suspended' ? 'warning' : 'secondary') ?>"><?= e($s['status']) ?></span></td>
          <td class="font-sm"><?= $tc > 0 ? $tc : '—' ?></td>
          <td class="text-muted font-sm"><?= fmtDate($s['activation_date']) ?></td>
          <td class="text-right">
            <a href="<?= APP_URL ?>/?page=active_services&action=detail&id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary" title="View"><?= svgIcon('eye') ?></a>
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
    <a href="?page=active_services&p=<?= $i ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?><?= $filterType ? '&service_type='.e($filterType) : '' ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?><?= $filterMon ? '&monitoring_status='.e($filterMon) : '' ?>" class="btn <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
