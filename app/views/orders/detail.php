<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($order['order_number']) ?></div>
    <div class="page-subtitle"><?= e($order['service_type']) ?> · <?= e($order['customer_name']) ?> · <?= e($order['partner_name']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary"><?= svgIcon('list') ?> All Orders</a>
    <?php if (!isPartnerUser() && !in_array($order['status'], ['Closed','Cancelled','Activated','Billing Triggered'])): ?>
    <button class="btn btn-primary" data-modal-open="statusModal"><?= svgIcon('edit') ?> Update Status</button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;margin-bottom:24px">
  <div class="card">
    <div class="card-header"><div class="card-title">Order Details</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Status</label><div><span class="badge <?= orderStatusClass($order['status']) ?>" style="font-size:.85rem;padding:6px 14px"><?= e($order['status']) ?></span></div></div>
        <div class="form-group"><label>Service Type</label><div><span class="badge badge-primary" style="font-size:.85rem;padding:6px 14px"><?= e($order['service_type']) ?></span></div></div>
        <div class="form-group"><label>Customer</label><div class="font-600"><?= e($order['customer_name']) ?></div></div>
        <div class="form-group"><label>Customer Location</label><div><?= e($order['customer_location'] ?: '—') ?></div></div>
        <div class="form-group"><label>Partner</label><div><?= e($order['partner_name']) ?></div></div>
        <div class="form-group"><label>Assigned KAM</label><div><?= e($order['assigned_kam_name'] ?: '—') ?></div></div>
        <?php if ($order['gps_coordinates']): ?><div class="form-group"><label>GPS</label><div><?= e($order['gps_coordinates']) ?></div></div><?php endif; ?>
        <?php if ($order['building_name']): ?><div class="form-group"><label>Building</label><div><?= e($order['building_name']) ?><?= $order['floor_number'] ? ', Floor '.e($order['floor_number']) : '' ?><?= $order['apartment_number'] ? ', Apt '.e($order['apartment_number']) : '' ?></div></div><?php endif; ?>
        <?php if ($order['circuit_id']): ?><div class="form-group"><label>Circuit ID</label><div class="font-600"><?= e($order['circuit_id']) ?></div></div><?php endif; ?>
        <?php if ($order['service_id']): ?><div class="form-group"><label>Service ID</label><div class="font-600"><?= e($order['service_id']) ?></div></div><?php endif; ?>
      </div>
      <?php if ($order['special_requirements']): ?>
      <div class="divider"></div>
      <div class="form-group"><label>Special Requirements</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:6px;font-size:.875rem;white-space:pre-wrap"><?= e($order['special_requirements']) ?></div></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Commercial Summary</div></div>
    <div class="card-body" style="padding:18px">
      <?php $isRH = $order['service_type'] === 'Remote Hands Only'; ?>
      <div class="commercial-box">
        <div class="commercial-row section-header">NRC</div>
        <div class="commercial-row"><span>Base NRC</span><span class="commercial-value">$<?= money($order['base_nrc_usd']) ?></span></div>
        <?php if ($order['remote_hands_nrc_usd'] > 0): ?>
        <div class="commercial-row"><span>Remote Hands NRC</span><span class="commercial-value">$<?= money($order['remote_hands_nrc_usd']) ?></span></div>
        <?php endif; ?>
        <div class="commercial-row"><span>Subtotal</span><span class="commercial-value">$<?= money($order['nrc_subtotal_usd']) ?></span></div>
        <div class="commercial-row"><span>VAT (18%)</span><span class="commercial-value">$<?= money($order['vat_on_nrc']) ?></span></div>
        <div class="commercial-row total"><span>Total NRC Incl. VAT</span><span class="commercial-value">$<?= money($order['total_nrc_incl_vat']) ?></span></div>

        <?php if (!$isRH && $order['base_mrc'] > 0): ?>
        <div class="commercial-row section-header divider">MRC</div>
        <div class="commercial-row"><span id="mrcLabel">Base MRC (<?= e($order['mrc_currency']) ?>)</span><span class="commercial-value"><?= e($order['mrc_currency']) ?> <?= money($order['base_mrc']) ?></span></div>
        <?php if ($order['discount_pct'] > 0): ?>
        <div class="commercial-row"><span>Discount (<?= money($order['discount_pct']) ?>%)</span><span class="commercial-value text-danger">-<?= e($order['mrc_currency']) ?> <?= money($order['discount_amount']) ?></span></div>
        <?php endif; ?>
        <div class="commercial-row"><span>VAT (18%)</span><span class="commercial-value"><?= e($order['mrc_currency']) ?> <?= money($order['vat_on_mrc']) ?></span></div>
        <div class="commercial-row total"><span>Total MRC Incl. VAT</span><span class="commercial-value"><?= e($order['mrc_currency']) ?> <?= money($order['total_mrc_incl_vat']) ?></span></div>
        <?php endif; ?>

        <div class="commercial-row section-header divider">Conversion</div>
        <div class="commercial-row"><span>USD → TZS Rate</span><span class="commercial-value"><?= money($order['usd_tzs_rate']) ?></span></div>
      </div>
    </div>
  </div>
</div>

<div class="tabs" data-group="order">
  <button class="tab-btn active" data-tab="timeline" data-tab-group="order">Timeline</button>
  <button class="tab-btn" data-tab="documents" data-tab-group="order">Documents (<?= count($docs) ?>)</button>
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
          <div class="timeline-dot <?= $tl['status'] === 'Closed' || $tl['status'] === 'Activated' ? 'success' : (in_array($tl['status'], ['Cancelled']) ? 'danger' : '') ?>"></div>
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
        <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr></thead>
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
            <td><a href="<?= APP_URL ?>/<?= e($d['file_path']) ?>" class="btn btn-sm btn-secondary" target="_blank"><?= svgIcon('download') ?></a></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!isPartnerUser()): ?>
