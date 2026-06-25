<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($ticket['ticket_number']) ?></div>
    <div class="page-subtitle">
      Created <?= fmtDateTime($ticket['created_at']) ?> by <?= e($ticket['opened_by_name'] ?: 'System') ?>
      &middot; Service: <?= e($ticket['service_id']) ?>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=tickets" class="btn btn-secondary">
      <?= svgIcon('list') ?> All Tickets
    </a>
    <?php if (!isPartnerUser()): ?>
    <button class="btn btn-secondary" data-modal-open="assignModal"><?= svgIcon('users') ?> Assign</button>
    <button class="btn btn-secondary" data-modal-open="queueModal"><?= svgIcon('refresh') ?> Move Queue</button>
    <button class="btn btn-secondary" data-modal-open="noteModal"><?= svgIcon('plus') ?> Add Note</button>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;margin-bottom:24px">
  <!-- Main Info -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Ticket Information</div>
    </div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label>Status</label>
          <div><span class="badge <?= ticketStatusClass($ticket['status']) ?>" style="font-size:.85rem;padding:6px 14px"><?= e($ticket['status']) ?></span></div>
        </div>
        <div class="form-group">
          <label>Queue</label>
          <div><span class="badge badge-primary" style="font-size:.85rem;padding:6px 14px"><?= e($ticket['current_queue']) ?></span></div>
        </div>
        <div class="form-group">
          <label>Fault Category</label>
          <div class="font-600"><?= e($ticket['fault_category']) ?></div>
        </div>
        <div class="form-group">
          <label>Severity</label>
          <div>
            <span class="badge badge-<?= in_array($ticket['severity'], ['Sev 1','Critical']) ? 'danger' : (in_array($ticket['severity'], ['Sev 2','Standard']) ? 'warning' : 'secondary') ?>" style="font-size:.85rem;padding:6px 14px">
              <?= e($ticket['severity']) ?>
            </span>
          </div>
        </div>
        <div class="form-group">
          <label>Assigned To</label>
          <div class="font-600"><?= e($ticket['assigned_to_name'] ?: 'Unassigned') ?></div>
        </div>
        <div class="form-group">
          <label>Partner</label>
          <div><?= e($ticket['partner_name']) ?></div>
        </div>
        <div class="form-group">
          <label>Customer</label>
          <div><?= e($ticket['customer_name']) ?></div>
        </div>
        <div class="form-group">
          <label>Service Type</label>
          <div><span class="badge badge-primary"><?= e($ticket['service_type']) ?></span></div>
        </div>
        <div class="form-group">
          <label>Circuit ID</label>
          <div class="font-600"><?= e($ticket['circuit_id'] ?: '—') ?></div>
        </div>
        <div class="form-group">
          <label>Bandwidth / Capacity</label>
          <div><?= e($ticket['bandwidth_capacity'] ?: '—') ?></div>
        </div>
        <div class="form-group">
          <label>Location</label>
          <div><?= e($ticket['location'] ?: '—') ?></div>
        </div>
        <div class="form-group">
          <label>Activation Date</label>
          <div><?= fmtDate($ticket['activation_date']) ?></div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="form-group">
        <label>Description</label>
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:6px;font-size:.875rem;line-height:1.7;white-space:pre-wrap"><?= e($ticket['description']) ?></div>
      </div>

      <?php if ($ticket['reopen_reason']): ?>
      <div class="divider"></div>
      <div class="form-group">
        <label>Reopen Reason</label>
        <div style="background:var(--danger-light);border:1px solid #EF9A9A;border-radius:var(--radius-sm);padding:14px;margin-top:6px;font-size:.875rem">
          <?= e($ticket['reopen_reason']) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- SLA / Sidebar -->
  <div>
    <!-- SLA Card -->
    <div class="card" style="margin-bottom:22px">
      <div class="card-header">
        <div class="card-title">SLA Status</div>
      </div>
      <div class="card-body">
        <div style="text-align:center;padding:12px 0">
          <div style="font-size:2.5rem;font-weight:800;color:<?= $ticket['sla_pct_consumed'] >= 100 ? 'var(--danger)' : ($ticket['sla_pct_consumed'] >= 80 ? 'var(--warning)' : 'var(--success)') ?>">
            <?= number_format($ticket['sla_pct_consumed'], 0) ?>%
          </div>
          <div class="sla-bar" style="margin:10px 0;height:12px">
            <div class="sla-bar-fill <?= $ticket['sla_pct_consumed'] >= 100 ? 'breach' : ($ticket['sla_pct_consumed'] >= 80 ? 'warning' : 'normal') ?>" style="width:<?= min(100, $ticket['sla_pct_consumed']) ?>%"></div>
          </div>
          <span class="badge <?= slaBadgeClass($ticket['sla_status']) ?>" style="font-size:.8rem;padding:5px 14px"><?= e($ticket['sla_status']) ?></span>
        </div>

        <div class="divider"></div>

        <div class="commercial-row">
          <span class="font-sm text-secondary">Response Target</span>
          <span class="font-600"><?= $slaTargets['response'] ?> min</span>
        </div>
        <div class="commercial-row">
          <span class="font-sm text-secondary">Resolution Target</span>
          <span class="font-600"><?= $slaTargets['resolution'] ? $slaTargets['resolution'] . ' min' : 'As agreed' ?></span>
        </div>
        <?php if ($ticket['responded_at']): ?>
        <div class="commercial-row">
          <span class="font-sm text-secondary">Responded At</span>
          <span class="font-sm"><?= fmtDateTime($ticket['responded_at']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($ticket['noc_resolution_time_mins'] !== null): ?>
        <div class="commercial-row">
          <span class="font-sm text-secondary">NOC Resolution</span>
          <span class="font-600"><?= $ticket['noc_resolution_time_mins'] ?> min</span>
        </div>
        <?php endif; ?>
        <?php if ($ticket['customer_wait_time_mins'] !== null): ?>
        <div class="commercial-row">
          <span class="font-sm text-secondary">Customer Wait</span>
          <span class="font-600"><?= $ticket['customer_wait_time_mins'] ?> min</span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status Actions -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Actions</div>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <?php if (!isPartnerUser()): ?>
          <?php if ($ticket['status'] === 'Open'): ?>
          <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=update_status">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="hidden" name="new_status" value="Assigned">
            <button type="submit" class="btn btn-primary w-100"><?= svgIcon('check') ?> Accept &amp; Assign</button>
          </form>
          <?php endif; ?>

          <?php if ($ticket['status'] === 'Assigned'): ?>
          <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=update_status">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="hidden" name="new_status" value="In Progress">
            <button type="submit" class="btn btn-primary w-100"><?= svgIcon('refresh') ?> Start Work</button>
          </form>
          <?php endif; ?>

          <?php if ($ticket['status'] === 'In Progress'): ?>
          <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=update_status">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="hidden" name="new_status" value="Resolved - Awaiting Customer Confirmation">
            <button type="submit" class="btn btn-success w-100" onclick="return confirmAction('Mark as resolved and await customer confirmation?')">
              <?= svgIcon('check') ?> Mark Resolved
            </button>
          </form>
          <?php endif; ?>

          <?php if ($ticket['status'] === 'Reopened'): ?>
          <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=update_status">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="hidden" name="new_status" value="Assigned">
            <button type="submit" class="btn btn-warning w-100"><?= svgIcon('refresh') ?> Re-accept Ticket</button>
          </form>
          <?php endif; ?>

          <?php if (!in_array($ticket['status'], ['Closed','Resolved - Awaiting Customer Confirmation'])): ?>
          <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=update_status" onsubmit="return confirmAction('Close this ticket?')">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="hidden" name="new_status" value="Closed">
            <button type="submit" class="btn btn-secondary w-100"><?= svgIcon('x') ?> Close Ticket</button>
          </form>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($ticket['status'] === 'Resolved - Awaiting Customer Confirmation' && isPartnerUser()): ?>
        <div style="background:var(--info-light);border:1px solid #81D4FA;border-radius:var(--radius-sm);padding:16px;text-align:center">
          <div style="font-size:.9rem;font-weight:700;color:var(--info);margin-bottom:12px">Is the issue resolved?</div>
          <div style="display:flex;gap:10px">
            <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=customer_action" style="flex:1">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
              <input type="hidden" name="customer_action" value="confirm">
              <button type="submit" class="btn btn-success w-100" onclick="return confirmAction('Confirm the issue is resolved?')">
                <?= svgIcon('check') ?> Yes, Resolved
              </button>
            </form>
            <button class="btn btn-danger" data-modal-open="reopenModal" style="flex:1">
              <?= svgIcon('alert') ?> Reopen
            </button>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Tabs: Timeline / Notes -->
<div class="tabs" data-group="ticket">
  <button class="tab-btn active" data-tab="timeline" data-tab-group="ticket">Timeline</button>
  <button class="tab-btn" data-tab="notes" data-tab-group="ticket">Notes (<?= count($notes) ?>)</button>
  <?php if (!isPartnerUser()): ?>
  <button class="tab-btn" data-tab="escalations" data-tab-group="ticket">Escalations</button>
  <?php endif; ?>
</div>

<!-- Timeline -->
<div class="tab-panel active" data-tab-panel="timeline" data-tab-group="ticket">
  <div class="card">
    <div class="card-body">
      <?php if (empty($timeline)): ?>
      <div class="empty-state"><div class="empty-state-title">No timeline entries</div></div>
      <?php else: ?>
      <div class="timeline">
        <?php foreach ($timeline as $tl): ?>
        <div class="timeline-item">
          <div class="timeline-dot <?= $tl['status'] === 'Closed' ? 'success' : (in_array($tl['status'], ['Breached','Critical Breach','Reopened']) ? 'danger' : '') ?>"></div>
          <div class="timeline-time"><?= fmtDateTime($tl['changed_at']) ?> by <?= e($tl['full_name'] ?: 'System') ?></div>
          <div class="timeline-label"><?= e($tl['action']) ?></div>
          <?php if ($tl['note'] && $tl['note'] !== $tl['action']): ?>
          <div class="timeline-note"><?= e($tl['note']) ?></div>
          <?php endif; ?>
          <?php if ($tl['queue']): ?>
          <span class="badge badge-secondary" style="margin-top:4px"><?= e($tl['queue']) ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Notes -->
<div class="tab-panel" data-tab-panel="notes" data-tab-group="ticket">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Notes</div>
      <?php if (!isPartnerUser()): ?>
      <button class="btn btn-primary btn-sm" data-modal-open="noteModal"><?= svgIcon('plus') ?> Add Note</button>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if (empty($notes)): ?>
      <div class="empty-state"><div class="empty-state-title">No notes</div></div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:14px">
        <?php foreach ($notes as $n): ?>
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <span class="font-600 font-sm"><?= e($n['full_name'] ?: 'System') ?></span>
            <div style="display:flex;gap:8px;align-items:center">
              <span class="badge badge-<?= $n['note_type'] === 'Partner Visible' ? 'info' : 'secondary' ?>"><?= e($n['note_type']) ?></span>
              <span class="text-muted font-xs"><?= fmtDateTime($n['created_at']) ?></span>
            </div>
          </div>
          <div style="font-size:.875rem;white-space:pre-wrap"><?= e($n['note']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Escalations -->
<?php if (!isPartnerUser()): ?>
<div class="tab-panel" data-tab-panel="escalations" data-tab-group="ticket">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Escalation History</div>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ESC #</th>
            <th>Level</th>
            <th>From</th>
            <th>To</th>
            <th>SLA %</th>
            <th>Notification</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $escStmt = $db->prepare("SELECT * FROM ticket_escalations WHERE ticket_id = ? ORDER BY created_at DESC");
          $escStmt->execute([$ticket['id']]);
          $escalations = $escStmt->fetchAll();
          ?>
          <?php if (empty($escalations)): ?>
          <tr><td colspan="7"><div class="empty-state"><div class="empty-state-title">No escalations</div></div></td></tr>
          <?php else: ?>
          <?php foreach ($escalations as $esc): ?>
          <tr>
            <td class="font-600"><?= e($esc['esc_number']) ?></td>
            <td>
              <span class="badge badge-<?= $esc['escalation_level'] >= 3 ? 'danger' : ($esc['escalation_level'] >= 2 ? 'warning' : 'info') ?>">
                Level <?= $esc['escalation_level'] ?>
              </span>
            </td>
            <td class="font-sm"><?= e($esc['from_queue']) ?></td>
            <td class="font-sm"><?= e($esc['to_queue']) ?></td>
            <td class="font-600"><?= number_format($esc['sla_pct'], 0) ?>%</td>
            <td>
              <span class="badge badge-<?= $esc['notification_status'] === 'Sent' ? 'success' : ($esc['notification_status'] === 'Failed' ? 'danger' : 'warning') ?>">
                <?= e($esc['notification_status']) ?>
              </span>
            </td>
            <td class="text-muted font-sm"><?= fmtDateTime($esc['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- Assign Modal -->
<?php if (!isPartnerUser()): ?>
<div class="modal-backdrop" id="assignModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Assign Ticket</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=assign">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Assign To</label>
          <select name="assign_to" class="form-control">
            <option value="">Unassigned</option>
            <?php foreach ($nocStaff as $ns): ?>
            <option value="<?= $ns['id'] ?>" <?= $ticket['assigned_to'] == $ns['id'] ? 'selected' : '' ?>><?= e($ns['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>

<!-- Queue Modal -->
<div class="modal-backdrop" id="queueModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Move to Queue</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=change_queue">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Queue</label>
          <select name="new_queue" class="form-control" required>
            <?php foreach ($queues as $q): ?>
            <option value="<?= e($q) ?>" <?= $ticket['current_queue'] === $q ? 'selected' : '' ?>><?= e($q) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Move</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Note Modal -->
<div class="modal-backdrop" id="noteModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Note</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=add_note">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Note Type</label>
          <select name="note_type" class="form-control">
            <option value="Internal">Internal (NOC only)</option>
            <option value="Partner Visible">Partner Visible</option>
          </select>
        </div>
        <div class="form-group" style="margin-top:14px">
          <label>Note</label>
          <textarea name="note" class="form-control" rows="5" required placeholder="Enter your note..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Add Note</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Reopen Modal (Partner) -->
<?php if ($ticket['status'] === 'Resolved - Awaiting Customer Confirmation' && isPartnerUser()): ?>
<div class="modal-backdrop" id="reopenModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Reopen Ticket</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=tickets&action=customer_action">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
      <input type="hidden" name="customer_action" value="reopen">
      <div class="modal-body">
        <p style="margin-bottom:14px;font-size:.9rem">Please explain why the issue is not resolved:</p>
        <div class="form-group">
          <textarea name="reopen_reason" class="form-control" rows="4" required placeholder="Reason for reopening..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-danger">Reopen Ticket</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
