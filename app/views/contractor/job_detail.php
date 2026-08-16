<?php
$isContractorView = isContractorUser();
$jobStatus = $assignment['status'];
$canUpdate = $isContractorView ? in_array($jobStatus, ['Assigned','Accepted','In Progress','Returned']) : true;
$canSubmit = $isContractorView ? in_array($jobStatus, ['Accepted','In Progress','Returned']) : true;

$svc = trim($assignment['service_type'] ?? '');
if ($svc === '') {
    if (!empty($assignment['fttx_package'])) {
        $svc = 'FTTH';
    } elseif (!empty($assignment['aggregate_capacity'])) {
        $svc = 'Layer 2 (last mile)';
    } elseif (!empty($assignment['bandwidth'])) {
        $svc = 'BIA (Broadband Internet Access)';
    } elseif ((float)($assignment['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($assignment['base_nrc_usd'] ?? 0) == 80000 || (float)($assignment['standard_nrc'] ?? 0) == 80000) {
        $svc = 'Remote Hands Only';
    } else {
        $svc = 'Service not specified';
    }
}
?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Job: <?= e($assignment['order_number']) ?></div>
    <div class="page-subtitle"><?= e($svc) ?> · <?= e($assignment['customer_name']) ?> · <?= e($assignment['customer_location'] ?: '—') ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-secondary"><?= svgIcon('list') ?> All Jobs</a>
    <?php if (!$isContractorView): ?>
    <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $assignment['order_id'] ?>" class="btn btn-secondary"><?= svgIcon('document') ?> Order Detail</a>
    <?php endif; ?>
  </div>
</div>

<!-- Job Details & Evidence Checklist Top Grid -->
<div class="row g-4" style="margin-bottom:24px">
  <!-- Job Details Column -->
  <div class="col-lg-7">
    <div class="card h-100" style="margin-bottom:0">
      <div class="card-header"><div class="card-title"><?= svgIcon('info') ?> Job Details</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Status</label><div><span class="badge badge-primary"><?= e($jobStatus) ?></span></div></div>
          <div class="form-group"><label>Order Status</label><div><span class="badge <?= orderStatusClass($assignment['order_status']) ?>"><?= e($assignment['order_status']) ?></span></div></div>
          <div class="form-group"><label>Customer</label><div class="font-600"><?= e($assignment['customer_name']) ?></div></div>
          <div class="form-group"><label>Location</label><div><?= e($assignment['customer_location'] ?: '—') ?></div></div>
          <?php if ($assignment['building_name']): ?><div class="form-group"><label>Building</label><div><?= e($assignment['building_name']) ?></div></div><?php endif; ?>
          <?php if ($assignment['gps_coordinates']): ?><div class="form-group"><label>GPS</label><div><?= e($assignment['gps_coordinates']) ?></div></div><?php endif; ?>
          <div class="form-group"><label>Assigned By</label><div><?= e($assignment['assigned_by_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Assigned Date</label><div><?= fmtDate($assignment['assigned_at']) ?></div></div>
          <div class="form-group"><label>Target Date</label><div><?= $assignment['target_date'] ? fmtDate($assignment['target_date']) : '—' ?></div></div>
          <?php if ($assignment['completed_at']): ?><div class="form-group"><label>Completed At</label><div><?= fmtDate($assignment['completed_at']) ?></div></div><?php endif; ?>
        </div>
        <?php if ($assignment['work_order_notes']): ?>
        <div class="divider" style="margin:16px 0"></div>
        <div class="form-group"><label>Work Order Notes</label><div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px;white-space:pre-wrap"><?= e($assignment['work_order_notes']) ?></div></div>
        <?php endif; ?>
        <?php if ($assignment['bsa_special_conditions']): ?>
        <div class="form-group" style="margin-top:12px"><label>Technical Conditions (BSA)</label><div style="background:rgba(255,171,64,.1);border:1px solid #ffab40;border-radius:var(--radius-sm);padding:12px;font-size:.875rem"><?= e($assignment['bsa_special_conditions']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Evidence Checklist Column -->
  <div class="col-lg-5">
    <div class="card h-100" style="margin-bottom:0">
      <?php
      $totalChecklistItems = count($checklist);
      $completedChecklistItems = 0;
      $mandatoryChecklistItems = 0;
      $mandatoryCompletedItems = 0;

      foreach ($checklist as $chkItem) {
          if ($chkItem['uploaded'] > 0) $completedChecklistItems++;
          if ($chkItem['is_mandatory']) {
              $mandatoryChecklistItems++;
              if ($chkItem['uploaded'] > 0) $mandatoryCompletedItems++;
          }
      }
      $checklistPct = $totalChecklistItems > 0 ? round(($completedChecklistItems / $totalChecklistItems) * 100) : 0;
      $isAllMandatorySatisfied = ($mandatoryChecklistItems === 0 || $mandatoryCompletedItems === $mandatoryChecklistItems);
      ?>
      
      <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:14px 18px;border-bottom:1px solid var(--border)">
        <div>
          <div class="card-title" style="display:flex;align-items:center;gap:8px;font-size:1rem">
            <?= svgIcon('check', 18) ?> Evidence Checklist
          </div>
          <div class="card-subtitle" style="font-size:0.78rem">Order requirement (<?= e($svc) ?>)</div>
        </div>
        <div>
          <?php if ($totalChecklistItems > 0): ?>
            <span class="badge <?= $checklistPct === 100 ? 'badge-success' : ($isAllMandatorySatisfied ? 'badge-info' : 'badge-danger') ?>" style="font-size:0.75rem;padding:4px 8px;">
              <?= $completedChecklistItems ?> / <?= $totalChecklistItems ?> Completed (<?= $checklistPct ?>%)
            </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Completion Progress Bar -->
      <?php if ($totalChecklistItems > 0): ?>
      <div style="background:var(--surface-2);height:5px;width:100%;overflow:hidden">
        <div style="height:100%;width:<?= $checklistPct ?>%;background:<?= $checklistPct === 100 ? '#10B981' : ($isAllMandatorySatisfied ? '#3B82F6' : '#EF4444') ?>;transition:width 0.4s ease"></div>
      </div>
      <?php endif; ?>

      <div class="card-body" style="padding:12px 16px;">
        <?php if (empty($checklist)): ?>
        <div class="empty-state" style="padding:24px 16px;text-align:center">
          <div style="font-size:1.5rem;margin-bottom:8px">📋</div>
          <div class="empty-state-title" style="font-weight:600;color:var(--text-muted)">No checklist configured</div>
          <div class="empty-state-text" style="font-size:0.82rem;color:var(--text-muted);margin-top:4px">No specific evidence required for this service type.</div>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($checklist as $item): ?>
          <?php
            $isUploaded = $item['uploaded'] > 0;
            $isMandatory = (bool)$item['is_mandatory'];
            $latestFile = $item['files'][0] ?? null;

            if ($isUploaded) {
                $rowBg = 'rgba(16, 185, 129, 0.08)';
                $rowBorder = '1px solid rgba(16, 185, 129, 0.3)';
                $iconColor = '#10B981';
                $iconSvg = svgIcon('check', 16);
            } elseif ($isMandatory) {
                $rowBg = 'rgba(239, 68, 68, 0.08)';
                $rowBorder = '1px solid rgba(239, 68, 68, 0.3)';
                $iconColor = '#EF4444';
                $iconSvg = svgIcon('x', 16);
            } else {
                $rowBg = 'var(--surface-2)';
                $rowBorder = '1px solid var(--border)';
                $iconColor = 'var(--text-muted)';
                $iconSvg = svgIcon('info', 16);
            }
          ?>
          <div class="checklist-item-row" 
               onclick="selectAndScrollToEvidence('<?= e($item['evidence_type']) ?>')"
               style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:var(--radius-sm);background:<?= $rowBg ?>;border:<?= $rowBorder ?>;cursor:pointer;transition:transform 0.15s ease, box-shadow 0.15s ease;flex-wrap:wrap;gap:8px"
               title="<?= $isUploaded ? 'Evidence uploaded. Click View, Replace, or Delete on the right.' : 'Click to select ' . e($item['evidence_type']) . ' in upload section below' ?>">
            
            <!-- Left Side: Status Icon + Evidence Type Name -->
            <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:160px">
              <div style="width:24px;height:24px;border-radius:50%;background:<?= $isUploaded ? 'rgba(16, 185, 129, 0.2)' : ($isMandatory ? 'rgba(239, 68, 68, 0.2)' : 'rgba(156, 163, 175, 0.2)') ?>;color:<?= $iconColor ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?= $iconSvg ?>
              </div>
              <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:0.88rem;color:<?= $isUploaded ? '#065F46' : 'var(--text-primary)' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  <?= e($item['evidence_type']) ?>
                </div>
              </div>
            </div>

            <!-- Right Side: Status & Actions in exact order: Uploaded (X) | View | Replace | Delete -->
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap">
              <?php if ($isUploaded): ?>
                <!-- Uploaded Status (Required badge removed when uploaded) -->
                <span class="badge-uploaded">
                  ✓ Uploaded (<?= (int)$item['uploaded'] ?>)
                </span>

                <!-- 1. View Button -->
                <button type="button" class="btn-file-action btn-file-view" onclick="event.stopPropagation(); openEvidenceFilesModal(<?= htmlspecialchars(json_encode([
                  'evidence_type' => $item['evidence_type'],
                  'is_mandatory'  => $item['is_mandatory'],
                  'assignment_id' => $assignment['id'],
                  'order_id'      => $assignment['order_id'],
                  'files'         => $item['files']
                ]), ENT_QUOTES, 'UTF-8') ?>)" title="View uploaded file preview and details">
                  <?= svgIcon('eye', 13) ?> View
                </button>

                <!-- 2. Replace Button -->
                <button type="button" class="btn-file-action btn-file-replace" onclick="event.stopPropagation(); triggerReplaceForEvidence('<?= e($item['evidence_type']) ?>')" title="Upload replacement file for this item">
                  <?= svgIcon('edit', 13) ?> Replace
                </button>

                <!-- 3. Delete Button -->
                <?php if ($latestFile): ?>
                <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=delete_evidence" style="display:inline" data-confirm="Delete evidence for <?= e($item['evidence_type']) ?>? This action cannot be undone." data-confirm-title="Delete Evidence File?" data-confirm-btn="Delete File" data-confirm-class="btn-danger" data-confirm-icon="🗑️" onclick="event.stopPropagation()">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="evidence_id" value="<?= $latestFile['id'] ?>">
                  <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                  <button type="submit" class="btn-file-action btn-file-delete" title="Delete uploaded file">
                    <?= svgIcon('trash', 13) ?> Delete
                  </button>
                </form>
                <?php endif; ?>

              <?php else: ?>
                <!-- Missing Row: Required / Optional Badge -->
                <?php if ($isMandatory): ?>
                  <span class="badge badge-danger" style="font-size:0.68rem;padding:3px 8px;font-weight:700">Required</span>
                <?php else: ?>
                  <span class="badge badge-secondary" style="font-size:0.68rem;padding:3px 8px">Optional</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>

          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Admin / PM Job Management Panel -->
<?php if (!isContractorUser()): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--primary)">
  <div class="card-header" style="background:var(--surface-hover)">
    <div class="card-title"><?= svgIcon('edit') ?> Admin / PM Job Management</div>
    <div class="card-subtitle">Update contractor assignment status, target date, and work order notes.</div>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=admin_update_job">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
      
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Job Assignment Status <span class="required">*</span></label>
          <select name="status" class="form-control" required>
            <?php foreach(['Assigned','Accepted','In Progress','Completed Submitted','Completed','Returned','Cancelled'] as $st): ?>
            <option value="<?= $st ?>" <?= $jobStatus === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Target Date</label>
          <input type="date" name="target_date" class="form-control" value="<?= e($assignment['target_date'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Contractor</label>
          <input type="text" class="form-control" value="<?= e($assignment['contractor_name']) ?>" readonly style="background:var(--surface-hover)">
        </div>
      </div>

      <div class="form-group">
        <label>Work Order Notes</label>
        <textarea name="work_order_notes" class="form-control" rows="2" placeholder="Update work order notes or instructions..."><?= e($assignment['work_order_notes'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary"><?= svgIcon('save') ?> Save Status &amp; Details</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Job Accept (if Assigned) -->
<?php if ($jobStatus === 'Assigned' && $isContractorView): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--warning)">
  <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:1">
      <div style="font-weight:700;font-size:1rem">New job assigned — please accept to proceed</div>
      <div style="color:var(--text-secondary);font-size:.875rem">Review the job details above, then accept the assignment to start work.</div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=accept_job">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
      <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Accept Job</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Evidence Upload -->
<?php if ($canUpdate): ?>
<div class="card" id="uploadEvidenceCard" style="margin-bottom:24px;transition:outline 0.3s ease">
  <div class="card-header"><div class="card-title"><?= svgIcon('upload') ?> Upload Evidence</div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=upload_evidence" enctype="multipart/form-data" id="evidenceUploadForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
      <input type="hidden" name="order_id" value="<?= $assignment['order_id'] ?>">
      
      <div class="row g-3 align-items-end" style="margin-bottom:16px">
        <div class="col-md-4" id="evidenceTypeCol">
          <label class="form-label">Evidence Type <span class="text-danger">*</span></label>
          <select name="evidence_type" id="evidence_type_select" class="form-control" required>
            <option value="">Select type...</option>
            <option value="Site Photo">Site Photo</option>
            <option value="ONT/ONU Serial">ONT/ONU Serial</option>
            <option value="Signal Test">Signal Test</option>
            <option value="Speed Test">Speed Test</option>
            <option value="Ping Test">Ping Test</option>
            <option value="Latency Test">Latency Test</option>
            <option value="UAT Sign-off">UAT Sign-off</option>
            <option value="Installation Remarks">Installation Remarks</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="col-md-4" id="serialNumberCol" style="display:none">
          <label class="form-label">Serial Number <span class="text-muted font-sm">(for ONT/ONU)</span></label>
          <input type="text" name="serial_number" id="serial_number_input" class="form-control" placeholder="e.g. HWTC1234567">
        </div>
        <div class="col-md-8" id="fileCol">
          <label class="form-label">Files <span class="text-muted font-sm">(Multiple photos / screenshots / PDFs supported)</span></label>
          <input type="file" name="evidence_files[]" id="evidence_file_input" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.xlsx,.doc,.docx">
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">Notes</label>
        <textarea name="notes" id="notes_input" class="form-control" rows="2" placeholder="Describe the evidence or any observations..."></textarea>
      </div>

      <button type="submit" class="btn btn-primary"><?= svgIcon('upload') ?> Upload Evidence</button>
    </form>
  </div>
</div>

<script>
function selectAndScrollToEvidence(type) {
  const uploadCard = document.getElementById('uploadEvidenceCard');
  const typeSelect = document.getElementById('evidence_type_select');
  if (typeSelect) {
    for (let i = 0; i < typeSelect.options.length; i++) {
      if (typeSelect.options[i].value.toLowerCase().trim() === type.toLowerCase().trim()) {
        typeSelect.selectedIndex = i;
        typeSelect.dispatchEvent(new Event('change'));
        break;
      }
    }
  }
  if (uploadCard) {
    uploadCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    uploadCard.style.outline = '2px solid var(--accent)';
    setTimeout(() => { uploadCard.style.outline = ''; }, 2000);
  }
}

function triggerReplaceForEvidence(type) {
  selectAndScrollToEvidence(type);
  const fileInput = document.getElementById('evidence_file_input');
  if (fileInput) {
    setTimeout(() => {
      fileInput.click();
    }, 300);
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const evTypeSelect = document.getElementById('evidence_type_select');
  const serialCol = document.getElementById('serialNumberCol');
  const serialInput = document.getElementById('serial_number_input');
  const fileCol = document.getElementById('fileCol');

  function updateSerialVisibility() {
    if (!evTypeSelect || !serialCol || !fileCol) return;
    if (evTypeSelect.value === 'ONT/ONU Serial') {
      serialCol.style.display = 'block';
      fileCol.className = 'col-md-4';
    } else {
      if (serialInput) serialInput.value = '';
      serialCol.style.display = 'none';
      fileCol.className = 'col-md-8';
    }
  }

  if (evTypeSelect) {
    evTypeSelect.addEventListener('change', updateSerialVisibility);
    updateSerialVisibility();
  }
});
</script>
<?php endif; ?>

<!-- Progress Update -->
<?php if ($canUpdate): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">Post Progress Update</div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=progress_update">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
      <input type="hidden" name="order_id" value="<?= $assignment['order_id'] ?>">
      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label>Update Status</label>
          <select name="progress_status" class="form-control">
            <option value="In Progress">In Progress</option>
            <option value="Delayed">Delayed</option>
            <option value="Blocked">Blocked</option>
          </select>
        </div>
        <div class="form-group" id="delayReasonGroup">
          <label>Delay / Block Reason</label>
          <select name="delay_reason" class="form-control">
            <option value="">Not applicable</option>
            <option value="Customer Unavailable">Customer Unavailable</option>
            <option value="Access Denied">Access Denied</option>
            <option value="Weather">Weather</option>
            <option value="Missing Materials">Missing Materials</option>
            <option value="Technical Issue">Technical Issue</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes <span class="required">*</span></label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Describe current progress, observations, or issues..." required></textarea>
      </div>

      <div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.875rem">
          <input type="checkbox" name="is_blocker" value="1" id="blockerCheck">
          <span style="font-weight:600;color:var(--danger)">⚠️ Report as Blocker (Pauses SLA clock)</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.875rem">
          <input type="checkbox" name="is_resumed" value="1" id="resumedCheck">
          <span style="font-weight:600;color:var(--success)">▶️ Installation Resumed (Resumes SLA clock)</span>
        </label>
      </div>

      <button type="submit" class="btn btn-secondary"><?= svgIcon('save') ?> Post Update</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Submit Completion -->
<?php if ($canSubmit): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--success)">
  <div class="card-header" style="background:rgba(var(--success-rgb,34,197,94),.1)">
    <div class="card-title"><?= svgIcon('check') ?> Submit Job Completion</div>
    <div class="card-subtitle">Ensure all mandatory evidence is uploaded before submitting.</div>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=contractor&action=submit_completion" data-confirm="Submit job as complete? This will move the order to Testing for PM review.">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
      <input type="hidden" name="order_id" value="<?= $assignment['order_id'] ?>">
      <div class="form-group">
        <label>Completion Remarks</label>
        <textarea name="completion_remarks" class="form-control" rows="3" placeholder="Any final remarks about the installation..."></textarea>
      </div>
      <button type="submit" class="btn btn-success"><?= svgIcon('check') ?> Submit Job as Complete</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Tabs: Evidence uploaded, Progress -->
