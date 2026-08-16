<?php
// ============================================================
// Neilos Partner Portal — One-Page KYC Application & Review Form
// ============================================================

$isReviewer = (isPartnerUser() || isContractorUser());
$isAdminOrMgmt = (isAdmin() || hasRole('Management'));
$status = $app['status'] ?? 'Draft';
$kycType = $app['kyc_type'] ?? 'Partner';
$selectedPartnerId = $app['partner_id'] ?? 0;
$appId = (int)($app['id'] ?? 0);

$isReadonly = $isReviewer || ($status === 'Approved') || in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted']);

$customFields = json_decode($app['custom_fields'] ?? '[]', true) ?: [];
$selectedRegions = array_map('trim', explode(',', $app['service_regions'] ?? ''));

$availableRegions = ['DAR ES SALAAM', 'DODOMA', 'ARUSHA', 'MBEYA', 'MWANZA', 'TANGA', 'ZANZIBAR', 'MOROGORO', 'SHINYANGA', 'KIGOMA', 'KILIMANJARO', 'PEMBA'];
$paymentTermsOptions = ['7 Days', '15 Days', '30 Days', '45 Days', '60 Days'];

// Helper to render one of the 7 compliance document upload rows with status badges & actions
function renderComplianceDocUploadRow($docKey, $docLabel, $filePath, $appId, $isReadonly, $extraHtml = '') {
    $hasFile = !empty($filePath);
    $fileName = basename($filePath);
    $downloadUrl = APP_URL . "/?page=download&table=partner_kyc_applications&column={$docKey}&id={$appId}";
    
    ob_start();
    ?>
    <div style="padding:14px 16px;background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:14px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:10px">
          <strong style="font-size:0.95rem;color:var(--text-primary)"><?= e($docLabel) ?> <span class="text-danger">*</span></strong>
          <?php if ($hasFile): ?>
            <span class="badge-uploaded">✓ Uploaded (1)</span>
          <?php else: ?>
            <span class="badge badge-danger" style="font-size:0.75rem;padding:4px 9px">Required</span>
          <?php endif; ?>
        </div>

        <?php if ($hasFile): ?>
          <div style="display:flex;align-items:center;gap:6px">
            <button type="button" class="btn-file-action btn-file-view" onclick="viewSystemFile('<?= e($filePath) ?>', '<?= e($fileName) ?>', '<?= e($downloadUrl) ?>')">
              <?= svgIcon('eye', 13) ?> View
            </button>
            <a href="<?= e($downloadUrl) ?>" class="btn-file-action btn-file-download" style="text-decoration:none">
              <?= svgIcon('download', 13) ?> Download
            </a>
            <?php if (!$isReadonly): ?>
              <label class="btn-file-action btn-file-replace" style="margin:0;cursor:pointer">
                <?= svgIcon('edit', 13) ?> Replace <input type="file" name="<?= e($docKey) ?>" style="display:none" onchange="this.form.submit()">
              </label>
              <button type="button" class="btn-file-action btn-file-delete" onclick="deleteKycDoc('<?= e($docKey) ?>')">
                <?= svgIcon('trash', 13) ?> Delete
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!$hasFile && !$isReadonly): ?>
        <div style="margin-top:8px">
          <input type="file" name="<?= e($docKey) ?>" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
        </div>
      <?php endif; ?>

      <?php if ($extraHtml): ?>
        <div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--border)">
          <?= $extraHtml ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <?= $isReviewer ? 'Review KYC Application' : ($app ? 'Edit KYC Application' : 'New KYC Application') ?>
    </h1>
    <div class="page-subtitle">
      <?= e($app['registered_name'] ?? $app['partner_name'] ?? 'Select Partner or Contractor to begin') ?>
    </div>
  </div>
  <div class="page-header-actions">
    <?php if ($isAdminOrMgmt): ?>
    <a href="<?= APP_URL ?>/?page=kyc&action=admin_list" class="btn btn-secondary"><?= svgIcon('list') ?> All KYC Applications</a>
    <?php endif; ?>
  </div>
</div>