<?php if ($order['status'] === 'Feasibility Review'): ?>
<!-- BSA Solution Design Form -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">BSA Solution Design</div></div>
  <div class="card-body">
    <?php if ($order['bsa_revision_note']): ?>
    <div class="alert alert-warning" style="margin-bottom:16px">
      <strong>Revision requested:</strong> <?= e($order['bsa_revision_note']) ?>
    </div>
    <?php endif; ?>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=save_bsa_design">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Feasibility Status</label>
          <select name="bsa_feasibility_status" class="form-control">
            <option value="">Select...</option>
            <option value="Serviceable" <?= $order['bsa_feasibility_status'] === 'Serviceable' ? 'selected' : '' ?>>Serviceable</option>
            <option value="Capacity Upgrade Required" <?= $order['bsa_feasibility_status'] === 'Capacity Upgrade Required' ? 'selected' : '' ?>>Capacity Upgrade Required</option>
            <option value="Construction Required" <?= $order['bsa_feasibility_status'] === 'Construction Required' ? 'selected' : '' ?>>Construction Required</option>
            <option value="No Coverage" <?= $order['bsa_feasibility_status'] === 'No Coverage' ? 'selected' : '' ?>>No Coverage</option>
          </select>
        </div>
        <div class="form-group">
          <label>Delivery Method</label>
          <input type="text" name="bsa_delivery_method" class="form-control" value="<?= e($order['bsa_delivery_method'] ?? '') ?>" placeholder="e.g. Fibre, Wireless">
        </div>
        <div class="form-group">
          <label>Delivery Cost (USD)</label>
          <input type="number" step="0.01" name="bsa_delivery_cost" class="form-control" value="<?= e($order['bsa_delivery_cost'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>SLA Level</label>
          <input type="text" name="bsa_sla_level" class="form-control" value="<?= e($order['bsa_sla_level'] ?? '') ?>" placeholder="e.g. Standard, Premium">
        </div>
        <div class="form-group">
          <label>Lead Time</label>
          <input type="text" name="bsa_lead_time" class="form-control" value="<?= e($order['bsa_lead_time'] ?? '') ?>" placeholder="e.g. 5 business days">
        </div>
      </div>
      <div class="form-group" style="margin-top:8px">
        <label>Special Conditions</label>
        <textarea name="bsa_special_conditions" class="form-control" rows="2" placeholder="Any special conditions..."><?= e($order['bsa_special_conditions'] ?? '') ?></textarea>
      </div>
      <div style="margin-top:12px">
        <button type="submit" class="btn btn-primary"><?= svgIcon('save') ?> Save BSA Design</button>
      </div>
    </form>
  </div>
</div>
<?php elseif ($order['status'] === 'Awaiting BSA Approval'): ?>
<!-- BSA Review Approval -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">BSA Design — Pending Approval</div></div>
  <div class="card-body">
    <div class="form-grid form-grid-3" style="margin-bottom:16px">
      <div class="form-group"><label>Feasibility</label><div><span class="badge badge-secondary"><?= e($order['bsa_feasibility_status'] ?: 'Not set') ?></span></div></div>
      <div class="form-group"><label>Delivery</label><div><?= e($order['bsa_delivery_method'] ?: '—') ?> <?= $order['bsa_delivery_cost'] ? '($'.money($order['bsa_delivery_cost']).')' : '' ?></div></div>
      <div class="form-group"><label>SLA / Lead Time</label><div><?= e($order['bsa_sla_level'] ?: '—') ?> / <?= e($order['bsa_lead_time'] ?: '—') ?></div></div>
    </div>
    <?php if ($order['bsa_special_conditions']): ?>
    <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px;font-size:.875rem">
      <strong>Special Conditions:</strong> <?= e($order['bsa_special_conditions']) ?>
    </div>
    <?php endif; ?>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=bsa_approve">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Approve BSA</button>
      </form>
      <button class="btn btn-warning" data-modal-open="bsaRevisionModal"><?= svgIcon('refresh') ?> Request Revision</button>
    </div>
  </div>
