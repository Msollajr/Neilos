<?php
// ============================================================
// Order Detail View — v1.0
// Role-specific action panels for each lifecycle status
// ============================================================
$status = $order['status'];
$isPartner = isPartnerUser();
$isBSA = hasRole('BSA');
$isKAM = hasRole('KAM');
$isMgmt = hasRole('Management');
$isPM   = hasRole('Project Manager');
$isContractor = isContractorUser();
$isAdminUser = isAdmin();

// Effective MRC (management override → revised → standard)
$effectiveMrc = $order['management_approved_price'] ?? $order['revised_mrc'] ?? $order['base_mrc'];
$effectiveNrc = $order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd'];

$stDisplay = !empty($order['service_type']) ? $order['service_type'] : '';
if (!$stDisplay) {
    if (!empty($order['fttx_package'])) {
        $stDisplay = 'FTTH';
    } elseif (!empty($order['aggregate_capacity'])) {
        $stDisplay = 'Layer 2 ( last mile)';
    } elseif (!empty($order['bandwidth'])) {
        $stDisplay = 'BIA (Broadband Internet Access)';
    } elseif ((float)($order['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($order['base_nrc_usd'] ?? 0) == 80000) {
        $stDisplay = 'Remote Hands Only';
    } else {
        $stDisplay = 'Not specified';
    }
}
?>
<div class="page-header" data-order-detail-id="<?= $order['id'] ?>">
  <div class="page-header-left">
    <div class="page-title"><?= e($order['order_number']) ?></div>
    <div class="page-subtitle"><?= e($stDisplay) ?> · <?= e($order['customer_name']) ?> · <?= e($order['partner_name']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary"><?= svgIcon('list') ?> All Orders</a>
    <button type="button" class="btn btn-info" onclick="viewSystemFile('<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>', 'SOF_<?= e($order['order_number']) ?>.pdf', '<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>&download=1', {doc_type:'Service Order Form'})"><?= svgIcon('document') ?> Generate &amp; View SOF</button>
    <a href="<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>&format=excel" class="btn btn-success"><?= svgIcon('download') ?> Excel SOF (.xlsx)</a>
    <?php if ($isAdminUser && !in_array($status, ['Closed','Cancelled'])): ?>
    <button class="btn btn-secondary" data-modal-open="statusModal"><?= svgIcon('edit') ?> Admin Override</button>
    <?php endif; ?>
    <?php if ($isAdminUser || $isMgmt): ?>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=delete" style="display:inline" data-confirm="Permanently delete order <?= e($order['order_number']) ?>?">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $order['id'] ?>">
      <button type="submit" class="btn btn-danger"><?= svgIcon('trash') ?> Delete Order</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php // ============================================================
// STATUS PROGRESS BAR
// ============================================================ ?>
<?php
$statusSteps = ['Feasibility Review','Await Commercial Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed'];
$currentIdx = array_search($status, $statusSteps);
?>
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 24px">
    <div style="display:flex;align-items:center;gap:0;overflow-x:auto">
      <?php foreach ($statusSteps as $idx => $step): ?>
      <?php
        if ($status === 'Closed') {
            $isDone    = true;
            $isCurrent = false;
        } else {
            $isDone    = $currentIdx !== false && $idx < $currentIdx;
            $isCurrent = $currentIdx !== false && $idx === $currentIdx;
        }
        $cls = $isDone ? 'step-done' : ($isCurrent ? 'step-current' : 'step-pending');
      ?>
      <div class="order-step <?= $cls ?>">
        <div class="order-step-dot"><?= $isDone ? svgIcon('check') : ($idx + 1) ?></div>
        <div class="order-step-label"><?= e($step) ?></div>
      </div>
      <?php if ($idx < count($statusSteps) - 1): ?>
      <div class="order-step-connector <?= ($status === 'Closed' || ($currentIdx !== false && $idx < $currentIdx)) ? 'done' : '' ?>"></div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php if ($status === 'Not Feasible'): ?>
    <div class="alert alert-danger" style="margin-top:12px;margin-bottom:0">
      <?= svgIcon('x') ?> <strong>Not Feasible:</strong> <?= e($order['bsa_not_feasible_reason']) ?>
    </div>
    <?php elseif ($status === 'Management Approval'): ?>
    <div class="alert alert-warning" style="margin-top:12px;margin-bottom:0">
      <?= svgIcon('users') ?> This order is awaiting <strong>Management Exception Approval</strong>.
    </div>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;margin-bottom:24px">
  <div class="card">
    <div class="card-header"><div class="card-title">Order Details</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Status</label><div><span class="badge <?= orderStatusClass($status) ?>" style="font-size:.85rem;padding:6px 14px"><?= e($status) ?></span></div></div>
        <div class="form-group"><label>Service Type</label><div><span class="badge badge-primary" style="font-size:.85rem;padding:6px 14px"><?= e($stDisplay) ?></span></div></div>
        <?php if (!empty($order['fttx_package'])): ?>
        <div class="form-group"><label>FTTx Package</label><div class="font-600"><?= e($order['fttx_package']) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($order['bandwidth'])): ?>
        <div class="form-group"><label>Bandwidth</label><div class="font-600"><?= e($order['bandwidth']) ?> Mbps</div></div>
        <?php endif; ?>
        <?php if (!empty($order['aggregate_capacity'])): ?>
        <div class="form-group"><label>Capacity</label><div class="font-600"><?= e($order['aggregate_capacity']) ?> Mbps</div></div>
        <?php endif; ?>
        <?php if (!empty($order['nni_location'])): ?>
        <div class="form-group"><label>NNI / Handoff Point</label><div><?= e($order['nni_location']) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($order['remote_hands_required']) || (float)($order['remote_hands_nrc_usd'] ?? 0) > 0): ?>
        <div class="form-group"><label>Remote Hands</label><div><span class="badge badge-warning">Required (+80,000 TZS)</span></div></div>
        <?php endif; ?>
        <?php if (!empty($order['contract_term'])): ?>
        <div class="form-group"><label>Minimum Service Term</label><div><?= e($order['contract_term']) ?></div></div>
        <?php endif; ?>
        <div class="form-group"><label>Customer</label><div class="font-600"><?= e($order['customer_name']) ?></div></div>
        <div class="form-group"><label>Location</label><div><?= e($order['customer_location'] ?: '—') ?></div></div>
        <?php if ($order['site_category']): ?><div class="form-group"><label>Site Category</label><div><?= e($order['site_category']) ?></div></div><?php endif; ?>
        <?php if ($order['gps_coordinates']): ?><div class="form-group"><label>GPS</label><div><?= e($order['gps_coordinates']) ?></div></div><?php endif; ?>
        <div class="form-group"><label>Partner</label><div><?= e($order['partner_name']) ?></div></div>
        <div class="form-group"><label>Assigned KAM</label><div><?= e($order['assigned_kam_name'] ?: '—') ?></div></div>
        <?php if ($order['customer_contact_name']): ?><div class="form-group"><label>Customer Contact</label><div><?= e($order['customer_contact_name']) ?><?= $order['customer_contact_phone'] ? ' · '.e($order['customer_contact_phone']) : '' ?></div></div><?php endif; ?>
        <?php if ($order['circuit_id']): ?><div class="form-group"><label>Circuit ID</label><div class="font-600"><?= e($order['circuit_id']) ?></div></div><?php endif; ?>
        <?php if ($order['service_id']): ?><div class="form-group"><label>Service ID</label><div class="font-600"><?= e($order['service_id']) ?></div></div><?php endif; ?>
        <?php if ($order['closed_date']): ?>
        <div class="form-group"><label>Closed Date</label><div class="font-600 text-success"><?= fmtDate($order['closed_date']) ?></div></div>
        <div class="form-group"><label>Billing Start Date</label><div class="font-600 text-success"><?= fmtDate($order['billing_start_date']) ?></div></div>
        <?php endif; ?>
      </div>
      <?php if ($order['special_requirements']): ?>
      <div class="divider"></div>
      <div class="form-group"><label>Special Requirements</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:6px;font-size:.875rem;white-space:pre-wrap"><?= e($order['special_requirements']) ?></div></div>
      <?php endif; ?>
      <?php if (!$isPartner && $order['bsa_technical_result']): ?>
      <div class="divider"></div>
      <div class="form-group"><label>Technical Assessment</label>
        <div><span class="badge <?= $order['bsa_technical_result'] === 'Technically Feasible' ? 'badge-success' : 'badge-danger' ?>"><?= e($order['bsa_technical_result']) ?></span>
        <?php if ($order['bsa_special_conditions']): ?><div class="text-muted font-sm" style="margin-top:6px"><?= e($order['bsa_special_conditions']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!$isPartner && $order['return_reason']): ?>
      <div class="divider"></div>
      <div class="alert alert-warning" style="margin-bottom:0">
        <strong>Returned:</strong> <?= e($order['return_reason']) ?> — <?= e($order['return_remarks']) ?>
        <?php if ($order['returned_at']): ?><div class="font-sm text-muted"><?= fmtDateTime($order['returned_at']) ?></div><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Commercial Summary Card (Single Source of Truth) -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden">
    <div class="card-header" style="background:var(--accent-pale);padding:14px 20px;border-bottom:1px solid var(--border)">
      <div class="card-title" style="display:flex;align-items:center;gap:8px;font-size:1.05rem;font-weight:700;color:var(--text-primary)">
        <?= svgIcon('chart', 18) ?> Commercial Summary
      </div>
    </div>
    
    <div class="card-body" style="padding:18px">
      <?php 
        $comm = getOrderCommercialSummary($order);
        $mrcCurr = $order['mrc_currency'] ?: 'TZS';
      ?>
      
      <!-- 1. NRC — One-Time Charges -->
      <div style="margin-bottom:18px;padding-bottom:16px;border-bottom:1px dashed var(--border)">
        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
          <span>NRC — One-Time Charges</span>
          <span style="font-size:0.72rem;font-weight:500;text-transform:none">Non-Recurring</span>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;font-size:0.88rem">
          <?php if (!$comm['has_rev_nrc'] && $comm['std_nrc'] > 0): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-secondary)">Standard NRC</span>
            <span style="font-weight:600;color:var(--text-primary);text-align:right">TZS <?= money($comm['std_nrc']) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($comm['has_rev_nrc']): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-muted);text-decoration:line-through">Standard NRC (<?= money($comm['std_nrc']) ?>)</span>
            <span class="badge badge-secondary" style="font-size:0.7rem">Replaced</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-primary);font-weight:600">Revised NRC</span>
            <span style="font-weight:700;color:var(--accent);text-align:right">TZS <?= money($comm['rev_nrc']) ?></span>
          </div>
          <?php if (!$isPartner && !empty($order['nrc_justification'])): ?>
          <div style="font-size:0.78rem;color:var(--text-muted);background:var(--surface-2);padding:4px 8px;border-radius:4px;margin-top:2px">
            <strong>Justification:</strong> <?= e($order['nrc_justification']) ?>
          </div>
          <?php endif; ?>
          <?php endif; ?>

          <?php if ($comm['rh_nrc'] > 0): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-secondary)">Remote Hands NRC</span>
            <span style="font-weight:600;color:var(--text-primary);text-align:right">TZS <?= money($comm['rh_nrc']) ?></span>
          </div>
          <?php endif; ?>

          <!-- NRC Subtotal -->
          <div style="display:flex;justify-content:space-between;align-items:center;padding-top:6px;margin-top:2px;border-top:1px solid var(--border);font-weight:600">
            <span>NRC Subtotal</span>
            <span style="text-align:right">TZS <?= money($comm['nrc_subtotal']) ?></span>
          </div>

          <!-- VAT (18%) -->
          <div style="display:flex;justify-content:space-between;align-items:center;color:var(--text-secondary)">
            <span>VAT (18%)</span>
            <span style="text-align:right">TZS <?= money($comm['vat_nrc']) ?></span>
          </div>

          <!-- Total NRC Incl. VAT -->
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:rgba(16, 185, 129, 0.08);border:1px solid rgba(16, 185, 129, 0.3);border-radius:var(--radius-md);margin-top:4px">
            <span style="font-weight:700;color:#065F46">Total NRC Incl. VAT</span>
            <span style="font-weight:800;font-size:1.05rem;color:#065F46;text-align:right">TZS <?= money($comm['total_nrc']) ?></span>
          </div>
        </div>
      </div>

      <!-- 2. MRC — Recurring Charges -->
      <div>
        <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
          <span>MRC — Recurring Charges</span>
          <span style="font-size:0.72rem;font-weight:500;text-transform:none">Monthly</span>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;font-size:0.88rem">
          <?php if (!$comm['has_rev_mrc'] && !$comm['has_mgmt_mrc']): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-secondary)">Standard MRC (<?= e($mrcCurr) ?>)</span>
            <span style="font-weight:600;color:var(--text-primary);text-align:right"><?= e($mrcCurr) ?> <?= money($comm['std_mrc']) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($comm['has_rev_mrc'] && !$comm['has_mgmt_mrc']): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-muted);text-decoration:line-through">Standard MRC (<?= money($comm['std_mrc']) ?>)</span>
            <span class="badge badge-secondary" style="font-size:0.7rem">Replaced</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-primary);font-weight:600">Revised MRC</span>
            <span style="font-weight:700;color:var(--accent);text-align:right"><?= e($mrcCurr) ?> <?= money($comm['rev_mrc']) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($comm['has_mgmt_mrc']): ?>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-muted);text-decoration:line-through">Standard MRC (<?= money($comm['std_mrc']) ?>)</span>
            <span class="badge badge-secondary" style="font-size:0.7rem">Overridden</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--text-primary);font-weight:600">Management Approved Price</span>
            <span style="font-weight:700;color:#10B981;text-align:right"><?= e($mrcCurr) ?> <?= money($comm['mgmt_mrc']) ?></span>
          </div>
          <?php endif; ?>

          <?php if (!empty($order['mrc_justification']) && !$isPartner): ?>
          <div style="font-size:0.78rem;color:var(--text-muted);background:var(--surface-2);padding:4px 8px;border-radius:4px;margin-top:2px">
            <strong>MRC Justification:</strong> <?= e($order['mrc_justification']) ?>
          </div>
          <?php endif; ?>

          <?php if ($order['management_remarks'] && ($order['management_remarks_visible'] || !$isPartner)): ?>
          <div style="font-size:0.78rem;background:var(--surface-2);border-radius:4px;padding:6px 8px;margin-top:2px">
            <strong>Management note:</strong> <?= e($order['management_remarks']) ?>
          </div>
          <?php endif; ?>

          <!-- Effective Base MRC Subtotal -->
          <div style="display:flex;justify-content:space-between;align-items:center;padding-top:6px;margin-top:2px;border-top:1px solid var(--border);font-weight:600">
            <span>Base MRC Subtotal</span>
            <span style="text-align:right"><?= e($mrcCurr) ?> <?= money($comm['mrc_val']) ?></span>
          </div>

          <!-- VAT (18%) -->
          <div style="display:flex;justify-content:space-between;align-items:center;color:var(--text-secondary)">
            <span>VAT (18%)</span>
            <span style="text-align:right"><?= e($mrcCurr) ?> <?= money($comm['vat_mrc']) ?></span>
          </div>

          <!-- Effective Total MRC Incl. VAT -->
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:rgba(15, 76, 129, 0.08);border:1px solid rgba(15, 76, 129, 0.3);border-radius:var(--radius-md);margin-top:4px">
            <span style="font-weight:700;color:#0F4C81">Effective Total MRC Incl. VAT</span>
            <span style="font-weight:800;font-size:1.05rem;color:#0F4C81;text-align:right"><?= e($mrcCurr) ?> <?= money($comm['total_mrc']) ?></span>
          </div>
        </div>
      </div>

      <!-- Combined Contract Total -->
      <div style="margin-top:16px;padding:10px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.85rem;font-weight:700;color:var(--text-primary)">Total First Payment (NRC + 1st Month MRC)</span>
        <span style="font-size:1.05rem;font-weight:800;color:var(--text-primary);text-align:right">TZS <?= money($comm['total_revenue']) ?></span>
      </div>

    </div>
  </div>
