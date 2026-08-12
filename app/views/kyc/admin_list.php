<?php // Admin KYC List View ?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">KYC Applications</h1>
    <div class="page-subtitle"><?= count($applications) ?> application(s) registered</div>
  </div>
  <?php if (isAdmin() || hasRole('Management')): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=kyc&action=new" class="btn btn-primary">
      <?= svgIcon('plus') ?> New KYC Application
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Partner / Contractor</th>
          <th>KYC Type</th>
          <th>Registered Name</th>
          <th>TIN</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Reviewer</th>
          <th>Last Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($applications)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-title">No KYC applications found</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($applications as $a): ?>
        <tr id="kyc-row-<?= (int)$a['id'] ?>">
          <td class="font-600"><?= e($a['partner_name']) ?></td>
          <td>
            <span class="badge <?= $a['kyc_type'] === 'Contractor' ? 'badge-warning' : 'badge-info' ?>">
              <?= e($a['kyc_type'] ?: 'Partner') ?>
            </span>
          </td>
          <td><?= e($a['registered_name'] ?: '—') ?></td>
          <td class="font-mono"><?= e($a['tin'] ?: '—') ?></td>
          <td>
            <span class="badge <?= $a['status'] === 'Approved' ? 'badge-success' : ($a['status'] === 'Rejected' ? 'badge-danger' : ($a['status'] === 'Pending Approval' || $a['status'] === 'Submitted' ? 'badge-warning' : 'badge-secondary')) ?>">
              <?= e($a['status']) ?>
            </span>
          </td>
          <td class="font-sm"><?= fmtDate($a['submitted_at']) ?></td>
          <td class="font-sm"><?= e($a['reviewer_name'] ?: '—') ?></td>
          <td class="text-muted font-sm"><?= fmtDateTime($a['updated_at']) ?></td>
          <td>
            <div class="actions" style="display:flex;gap:6px;align-items:center">
              <a href="<?= APP_URL ?>/?page=kyc&action=edit&id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm" title="Manage KYC"><?= svgIcon('edit') ?> Manage</a>
              <?php if (isAdmin() || isManagement() || hasRole('Management')): ?>
              <button type="button" class="btn btn-danger btn-sm"
                      onclick="openDeleteKycModal(event, <?= (int)$a['id'] ?>, '<?= e(addslashes($a['registered_name'] ?: $a['partner_name'])) ?>', '<?= e($a['kyc_type'] ?: 'Partner') ?>', '<?= e($a['status']) ?>')"
                      title="Delete KYC Application">
                <?= svgIcon('trash') ?> Delete
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (isAdmin() || isManagement() || hasRole('Management')): ?>
<!-- Delete Confirmation Modal (Admin & Management Only) -->
<div class="modal" id="deleteKycModal" style="display:none" onclick="handleBackdropClick(event)">
  <div class="modal-dialog" style="max-width:500px" onclick="event.stopPropagation()">
    <form id="deleteKycForm" onsubmit="handleKycDeletion(event)">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="kyc_id" id="deleteKycId">
      
      <div class="modal-header" style="border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
        <div class="modal-title" style="color:var(--danger);font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
          <?= svgIcon('trash', 20) ?> Delete KYC Application?
        </div>
        <button type="button" class="modal-close" onclick="closeDeleteKycModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-muted)">&times;</button>
      </div>

      <div class="modal-body" style="padding:20px">
        <p style="margin:0 0 16px;font-size:0.95rem;color:var(--text-primary);line-height:1.5">
          Are you sure you want to delete this KYC application? This action cannot be undone.
        </p>

        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px">
          <div style="font-size:0.85rem;margin-bottom:6px"><strong>Partner / Contractor:</strong> <span id="deleteKycName" style="color:var(--primary);font-weight:600">—</span></div>
          <div style="font-size:0.85rem;margin-bottom:6px"><strong>KYC Type:</strong> <span id="deleteKycType" class="badge badge-info">—</span></div>
          <div style="font-size:0.85rem"><strong>Current Status:</strong> <span id="deleteKycStatus" class="badge badge-secondary">—</span></div>
        </div>

        <div id="deleteKycError" style="display:none;padding:10px 14px;background:var(--danger-light);color:var(--danger);border-radius:var(--radius-sm);font-size:0.88rem;margin-bottom:12px"></div>
      </div>

      <div class="modal-footer" style="border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeDeleteKycModal()">Cancel</button>
        <button type="submit" class="btn btn-danger" id="deleteKycSubmitBtn">
          Delete KYC Application
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let targetKycRow = null;

function openDeleteKycModal(evt, id, name, type, status) {
    document.getElementById('deleteKycId').value = id;
    document.getElementById('deleteKycName').textContent = name;
    document.getElementById('deleteKycType').textContent = type;
    document.getElementById('deleteKycStatus').textContent = status;
    document.getElementById('deleteKycError').style.display = 'none';

    targetKycRow = document.getElementById('kyc-row-' + id) || (evt.target ? evt.target.closest('tr') : null);

    document.getElementById('deleteKycModal').style.display = 'flex';
}

function closeDeleteKycModal() {
    document.getElementById('deleteKycModal').style.display = 'none';
    targetKycRow = null;
}

function handleBackdropClick(evt) {
    if (evt.target === document.getElementById('deleteKycModal')) {
        closeDeleteKycModal();
    }
}

function handleKycDeletion(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('deleteKycSubmitBtn');
    const errDiv = document.getElementById('deleteKycError');
    const kycId = document.getElementById('deleteKycId').value;

    btn.disabled = true;
    btn.textContent = 'Deleting...';
    errDiv.style.display = 'none';

    const formData = new FormData(form);

    fetch('<?= APP_URL ?>/?page=kyc&action=delete', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Delete KYC Application';

        if (data.success) {
            closeDeleteKycModal();
            
            // 1. Immediately remove the deleted KYC application row from the table
            const rowToRemove = document.getElementById('kyc-row-' + kycId) || targetKycRow;
            if (rowToRemove) {
                rowToRemove.remove();
            }

            // 2. Update the total application count immediately
            const subtitle = document.querySelector('.page-subtitle');
            const tbody = document.querySelector('.data-table tbody');
            if (tbody) {
                const remainingRows = tbody.querySelectorAll('tr[id^="kyc-row-"]');
                const count = remainingRows.length;
                if (subtitle) {
                    subtitle.textContent = count + ' application(s) registered';
                }
                if (count === 0) {
                    tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><div class="empty-state-title">No KYC applications found</div></div></td></tr>';
                }
            }

            // 3. Show success toast: "KYC application deleted successfully."
            if (typeof showToast === 'function') {
                showToast(data.message || 'KYC application deleted successfully.', 'success');
            } else {
                alert(data.message || 'KYC application deleted successfully.');
            }
        } else {
            // Failure: Keep the row in the table, show error toast
            errDiv.textContent = data.message || 'Failed to delete KYC application. Please try again.';
            errDiv.style.display = 'block';

            if (typeof showToast === 'function') {
                showToast('Failed to delete KYC application. Please try again.', 'danger');
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.textContent = 'Delete KYC Application';
        errDiv.textContent = 'Failed to delete KYC application. Please try again.';
        errDiv.style.display = 'block';

        if (typeof showToast === 'function') {
            showToast('Failed to delete KYC application. Please try again.', 'danger');
        }
    });
}
</script>
<?php endif; ?>
