<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Partner Management</h1>
    <div class="page-subtitle"><?= $total ?> partner(s) found</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=partners&action=create" class="btn btn-primary"><?= svgIcon('plus') ?> New Partner</a>
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
          <th>Status</th>
          <th>City / Region</th>
          <th>Country</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($partners)): ?>
        <tr><td colspan="8"><div class="empty-state"><?= svgIcon('building', 32) ?><div class="empty-state-title">No partners found</div><div class="empty-state-text">Click "New Partner" to add the first partner.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($partners as $p): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $p['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($p['name']) ?></a></td>
          <td class="font-sm"><?= e($p['trading_name'] ?: '—') ?></td>
          <td><span class="badge badge-primary"><?= e($p['partner_type']) ?></span></td>
          <td>
            <?php if ($p['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
            <?php elseif ($p['status'] === 'Inactive'): ?>
            <span class="badge badge-secondary">Inactive</span>
            <?php else: ?>
            <span class="badge badge-danger">Suspended</span>
            <?php endif; ?>
          </td>
          <td class="font-sm"><?= e($p['city_region'] ?: '—') ?></td>
          <td class="font-sm"><?= e($p['country']) ?></td>
          <td class="text-muted font-sm"><?= fmtDate($p['created_at']) ?></td>
          <td>
            <div class="actions">
              <a href="<?= APP_URL ?>/?page=partners&action=detail&id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><?= svgIcon('eye') ?></a>
              <a href="<?= APP_URL ?>/?page=partners&action=edit&id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit"><?= svgIcon('edit') ?></a>
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