</div>

<?php // ============================================================
// ROLE-SPECIFIC ACTION PANELS
// ============================================================ ?>

<?php // === BSA: FEASIBILITY REVIEW PANEL === ?>
<?php if ($status === 'Feasibility Review' && ($isBSA || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--warning)">
  <div class="card-header" style="background:rgba(var(--warning-rgb,255,171,64),.12)">
    <div class="card-title"><?= svgIcon('search') ?> BSA Technical Review</div>
    <div class="card-subtitle">Review technical feasibility and confirm NRC. NRC only — do not edit MRC.</div>
  </div>
  <div class="card-body">
    <?php if ($order['return_remarks']): ?>
    <div class="alert alert-info" style="margin-bottom:16px">
      <strong>Partner concern:</strong> <?= e($order['return_remarks']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=bsa_feasible">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Standard NRC (TZS) <span class="text-muted font-sm">Auto-populated</span></label>
          <input type="number" class="form-control" value="<?= e($order['standard_nrc'] ?? $order['base_nrc_usd']) ?>" disabled style="opacity:.6">
        </div>
        <div class="form-group">
          <label>Revised NRC (TZS) <span class="text-muted font-sm">Edit if different from standard</span></label>
          <input type="number" step="0.01" name="revised_nrc" class="form-control" value="<?= e($order['revised_nrc'] ?? '') ?>" placeholder="Leave blank if same as standard">
        </div>
        <div class="form-group">
          <label>NRC Justification <span class="required">*</span> <span class="text-muted font-sm">Required if NRC changes</span></label>
          <input type="text" name="nrc_justification" class="form-control" value="<?= e($order['nrc_justification'] ?? '') ?>" placeholder="e.g. Extended fibre route 350m">
        </div>
      </div>
      <div class="form-group" style="margin-top:8px">
        <label>Technical Remarks <span class="required">*</span></label>
        <textarea name="technical_remarks" class="form-control" rows="3" placeholder="Describe build requirement, distance, infrastructure conditions..."><?= e($order['bsa_special_conditions'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Technically Feasible</button>
      </div>
    </form>

    <div class="divider" style="margin:16px 0"></div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=bsa_not_feasible" id="bsaNotFeasibleForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-group">
        <label>Not Feasible Reason <span class="required">*</span></label>
        <textarea name="not_feasible_reason" id="not_feasible_reason_input" class="form-control" rows="2" placeholder="Mandatory reason why this is not technically feasible..."></textarea>
      </div>
      <button type="button" class="btn btn-danger" id="bsaNotFeasibleBtn"><?= svgIcon('x') ?> Technically Not Feasible</button>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const notFeasibleBtn = document.getElementById('bsaNotFeasibleBtn');
      const notFeasibleForm = document.getElementById('bsaNotFeasibleForm');
      const reasonInput = document.getElementById('not_feasible_reason_input');

      if (notFeasibleBtn && notFeasibleForm) {
        notFeasibleBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const reason = reasonInput ? reasonInput.value.trim() : '';
          if (!reason) {
            neilosAlert('Please enter a mandatory reason why this order is technically not feasible.', 'Reason Required', '⚠️');
            reasonInput?.focus();
            return;
          }

          neilosConfirm(
            'This will notify the partner that this order has been marked as technically not feasible.',
            'Mark as Technically Not Feasible?',
            'Mark as Not Feasible',
            'btn-danger',
            '🚫'
          ).then(function(confirmed) {
            if (confirmed) {
              notFeasibleForm.submit();
            }
          });
        });
      }
    });
    </script>
  </div>
