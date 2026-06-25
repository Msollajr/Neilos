<?php // SLA Tracking View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">SLA Tracking</div>
    <div class="page-subtitle"><?= $total ?> order(s) &middot; Stage duration analysis</div>
  </div>
  <div class="page-header-actions">
    <a href="?page=sla_tracking&action=export&<?= http_build_query(array_merge($_GET, ['action'=>null])) ?>" class="btn btn-secondary">
      <?= svgIcon('download') ?> Export CSV
    </a>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="">
      <input type="hidden" name="page" value="sla_tracking">
      <div class="filter-bar">
        <div class="search-box">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search order # or customer..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach ($allStatuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=sla_tracking" class="btn btn-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
      <table class="data-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th class="hide-tablet-mobile">Service Type</th>
          <th>Status</th>
          <th class="hide-mobile">Submitted → BSA</th>
          <th class="hide-mobile">BSA → Approved</th>
          <th class="hide-mobile">Approved → Activated</th>
          <th class="hide-tablet-mobile">Total Duration</th>
          <th class="hide-tablet-mobile">Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($slaData)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-title">No orders found</div><div class="empty-state-text">Try adjusting your filters.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($slaData as $o):
          $t1 = $o['bsa_start_ts'] && $o['submitted_ts'] ? round(($o['bsa_start_ts'] - $o['submitted_ts']) / 3600, 1) : null;
          $t2 = $o['approved_ts'] && $o['bsa_start_ts'] ? round(($o['approved_ts'] - $o['bsa_start_ts']) / 3600, 1) : null;
          $t3 = $o['activated_ts'] && $o['approved_ts'] ? round(($o['activated_ts'] - $o['approved_ts']) / 3600, 1) : null;
          $total = $o['end_ts'] && $o['submitted_ts'] ? round(($o['end_ts'] - $o['submitted_ts']) / 3600, 1) : null;
        ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($o['order_number']) ?></a></td>
          <td><?= e($o['customer_name']) ?></td>
          <td class="hide-tablet-mobile"><span class="badge badge-primary"><?= e($o['service_type']) ?></span></td>
          <td><span class="badge <?= orderStatusClass($o['status']) ?>"><?= e($o['status']) ?></span></td>
          <td class="font-sm hide-mobile"><?= $t1 !== null ? number_format($t1, 1) . ' hrs' : '—' ?></td>
          <td class="font-sm hide-mobile"><?= $t2 !== null ? number_format($t2, 1) . ' hrs' : '—' ?></td>
          <td class="font-sm hide-mobile"><?= $t3 !== null ? number_format($t3, 1) . ' hrs' : '—' ?></td>
          <td class="font-600 hide-tablet-mobile"><?= $total !== null ? number_format($total, 1) . ' hrs' : '—' ?></td>
          <td class="text-muted font-sm hide-tablet-mobile"><?= fmtDate($o['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;gap:8px;align-items:center;justify-content:center">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?page=sla_tracking&p=<?= $i ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?>" class="btn <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