<div class="tabs" data-group="job">
  <button class="tab-btn active" data-tab="evidence-list" data-tab-group="job">Evidence Uploaded (<?= count($evidence) ?>)</button>
  <button class="tab-btn" data-tab="progress-list" data-tab-group="job">Progress Updates (<?= count($progressUpdates) ?>)</button>
</div>

<div class="tab-panel active" data-tab-panel="evidence-list" data-tab-group="job">
  <div class="card" style="width:100%;overflow:hidden">
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
          <?php if (empty($evidence)): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px"><div class="empty-state"><div class="empty-state-title">No evidence uploaded yet</div></div></td></tr>
          <?php else: ?>
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
              <a href="<?= APP_URL ?>/?page=contractor&action=download_evidence&id=<?= $ev['id'] ?>" class="btn btn-sm btn-secondary" style="padding:4px 10px;display:inline-flex;align-items:center;gap:4px">
                <?= svgIcon('download') ?> Download
              </a>
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
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="tab-panel" data-tab-panel="progress-list" data-tab-group="job">
  <div class="card">
    <div class="card-body">
      <?php if (empty($progressUpdates)): ?>
      <div class="empty-state"><div class="empty-state-title">No updates yet</div></div>
      <?php else: ?>
      <div class="timeline">
        <?php foreach ($progressUpdates as $pu): ?>
        <div class="timeline-item">
          <div class="timeline-dot <?= $pu['progress_status'] === 'Completed' ? 'success' : ($pu['progress_status'] === 'Blocked' ? 'danger' : '') ?>"></div>
          <div class="timeline-time"><?= fmtDateTime($pu['created_at']) ?> by <?= e($pu['full_name'] ?: '—') ?></div>
          <div class="timeline-label"><span class="badge badge-secondary"><?= e($pu['progress_status']) ?></span><?php if ($pu['delay_reason']): ?> <span class="badge badge-warning"><?= e($pu['delay_reason']) ?></span><?php endif; ?></div>
          <div class="timeline-note"><?= e($pu['notes']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Neilos Evidence Files Preview & Management Modal -->
<div class="modal-backdrop" id="neilosEvidenceViewModal" style="z-index:999999">
  <div class="modal" style="max-width:760px;width:92%;padding:0;overflow:hidden;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);border:1px solid var(--border);background:var(--surface)">
    <!-- Modal Header -->
    <div style="background:var(--accent-pale);padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:40px;height:40px;border-radius:10px;background:var(--accent-glow);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.25rem">
          📁
        </div>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;color:var(--text-primary)" id="evModalTitle">Evidence Files</h3>
          <div style="font-size:0.8rem;color:var(--text-muted)" id="evModalSubtitle">Uploaded files for order requirement</div>
        </div>
      </div>
      <button type="button" class="modal-close" onclick="closeEvidenceViewModal()" style="background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;line-height:1">&times;</button>
    </div>

    <!-- Modal Body -->
    <div style="padding:20px 24px;max-height:75vh;overflow-y:auto" id="evModalBody">
      <!-- Dynamically populated file cards -->
    </div>

    <!-- Modal Footer -->
    <div style="padding:14px 24px;border-top:1px solid var(--border);background:var(--surface-2);display:flex;justify-content:space-between;align-items:center">
      <div class="text-muted font-sm">Click Download for direct local file saving</div>
      <button type="button" class="btn btn-secondary" onclick="closeEvidenceViewModal()">Close</button>
    </div>
  </div>
</div>

<script>
function openEvidenceFilesModal(data) {
  const modal = document.getElementById('neilosEvidenceViewModal');
  const title = document.getElementById('evModalTitle');
  const subtitle = document.getElementById('evModalSubtitle');
  const body = document.getElementById('evModalBody');

  if (!modal || !body) return;

  if (title) title.textContent = data.evidence_type + ' Files (' + (data.files ? data.files.length : 0) + ')';
  if (subtitle) subtitle.textContent = (data.is_mandatory ? 'Mandatory' : 'Optional') + ' evidence requirement';

  body.innerHTML = '';

  if (!data.files || data.files.length === 0) {
    body.innerHTML = '<div class="empty-state" style="padding:24px;text-align:center"><div class="empty-state-title">No uploaded files</div></div>';
  } else {
    data.files.forEach((file, index) => {
      const isLatest = (index === 0);
      const ext = file.file_name ? file.file_name.split('.').pop().toLowerCase() : '';
      const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
      const isPdf = (ext === 'pdf');
      const fileUrl = APP_URL + '/' + (file.file_path || '').replace(/^\/+/, '');

      const card = document.createElement('div');
      card.style.cssText = 'background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:18px;margin-bottom:16px;position:relative;';

      let previewHtml = '';
      if (isImg) {
        previewHtml = `<div style="text-align:center;margin-bottom:14px;background:#000;border-radius:8px;padding:8px;max-height:300px;overflow:hidden">
          <img src="${fileUrl}" alt="${file.file_name}" style="max-height:280px;max-width:100%;object-fit:contain;border-radius:4px">
        </div>`;
      } else if (isPdf) {
        previewHtml = `<div style="margin-bottom:14px">
          <iframe src="${fileUrl}" style="width:100%;height:320px;border:1px solid var(--border);border-radius:8px"></iframe>
        </div>`;
      } else {
        previewHtml = `<div style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--surface-1);border-radius:8px;margin-bottom:14px">
          <div style="font-size:2rem">📄</div>
          <div>
            <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary)">${file.file_name || 'Uploaded Document'}</div>
            <div style="font-size:0.8rem;color:var(--text-muted)">Direct browser preview unavailable for .${ext} files. Click Download to save file directly.</div>
          </div>
        </div>`;
      }

      const formattedSize = file.file_size ? (file.file_size > 1048576 ? (file.file_size / 1048576).toFixed(1) + ' MB' : (file.file_size / 1024).toFixed(1) + ' KB') : '—';

      card.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-weight:700;font-size:0.95rem;color:var(--text-primary)">${file.file_name || 'Evidence File #' + file.id}</span>
            ${isLatest ? '<span class="badge badge-success" style="font-size:0.7rem">★ Current / Latest File</span>' : '<span class="badge badge-secondary" style="font-size:0.7rem">Previous Version</span>'}
          </div>
          <span class="badge badge-outline" style="font-size:0.72rem">${ext.toUpperCase()}</span>
        </div>

        ${previewHtml}

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:10px;font-size:0.82rem;background:var(--surface-1);padding:10px 14px;border-radius:6px;margin-bottom:14px;border:1px solid var(--border)">
          <div><strong style="color:var(--text-muted)">Uploader:</strong> ${file.uploader_name || 'Contractor'}</div>
          <div><strong style="color:var(--text-muted)">Upload Date:</strong> ${file.uploaded_at || 'Recently'}</div>
          <div><strong style="color:var(--text-muted)">File Size:</strong> ${formattedSize}</div>
          ${file.serial_number ? `<div><strong style="color:var(--text-muted)">ONT Serial:</strong> ${file.serial_number}</div>` : ''}
          ${file.notes ? `<div style="grid-column:1/-1"><strong style="color:var(--text-muted)">Notes:</strong> ${file.notes}</div>` : ''}
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
          <a href="${APP_URL}/?page=contractor&action=download_evidence&id=${file.id}" class="btn btn-sm btn-primary" style="text-decoration:none">
            <?= svgIcon('download', 14) ?> Download
          </a>
          <button type="button" class="btn btn-sm btn-secondary" onclick="toggleInlineReplaceForm(${file.id})">
            <?= svgIcon('edit', 14) ?> Replace File
          </button>
          <form method="POST" action="${APP_URL}/?page=contractor&action=delete_evidence" style="display:inline" data-confirm="Delete this file? This action cannot be undone." data-confirm-title="Delete Evidence File?" data-confirm-btn="Delete File" data-confirm-class="btn-danger" data-confirm-icon="🗑️">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="evidence_id" value="${file.id}">
            <input type="hidden" name="assignment_id" value="${data.assignment_id}">
            <button type="submit" class="btn btn-sm btn-danger"><?= svgIcon('trash', 14) ?> Delete</button>
          </form>
        </div>

        <div id="replaceFormWrap_${file.id}" style="display:none;margin-top:14px;padding-top:14px;border-top:1px dashed var(--border)">
          <form method="POST" action="${APP_URL}/?page=contractor&action=replace_evidence" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="evidence_id" value="${file.id}">
            <input type="hidden" name="assignment_id" value="${data.assignment_id}">
            <input type="hidden" name="order_id" value="${data.order_id}">
            <input type="hidden" name="evidence_type" value="${data.evidence_type}">
            
            <div style="font-weight:600;font-size:0.85rem;margin-bottom:8px">Replace File for ${data.evidence_type}</div>
            <div class="form-group mb-12">
              <label class="font-sm">Select New File</label>
              <input type="file" name="evidence_file" class="form-control form-control-sm" required accept=".pdf,.jpg,.jpeg,.png,.xlsx,.doc,.docx">
            </div>
            ${data.evidence_type === 'ONT/ONU Serial' ? `
            <div class="form-group mb-12">
              <label class="font-sm">Updated Serial Number</label>
              <input type="text" name="serial_number" class="form-control form-control-sm" value="${file.serial_number || ''}">
            </div>` : ''}
            <div class="form-group mb-12">
              <label class="font-sm">Replacement Notes</label>
              <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes for replacement..." value="${file.notes || ''}">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
              <button type="button" class="btn btn-sm btn-secondary" onclick="toggleInlineReplaceForm(${file.id})">Cancel</button>
              <button type="submit" class="btn btn-sm btn-success"><?= svgIcon('upload', 13) ?> Upload Replacement</button>
            </div>
          </form>
        </div>
      `;

      body.appendChild(card);
    });
  }

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeEvidenceViewModal() {
  const modal = document.getElementById('neilosEvidenceViewModal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}

function toggleInlineReplaceForm(fileId) {
  const wrap = document.getElementById('replaceFormWrap_' + fileId);
  if (wrap) {
    wrap.style.display = (wrap.style.display === 'none' || !wrap.style.display) ? 'block' : 'none';
  }
}
</script>
