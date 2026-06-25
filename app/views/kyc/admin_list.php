<?php // Admin KYC List View ?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">KYC Applications</h1>
    <div class="page-subtitle"><?= count($applications) ?> application(s)</div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Partner</th>
          <th>Registered Name</th>
          <th>Partner Type</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Reviewed By</th>
          <th>Last Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($applications)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-title">No KYC applications</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($applications as $a): ?>
        <tr>
          <td class="font-600"><?= e($a['partner_name']) ?></td>
          <td><?= e($a['registered_name'] ?: '—') ?></td>
          <td><?= e($a['partner_type'] ?: '—') ?></td>
          <td>
            <span class="badge <?= $a['status'] === 'Approved' ? 'badge-success' : ($a['status'] === 'Rejected' ? 'badge-danger' : ($a['status'] === 'Submitted' || $a['status'] === 'Under Review' ? 'badge-warning' : 'badge-secondary')) ?>">
              <?= e($a['status']) ?>
            </span>
          </td>
          <td class="font-sm"><?= fmtDate($a['submitted_at']) ?></td>
          <td class="font-sm"><?= e($a['reviewer_name'] ?: '—') ?></td>
          <td class="text-muted font-sm"><?= fmtDateTime($a['updated_at']) ?></td>
          <td>
            <div class="actions">
              <a href="<?= APP_URL ?>/?page=kyc&action=admin_detail&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><?= svgIcon('eye') ?></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
