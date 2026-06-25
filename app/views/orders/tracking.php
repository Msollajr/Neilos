<?php // Order Tracking List View ?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Order Tracking</h1>
    <div class="page-subtitle"><?= $total ?> order(s) found</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary"><?= svgIcon('plus') ?> New Order</a>
    <a href="?page=orders&<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary"><?= svgIcon('download') ?> Export CSV</a>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="">
      <input type="hidden" name="page" value="orders">
      <div class="filter-bar">
        <div class="search-box">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search order #, customer, circuit..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach($allStatuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="service_type" class="form-control">
          <option value="">All Service Types</option>
          <?php foreach($serviceTypes as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterService === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=orders" class="btn btn-secondary btn-sm">Clear</a>
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
          <?php if (!isPartnerUser()): ?><th>Partner</th><?php endif; ?>
          <th>Customer</th>
          <th>Service Type</th>
          <th>Assigned KAM</th>
          <th>Status</th>
          <th>NRC</th>
          <th>MRC</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="10"><div class="empty-state"><?= svgIcon('list', 32) ?><div class="empty-state-title">No orders found</div><div class="empty-state-text">Try adjusting your filters or create a new order.</div></div></td></tr>
        <?php else: ?>
        <?php foreach($orders as $o): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($o['order_number']) ?></a></td>
          <?php if (!isPartnerUser()): ?><td class="font-sm"><?= e($o['partner_name']) ?></td><?php endif; ?>
          <td><?= e($o['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($o['service_type']) ?></span></td>
          <td class="font-sm"><?= e($o['assigned_kam_name'] ?: '—') ?></td>
          <td><span class="badge <?= orderStatusClass($o['status']) ?>"><?= e($o['status']) ?></span></td>
          <td class="font-sm">$<?= money($o['total_nrc_incl_vat']) ?></td>
          <td class="font-sm"><?= $o['total_mrc_incl_vat'] > 0 ? e($o['mrc_currency']).' '.money($o['total_mrc_incl_vat']) : '—' ?></td>
          <td class="text-muted font-sm"><?= fmtDate($o['created_at']) ?></td>
          <td>
            <div class="actions">
              <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $o['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><?= svgIcon('eye') ?></a>
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
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$i])) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