<!-- Rejection Reason Highlight Banner -->
<?php if ($status === 'Rejected' && !empty($app['rejection_reason'])): ?>
<div style="margin-bottom:24px;padding:16px 20px;background:#FEF2F2;border:1px solid #FCA5A5;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm)">
  <div style="display:flex;align-items:center;gap:10px;color:#991B1B;font-weight:700;font-size:1.05rem;margin-bottom:6px">
    <span>⚠️</span> KYC Application Rejected
  </div>
  <div style="font-size:0.9rem;color:#7F1D1D;line-height:1.5">
    <strong>Rejection Reason:</strong> <?= e($app['rejection_reason']) ?>
  </div>
  <?php if ($isAdminOrMgmt): ?>
  <div style="margin-top:10px;font-size:0.82rem;color:#991B1B">
    Please update the missing or corrected information below, upload required files, and click <strong>[ Resubmit for Approval ]</strong>.
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/?page=kyc&action=<?= $status === 'Rejected' ? 'resubmit' : 'submit' ?>" enctype="multipart/form-data" id="kycOnePageForm">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="kyc_id" id="kycIdInput" value="<?= $appId ?>">

  <!-- 1. KYC INFORMATION & ENTITY SELECTOR -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--accent-pale);padding:14px 20px;display:flex;align-items:center;justify-content:space-between">
      <div class="card-title" style="font-weight:700;font-size:1.05rem;display:flex;align-items:center;gap:8px">
        <?= svgIcon('document') ?> 1. KYC Information &amp; Type
      </div>
      <span class="badge <?= $status === 'Approved' ? 'badge-success' : ($status === 'Rejected' ? 'badge-danger' : ($status === 'Pending Approval' || $status === 'Submitted' ? 'badge-warning' : 'badge-secondary')) ?>" style="font-size:0.85rem;padding:6px 12px">
        Status: <?= e($status) ?>
      </span>
    </div>
    <div class="card-body" style="padding:20px">
      <div class="grid-2" style="gap:20px">
        <div>
          <label style="font-weight:700;margin-bottom:6px;display:block">KYC Type <span class="text-danger">*</span></label>
          <select name="kyc_type" id="kycTypeSelect" class="form-control" style="font-weight:600" <?= $isReadonly ? 'disabled' : '' ?> onchange="onKycTypeChange()">
            <option value="Partner" <?= $kycType === 'Partner' ? 'selected' : '' ?>>Partner (ISP / MNO / Service Provider)</option>
            <option value="Contractor" <?= $kycType === 'Contractor' ? 'selected' : '' ?>>Contractor (Field Installation / Support Vendor)</option>
          </select>
        </div>

        <div>
          <label style="font-weight:700;margin-bottom:6px;display:block" id="entitySelectLabel">
            <?= $kycType === 'Contractor' ? 'Contractor *' : 'Partner *' ?>
          </label>
          <select name="partner_id" id="entityPartnerSelect" class="form-control" style="font-weight:600" <?= $isReadonly ? 'disabled' : '' ?> onchange="onEntitySelectChange()">
            <option value="">-- Select Entity --</option>
            <optgroup label="Partners" id="partnerGroup" style="<?= $kycType === 'Contractor' ? 'display:none' : '' ?>">
              <?php foreach ($partnersList as $p): ?>
              <?php $stLabel = !empty($p['kyc_status']) ? ' — KYC ' . $p['kyc_status'] : ' — No KYC Application'; ?>
              <option value="<?= $p['id'] ?>" data-kyc-id="<?= (int)($p['kyc_app_id'] ?? 0) ?>" data-kyc-status="<?= e($p['kyc_status'] ?? '') ?>" <?= (int)$selectedPartnerId === (int)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?><?= e($stLabel) ?>
              </option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Contractors" id="contractorGroup" style="<?= $kycType === 'Partner' ? 'display:none' : '' ?>">
              <?php foreach ($contractorsList as $c): ?>
              <?php $stLabel = !empty($c['kyc_status']) ? ' — KYC ' . $c['kyc_status'] : ' — No KYC Application'; ?>
              <option value="<?= $c['id'] ?>" data-kyc-id="<?= (int)($c['kyc_app_id'] ?? 0) ?>" data-kyc-status="<?= e($c['kyc_status'] ?? '') ?>" <?= (int)$selectedPartnerId === (int)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?><?= e($stLabel) ?>
              </option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. COMPANY DETAILS -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--surface-2);padding:14px 20px;display:flex;align-items:center;justify-content:space-between">
      <div class="card-title" style="font-weight:700;font-size:1.05rem">COMPANY DETAILS</div>
    </div>
    <div class="card-body" style="padding:20px">
      <div class="grid-3" style="gap:16px;margin-bottom:16px">
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Partner / Contractor Name <span class="text-danger">*</span></label>
          <input type="text" name="registered_name" id="registered_name" class="form-control" value="<?= e($app['registered_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Registration Number <span class="text-danger">*</span></label>
          <input type="text" name="registration_number" id="registration_number" class="form-control" value="<?= e($app['registration_number'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">TIN <span class="text-danger">*</span></label>
          <input type="text" name="tin" id="tin" class="form-control" value="<?= e($app['tin'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
      </div>

      <div class="grid-3" style="gap:16px;margin-bottom:16px">
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">VAT / VRN Number <span class="text-danger">*</span></label>
          <input type="text" name="vat_vrn" id="vat_vrn" class="form-control" value="<?= e($app['vat_vrn'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">City / Region</label>
          <input type="text" name="city_region" id="city_region" class="form-control" value="<?= e($app['city_region'] ?? 'Dar es Salaam') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Country</label>
          <input type="text" name="country" id="country" class="form-control" value="<?= e($app['country'] ?? 'Tanzania') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
      </div>

      <div style="margin-bottom:16px">
        <label style="font-weight:600;margin-bottom:4px;display:block">Physical Address <span class="text-danger">*</span></label>
        <textarea name="office_address" id="office_address" class="form-control" rows="2" required <?= $isReadonly ? 'readonly' : '' ?>><?= e($app['office_address'] ?? '') ?></textarea>
      </div>

      <!-- Business License File Upload & Actions -->
      <div style="margin-bottom:16px">
        <?= renderComplianceDocUploadRow('business_license', 'Business License Document', $app['business_license'] ?? '', $appId, $isReadonly) ?>
      </div>

      <!-- Dynamic Custom Fields ([ + Add Field ]) -->
      <div style="margin-top:20px;padding-top:16px;border-top:1px dashed var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <label style="font-weight:700;font-size:0.95rem;margin:0">Custom Company Fields</label>
          <?php if (!$isReadonly): ?>
          <button type="button" class="btn btn-secondary btn-sm" onclick="addCustomFieldRow()">
            <?= svgIcon('plus') ?> Add Field
          </button>
          <?php endif; ?>
        </div>

        <div id="customFieldsContainer" style="display:flex;flex-direction:column;gap:10px">
          <?php if (!empty($customFields)): ?>
            <?php foreach ($customFields as $idx => $cf): ?>
            <div class="custom-field-row grid-4" style="gap:10px;align-items:center;background:var(--surface-2);padding:8px 12px;border-radius:6px">
              <input type="text" name="custom_fields[<?= $idx ?>][name]" class="form-control" placeholder="Field Name" value="<?= e($cf['name'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
              <select name="custom_fields[<?= $idx ?>][type]" class="form-control" <?= $isReadonly ? 'disabled' : '' ?>>
                <option value="text" <?= ($cf['type'] ?? '') === 'text' ? 'selected' : '' ?>>Text</option>
                <option value="number" <?= ($cf['type'] ?? '') === 'number' ? 'selected' : '' ?>>Number</option>
                <option value="date" <?= ($cf['type'] ?? '') === 'date' ? 'selected' : '' ?>>Date</option>
              </select>
              <input type="text" name="custom_fields[<?= $idx ?>][value]" class="form-control" placeholder="Field Value" value="<?= e($cf['value'] ?? '') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
              <?php if (!$isReadonly): ?>
              <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.custom-field-row').remove()">&times;</button>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div id="emptyCustomFieldsMsg" style="font-size:0.85rem;color:var(--text-muted);font-style:italic">No custom fields added yet. Click [ + Add Field ] to add dynamic fields.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. CONTACTS -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--surface-2);padding:14px 20px">
      <div class="card-title" style="font-weight:700;font-size:1.05rem">CONTACTS</div>
    </div>
    <div class="card-body" style="padding:20px">
      <!-- SIGNATORY 1 -->
      <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
        <div style="font-weight:700;font-size:0.9rem;text-transform:uppercase;color:var(--accent);margin-bottom:10px">SIGNATORY 1</div>
        <div class="grid-3" style="gap:16px">
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Name <span class="text-danger">*</span></label>
            <input type="text" name="auth_signatory_name" id="auth_signatory_name" class="form-control" value="<?= e($app['auth_signatory_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Email <span class="text-danger">*</span></label>
            <input type="email" name="auth_signatory_email" id="auth_signatory_email" class="form-control" value="<?= e($app['auth_signatory_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Phone <span class="text-danger">*</span></label>
            <input type="text" name="auth_signatory_mobile" id="auth_signatory_mobile" class="form-control" value="<?= e($app['auth_signatory_mobile'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>

      <!-- SIGNATORY 2 -->
      <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
        <div style="font-weight:700;font-size:0.9rem;text-transform:uppercase;color:var(--accent);margin-bottom:10px">SIGNATORY 2</div>
        <div class="grid-3" style="gap:16px">
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Name <span class="text-danger">*</span></label>
            <input type="text" name="signatory2_name" id="signatory2_name" class="form-control" value="<?= e($app['signatory2_name'] ?? $app['ops_contact_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Email <span class="text-danger">*</span></label>
            <input type="email" name="signatory2_email" id="signatory2_email" class="form-control" value="<?= e($app['signatory2_email'] ?? $app['ops_contact_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Phone <span class="text-danger">*</span></label>
            <input type="text" name="signatory2_phone" id="signatory2_phone" class="form-control" value="<?= e($app['signatory2_phone'] ?? $app['ops_contact_phone'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>

      <!-- TECHNICAL CONTACT -->
      <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
        <div style="font-weight:700;font-size:0.9rem;text-transform:uppercase;color:var(--accent);margin-bottom:10px">TECHNICAL CONTACT</div>
        <div class="grid-3" style="gap:16px">
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Name <span class="text-danger">*</span></label>
            <input type="text" name="tech_supervisor_name" id="tech_supervisor_name" class="form-control" value="<?= e($app['tech_supervisor_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Email <span class="text-danger">*</span></label>
            <input type="email" name="tech_supervisor_email" id="tech_supervisor_email" class="form-control" value="<?= e($app['tech_supervisor_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Phone <span class="text-danger">*</span></label>
            <input type="text" name="tech_supervisor_phone" id="tech_supervisor_phone" class="form-control" value="<?= e($app['tech_supervisor_phone'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>

      <!-- BILLING CONTACT -->
      <div>
        <div style="font-weight:700;font-size:0.9rem;text-transform:uppercase;color:var(--accent);margin-bottom:10px">BILLING CONTACT</div>
        <div class="grid-3" style="gap:16px">
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Name <span class="text-danger">*</span></label>
            <input type="text" name="billing_contact_name" id="billing_contact_name" class="form-control" value="<?= e($app['billing_contact_name'] ?? $app['escalation_contact_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Email <span class="text-danger">*</span></label>
            <input type="email" name="billing_contact_email" id="billing_contact_email" class="form-control" value="<?= e($app['billing_contact_email'] ?? $app['escalation_contact_email'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
          <div>
            <label style="font-weight:600;margin-bottom:4px;display:block">Phone <span class="text-danger">*</span></label>
            <input type="text" name="billing_contact_phone" id="billing_contact_phone" class="form-control" value="<?= e($app['billing_contact_phone'] ?? $app['escalation_contact_phone'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. OTHER DOCUMENTS & COMPLIANCE (ALL 7 ITEMS ARE FIRST-CLASS UPLOADS) -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--surface-2);padding:14px 20px">
      <div class="card-title" style="font-weight:700;font-size:1.05rem">OTHER DOCUMENTS &amp; COMPLIANCE (7 MANDATORY UPLOADS)</div>
    </div>
    <div class="card-body" style="padding:20px">

      <!-- Item 1: Contractor Agreement -->
      <?= renderComplianceDocUploadRow(
        'contractor_agreement', 
        '1. Contractor Agreement', 
        $app['contractor_agreement'] ?? '', 
        $appId, 
        $isReadonly
      ) ?>

      <!-- Item 2: SLA Acceptance / Signed SLA -->
      <?php
      $slaExtra = '<label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer"><input type="checkbox" name="sla_accepted" value="1" ' . (!empty($app['sla_accepted']) ? 'checked' : '') . ' ' . ($isReadonly ? 'disabled' : '') . '> <span><strong>Confirm SLA Policy Acceptance</strong> — Agrees to Neilos Open-Access Infrastructure Service Level Agreement</span></label>';
      echo renderComplianceDocUploadRow('signed_sla_file', '2. SLA Acceptance / Signed SLA', $app['signed_sla_file'] ?? '', $appId, $isReadonly, $slaExtra);
      ?>

      <!-- Item 3: Safety / ESG Policy Acceptance -->
      <?php
      $esgExtra = '<label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer"><input type="checkbox" name="safety_esg_accepted" value="1" ' . (!empty($app['safety_esg_accepted']) ? 'checked' : '') . ' ' . ($isReadonly ? 'disabled' : '') . '> <span><strong>Confirm Safety / ESG Policy Acceptance</strong> — Agrees to Environmental, Social & Safety Governance policies</span></label>';
      echo renderComplianceDocUploadRow('safety_esg_file', '3. Safety / ESG Policy Acceptance', $app['safety_esg_file'] ?? '', $appId, $isReadonly, $esgExtra);
      ?>

      <!-- Item 4: Confidentiality Clause / NDA Acceptance -->
      <?php
      $ndaExtra = '<label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer"><input type="checkbox" name="confidentiality_accepted" value="1" ' . (!empty($app['confidentiality_accepted']) ? 'checked' : '') . ' ' . ($isReadonly ? 'disabled' : '') . '> <span><strong>Confirm Confidentiality Clause Acceptance</strong> — Agrees to Non-Disclosure & Commercial Confidentiality clauses</span></label>';
      echo renderComplianceDocUploadRow('confidentiality_nda_file', '4. Confidentiality Clause / NDA Acceptance', $app['confidentiality_nda_file'] ?? '', $appId, $isReadonly, $ndaExtra);
      ?>

      <!-- Item 5: Service Regions Supporting Document & Selection -->
      <?php
      ob_start();
      ?>
      <div style="margin-top:6px">
        <label style="font-weight:700;font-size:0.88rem;margin-bottom:6px;display:block">Select Authorized Service Regions <span class="text-danger">*</span></label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:10px;background:var(--surface-2);padding:10px;border-radius:6px">
          <?php foreach ($availableRegions as $reg): ?>
          <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer">
            <input type="checkbox" name="service_regions[]" value="<?= e($reg) ?>" <?= in_array($reg, $selectedRegions) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?>>
            <span><?= e($reg) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php
      $regExtra = ob_get_clean();
      echo renderComplianceDocUploadRow('service_regions_file', '5. Service Regions Supporting Document', $app['service_regions_file'] ?? '', $appId, $isReadonly, $regExtra);
      ?>

      <!-- Item 6: HSE Certificate -->
      <?= renderComplianceDocUploadRow(
        'hse_certificate', 
        '6. HSE Certificate', 
        $app['hse_certificate'] ?? '', 
        $appId, 
        $isReadonly
      ) ?>

      <!-- Item 7: TRCA Installation License -->
      <?= renderComplianceDocUploadRow(
        'trca_certificate', 
        '7. TRCA Installation License', 
        $app['trca_certificate'] ?? '', 
        $appId, 
        $isReadonly
      ) ?>

    </div>
  </div>

  <!-- 5. BANK DETAILS -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--surface-2);padding:14px 20px">
      <div class="card-title" style="font-weight:700;font-size:1.05rem">BANK DETAILS</div>
    </div>
    <div class="card-body" style="padding:20px">
      <div class="grid-3" style="gap:16px;margin-bottom:16px">
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Bank Name <span class="text-danger">*</span></label>
          <input type="text" name="bank_name" id="bank_name" class="form-control" value="<?= e($app['bank_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Branch <span class="text-danger">*</span></label>
          <input type="text" name="bank_branch" id="bank_branch" class="form-control" value="<?= e($app['bank_branch'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Account Name <span class="text-danger">*</span></label>
          <input type="text" name="bank_account_name" id="bank_account_name" class="form-control" value="<?= e($app['bank_account_name'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
      </div>

      <div class="grid-2" style="gap:16px">
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Account Number <span class="text-danger">*</span></label>
          <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" value="<?= e($app['bank_account_number'] ?? '') ?>" required <?= $isReadonly ? 'readonly' : '' ?>>
        </div>
        <div>
          <label style="font-weight:600;margin-bottom:4px;display:block">Payment Terms <span class="text-danger">*</span></label>
          <select name="bank_payment_terms" id="bank_payment_terms" class="form-control" required <?= $isReadonly ? 'disabled' : '' ?>>
            <?php foreach ($paymentTermsOptions as $pt): ?>
            <option value="<?= e($pt) ?>" <?= ($app['bank_payment_terms'] ?? '30 Days') === $pt ? 'selected' : '' ?>><?= e($pt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- 6. CAPABILITIES -->
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--surface-2);padding:14px 20px">
      <div class="card-title" style="font-weight:700;font-size:1.05rem">CAPABILITIES</div>
    </div>
    <div class="card-body" style="padding:20px">
      <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:14px">
        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_ftth_install" value="1" <?= !empty($app['cap_ftth_install']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px">
          <span>FTTH Install</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_sme_install" value="1" <?= !empty($app['cap_sme_install']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px">
          <span>SME Install</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_enterprise_install" value="1" <?= !empty($app['cap_enterprise_install']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px">
          <span>Enterprise Install</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_site_survey" value="1" <?= !empty($app['cap_site_survey']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px">
          <span>Site Survey</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_maintenance" value="1" <?= !empty($app['cap_maintenance']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px">
          <span>Maintenance Support Visit</span>
        </label>

        <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;cursor:pointer">
          <input type="checkbox" name="cap_remote_support" value="1" <?= !empty($app['cap_remote_support']) ? 'checked' : '' ?> <?= $isReadonly ? 'disabled' : '' ?> style="width:18px;height:18px">
          <span>Remote Laptop Support</span>
        </label>
      </div>
    </div>
  </div>

  <!-- 7. AUDIT TRAIL / HISTORY -->
  <?php if (!empty($history)): ?>
  <div class="card" style="margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div class="card-header" style="background:var(--surface-2);padding:14px 20px">
      <div class="card-title" style="font-weight:700;font-size:1.05rem">KYC HISTORY &amp; AUDIT TRAIL</div>
    </div>
    <div class="card-body" style="padding:0">
      <div class="table-responsive">
        <table class="data-table" style="font-size:0.85rem">
          <thead>
            <tr>
              <th>Date / Time</th>
              <th>Action / Event</th>
              <th>Actor</th>
              <th>Role</th>
              <th>Status Transition</th>
              <th>Details / Reason</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
              <td class="font-mono text-muted"><?= fmtDateTime($h['created_at']) ?></td>
              <td class="font-600"><?= e($h['action_title']) ?></td>
              <td><?= e($h['actor_name'] ?: 'System') ?></td>
              <td><span class="badge badge-secondary"><?= e($h['action_role']) ?></span></td>
              <td>
                <span class="text-muted"><?= e($h['from_status'] ?: 'Initial') ?></span> &rarr; 
                <span class="badge <?= $h['to_status'] === 'Approved' ? 'badge-success' : ($h['to_status'] === 'Rejected' ? 'badge-danger' : 'badge-warning') ?>">
                  <?= e($h['to_status']) ?>
                </span>
              </td>
              <td style="max-width:280px;white-space:normal"><?= e($h['details'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- BOTTOM ACTION FOOTER -->
  <div class="card" style="margin-bottom:40px;padding:16px 20px;background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <span style="font-weight:700;color:var(--text-primary)">Current Status:</span>
      <span class="badge <?= $status === 'Approved' ? 'badge-success' : ($status === 'Rejected' ? 'badge-danger' : ($status === 'Pending Approval' || $status === 'Submitted' ? 'badge-warning' : 'badge-secondary')) ?>" style="margin-left:6px;padding:6px 12px">
        <?= e($status) ?>
      </span>
    </div>

    <div style="display:flex;align-items:center;gap:12px">
      <?php if ($isAdminOrMgmt && $status !== 'Approved' && !in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
        <button type="submit" formaction="<?= APP_URL ?>/?page=kyc&action=save_draft" class="btn btn-secondary">
          <?= svgIcon('edit') ?> Save Draft
        </button>

        <?php if ($status === 'Rejected'): ?>
        <button type="submit" formaction="<?= APP_URL ?>/?page=kyc&action=resubmit" class="btn btn-success">
          <?= svgIcon('check') ?> Resubmit for Approval
        </button>
        <?php else: ?>
        <button type="submit" formaction="<?= APP_URL ?>/?page=kyc&action=submit" class="btn btn-primary">
          <?= svgIcon('check') ?> Submit for Approval
        </button>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($isReviewer && in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
        <button type="button" class="btn btn-success" onclick="confirmApproveKyc()">
          <?= svgIcon('check') ?> Approve KYC
        </button>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- Delete Document Form -->
<form id="deleteDocForm" method="POST" action="<?= APP_URL ?>/?page=kyc&action=delete_doc" style="display:none">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <input type="hidden" name="kyc_id" value="<?= $appId ?>">
  <input type="hidden" name="doc_key" id="deleteDocKeyInput">
</form>

<!-- Custom Rejection Modal (Rendered ONLY when reviewing pending applications) -->
<?php if ($isReviewer && in_array($status, ['Submitted for Approval', 'Pending Approval', 'Submitted'])): ?>
<div class="modal" id="kycRejectModal">
  <div class="modal-dialog" style="max-width:520px">
    <form method="POST" action="<?= APP_URL ?>/?page=kyc&action=reject">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="kyc_id" value="<?= $appId ?>">
      
      <div class="modal-header" style="background:#FEF2F2;border-bottom:1px solid #FCA5A5;padding:14px 20px">
        <div class="modal-title" style="color:#991B1B;font-weight:700;font-size:1.1rem">Reject KYC Application</div>
        <button type="button" class="modal-close" onclick="closeRejectKycModal()">&times;</button>
      </div>
      
      <div class="modal-body" style="padding:20px">
        <div style="margin-bottom:16px;padding:12px 14px;background:var(--surface-2);border-radius:6px;font-size:0.9rem;display:flex;flex-direction:column;gap:6px">
          <div><strong style="color:var(--text-muted)">Application:</strong> <span style="font-weight:700;color:var(--text-primary)"><?= e($app['registered_name'] ?: ($app['partner_name'] ?? 'N/A')) ?></span></div>
          <div><strong style="color:var(--text-muted)">KYC Type:</strong> <span class="badge badge-info"><?= e($kycType) ?></span></div>
        </div>

        <label style="font-weight:700;color:var(--text-primary);margin-bottom:6px;display:block">
          Reason for Rejection <span class="text-danger">*</span>
        </label>
        <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Enter the reason why this KYC application is being rejected..." required></textarea>
      </div>

      <div class="modal-footer" style="padding:14px 20px;background:var(--surface-2);display:flex;justify-content:flex-end;gap:10px">
        <button type="button" class="btn btn-secondary" onclick="closeRejectKycModal()">Cancel</button>
        <button type="submit" class="btn btn-danger">Reject KYC Application</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function deleteKycDoc(docKey) {
  neilosConfirm('Are you sure you want to delete this document?', 'Delete KYC Document', 'Delete', 'btn-danger').then(confirmed => {
    if (confirmed) {
      document.getElementById('deleteDocKeyInput').value = docKey;
      document.getElementById('deleteDocForm').submit();
    }
  });
}

function onKycTypeChange() {
  const typeSelect = document.getElementById('kycTypeSelect');
  const type = typeSelect.value;
  const label = document.getElementById('entitySelectLabel');
  const partnerGroup = document.getElementById('partnerGroup');
  const contractorGroup = document.getElementById('contractorGroup');

  if (label) label.textContent = (type === 'Contractor' ? 'Contractor *' : 'Partner *');
  if (partnerGroup) partnerGroup.style.display = (type === 'Contractor' ? 'none' : '');
  if (contractorGroup) contractorGroup.style.display = (type === 'Partner' ? 'none' : '');
}

function onEntitySelectChange() {
  const select = document.getElementById('entityPartnerSelect');
  const partnerId = select.value;
  if (!partnerId) return;

  const opt = select.options[select.selectedIndex];
  const kycId = opt ? opt.getAttribute('data-kyc-id') : null;
  const isNewMode = <?= json_encode($action === 'new') ?>;

  if (isNewMode && kycId && parseInt(kycId) > 0) {
    const status = opt.getAttribute('data-kyc-status');
    const appUrl = (typeof APP_URL !== 'undefined' ? APP_URL : '');
    neilosConfirm(
      'A KYC application already exists for this organization (Status: ' + status + '). Would you like to view/edit the existing application?',
      'KYC Application Exists',
      'Open Application',
      'btn-primary'
    ).then(confirmed => {
      if (confirmed) {
        window.location.href = appUrl + '/?page=kyc&action=edit&id=' + kycId;
      }
    });
    return;
  }

  fetch((typeof APP_URL !== 'undefined' ? APP_URL : '') + '/?page=kyc&action=api_entity_data&partner_id=' + partnerId)
    .then(r => r.json())
    .then(res => {
      if (res && res.success && res.data) {
        const d = res.data;
        const setVal = (id, val) => {
          const el = document.getElementById(id);
          if (el && val !== null && val !== undefined) el.value = val;
        };

        setVal('registered_name', d.registered_name || d.name);
        setVal('registration_number', d.registration_number);
        setVal('tin', d.tin);
        setVal('vat_vrn', d.vat_vrn);
        setVal('office_address', d.office_address || d.address);
        setVal('city_region', d.city_region || 'Dar es Salaam');
        setVal('country', d.country || 'Tanzania');

        setVal('auth_signatory_name', d.auth_signatory_name || d.main_contact_name);
        setVal('auth_signatory_email', d.auth_signatory_email || d.main_contact_email);
        setVal('auth_signatory_mobile', d.auth_signatory_mobile || d.main_contact_phone);

        setVal('signatory2_name', d.signatory2_name || d.ops_contact_name);
        setVal('signatory2_email', d.signatory2_email || d.ops_contact_email);
        setVal('signatory2_phone', d.signatory2_phone || d.ops_contact_phone);

        setVal('tech_supervisor_name', d.tech_supervisor_name);
        setVal('tech_supervisor_email', d.tech_supervisor_email);
        setVal('tech_supervisor_phone', d.tech_supervisor_phone);

        setVal('billing_contact_name', d.billing_contact_name || d.escalation_contact_name);
        setVal('billing_contact_email', d.billing_contact_email || d.escalation_contact_email);
        setVal('billing_contact_phone', d.billing_contact_phone || d.escalation_contact_phone);

        setVal('bank_name', d.bank_name);
        setVal('bank_branch', d.bank_branch);
        setVal('bank_account_name', d.bank_account_name);
        setVal('bank_account_number', d.bank_account_number);
        setVal('bank_payment_terms', d.bank_payment_terms || '30 Days');
      }
    })
    .catch(() => {});
}

function addCustomFieldRow() {
  const container = document.getElementById('customFieldsContainer');
  const emptyMsg = document.getElementById('emptyCustomFieldsMsg');
  if (emptyMsg) emptyMsg.style.display = 'none';

  const idx = container.querySelectorAll('.custom-field-row').length;
  const row = document.createElement('div');
  row.className = 'custom-field-row grid-4';
  row.style.cssText = 'gap:10px;align-items:center;background:var(--surface-2);padding:8px 12px;border-radius:6px;margin-bottom:8px';
  row.innerHTML = `
    <input type="text" name="custom_fields[${idx}][name]" class="form-control" placeholder="Field Name (e.g. Employee Count)" required>
    <select name="custom_fields[${idx}][type]" class="form-control">
      <option value="text">Text</option>
      <option value="number">Number</option>
      <option value="date">Date</option>
    </select>
    <input type="text" name="custom_fields[${idx}][value]" class="form-control" placeholder="Field Value">
    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.custom-field-row').remove()">&times;</button>
  `;
  container.appendChild(row);
}

function confirmApproveKyc() {
  neilosConfirm('Are you sure you want to approve this KYC application?', 'Approve KYC Application', 'Approve KYC', 'btn-success').then(confirmed => {
    if (confirmed) {
      const f = document.createElement('form');
      f.method = 'POST';
      f.action = '<?= APP_URL ?>/?page=kyc&action=approve';
      const token = document.createElement('input');
      token.type = 'hidden'; token.name = 'csrf_token'; token.value = '<?= csrfToken() ?>';
      const id = document.createElement('input');
      id.type = 'hidden'; id.name = 'kyc_id'; id.value = '<?= $appId ?>';
      f.appendChild(token); f.appendChild(id);
      document.body.appendChild(f);
      f.submit();
    }
  });
}

function openRejectKycModal() {
  const modal = document.getElementById('kycRejectModal');
  if (modal) modal.classList.add('open');
}

function closeRejectKycModal() {
  const modal = document.getElementById('kycRejectModal');
  if (modal) modal.classList.remove('open');
}
</script>
