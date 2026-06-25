<?php // Projects List View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Project Delivery</div>
    <div class="page-subtitle"><?= $total ?> project(s) found</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="">
      <input type="hidden" name="page" value="projects">
      <div class="filter-bar">
        <div class="search-box" style="flex:1;max-width:320px">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search project, order # or customer..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach ($statusOptions as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=projects" class="btn btn-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Project</th>
          <th>Order #</th>
          <th>Customer</th>
          <th>Service</th>
          <th>Tasks</th>
          <th>Target Date</th>
          <th>Status</th>
          <th>Created</th>
          <th class="text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($projects)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-title">No projects found</div><div class="empty-state-text">Projects are created from approved orders.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($projects as $p):
          $tc = $taskCounts[(int)$p['id']] ?? ['total' => 0, 'done' => 0];
        ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=projects&action=detail&id=<?= $p['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($p['project_name']) ?></a></td>
          <td class="font-sm"><?= e($p['order_number']) ?></td>
          <td><?= e($p['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($p['service_type']) ?></span></td>
          <td class="font-sm"><?= $tc['total'] > 0 ? $tc['done'] . '/' . $tc['total'] : '—' ?></td>
          <td class="font-sm"><?= fmtDate($p['target_date']) ?></td>
          <td><span class="badge <?= $p['status'] === 'Completed' ? 'badge-success' : ($p['status'] === 'In Progress' ? 'badge-primary' : ($p['status'] === 'On Hold' ? 'badge-warning' : ($p['status'] === 'Cancelled' ? 'badge-danger' : 'badge-secondary'))) ?>"><?= e($p['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($p['created_at']) ?></td>
          <td class="text-right">
            <a href="<?= APP_URL ?>/?page=projects&action=detail&id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary" title="View"><?= svgIcon('eye') ?></a>
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
    <a href="?page=projects&p=<?= $i ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?>" class="btn <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
