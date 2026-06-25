<?php // Bulk Upload Results View ?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Bulk Upload Results</h1>
    <div class="page-subtitle">Batch <?= e($batchNum) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=bulk_upload" class="btn btn-secondary"><?= svgIcon('upload') ?> New Upload</a>
    <a href="<?= APP_URL ?>/?page=orders" class="btn btn-primary"><?= svgIcon('list') ?> View Orders</a>
  </div>
</div>

<div class="stats-grid" style="margin-bottom:22px">
  <div class="stat-card">
    <div class="stat-icon green"><?= svgIcon('check', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= count($validRows) ?></div>
      <div class="stat-label">Valid Rows</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon <?= !empty($errors) ? 'red' : 'green' ?>"><?= svgIcon('x', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= count($errors) ?></div>
      <div class="stat-label">Invalid Rows</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><?= svgIcon('list', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= $ordersCreated ?></div>
      <div class="stat-label">Orders Created</div>
    </div>
  </div>
</div>

<?php if ($ordersCreated > 0): ?>
<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Created Orders</div></div>
  <div class="card-body">
    <div class="doc-checklist">
      <?php foreach ($createdOrders as $co): ?>
      <div class="doc-row">
        <div class="doc-row-info">
          <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $co['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($co['order_number']) ?></a>
        </div>
        <div class="doc-row-actions">
          <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $co['id'] ?>" class="btn btn-sm btn-secondary"><?= svgIcon('eye') ?> View</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="card">
  <div class="card-header"><div class="card-title">Errors (<?= count($errors) ?> rows skipped)</div></div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>CSV Row</th>
          <th>Customer Name</th>
          <th>Errors</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($errors as $err): ?>
        <tr>
          <td><?= $err['row'] ?></td>
          <td><?= e($err['customer_name'] ?: '—') ?></td>
          <td>
            <ul style="margin:0;padding-left:16px">
              <?php foreach ($err['errors'] as $eMsg): ?>
              <li class="text-danger font-sm"><?= e($eMsg) ?></li>
              <?php endforeach; ?>
            </ul>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