</div>

<!-- BSA Revision Modal -->
<div class="modal-backdrop" id="bsaRevisionModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Request BSA Revision</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=bsa_revision">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Revision Note <span class="required">*</span></label>
          <textarea name="bsa_revision_note" class="form-control" rows="4" placeholder="Describe what needs to be revised..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-warning"><?= svgIcon('refresh') ?> Request Revision</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($order['status'] === 'UAT - Awaiting Confirmation' && (isPartnerUser() || hasRole('KAM', 'BSA'))): ?>
<!-- UAT Confirmation -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">UAT — Awaiting Your Confirmation</div></div>
  <div class="card-body">
    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center">
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=uat_accept">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Accept — Activate Service</button>
      </form>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=uat_reject" onsubmit="return confirm('Reject UAT and return to Testing?')">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <input type="text" name="rejection_reason" class="form-control" placeholder="Reason for rejection..." required style="display:inline-block;width:auto;min-width:200px">
        <button type="submit" class="btn btn-danger"><?= svgIcon('x') ?> Reject</button>
      </form>
    </div>
    <?php if ($order['uat_notified_at']): ?>
    <div style="margin-top:12px;font-size:.82rem;color:var(--text-muted)">
      Notified at <?= fmtDateTime($order['uat_notified_at']) ?>
      <?php if ($order['uat_deadline']): ?>
        · Deadline: <?= fmtDateTime($order['uat_deadline']) ?>
        · <?= strtotime($order['uat_deadline']) > time() ? 'Remaining: ' . round((strtotime($order['uat_deadline']) - time()) / 3600) . ' hrs' : 'EXPIRED' ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!isPartnerUser() && in_array($order['status'], ['Activated', 'Billing Triggered'])): ?>
<!-- Billing Status -->
<div class="card" style="margin-bottom:24px">
  <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
    <div>
      <div style="font-weight:700;font-size:.95rem">Billing</div>
      <div style="font-size:.8rem;color:var(--text-secondary)">
        <?php if ($order['service_id']): ?>
          Service ID: <?= e($order['service_id']) ?>
          <?php if ($order['billing_trigger_date']): ?> · Billing triggered: <?= $order['billing_trigger_date'] ?><?php endif; ?>
        <?php else: ?>
          Service activation details not yet entered
        <?php endif; ?>
      </div>
    </div>
    <?php if ($order['status'] === 'Activated'): ?>
    <div style="display:flex;gap:8px;flex-shrink:0">
      <?php if (!$order['service_id']): ?>
      <button class="btn btn-success" data-modal-open="activateModal"><?= svgIcon('server') ?> Activate Service</button>
      <?php endif; ?>
      <form method="POST" action="<?= APP_URL ?>/?page=orders&action=trigger_billing" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-primary"><?= svgIcon('dollar') ?> Trigger Billing</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!isPartnerUser() && in_array($order['status'], ['Activated', 'Billing Triggered']) && !$order['service_id']): ?>
<!-- Service Activation Modal -->
<div class="modal-backdrop" id="activateModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><div class="modal-title">Activate Service</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=activate_service">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label>Circuit ID</label>
            <input type="text" name="circuit_id" class="form-control" value="<?= e($order['circuit_id'] ?: 'CKT-' . $order['order_number']) ?>">
          </div>
          <div class="form-group">
            <label>Activation Date</label>
            <input type="date" name="activation_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>Billing Start Date</label>
            <input type="date" name="billing_start_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>ONU Serial</label>
            <input type="text" name="onu_serial" class="form-control" placeholder="Serial number">
          </div>
          <div class="form-group">
            <label>ONU Model</label>
            <input type="text" name="onu_model" class="form-control" placeholder="e.g. Huawei HG8245">
          </div>
          <div class="form-group">
            <label>Router Serial</label>
            <input type="text" name="router_serial" class="form-control" placeholder="Serial number">
          </div>
          <div class="form-group">
            <label>Router Model</label>
            <input type="text" name="router_model" class="form-control" placeholder="e.g. MikroTik RB750">
          </div>
          <div class="form-group">
            <label>IP Address</label>
            <input type="text" name="ip_address" class="form-control" placeholder="e.g. 10.0.0.1/30">
          </div>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-success"><?= svgIcon('server') ?> Activate Service</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (!isPartnerUser() && !in_array($order['status'], ['Closed','Cancelled','Activated','Billing Triggered'])): ?>
<div class="modal-backdrop" id="statusModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Update Order Status</div><button class="modal-close" data-modal-close>&times;</button></div>
    <form method="POST" action="<?= APP_URL ?>/?page=orders&action=update_status">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>New Status</label>
          <select name="new_status" class="form-control" required>
            <?php foreach ($allStatuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $order['status'] === $st ? 'disabled' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Note</label>
          <textarea name="note" class="form-control" rows="3" placeholder="Optional note..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Update Status</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>