</div>
<?php endif; ?>

<?php // === KAM: COMMERCIAL APPROVAL PANEL === ?>
<?php if ($status === 'Await Commercial Approval' && ($isKAM || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--warning)">
  <div class="card-header" style="background:rgba(var(--warning-rgb,255,171,64),.12)">
    <div class="card-title"><?= svgIcon('dollar') ?> KAM Commercial Review</div>
    <div class="card-subtitle">Review and approve MRC. NRC is locked — BSA-confirmed.</div>
  </div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:16px">
      <?= svgIcon('info') ?> <strong>NRC confirmed by BSA:</strong> TZS <?= money((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd'])) ?> (read-only)
    </div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=kam_approve" id="kamApproveForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Standard MRC (<?= e($order['mrc_currency']) ?>)</label>
          <input type="number" class="form-control" value="<?= e($order['standard_mrc'] ?? $order['base_mrc']) ?>" disabled style="opacity:.6">
        </div>
        <div class="form-group">
          <label>Revised MRC (<?= e($order['mrc_currency']) ?>) <span class="text-muted font-sm">If different from standard</span></label>
          <input type="number" step="0.01" name="revised_mrc" id="revisedMrc" class="form-control" value="<?= e($order['revised_mrc'] ?? '') ?>" placeholder="Leave blank if standard applies">
        </div>
        <div class="form-group">
          <label>Commercial Justification</label>
          <input type="text" name="mrc_justification" class="form-control" value="<?= e($order['mrc_justification'] ?? '') ?>" placeholder="Required if MRC differs from standard">
        </div>
        <div class="form-group" style="grid-column: 1 / -1">
          <label>KAM Attachment (Survey report / Commercial agreement)</label>
          <input type="file" name="kam_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        </div>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Approve — Move to Pending SOF</button>
      </div>
    </form>

    <div class="divider" style="margin:16px 0"></div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=kam_escalate">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label>Proposed Revised MRC (for Management review)</label>
          <input type="number" step="0.01" name="revised_mrc" class="form-control" placeholder="Price to be approved by Management">
        </div>
        <div class="form-group">
          <label>Escalation Justification <span class="required">*</span></label>
          <input type="text" name="mrc_justification" class="form-control" placeholder="Explain why exception approval is needed" required>
        </div>
      </div>
      <button type="submit" class="btn btn-warning"><?= svgIcon('users') ?> Requires Further Approval — Send to Management</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // === MANAGEMENT APPROVAL PANEL === ?>
<?php if ($status === 'Management Approval' && ($isMgmt || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--danger)">
  <div class="card-header" style="background:rgba(220,53,69,.1)">
    <div class="card-title"><?= svgIcon('users') ?> Management Approval — Exception Review</div>
    <div class="card-subtitle">KAM has requested exception approval for non-standard pricing.</div>
  </div>
  <div class="card-body">
    <div class="form-grid form-grid-3" style="margin-bottom:16px">
      <div class="form-group"><label>Standard MRC</label><div><?= e($order['mrc_currency']) ?> <?= money((float)($order['standard_mrc'] ?? $order['base_mrc'])) ?></div></div>
      <div class="form-group"><label>KAM Proposed MRC</label><div class="font-600"><?= e($order['mrc_currency']) ?> <?= money((float)($order['revised_mrc'] ?? 0)) ?></div></div>
      <div class="form-group"><label>KAM Justification</label><div><?= e($order['mrc_justification'] ?: '—') ?></div></div>
    </div>

    <div class="form-grid form-grid-2">
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=management_approve">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-group">
          <label>Management Approved Price (leave blank for requested price)</label>
          <input type="number" step="0.01" name="management_approved_price" class="form-control" value="<?= e($order['revised_mrc'] ?? '') ?>" placeholder="Leave blank to approve as requested">
        </div>
        <div class="form-group">
          <label>Management Remarks</label>
          <textarea name="management_remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="management_remarks_visible" value="1" id="mrvCheck">
          <label for="mrvCheck" style="margin:0;cursor:pointer">Make remarks visible to partner</label>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Approve as requested</button>
          <button type="submit" class="btn btn-primary" onclick="if(!this.form.management_approved_price.value){neilosAlert('Please enter a revised price.', 'Price Required');return false;}"><?= svgIcon('edit') ?> Approve with revised price</button>
        </div>
      </form>

      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=management_reject">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-group">
          <label>Reason for Rejecting Exception</label>
          <textarea name="management_remarks" class="form-control" rows="3" placeholder="Explain why the exception is not approved (standard price will apply)..." required></textarea>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="management_remarks_visible" value="1" id="mrvCheck2">
          <label for="mrvCheck2" style="margin:0;cursor:pointer">Make remarks visible to partner</label>
        </div>
        <button type="submit" class="btn btn-danger"><?= svgIcon('x') ?> Reject exception / keep standard price</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php // === PARTNER: PENDING SOF PANEL === ?>
<?php if ($status === 'Pending SOF' && ($isPartner || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
  <div class="card-header" style="background:rgba(var(--primary-rgb,99,102,241),.1)">
    <div class="card-title"><?= svgIcon('document') ?> Pending SOF — Action Required</div>
    <div class="card-subtitle">Your service is feasible and commercially approved. Please generate, sign, and upload the Service Order Form.</div>
  </div>
  <div class="card-body">
    <?php if ($order['sof_return_comments']): ?>
    <div class="alert alert-warning" style="margin-bottom:16px">
      <?= svgIcon('info') ?> <strong>SOF returned for correction:</strong> <?= e($order['sof_return_comments']) ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
      <div style="flex:1;min-width:280px">
        <div class="card" style="background:var(--surface-2)">
          <div class="card-body" style="padding:16px">
            <div style="font-weight:600;margin-bottom:8px">Step 1 — Generate SOF</div>
            <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Download and print your Service Order Form pre-filled with all approved terms.</p>
            <button type="button" class="btn btn-primary" onclick="viewSystemFile('<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>', 'SOF_<?= e($order['order_number']) ?>.pdf', '<?= APP_URL ?>/?page=orders&action=generate_sof&id=<?= $order['id'] ?>&download=1', {doc_type:'Service Order Form'})"><?= svgIcon('document') ?> Generate &amp; View SOF</button>
          </div>
        </div>
      </div>
      <div style="flex:1;min-width:280px">
        <div class="card" style="background:var(--surface-2)">
          <div class="card-body" style="padding:16px">
            <div style="font-weight:600;margin-bottom:8px">Step 2 — Upload Signed SOF</div>
            <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Sign the SOF (stamp + authorized signature) and upload it here.</p>
            <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_signed_sof" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <div class="form-group" style="margin-bottom:8px">
                <input type="file" name="signed_sof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
              </div>
              <button type="submit" class="btn btn-success"><?= svgIcon('upload') ?> Upload Signed SOF &amp; Submit Order</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="divider" style="margin:20px 0"></div>

    <div>
      <div style="font-weight:600;margin-bottom:10px;color:var(--text-secondary)">Not satisfied with the terms?</div>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_feasibility">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-grid form-grid-3" style="margin-bottom:12px">
          <div class="form-group">
            <label>Return Action</label>
            <select name="return_action" class="form-control" required>
              <option value="">Select option...</option>
              <option value="back_to_survey">Back to survey (Routes to BSA)</option>
              <option value="back_to_pricing">Back to Pricing (Routes to KAM)</option>
              <option value="start_project">Start Project (Proceed with SOF)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Reason</label>
            <select name="return_reason" class="form-control" required>
              <option value="">Select reason...</option>
              <option value="Technical concern">Technical concern</option>
              <option value="NRC concern">NRC concern</option>
              <option value="MRC / pricing concern">MRC / pricing concern</option>
              <option value="Wrong customer/site details">Wrong customer/site details</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Your Remarks <span class="required">*</span></label>
            <input type="text" name="return_remarks" class="form-control" placeholder="Explain your concern..." required>
          </div>
        </div>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return to Feasibility</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php // === SOF REVIEW PANEL (Internal) === ?>
<?php if ($status === 'SOF Review' && !$isPartner): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
  <div class="card-header"><div class="card-title"><?= svgIcon('edit') ?> SOF Review &amp; Countersignature</div></div>
  <div class="card-body">
    <?php if ($order['sof_signed_file']): ?>
    <div class="alert alert-success" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div><?= svgIcon('check') ?> Signed SOF uploaded by partner: <strong><?= e($order['sof_signed_filename']) ?></strong></div>
      <?= renderFileActions(['file_url' => $order['sof_signed_file'], 'file_name' => $order['sof_signed_filename'] ?: 'Signed SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=sof_signed_file&id=' . $order['id']]) ?>
    </div>
    <?php endif; ?>

    <div class="form-grid form-grid-2" style="margin-bottom:16px">
      <div>
        <div style="font-weight:600;margin-bottom:8px">Step 1 — Upload Countersigned SOF</div>
        <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Upload the Neilos-countersigned copy. This becomes the official contractual document.</p>
        <?php if (!$order['countersigned_sof_file']): ?>
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_countersigned_sof" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div class="form-group" style="margin-bottom:8px">
            <input type="file" name="countersigned_sof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>
          <button type="submit" class="btn btn-primary"><?= svgIcon('upload') ?> Upload Countersigned SOF</button>
        </form>
        <?php else: ?>
        <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
          <div><?= svgIcon('check') ?> Countersigned SOF uploaded: <strong><?= e($order['countersigned_sof_filename']) ?></strong> on <?= fmtDate($order['countersigned_sof_at']) ?></div>
          <?= renderFileActions(['file_url' => $order['countersigned_sof_file'], 'file_name' => $order['countersigned_sof_filename'] ?: 'Countersigned SOF', 'download_url' => APP_URL . '/?page=download&table=orders&column=countersigned_sof_file&id=' . $order['id']]) ?>
        </div>
        <?php endif; ?>
      </div>
      <div>
        <div style="font-weight:600;margin-bottom:8px">Step 2 — Proceed to Project</div>
        <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Only available after countersigned SOF is uploaded. This releases the order to Project/Installation.</p>
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=proceed_to_project">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <button type="submit" class="btn btn-success" <?= !$order['countersigned_sof_file'] ? 'disabled title="Upload countersigned SOF first"' : '' ?>>
            <?= svgIcon('project') ?> Proceed to Project
          </button>
        </form>
      </div>
    </div>

    <div class="divider" style="margin:16px 0"></div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_sof">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-group">
        <label>Return SOF for Correction — Comments <span class="required">*</span></label>
        <textarea name="sof_return_comments" class="form-control" rows="2" placeholder="Describe what the partner needs to correct..."></textarea>
      </div>
      <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return SOF to Partner</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // === INSTALLATION PANEL === ?>
<?php if ($status === 'Installation'): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><?= svgIcon('project') ?> Installation</div>
    <div class="card-subtitle">Project is in progress. Contractor will update and submit evidence.</div>
  </div>
  <div class="card-body">
    <?php if (!$isPartner && ($isPM || $isAdminUser)): ?>
    <?php if ($assignment): ?>
    <div class="alert alert-info" style="margin-bottom:16px">
      Assigned to <strong><?= e($assignment['contractor_name']) ?></strong>
      <?php if ($assignment['contractor_user_name']): ?>(<?= e($assignment['contractor_user_name']) ?>)<?php endif; ?>
      · Status: <span class="badge badge-primary"><?= e($assignment['status']) ?></span>
      <?php if ($assignment['target_date']): ?> · Due: <?= fmtDate($assignment['target_date']) ?><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="margin-bottom:16px"><?= svgIcon('info') ?> No contractor assigned yet.</div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=assign_contractor">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Assign Contractor <span class="required">*</span></label>
          <select name="contractor_partner_id" class="form-control" required>
            <option value="">Select contractor...</option>
            <?php foreach ($contractorList as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Installation Target Date <span class="required">*</span></label>
          <input type="date" name="target_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Work Order Notes</label>
          <input type="text" name="work_order_notes" class="form-control" placeholder="Any special instructions...">
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><?= svgIcon('users') ?> Assign Contractor</button>
    <?php endif; ?>

    <!-- PM Supplemental Project Updates & Challenges -->
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <div class="card-subtitle" style="font-weight:700;color:var(--primary);margin-bottom:12px"><?= svgIcon('upload') ?> Project Manager — Installation Challenges &amp; Site Photos</div>
      <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=upload_evidence" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?? 0 ?>">
        <div class="form-group" style="margin:0">
          <label class="font-sm">Evidence Type / Category</label>
          <select name="evidence_type" class="form-control" required>
            <option value="Site Photo">Customer / Site Photo</option>
            <option value="ONT/ONU Serial">ONT / ONU / CPE Serial</option>
            <option value="Signal Test">Signal Test / Optical Reading</option>
            <option value="Speed Test">Speed Test Result</option>
            <option value="Installation Remarks">Installation Challenge / Note</option>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label class="font-sm">Notes / Challenge Details</label>
          <input type="text" name="notes" class="form-control" placeholder="Describe progress, challenge, or photo details...">
        </div>
        <div class="form-group" style="margin:0">
          <label class="font-sm">Upload Photo / Document</label>
          <input type="file" name="evidence_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm" style="grid-column: 1 / -1; justify-self: start; margin-top: 6px">
          <?= svgIcon('upload') ?> Upload Supplemental Evidence / Notes
        </button>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($isPartner): ?>
    <div class="alert alert-info">
      <?= svgIcon('info') ?> Your order is currently with our installation team. You will be notified once installation is complete and ready for your UAT acceptance.
    </div>
    <?php endif; ?>

    <?php if ($order['uat_return_reason']): ?>
    <div class="alert alert-warning" style="margin-top:12px">
      <strong>Returned from UAT:</strong> <?= e($order['uat_return_reason']) ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php // === TESTING PANEL (PM/BSA review) === ?>
<?php if ($status === 'Testing' && !$isPartner && !$isContractor): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
  <div class="card-header"><div class="card-title"><?= svgIcon('check') ?> Testing — Internal Review</div></div>
  <div class="card-body">
    <p style="color:var(--text-secondary);margin-bottom:16px">Contractor has submitted work. Review evidence before sending to partner for UAT.</p>

    <?php if (!empty($evidence)): ?>
    <div class="table-responsive" style="margin-bottom:16px;width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table class="data-table evidence-table" style="width:100%;min-width:680px;border-collapse:collapse;table-layout:fixed">
        <thead>
          <tr>
            <th style="width:25%;text-align:left;padding:12px 16px">TYPE</th>
            <th style="width:30%;text-align:left;padding:12px 16px">NOTES / SERIAL</th>
            <th style="width:15%;text-align:center;padding:12px 16px">FILE</th>
            <th style="width:15%;text-align:left;padding:12px 16px;white-space:nowrap;min-width:100px">BY</th>
            <th style="width:15%;text-align:left;padding:12px 16px;white-space:nowrap;min-width:110px">DATE</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evidence as $ev): ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:12px 16px;vertical-align:middle;text-align:left">
              <span class="badge badge-secondary" style="font-size:.8rem;padding:6px 10px;white-space:nowrap;display:inline-block"><?= e($ev['evidence_type']) ?></span>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($ev['notes'] ?: ($ev['serial_number'] ?: '—')) ?>">
              <?= e($ev['notes'] ?: ($ev['serial_number'] ?: '—')) ?>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:center;white-space:nowrap">
              <?php if ($ev['file_path']): ?>
              <?= renderFileActions(['file_url' => $ev['file_path'], 'file_name' => $ev['file_name'] ?: $ev['evidence_type'], 'download_url' => APP_URL . '/?page=download&table=contractor_evidence&id=' . $ev['id']]) ?>
              <?php else: ?>
              —
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:left;white-space:nowrap;min-width:100px;font-size:.875rem" class="font-sm">
              <?= e($ev['full_name'] ?: '—') ?>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:left;white-space:nowrap;min-width:110px;font-size:.875rem" class="text-muted font-sm">
              <?= fmtDate($ev['uploaded_at']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="margin-bottom:16px"><?= svgIcon('info') ?> No evidence uploaded yet.</div>
    <?php endif; ?>

    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=approve_testing">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Approve Testing — Move to UAT</button>
      </form>
      <button class="btn btn-warning" data-modal-open="returnContractorModal"><?= svgIcon('refresh') ?> Return to Contractor</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="returnContractorModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Return to Contractor</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_contractor">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Comments for Contractor <span class="required">*</span></label>
          <textarea name="return_comments" class="form-control" rows="4" placeholder="What needs to be corrected or re-submitted?" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return to Contractor</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // === UAT PANEL (Partner) === ?>
<?php if ($status === 'UAT'): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--success)">
  <div class="card-header" style="background:rgba(var(--success-rgb,34,197,94),.1)">
    <div class="card-title"><?= svgIcon('check') ?> UAT — User Acceptance Testing</div>
    <div class="card-subtitle"><?= $isPartner ? 'Please review the installation evidence and accept or return the service.' : 'Awaiting partner UAT acceptance.' ?></div>
  </div>
  <div class="card-body">
    <?php if ($order['uat_notified_at']): ?>
    <div class="alert alert-info" style="margin-bottom:16px">
      <?= svgIcon('clock') ?> Partner notified: <?= fmtDateTime($order['uat_notified_at']) ?>
      <?php if ($order['uat_deadline']): ?>
      · Deadline: <?= fmtDateTime($order['uat_deadline']) ?>
      <strong> · <?= strtotime($order['uat_deadline']) > time() ? round((strtotime($order['uat_deadline']) - time()) / 3600) . ' hrs remaining' : '⚠️ EXPIRED — Auto-accept will trigger' ?></strong>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($isPartner || $isAdminUser): ?>
    <div style="display:flex;gap:16px;flex-wrap:wrap">
      <div style="flex:1;min-width:240px">
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=uat_accept">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div style="background:rgba(var(--success-rgb,34,197,94),.08);border:1px solid var(--success);border-radius:var(--radius);padding:20px">
            <div style="font-weight:600;font-size:.95rem;margin-bottom:8px">✅ Accept Service</div>
            <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px">Service is working correctly. Accept and close the order. Billing start date will be set to today.</p>
            <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Accept Service — Close Order</button>
          </div>
        </form>
      </div>
      <div style="flex:1;min-width:240px">
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=return_to_installation">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <div style="background:rgba(220,53,69,.06);border:1px solid var(--danger);border-radius:var(--radius);padding:20px">
            <div style="font-weight:600;font-size:.95rem;margin-bottom:8px">🔄 Return to Installation</div>
            <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px">Service requires correction. Please describe the issue.</p>
            <div class="form-group" style="margin-bottom:10px">
              <textarea name="return_reason" class="form-control" rows="3" placeholder="Describe what needs to be fixed..." required></textarea>
            </div>
            <button type="submit" class="btn btn-danger"><?= svgIcon('refresh') ?> Return to Installation</button>
          </div>
        </form>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">Partner is reviewing installation. Awaiting acceptance or return.</div>
    <?php endif; ?>

    <?php if (!empty($evidence) && ($isPartner || !$isPartner)): ?>
    <div class="divider" style="margin:20px 0"></div>
    <div style="font-weight:600;margin-bottom:12px">Installation Evidence</div>
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Evidence Type</th><th>Notes</th><th>File</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($evidence as $ev): ?>
          <tr>
            <td><span class="badge badge-secondary"><?= e($ev['evidence_type']) ?></span></td>
            <td><?= e($ev['notes'] ?: $ev['serial_number'] ?: '—') ?></td>
            <td><?php if ($ev['file_path']): ?><?= renderFileActions(['file_url' => $ev['file_path'], 'file_name' => $ev['file_name'] ?: $ev['evidence_type'], 'download_url' => APP_URL . '/?page=download&table=contractor_evidence&id=' . $ev['id']]) ?><?php else: ?>—<?php endif; ?></td>
            <td class="text-muted font-sm"><?= fmtDate($ev['uploaded_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php // === CLOSED PANEL === ?>
<?php if ($status === 'Closed'): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--success)">
  <div class="card-header" style="background:rgba(var(--success-rgb,34,197,94),.1)">
    <div class="card-title"><?= svgIcon('check') ?> Order Closed</div>
  </div>
  <div class="card-body">
    <div class="form-grid form-grid-3">
      <div class="form-group"><label>Closed Date</label><div class="font-600 text-success"><?= fmtDate($order['closed_date']) ?></div></div>
      <div class="form-group"><label>Billing Start Date</label><div class="font-600 text-success"><?= fmtDate($order['billing_start_date']) ?></div></div>
      <div class="form-group"><label>Service ID</label><div class="font-600"><?= e($order['service_id'] ?: '—') ?></div></div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php // ============================================================
// TABS: Timeline, Documents, Evidence, Returns
// ============================================================ ?>
<div class="tabs" data-group="order">
  <button class="tab-btn active" data-tab="timeline" data-tab-group="order">Timeline</button>
  <button class="tab-btn" data-tab="documents" data-tab-group="order">Documents (<?= count($docs) ?>)</button>
  <?php if (!empty($evidence)): ?><button class="tab-btn" data-tab="evidence" data-tab-group="order">Evidence (<?= count($evidence) ?>)</button><?php endif; ?>
  <?php if (!empty($progressUpdates) && !$isPartner): ?><button class="tab-btn" data-tab="progress" data-tab-group="order">Contractor Updates (<?= count($progressUpdates) ?>)</button><?php endif; ?>
  <?php if (!empty($orderReturns) && !$isPartner): ?><button class="tab-btn" data-tab="returns" data-tab-group="order">Return Audit (<?= count($orderReturns) ?>)</button><?php endif; ?>
</div>

<div class="tab-panel active" data-tab-panel="timeline" data-tab-group="order">
  <div class="card">
    <div class="card-body">
      <?php if (empty($timeline)): ?>
      <div class="empty-state"><div class="empty-state-title">No timeline entries</div></div>
      <?php else: ?>
      <div class="timeline">
        <?php foreach ($timeline as $tl): ?>
        <div class="timeline-item">
          <div class="timeline-dot <?= $tl['status'] === 'Closed' ? 'success' : (in_array($tl['status'], ['Cancelled','Not Feasible']) ? 'danger' : '') ?>"></div>
          <div class="timeline-time"><?= fmtDateTime($tl['changed_at']) ?> by <?= e($tl['full_name'] ?: 'System') ?></div>
          <div class="timeline-label"><?= e($tl['status']) ?></div>
          <?php if ($tl['note']): ?><div class="timeline-note"><?= e($tl['note']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="tab-panel" data-tab-panel="documents" data-tab-group="order">
  <div class="card">
    <div class="card-header"><div class="card-title">Order Documents</div></div>
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Uploaded By</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($docs)): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No documents uploaded</div></div></td></tr>
          <?php else: ?>
          <?php foreach ($docs as $d): ?>
          <tr>
            <td class="font-600"><?= e($d['file_name']) ?></td>
            <td><span class="badge badge-secondary"><?= e($d['document_type']) ?></span></td>
            <td class="font-sm"><?= formatBytes($d['file_size']) ?></td>
            <td class="font-sm"><?= e($d['full_name'] ?: '—') ?></td>
            <td class="text-muted font-sm"><?= fmtDate($d['uploaded_at']) ?></td>
            <td><?= renderFileActions(['file_url' => $d['file_path'], 'file_name' => $d['file_name'] ?: 'Document', 'download_url' => APP_URL . '/?page=download&table=order_documents&id=' . $d['id']]) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!empty($evidence)): ?>
<div class="tab-panel" data-tab-panel="evidence" data-tab-group="order">
  <div class="card">
    <div class="card-header"><div class="card-title">Installation Evidence</div></div>
    <div class="table-responsive" style="width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table class="data-table evidence-table" style="width:100%;min-width:680px;border-collapse:collapse;table-layout:fixed">
        <thead>
          <tr>
            <th style="width:25%;text-align:left;padding:12px 16px">TYPE</th>
            <th style="width:30%;text-align:left;padding:12px 16px">NOTES / SERIAL</th>
            <th style="width:15%;text-align:center;padding:12px 16px">FILE</th>
            <th style="width:15%;text-align:left;padding:12px 16px;white-space:nowrap;min-width:100px">BY</th>
            <th style="width:15%;text-align:left;padding:12px 16px;white-space:nowrap;min-width:110px">DATE</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evidence as $ev): ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:12px 16px;vertical-align:middle;text-align:left">
              <span class="badge badge-secondary" style="font-size:.8rem;padding:6px 10px;white-space:nowrap;display:inline-block"><?= e($ev['evidence_type']) ?></span>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($ev['notes'] ?: ($ev['serial_number'] ?: '—')) ?>">
              <?= e($ev['notes'] ?: ($ev['serial_number'] ?: '—')) ?>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:center;white-space:nowrap">
              <?php if ($ev['file_path']): ?>
              <?= renderFileActions(['file_url' => $ev['file_path'], 'file_name' => $ev['file_name'] ?: $ev['evidence_type'], 'download_url' => APP_URL . '/?page=download&table=contractor_evidence&id=' . $ev['id']]) ?>
              <?php else: ?>
              —
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:left;white-space:nowrap;min-width:100px;font-size:.875rem" class="font-sm">
              <?= e($ev['full_name'] ?: '—') ?>
            </td>
            <td style="padding:12px 16px;vertical-align:middle;text-align:left;white-space:nowrap;min-width:110px;font-size:.875rem" class="text-muted font-sm">
              <?= fmtDate($ev['uploaded_at']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($progressUpdates) && !$isPartner): ?>
<div class="tab-panel" data-tab-panel="progress" data-tab-group="order">
  <div class="card">
    <div class="card-header"><div class="card-title">Contractor Progress Updates</div></div>
    <div class="card-body">
      <div class="timeline">
        <?php foreach ($progressUpdates as $pu): ?>
        <div class="timeline-item">
          <div class="timeline-dot"></div>
          <div class="timeline-time"><?= fmtDateTime($pu['created_at']) ?> by <?= e($pu['full_name'] ?: '—') ?></div>
          <div class="timeline-label"><span class="badge badge-secondary"><?= e($pu['progress_status']) ?></span><?php if ($pu['delay_reason']): ?> <span class="badge badge-warning"><?= e($pu['delay_reason']) ?></span><?php endif; ?></div>
          <div class="timeline-note"><?= e($pu['notes']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($orderReturns) && !$isPartner): ?>
<div class="tab-panel" data-tab-panel="returns" data-tab-group="order">
  <div class="card">
    <div class="card-header"><div class="card-title">Return Audit Trail</div></div>
    <div class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Date</th><th>By</th><th>From</th><th>To</th><th>Routed To</th><th>Reason</th><th>Remarks</th></tr></thead>
        <tbody>
          <?php foreach ($orderReturns as $r): ?>
          <tr>
            <td class="font-sm"><?= fmtDate($r['returned_at']) ?></td>
            <td class="font-sm"><?= e($r['full_name'] ?: '—') ?></td>
            <td><span class="badge <?= orderStatusClass($r['from_status']) ?>"><?= e($r['from_status']) ?></span></td>
            <td><span class="badge <?= orderStatusClass($r['to_status']) ?>"><?= e($r['to_status']) ?></span></td>
            <td><span class="badge badge-secondary"><?= e($r['routed_to']) ?></span></td>
            <td><?= e($r['return_reason']) ?></td>
            <td><?= e($r['return_remarks'] ?: '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php // Admin Status Override Modal ?>
<?php if ($isAdminUser && !in_array($status, ['Closed','Cancelled'])): ?>
<div class="modal-backdrop" id="statusModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Admin Status Override</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_status">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>New Status</label>
          <select name="new_status" class="form-control" required>
            <?php foreach ($allStatuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $status === $st ? 'disabled' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Service Type Override (Optional)</label>
          <select name="new_service_type" class="form-control">
            <option value="">— Keep Current (<?= e($order['service_type']) ?>) —</option>
            <option value="Layer 2 ( last mile)">Layer 2 ( last mile)</option>
            <option value="FTTH">FTTH</option>
            <option value="FTTB">FTTB</option>
            <option value="BIA (Broadband Internet Access)">BIA (Broadband Internet Access)</option>
            <option value="Remote Hands Only">Remote Hands Only</option>
            <option value="DIA">DIA</option>
            <option value="Dedicated Layer 2">Dedicated Layer 2</option>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Note</label>
          <textarea name="note" class="form-control" rows="3" placeholder="Reason for admin override..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Override Status</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>