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
    <?php if ($status === 'Closed'): ?>
      <?php if ($isAdminUser || $isMgmt): ?>
        <button type="button" class="btn btn-warning" data-modal-open="editClosedOrderModal"><?= svgIcon('edit') ?> Edit Closed Order</button>
        <button type="button" class="btn btn-secondary" data-modal-open="statusModal"><?= svgIcon('refresh') ?> Status Override</button>
      <?php else: ?>
        <span class="badge badge-secondary" style="font-size:0.8rem;padding:7px 12px;display:inline-flex;align-items:center;gap:4px">🔒 Closed &amp; Locked</span>
      <?php endif; ?>
    <?php else: ?>
      <?php if ($isAdminUser || $isMgmt): ?>
        <button class="btn btn-secondary" data-modal-open="statusModal"><?= svgIcon('edit') ?> Admin Override</button>
      <?php endif; ?>
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
    <?php if (!empty($order['sla_paused'])): ?>
    <div class="alert alert-warning" style="margin-top:12px;margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
      <div>
        <strong>⏸ SLA Clock Paused:</strong> A contractor blocker has been posted on this order. (Paused since <?= fmtDateTime($order['sla_paused_at']) ?> · Accumulated: <?= (float)$order['sla_paused_hours'] ?> hrs)
      </div>
      <span class="badge badge-warning" style="font-size:0.8rem">SLA Clock Paused</span>
    </div>
    <?php endif; ?>
    <?php if ($status === 'Not Feasible'): ?>
    <div class="alert alert-danger" style="margin-top:12px;margin-bottom:0">
      <?= svgIcon('x') ?> <strong>Not Feasible:</strong> <?= e($order['bsa_not_feasible_reason']) ?>
    </div>
    <?php elseif ($status === 'Management Approval'): ?>
    <div class="alert alert-warning" style="margin-top:12px;margin-bottom:0">
      <?= svgIcon('users') ?> This order is awaiting <strong>Management Exception Approval</strong> (KAM Proposed NRC: <?= $order['kam_proposed_nrc'] !== null ? formatTZS((float)$order['kam_proposed_nrc']) : 'Standard' ?>, MRC: <?= $order['kam_proposed_mrc'] !== null ? formatTZS((float)$order['kam_proposed_mrc']) : 'Standard' ?>).
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
        <div class="form-group"><label>Customer Name</label><div class="font-600"><?= e($order['customer_name']) ?></div></div>
        <div class="form-group"><label>Contact Person</label><div class="font-600"><?= e($order['customer_contact_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Contact Phone</label><div><?= e($order['customer_contact_phone'] ?: '—') ?></div></div>
        <div class="form-group"><label>Contact Email</label><div><?= e($order['customer_contact_email'] ?: '—') ?></div></div>
        <div class="form-group"><label>Location</label><div><?= e($order['customer_location'] ?: '—') ?></div></div>
        <?php if ($order['site_category']): ?><div class="form-group"><label>Site Category</label><div><?= e($order['site_category']) ?></div></div><?php endif; ?>
        <?php if ($order['gps_coordinates']): ?><div class="form-group"><label>GPS</label><div><?= e($order['gps_coordinates']) ?></div></div><?php endif; ?>
        <div class="form-group"><label>Partner</label><div><?= e($order['partner_name']) ?></div></div>
        <div class="form-group"><label>Assigned KAM</label><div><?= e($order['assigned_kam_name'] ?: '—') ?></div></div>
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

<?php if (!$isPartner): ?>
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
<?php endif; // !$isPartner — commercial summary ?>

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
          <label>Standard NRC (TZS)</label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">TZS</span>
            <input type="text" class="form-control" value="<?= number_format((float)($order['standard_nrc'] ?? $order['base_nrc_usd']), 0) ?>" disabled style="opacity:.7">
          </div>
        </div>
        <div class="form-group">
          <label>Revised NRC (TZS) <span class="text-muted font-sm">Enter TZS amount</span></label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">TZS</span>
            <input type="text" name="revised_nrc" class="form-control currency-input" data-currency="TZS" value="<?= $order['revised_nrc'] !== null ? number_format((float)$order['revised_nrc'], 0) : '' ?>" placeholder="e.g. 600,000">
          </div>
        </div>
        <div class="form-group">
          <label>NRC Justification <span class="required">*</span> <span class="text-muted font-sm">Mandatory if NRC changes</span></label>
          <input type="text" name="nrc_justification" class="form-control" value="<?= e($order['nrc_justification'] ?? '') ?>" placeholder="e.g. Extended fibre route 350m">
        </div>
      </div>
      <div class="form-group" style="margin-top:8px">
        <label>Technical Remarks <span class="required">*</span></label>
        <textarea name="technical_remarks" class="form-control" rows="3" placeholder="Describe build requirement, distance, infrastructure conditions..." required><?= e($order['bsa_special_conditions'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Mark Technically Feasible &amp; Proceed to KAM</button>
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
    <div class="card-subtitle">Review feasibility results, verify standard pricing, or propose commercial pricing exception for Management approval.</div>
  </div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:16px">
      <?= svgIcon('info') ?> <strong>NRC confirmed by BSA:</strong> TZS <?= number_format((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd']), 0) ?>
      <?php if (!empty($order['management_return_remarks'])): ?>
        <br><strong>⚠️ Management Return Note:</strong> <?= e($order['management_return_remarks']) ?>
      <?php endif; ?>
    </div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=kam_approve" id="kamApproveForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

      <div class="form-grid form-grid-2" style="margin-bottom:16px">
        <div class="form-group">
          <label>Standard MRC (System Price Book)</label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">TZS</span>
            <input type="text" class="form-control" value="<?= number_format((float)($order['standard_mrc'] ?? $order['base_mrc']), 0) ?>" disabled style="opacity:.7">
          </div>
        </div>
        <div class="form-group">
          <label>Proposed Exception MRC (TZS) <span class="text-muted font-sm">Leave blank for standard</span></label>
          <div class="input-group currency-input-group">
            <span class="input-group-text currency-badge">TZS</span>
            <input type="text" name="kam_proposed_mrc" class="form-control currency-input" data-currency="TZS" value="<?= $order['kam_proposed_mrc'] !== null ? number_format((float)$order['kam_proposed_mrc'], 0) : '' ?>" placeholder="e.g. 150,000">
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>Proposed Exception NRC (TZS) <span class="text-muted font-sm">Only if proposing different from BSA's NRC</span></label>
        <div class="input-group currency-input-group">
          <span class="input-group-text currency-badge">TZS</span>
          <input type="text" name="kam_proposed_nrc" class="form-control currency-input" data-currency="TZS" value="<?= $order['kam_proposed_nrc'] !== null ? number_format((float)$order['kam_proposed_nrc'], 0) : '' ?>" placeholder="Leave blank to use BSA confirmed NRC">
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>Commercial Justification <span class="text-muted font-sm">Mandatory if proposing pricing exception</span></label>
        <input type="text" name="kam_commercial_justification" class="form-control" value="<?= e($order['kam_commercial_justification'] ?? '') ?>" placeholder="e.g. Volume discount for 50-site deal approved by sales head">
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>General KAM Remarks</label>
        <textarea name="kam_remarks" class="form-control" rows="2" placeholder="Optional notes..."><?= e($order['kam_remarks'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Submit Commercial Decision</button>
      </div>
      <div class="form-hint" style="margin-top:8px">
        <strong>Note:</strong> Standard pricing moves order directly to <strong>Pending SOF</strong>. Discounted pricing is automatically routed to <strong>Management Approval</strong>.
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // === MANAGEMENT APPROVAL PANEL (4-Option Decision Model) === ?>
<?php if ($status === 'Management Approval' && ($isMgmt || $isAdminUser)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--danger)">
  <div class="card-header" style="background:rgba(220,53,69,.1)">
    <div class="card-title"><?= svgIcon('users') ?> Management Approval — Pricing Exception Review</div>
    <div class="card-subtitle">Review KAM's pricing exception proposal and make an authoritative determination.</div>
  </div>
  <div class="card-body">
    <div class="grid-2col" style="gap:16px;margin-bottom:16px;padding:12px;background:var(--surface-2);border-radius:6px">
      <div>
        <div style="font-weight:700;margin-bottom:6px">Price Comparison (TZS)</div>
        <div style="font-size:0.875rem">
          <strong>Standard NRC:</strong> TZS <?= number_format((float)($order['standard_nrc'] ?? $order['base_nrc_usd']), 0) ?><br>
          <strong>BSA Confirmed NRC:</strong> TZS <?= number_format((float)($order['revised_nrc'] ?? $order['standard_nrc'] ?? $order['base_nrc_usd']), 0) ?><br>
          <strong>KAM Proposed NRC:</strong> <span class="text-danger font-600">TZS <?= $order['kam_proposed_nrc'] !== null ? number_format((float)$order['kam_proposed_nrc'], 0) : 'Standard' ?></span><br>
          <strong>Standard MRC:</strong> TZS <?= number_format((float)($order['standard_mrc'] ?? $order['base_mrc']), 0) ?><br>
          <strong>KAM Proposed MRC:</strong> <span class="text-danger font-600">TZS <?= $order['kam_proposed_mrc'] !== null ? number_format((float)$order['kam_proposed_mrc'], 0) : 'Standard' ?></span>
        </div>
      </div>
      <div>
        <div style="font-weight:700;margin-bottom:6px">Exception Justification</div>
        <div style="font-size:0.875rem;color:var(--text-secondary)">
          <?= e($order['kam_commercial_justification'] ?: ($order['mrc_justification'] ?: 'No justification provided.')) ?>
        </div>
      </div>
    </div>

    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=management_decide" id="mgmtDecisionForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label" style="font-weight:700">Select Management Decision <span class="required">*</span></label>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:6px">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px;border:1px solid var(--border);border-radius:6px">
            <input type="radio" name="management_decision" value="Approve as Requested" required onchange="toggleMgmtFields(this.value)">
            <div>
              <strong>1. Approve as Requested</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary)">Accept KAM's proposed exception pricing and proceed to Pending SOF.</div>
            </div>
          </label>

          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px;border:1px solid var(--border);border-radius:6px">
            <input type="radio" name="management_decision" value="Approve with Revised Price" required onchange="toggleMgmtFields(this.value)">
            <div>
              <strong>2. Approve with Revised Price</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary)">Set custom management-approved NRC and/or MRC and proceed to Pending SOF.</div>
            </div>
          </label>

          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px;border:1px solid var(--border);border-radius:6px">
            <input type="radio" name="management_decision" value="Keep Standard Price" required onchange="toggleMgmtFields(this.value)">
            <div>
              <strong>3. Keep Standard Price</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary)">Reject the exception, enforce standard price book rates, and proceed to Pending SOF.</div>
            </div>
          </label>

          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px;border:1px solid var(--border);border-radius:6px">
            <input type="radio" name="management_decision" value="Return to KAM" required onchange="toggleMgmtFields(this.value)">
            <div>
              <strong>4. Return to Account Manager (KAM)</strong>
              <div style="font-size:0.8rem;color:var(--text-secondary)">Send back to Commercial stage for revision with mandatory remarks.</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Revised Price Fields (Only for Approve with Revised Price) -->
      <div id="mgmtRevisedPriceFields" style="display:none;margin-bottom:16px;padding:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px">
        <div style="font-weight:700;margin-bottom:8px;color:#166534">Management Final Price Override (TZS)</div>
        <div class="grid-2col" style="gap:12px">
          <div class="form-group">
            <label>Final NRC (TZS)</label>
            <div class="input-group currency-input-group">
              <span class="input-group-text currency-badge">TZS</span>
              <input type="text" name="management_final_nrc" class="form-control currency-input" data-currency="TZS" value="<?= number_format((float)($order['kam_proposed_nrc'] ?? $order['revised_nrc'] ?? $order['standard_nrc'] ?? 0), 0) ?>" placeholder="e.g. 500,000">
            </div>
          </div>
          <div class="form-group">
            <label>Final MRC (TZS)</label>
            <div class="input-group currency-input-group">
              <span class="input-group-text currency-badge">TZS</span>
              <input type="text" name="management_final_mrc" class="form-control currency-input" data-currency="TZS" value="<?= number_format((float)($order['kam_proposed_mrc'] ?? $order['standard_mrc'] ?? 0), 0) ?>" placeholder="e.g. 180,000">
            </div>
          </div>
        </div>
      </div>

      <!-- Return Remarks (Only for Return to KAM) -->
      <div id="mgmtReturnRemarksField" style="display:none;margin-bottom:16px">
        <div class="form-group">
          <label>Return Remarks for KAM <span class="required">*</span></label>
          <textarea name="management_return_remarks" class="form-control" rows="2" placeholder="Explain what the KAM should revise..."></textarea>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label>Management Decision Notes</label>
        <textarea name="management_remarks" class="form-control" rows="2" placeholder="Optional audit comments..."><?= e($order['management_remarks'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg"><?= svgIcon('check') ?> Confirm Management Decision</button>
    </form>

    <script>
    function toggleMgmtFields(val) {
      document.getElementById('mgmtRevisedPriceFields').style.display = (val === 'Approve with Revised Price') ? 'block' : 'none';
      document.getElementById('mgmtReturnRemarksField').style.display = (val === 'Return to KAM') ? 'block' : 'none';
    }
    </script>
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
            <?php if (!empty($order['sof_signed_file'])): ?>
              <div class="alert alert-success" style="padding:10px;margin-bottom:10px;font-size:0.85rem">
                <?= svgIcon('check') ?> Signed SOF uploaded: <strong><?= e($order['sof_signed_filename']) ?></strong>
              </div>
              <form method="POST" action="<?= APP_URL ?>/?page=orders&action=delete_signed_sof" style="margin-bottom:10px">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove the uploaded signed SOF to re-upload?')">
                  <?= svgIcon('trash') ?> Remove &amp; Re-upload SOF
                </button>
              </form>
            <?php else: ?>
              <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px">Sign the SOF (stamp + authorized signature) and upload it here.</p>
              <form method="POST" action="<?= APP_URL ?>/?page=orders&action=upload_signed_sof" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <div class="form-group" style="margin-bottom:8px">
                  <input type="file" name="signed_sof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <button type="submit" class="btn btn-success"><?= svgIcon('upload') ?> Upload Signed SOF &amp; Submit Order</button>
              </form>
            <?php endif; ?>
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
        <div class="form-group" style="margin-bottom:12px">
          <label>Reason &amp; Remarks for Account Manager <span class="required">*</span></label>
          <input type="text" name="return_remarks" class="form-control" placeholder="Describe your concern (e.g. request higher bandwidth discount or revision)..." required>
        </div>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Return to Account Manager (KAM)</button>
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
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=assign_contractor" data-no-lookup="true">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Assign Contractor <span class="required">*</span></label>
          <select name="contractor_partner_id" class="form-control" data-no-lookup="true" required>
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
            <option value="Ping Test">Ping Test Result</option>
            <option value="Latency Test">Latency Test Result</option>
            <option value="UAT Sign-off">UAT Sign-off Document</option>
            <option value="Installation Remarks">Installation Challenge / Note</option>
            <option value="Other">Other Document / Attachment</option>
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

    <!-- PM Complete Installation / Move to Testing -->
    <div style="margin-top:20px;padding:16px 20px;border-top:1px solid var(--border);background:var(--surface-2);border-radius:var(--radius-sm)">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary)">
            <?= svgIcon('check') ?> Submit Job as Complete
          </div>
          <div style="font-size:0.84rem;color:var(--text-secondary);margin-top:2px">
            Installation work is finished. Move the order to <strong>Testing — Internal Review</strong> for PM/BSA testing approval.
          </div>
        </div>
        <form method="POST" action="<?= APP_URL ?>/?page=orders&action=submit_installation_complete" data-confirm="Submit job as complete? This will transition the order to Testing — Internal Review." style="margin:0">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <button type="submit" class="btn btn-success">
            <?= svgIcon('check') ?> Submit Job as Complete — Move to Testing
          </button>
        </form>
      </div>
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

    <!-- NOC IP Configuration Status & Control -->
    <div style="margin-bottom:16px;padding:12px 16px;border:1px solid <?= !empty($order['noc_ip_configured']) ? '#86efac' : '#fde047' ?>;background:<?= !empty($order['noc_ip_configured']) ? '#f0fdf4' : '#fefce8' ?>;border-radius:6px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <div style="font-weight:700;color:<?= !empty($order['noc_ip_configured']) ? '#166534' : '#854d0e' ?>">
          <?= !empty($order['noc_ip_configured']) ? '✅ NOC IP Configuration: COMPLETE' : '⏳ NOC IP Configuration: PENDING' ?>
        </div>
        <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">
          <?= !empty($order['noc_ip_configured']) ? 'IP and routing are configured. Speed Test and Ping Test evidence are mandatory to move to UAT.' : 'NOC has not yet completed IP config. Speed Test and Ping Test are optional until IP is configured.' ?>
        </div>
      </div>
      <?php if ($isPM || $isBSA || $isAdminUser): ?>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=toggle_noc_ip" style="margin:0">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="noc_ip_configured" value="<?= !empty($order['noc_ip_configured']) ? 0 : 1 ?>">
        <button type="submit" class="btn btn-sm <?= !empty($order['noc_ip_configured']) ? 'btn-outline-secondary' : 'btn-primary' ?>">
          <?= !empty($order['noc_ip_configured']) ? 'Revert to Pending' : 'Mark NOC IP as Configured' ?>
        </button>
      </form>
      <?php endif; ?>
    </div>

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
  <?php if (!empty($priceAudit) && !$isPartner): ?><button class="tab-btn" data-tab="price_audit" data-tab-group="order">Price Audit (<?= count($priceAudit) ?>)</button><?php endif; ?>
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
          <?php if (!$isPartner && ($tl['note'] ?? '')): ?><div class="timeline-note"><?= e($tl['note']) ?></div><?php endif; ?>
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
            <td>
              <?php
              $isCountersigned = (($d['doc_type'] ?? '') === 'countersigned_sof' || $d['document_type'] === 'Countersigned SOF');
              $isSigned = (($d['doc_type'] ?? '') === 'sof' || $d['document_type'] === 'Signed SOF');
              $badgeClass = $isCountersigned ? 'badge-success' : ($isSigned ? 'badge-primary' : 'badge-secondary');
              ?>
              <span class="badge <?= $badgeClass ?>"><?= e($d['document_type']) ?></span>
            </td>
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

<?php if (!empty($priceAudit) && !$isPartner): ?>
<div class="card" style="margin-bottom:24px;border:1px solid var(--border)">
  <div class="card-header" style="background:var(--surface-2)">
    <div>
      <div class="card-title"><?= svgIcon('dollar') ?> Commercial Pricing Modification Audit Trail</div>
      <div class="card-subtitle">Complete chronological record of all NRC and MRC revisions, proposals, and approvals.</div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date &amp; Time</th>
          <th>Field Modified</th>
          <th>Previous Value</th>
          <th>New Value</th>
          <th>Modified By</th>
          <th>Lifecycle Stage</th>
          <th>Justification / Reason</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($priceAudit as $pa): ?>
        <tr>
          <td class="font-sm" style="white-space:nowrap"><?= fmtDateTime($pa['changed_at']) ?></td>
          <td><strong><?= e(priceFieldLabel($pa['field_name'])) ?></strong></td>
          <td class="font-sm text-muted"><?= $pa['old_value'] !== null ? formatTZS((float)$pa['old_value']) : '—' ?></td>
          <td class="font-sm font-600" style="color:var(--primary)"><?= $pa['new_value'] !== null ? formatTZS((float)$pa['new_value']) : '—' ?></td>
          <td class="font-sm"><?= e($pa['changed_by_name'] ?: 'System') ?></td>
          <td><span class="badge badge-secondary"><?= e($pa['stage']) ?></span></td>
          <td style="max-width:300px;font-size:0.85rem;color:var(--text-secondary)"><?= e($pa['justification'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php // Edit Closed Order Modal (Admin and Management only with mandatory audit reason) ?>
<?php if (($isAdminUser || $isMgmt) && $status === 'Closed'): ?>
<div class="modal-backdrop" id="editClosedOrderModal">
  <div class="modal" style="max-width:720px;width:95%">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:8px">
        <div class="modal-title">Edit Closed Order — <?= e($order['order_number']) ?></div>
        <span class="badge badge-warning" style="font-size:0.75rem">Audit Reason Required</span>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=edit_closed_order">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="alert alert-warning" style="margin-bottom:16px;font-size:0.85rem">
          <strong>⚠️ Audit Compliance Notice:</strong> Modifying a closed order will permanently log your identity, timestamp, and audit reason in the system audit trail.
        </div>

        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Customer Name <span class="text-danger">*</span></label>
            <input type="text" name="customer_name" class="form-control" value="<?= e($order['customer_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Contact Person Name</label>
            <input type="text" name="customer_contact_name" class="form-control" value="<?= e($order['customer_contact_name']) ?>">
          </div>
          <div class="form-group">
            <label>Contact Phone</label>
            <input type="text" name="customer_contact_phone" class="form-control" value="<?= e($order['customer_contact_phone']) ?>">
          </div>
          <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="customer_contact_email" class="form-control" value="<?= e($order['customer_contact_email']) ?>">
          </div>
          <div class="form-group">
            <label>Location / Address</label>
            <input type="text" name="customer_location" class="form-control" value="<?= e($order['customer_location']) ?>">
          </div>
          <div class="form-group">
            <label>Building Name</label>
            <input type="text" name="building_name" class="form-control" value="<?= e($order['building_name']) ?>">
          </div>
          <div class="form-group">
            <label>GPS Coordinates</label>
            <input type="text" name="gps_coordinates" class="form-control" value="<?= e($order['gps_coordinates']) ?>">
          </div>
          <div class="form-group">
            <label>Bandwidth / Package</label>
            <input type="text" name="bandwidth" class="form-control" value="<?= e($order['bandwidth'] ?: $order['fttx_package']) ?>">
          </div>
          <div class="form-group">
            <label>Circuit ID</label>
            <input type="text" name="circuit_id" class="form-control" value="<?= e($order['circuit_id']) ?>">
          </div>
          <div class="form-group">
            <label>Service ID</label>
            <input type="text" name="service_id" class="form-control" value="<?= e($order['service_id']) ?>">
          </div>
        </div>

        <div class="form-group" style="margin-top:12px">
          <label>Special Requirements / Service Notes</label>
          <textarea name="special_requirements" class="form-control" rows="2"><?= e($order['special_requirements']) ?></textarea>
        </div>

        <div class="form-group" style="margin-top:16px;padding:12px 16px;background:var(--surface-2);border-radius:6px;border:1px solid var(--border)">
          <label style="color:var(--text-primary);font-weight:700">Audit Reason for Edit <span class="text-danger">*</span></label>
          <div class="text-muted font-sm" style="margin-bottom:6px">Explain why this closed order is being edited (mandatory for audit compliance).</div>
          <textarea name="audit_reason" class="form-control" rows="3" required placeholder="State operational/business reason for modifying this closed order..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-warning">Save Changes (Audit Logged)</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php // Admin / Management Status Override Modal ?>
<?php if ($isAdminUser || $isMgmt): ?>
<div class="modal-backdrop" id="statusModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Status Override</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_status">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <?php if ($status === 'Closed'): ?>
        <div class="alert alert-warning" style="margin-bottom:14px;font-size:0.85rem">
          <strong>⚠️ Notice:</strong> This order is currently <strong>Closed</strong>. An audit reason is mandatory for any status change.
        </div>
        <?php endif; ?>

        <div class="form-group">
          <label>New Status</label>
          <select name="new_status" class="form-control" required>
            <?php foreach ($allStatuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e($st) ?></option>
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
          <label>Audit Reason / Note <?= $status === 'Closed' ? '<span class="text-danger">*</span>' : '' ?></label>
          <textarea name="note" class="form-control" rows="3" placeholder="Reason for status change..." <?= $status === 'Closed' ? 'required' : '' ?>></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Status Change</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>