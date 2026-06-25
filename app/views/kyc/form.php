<?php
$readonly = $isSubmitted ? 'readonly' : '';
$disabled = $isSubmitted ? 'disabled' : '';
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">KYC Application</h1>
    <div class="page-subtitle">
      Status: <span class="badge <?= $app['status'] === 'Approved' ? 'badge-success' : ($app['status'] === 'Rejected' ? 'badge-danger' : ($app['status'] === 'Submitted' || $app['status'] === 'Under Review' ? 'badge-warning' : 'badge-secondary')) ?>"><?= e($app['status']) ?></span>
      <?php if ($app['status'] === 'Rejected' && $app['review_notes']): ?>
      &middot; Review notes: <?= e($app['review_notes']) ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="page-header-actions">
    <?php if (!isPartnerUser()): ?>
    <a href="<?= APP_URL ?>/?page=kyc&action=admin_list" class="btn btn-secondary"><?= svgIcon('list') ?> All Applications</a>
    <?php endif; ?>
  </div>
</div>

<form method="POST" action="<?= APP_URL ?>/?page=kyc&action=save" enctype="multipart/form-data" id="kycForm">
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
<input type="hidden" name="partner_id" value="<?= $app['partner_id_val'] ?>">

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Section 1: Partner Details</div></div>
  <div class="card-body">
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label>Registered Name <span class="required">*</span></label>
        <input type="text" name="registered_name" class="form-control" value="<?= e($app['registered_name'] ?? $partner['name']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Trading Name <span class="required">*</span></label>
        <input type="text" name="trading_name" class="form-control" value="<?= e($app['trading_name'] ?? $partner['trading_name']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Partner Type <span class="required">*</span></label>
        <select name="partner_type" class="form-control" required <?= $disabled ?>>
          <option value="">— Select —</option>
          <?php foreach (['ISP','Reseller','VAR','Enterprise','Government','Other'] as $pt): ?>
          <option value="<?= $pt ?>" <?= ($app['partner_type'] ?? $partner['partner_type']) === $pt ? 'selected' : '' ?>><?= $pt ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Customer Category</label>
        <input type="text" name="customer_category" class="form-control" value="<?= e($app['customer_category'] ?? $partner['customer_category']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Industry Sector</label>
        <input type="text" name="industry_sector" class="form-control" value="<?= e($app['industry_sector'] ?? $partner['industry_sector']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Nature of Business</label>
        <textarea name="nature_of_business" class="form-control" rows="2" <?= $readonly ?>><?= e($app['nature_of_business'] ?? $partner['nature_of_business']) ?></textarea>
      </div>
      <div class="form-group">
        <label>Registration Number <span class="required">*</span></label>
        <input type="text" name="registration_number" class="form-control" value="<?= e($app['registration_number'] ?? $partner['registration_number']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>TIN <span class="required">*</span></label>
        <input type="text" name="tin" class="form-control" value="<?= e($app['tin'] ?? $partner['tin']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>VAT / VRN</label>
        <input type="text" name="vat_vrn" class="form-control" value="<?= e($app['vat_vrn'] ?? $partner['vat_vrn']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Address <span class="required">*</span></label>
        <textarea name="address" class="form-control" rows="2" required <?= $readonly ?>><?= e($app['address'] ?? $partner['address']) ?></textarea>
      </div>
      <div class="form-group">
        <label>City / Region <span class="required">*</span></label>
        <input type="text" name="city_region" class="form-control" value="<?= e($app['city_region'] ?? $partner['city_region']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Country <span class="required">*</span></label>
        <input type="text" name="country" class="form-control" value="<?= e($app['country'] ?? $partner['country'] ?? 'Tanzania') ?>" required <?= $readonly ?>>
      </div>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Section 2: Authorized Signatory</div></div>
  <div class="card-body">
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="auth_signatory_name" class="form-control" value="<?= e($app['auth_signatory_name']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Title / Position</label>
        <input type="text" name="auth_signatory_title" class="form-control" value="<?= e($app['auth_signatory_title']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Department</label>
        <input type="text" name="auth_signatory_dept" class="form-control" value="<?= e($app['auth_signatory_dept']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>ID Type</label>
        <input type="text" name="auth_signatory_id_type" class="form-control" value="<?= e($app['auth_signatory_id_type']) ?>" placeholder="e.g. National ID, Passport" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>ID Number</label>
        <input type="text" name="auth_signatory_id_number" class="form-control" value="<?= e($app['auth_signatory_id_number']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Mobile <span class="required">*</span></label>
        <input type="tel" name="auth_signatory_mobile" class="form-control" value="<?= e($app['auth_signatory_mobile']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="auth_signatory_email" class="form-control" value="<?= e($app['auth_signatory_email']) ?>" required <?= $readonly ?>>
      </div>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Section 3: Finance &amp; Billing Contact</div></div>
  <div class="card-body">
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="finance_contact_name" class="form-control" value="<?= e($app['finance_contact_name']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Title / Position</label>
        <input type="text" name="finance_contact_title" class="form-control" value="<?= e($app['finance_contact_title']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Mobile</label>
        <input type="tel" name="finance_contact_mobile" class="form-control" value="<?= e($app['finance_contact_mobile']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="finance_contact_email" class="form-control" value="<?= e($app['finance_contact_email']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Billing Email <span class="required">*</span></label>
        <input type="email" name="billing_email" class="form-control" value="<?= e($app['billing_email']) ?>" required <?= $readonly ?>>
      </div>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Section 4: Technical Contact</div></div>
  <div class="card-body">
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="tech_contact_name" class="form-control" value="<?= e($app['tech_contact_name']) ?>" required <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Title / Position</label>
        <input type="text" name="tech_contact_title" class="form-control" value="<?= e($app['tech_contact_title']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Mobile</label>
        <input type="tel" name="tech_contact_mobile" class="form-control" value="<?= e($app['tech_contact_mobile']) ?>" <?= $readonly ?>>
      </div>
      <div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="tech_contact_email" class="form-control" value="<?= e($app['tech_contact_email']) ?>" required <?= $readonly ?>>
      </div>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Section 5: Document Checklist</div></div>
  <div class="card-body">
    <div class="doc-checklist">
      <?php foreach ($docs as $doc): ?>
      <div class="doc-row">
        <div class="doc-row-info">
          <div class="doc-row-name">
            <?= e($doc['document_type']) ?>
            <?php if ($doc['is_mandatory']): ?><span class="required">*</span><?php endif; ?>
          </div>
          <div class="doc-row-status">
            <span class="badge <?= $doc['status'] === 'Uploaded' || $doc['status'] === 'Verified' ? 'badge-success' : ($doc['status'] === 'Rejected' ? 'badge-danger' : 'badge-secondary') ?>">
              <?= e($doc['status']) ?>
            </span>
            <?php if ($doc['file_name']): ?>
            <span class="font-sm" style="margin-left:8px"><?= e($doc['file_name']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="doc-row-actions">
          <?php if ($doc['file_path']): ?>
          <a href="<?= APP_URL ?>/<?= e($doc['file_path']) ?>" class="btn btn-sm btn-secondary" target="_blank"><?= svgIcon('download') ?> View</a>
          <?php endif; ?>
          <?php if (!$isSubmitted): ?>
          <div class="file-input-inline">
            <input type="file" name="documents[<?= $doc['id'] ?>]" id="doc_<?= $doc['id'] ?>" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none" onchange="this.parentElement.querySelector('.file-name').textContent = this.files[0]?.name || ''">
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('doc_<?= $doc['id'] ?>').click()"><?= svgIcon('upload') ?> Upload</button>
            <span class="file-name font-sm" style="margin-left:6px"></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if (!isPartnerUser() && $app['countersigned_kyc_file']): ?>
<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Countersigned KYC</div></div>
  <div class="card-body">
    <p><a href="<?= APP_URL ?>/<?= e($app['countersigned_kyc_file']) ?>" target="_blank" class="btn btn-secondary btn-sm"><?= svgIcon('download') ?> <?= e($app['countersigned_kyc_filename'] ?: 'Download') ?></a></p>
    <?php if ($app['countersigned_kyc_date']): ?>
    <p class="font-sm text-muted" style="margin-top:6px">Uploaded: <?= fmtDateTime($app['countersigned_kyc_date']) ?></p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!$isSubmitted): ?>
<div style="display:flex;gap:12px;justify-content:flex-end">
  <button type="submit" class="btn btn-primary">
    <?= svgIcon('check') ?> Save Changes
  </button>
  <button type="button" class="btn btn-success" onclick="if(confirm('Are you sure you want to submit this KYC application? All mandatory fields must be complete.')){var f=document.createElement('form');f.method='POST';f.action='<?= APP_URL ?>/?page=kyc&action=submit';var i=document.createElement('input');i.type='hidden';i.name='csrf_token';i.value='<?= csrfToken() ?>';f.appendChild(i);document.body.appendChild(f);f.submit();}">
    <?= svgIcon('check') ?> Submit KYC
  </button>
</div>
<?php endif; ?>

</form>
