<?php // Bulk Upload Form + History ?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Bulk FTTH Upload</h1>
    <div class="page-subtitle">Upload a CSV file to create multiple FTTH service orders at once.</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=bulk_upload&action=download_template" class="btn btn-secondary">
      <?= svgIcon('download') ?> Download CSV Template
    </a>
  </div>
</div>

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Upload CSV File</div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=bulk_upload&action=upload" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <?php if (!isPartnerUser() && !empty($partners)): ?>
      <div class="form-group" style="margin-bottom:16px">
        <label>Partner <span class="required">*</span></label>
        <select name="partner_id" class="form-control" required>
          <option value="">— Select Partner —</option>
          <?php foreach ($partners as $p): ?>
          <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="file-input-wrapper" onclick="document.getElementById('csvFile').click()">
        <input type="file" name="csv_file" id="csvFile" accept=".csv" required onchange="document.getElementById('fileLabel').textContent = this.files[0]?.name || ''">
        <div class="file-input-icon"><?= svgIcon('upload', 32) ?></div>
        <div class="file-input-text"><strong>Click to select CSV file</strong> or drag and drop</div>
        <div class="file-input-text" style="font-size:.75rem;margin-top:4px" id="fileLabel">CSV format — max 10 MB</div>
      </div>

      <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary"><?= svgIcon('upload') ?> Upload &amp; Process</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Upload History</div>
    <div class="card-subtitle">Last 20 batch uploads</div>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Batch #</th>
          <?php if (!isPartnerUser()): ?><th>Partner</th><?php endif; ?>
          <th>File</th>
          <th>Total</th>
          <th>Valid</th>
          <th>Invalid</th>
          <th>Orders Created</th>
          <th>Status</th>
          <th>Uploaded</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($batches)): ?>
        <tr><td colspan="<?= isPartnerUser() ? 8 : 9 ?>"><div class="empty-state"><?= svgIcon('upload', 32) ?><div class="empty-state-title">No uploads yet</div><div class="empty-state-text">Upload a CSV file to get started.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($batches as $b): ?>
        <tr>
          <td class="font-600"><?= e($b['batch_number']) ?></td>
          <?php if (!isPartnerUser()): ?><td class="font-sm"><?= e($b['partner_name']) ?></td><?php endif; ?>
          <td class="font-sm"><?= e($b['file_name'] ?: '—') ?></td>
          <td><?= (int)$b['total_rows'] ?></td>
          <td class="text-success font-600"><?= (int)$b['valid_rows'] ?></td>
          <td class="text-danger font-600"><?= (int)$b['invalid_rows'] ?></td>
          <td class="font-600"><?= (int)$b['orders_created'] ?></td>
          <td>
            <span class="badge <?= $b['status'] === 'Completed' ? 'badge-success' : ($b['status'] === 'Failed' ? 'badge-danger' : 'badge-warning') ?>">
              <?= e($b['status']) ?>
            </span>
          </td>
          <td class="text-muted font-sm"><?= fmtDateTime($b['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
