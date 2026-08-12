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
      <div class="filter-bar" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <div class="search-box" style="flex:1;min-width:200px">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search order # or customer..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="status" class="form-control" style="width:auto">
          <option value="">All Statuses</option>
          <?php foreach ($allStatuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="sort" class="form-control" style="width:auto">
          <option value="newest" <?= $sortKey === 'newest' ? 'selected' : '' ?>>Sort: Newest First</option>
          <option value="oldest" <?= $sortKey === 'oldest' ? 'selected' : '' ?>>Sort: Oldest First</option>
          <option value="total_desc" <?= $sortKey === 'total_desc' ? 'selected' : '' ?>>Sort: Total Duration (High → Low)</option>
          <option value="total_asc" <?= $sortKey === 'total_asc' ? 'selected' : '' ?>>Sort: Total Duration (Low → High)</option>
          <option value="bsa_desc" <?= $sortKey === 'bsa_desc' ? 'selected' : '' ?>>Sort: Submitted → BSA (High → Low)</option>
          <option value="bsa_asc" <?= $sortKey === 'bsa_asc' ? 'selected' : '' ?>>Sort: Submitted → BSA (Low → High)</option>
          <option value="approved_desc" <?= $sortKey === 'approved_desc' ? 'selected' : '' ?>>Sort: BSA → Approved (High → Low)</option>
          <option value="approved_asc" <?= $sortKey === 'approved_asc' ? 'selected' : '' ?>>Sort: BSA → Approved (Low → High)</option>
          <option value="activated_desc" <?= $sortKey === 'activated_desc' ? 'selected' : '' ?>>Sort: Approved → Activated (High → Low)</option>
          <option value="activated_asc" <?= $sortKey === 'activated_asc' ? 'selected' : '' ?>>Sort: Approved → Activated (Low → High)</option>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=sla_tracking" class="btn btn-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card" style="width:100%;overflow:hidden">
  <div class="table-responsive" style="width:100%;max-height:650px;overflow-x:auto;overflow-y:auto;-webkit-overflow-scrolling:touch">
    <table class="data-table sla-table" style="width:100%;min-width:1470px;border-collapse:separate;border-spacing:0;table-layout:auto">
      <thead>
        <tr style="position:sticky;top:0;z-index:10;background:var(--surface-2);box-shadow:inset 0 -2px 0 var(--border)">
          <th style="min-width:140px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">ORDER #</th>
          <th style="min-width:160px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">CUSTOMER</th>
          <th style="min-width:240px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">SERVICE TYPE</th>
          <th style="min-width:160px;text-align:left;padding:14px 16px;white-space:nowrap;font-weight:700">STATUS</th>
          <th style="min-width:180px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">SUBMITTED → BSA</th>
          <th style="min-width:180px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">BSA → APPROVED</th>
          <th style="min-width:200px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700">APPROVED → ACTIVATED</th>
          <th style="min-width:210px;text-align:right;padding:14px 16px;white-space:nowrap;font-weight:700;color:var(--primary)">TOTAL DURATION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($slaData)): ?>
        <tr><td colspan="8" style="text-align:center;padding:24px"><div class="empty-state"><div class="empty-state-title">No orders found</div><div class="empty-state-text">Try adjusting your filters.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($slaData as $o): ?>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap"><a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($o['order_number']) ?></a></td>
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($o['customer_name']) ?>"><?= e($o['customer_name']) ?></td>
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap">
            <?php if (!empty($o['service_type_display'])): ?>
            <span class="badge badge-primary" style="font-size:.8rem;padding:6px 10px;white-space:nowrap;display:inline-block"><?= e($o['service_type_display']) ?></span>
            <?php else: ?>
            —
            <?php endif; ?>
          </td>
          <td style="padding:14px 16px;vertical-align:middle;white-space:nowrap"><span class="badge <?= orderStatusClass($o['status']) ?>"><?= e($o['status']) ?></span></td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm"><?= formatSlaDuration($o['dur_submitted_bsa'], $o['is_dur1_ongoing']) ?></td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm"><?= formatSlaDuration($o['dur_bsa_approved'], $o['is_dur2_ongoing']) ?></td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap" class="font-sm"><?= formatSlaDuration($o['dur_approved_activated'], $o['is_dur3_ongoing']) ?></td>
          <td style="padding:14px 16px;text-align:right;vertical-align:middle;white-space:nowrap;background:rgba(99,102,241,.04)">
            <span style="color:var(--primary);font-weight:700"><?= formatSlaDuration($o['dur_total'], $o['is_total_ongoing']) ?></span>
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
    <a href="?page=sla_tracking&p=<?= $i ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?><?= $sortKey ? '&sort='.e($sortKey) : '' ?>" class="btn <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
