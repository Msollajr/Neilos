<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Asset Inventory</div>
    <div class="page-subtitle"><?= $total ?> asset(s) found</div>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" data-modal-open="createModal"><?= svgIcon('plus') ?> Add Asset</button>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="">
      <input type="hidden" name="page" value="assets">
      <div class="filter-bar">
        <div class="search-box">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search serial, model, customer..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="type" class="form-control">
          <option value="">All Types</option>
          <?php foreach ($assetTypes as $t): ?>
          <option value="<?= e($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach ($statuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=assets" class="btn btn-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Serial Number</th>
          <th>Model</th>
          <th>Customer</th>
          <th>Partner</th>
          <th>Site Location</th>
          <th>Status</th>
          <th>Service ID</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-title">No assets found</div><div class="empty-state-text">Add your first asset to the inventory.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($assets as $a): ?>
        <tr>
          <td><span class="badge badge-secondary"><?= e($a['asset_type']) ?></span></td>
          <td class="font-600"><?= e($a['serial_number']) ?></td>
          <td class="font-sm"><?= e($a['model'] ?: '—') ?></td>
          <td><?= e($a['customer_name'] ?: '—') ?></td>
          <td class="font-sm"><?= e($a['partner_name'] ?: '—') ?></td>
          <td class="font-sm"><?= e($a['site_location'] ?: '—') ?></td>
          <td><span class="badge <?= $a['status'] === 'Deployed' ? 'badge-success' : ($a['status'] === 'Faulty' ? 'badge-danger' : ($a['status'] === 'Retired' ? 'badge-secondary' : 'badge-info')) ?>"><?= e($a['status']) ?></span></td>
          <td class="font-sm"><?= e($a['svc_id'] ?: '—') ?></td>
          <td>
            <div class="actions">
              <a href="?page=assets&action=detail&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><?= svgIcon('eye') ?></a>
              <button class="btn btn-secondary btn-sm btn-icon" data-modal-open="editModal<?= $a['id'] ?>" title="Edit"><?= svgIcon('edit') ?></button>
            </div>
            <!-- Edit Modal -->
            <div class="modal-backdrop" id="editModal<?= $a['id'] ?>">
              <div class="modal">
                <div class="modal-header"><div class="modal-title">Edit Asset</div><button class="modal-close" data-modal-close>&times;</button></div>
                <form method="POST" action="?page=assets&action=update">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="id" value="<?= $a['id'] ?>">
                  <div class="modal-body">
                    <div class="form-grid form-grid-2">
                      <div class="form-group">
                        <label>Asset Type</label>
                        <select name="asset_type" class="form-control" required>
                          <?php foreach (['Router','ONU','Switch','SFP','Other'] as $t): ?>
                          <option value="<?= $t ?>" <?= $a['asset_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number" class="form-control" value="<?= e($a['serial_number']) ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Model</label>
                        <input type="text" name="model" class="form-control" value="<?= e($a['model'] ?? '') ?>">
                      </div>
                      <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                          <?php foreach ($statuses as $s): ?>
                          <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" value="<?= e($a['customer_name'] ?? '') ?>">
                      </div>
                      <div class="form-group">
                        <label>Site Location</label>
                        <input type="text" name="site_location" class="form-control" value="<?= e($a['site_location'] ?? '') ?>">
                      </div>
                    </div>
                    <div class="form-group">
                      <label>Notes</label>
                      <textarea name="notes" class="form-control" rows="2"><?= e($a['notes'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                  </div>
                </form>
              </div>
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
    <a href="?page=assets&p=<?= $i ?><?= $filterType ? '&type='.e($filterType) : '' ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?>" class="btn <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Create Modal -->
<div class="modal-backdrop" id="createModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Add Asset</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="?page=assets&action=create">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Asset Type <span class="required">*</span></label>
            <select name="asset_type" class="form-control" required>
              <option value="">— Select —</option>
              <option value="Router">Router</option>
              <option value="ONU">ONU</option>
              <option value="Switch">Switch</option>
              <option value="SFP">SFP</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Serial Number <span class="required">*</span></label>
            <input type="text" name="serial_number" class="form-control" required placeholder="Serial number">
          </div>
          <div class="form-group">
            <label>Model</label>
            <input type="text" name="model" class="form-control" placeholder="e.g. Huawei HG8245">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="In Stock">In Stock</option>
              <option value="Deployed">Deployed</option>
              <option value="Faulty">Faulty</option>
              <option value="Returned">Returned</option>
              <option value="Retired">Retired</option>
            </select>
          </div>
          <div class="form-group">
            <label>Customer Name</label>
            <input type="text" name="customer_name" class="form-control" placeholder="Customer name">
          </div>
          <div class="form-group">
            <label>Site Location</label>
            <input type="text" name="site_location" class="form-control" placeholder="Site address">
          </div>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Any notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('save') ?> Create Asset</button>
      </div>
    </form>
  </div>
</div>
