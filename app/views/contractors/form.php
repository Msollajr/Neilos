<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Edit Contractor</h1>
    <div class="page-subtitle">Update contractor details and status</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $contractor['id'] ?>" class="btn btn-secondary"><?= svgIcon('eye') ?> View Contractor</a>
    <a href="<?= APP_URL ?>/?page=contractors" class="btn btn-secondary"><?= svgIcon('list') ?> All Contractors</a>
  </div>
</div>

<div class="card card-max-800">
  <div class="card-header"><div class="card-title">Contractor Details</div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=contractors&action=update">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= $contractor['id'] ?>">

      <div class="form-group"><label class="form-section-label">General Information</label></div>
      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label for="name">Company Name <span class="text-danger">*</span></label>
          <input type="text" id="name" name="name" class="form-control" required value="<?= e($contractor['name']) ?>">
        </div>
        <div class="form-group">
          <label for="trading_name">Trading Name</label>
          <input type="text" id="trading_name" name="trading_name" class="form-control" value="<?= e($contractor['trading_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="partner_type">Type</label>
          <select id="partner_type" name="partner_type" class="form-control">
            <?php foreach (['Other', 'ISP', 'Reseller', 'VAR', 'Enterprise', 'Government'] as $pt): ?>
            <option value="<?= $pt ?>" <?= ($contractor['partner_type'] ?? '') === $pt ? 'selected' : '' ?>><?= $pt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="status">Status <span class="text-danger">*</span></label>
          <select id="status" name="status" class="form-control" required>
            <?php foreach (['Active', 'Inactive', 'Suspended'] as $st): ?>
            <option value="<?= $st ?>" <?= ($contractor['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="customer_category">Customer Category</label>
          <input type="text" id="customer_category" name="customer_category" class="form-control" value="<?= e($contractor['customer_category'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="industry_sector">Industry Sector</label>
          <input type="text" id="industry_sector" name="industry_sector" class="form-control" value="<?= e($contractor['industry_sector'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="registration_number">Registration Number</label>
          <input type="text" id="registration_number" name="registration_number" class="form-control" value="<?= e($contractor['registration_number'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="tin">TIN</label>
          <input type="text" id="tin" name="tin" class="form-control" value="<?= e($contractor['tin'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="vat_vrn">VAT / VRN</label>
          <input type="text" id="vat_vrn" name="vat_vrn" class="form-control" value="<?= e($contractor['vat_vrn'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="city_region">City / Region</label>
          <input type="text" id="city_region" name="city_region" class="form-control" value="<?= e($contractor['city_region'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="country">Country</label>
          <input type="text" id="country" name="country" class="form-control" value="<?= e($contractor['country'] ?? 'Tanzania') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="address">Address</label>
        <textarea id="address" name="address" class="form-control" rows="2" placeholder="Street address..."><?= e($contractor['address'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label for="nature_of_business">Nature of Business</label>
        <textarea id="nature_of_business" name="nature_of_business" class="form-control" rows="2" placeholder="Describe the contractor's line of business..."><?= e($contractor['nature_of_business'] ?? '') ?></textarea>
      </div>

      <div class="divider"></div>
      <div class="form-group"><label class="form-section-label">Contacts</label></div>
      <div class="form-grid form-grid-3">
        <div class="form-group"><label>Main Contact Name</label><input type="text" name="main_contact_name" class="form-control" value="<?= e($contractor['main_contact_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Main Contact Phone</label><input type="text" name="main_contact_phone" class="form-control" value="<?= e($contractor['main_contact_phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Main Contact Email</label><input type="email" name="main_contact_email" class="form-control" value="<?= e($contractor['main_contact_email'] ?? '') ?>"></div>
        <div class="form-group"><label>Ops Contact Name</label><input type="text" name="ops_contact_name" class="form-control" value="<?= e($contractor['ops_contact_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Ops Contact Phone</label><input type="text" name="ops_contact_phone" class="form-control" value="<?= e($contractor['ops_contact_phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Ops Contact Email</label><input type="email" name="ops_contact_email" class="form-control" value="<?= e($contractor['ops_contact_email'] ?? '') ?>"></div>
        <div class="form-group"><label>Tech Supervisor</label><input type="text" name="tech_supervisor_name" class="form-control" value="<?= e($contractor['tech_supervisor_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Tech Supervisor Phone</label><input type="text" name="tech_supervisor_phone" class="form-control" value="<?= e($contractor['tech_supervisor_phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Tech Supervisor Email</label><input type="email" name="tech_supervisor_email" class="form-control" value="<?= e($contractor['tech_supervisor_email'] ?? '') ?>"></div>
        <div class="form-group"><label>Escalation Contact</label><input type="text" name="escalation_contact_name" class="form-control" value="<?= e($contractor['escalation_contact_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Escalation Phone</label><input type="text" name="escalation_contact_phone" class="form-control" value="<?= e($contractor['escalation_contact_phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Escalation Email</label><input type="email" name="escalation_contact_email" class="form-control" value="<?= e($contractor['escalation_contact_email'] ?? '') ?>"></div>
      </div>

      <div class="divider"></div>
      <div class="form-group"><label class="form-section-label">Banking Details</label></div>
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" class="form-control" value="<?= e($contractor['bank_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Branch</label><input type="text" name="bank_branch" class="form-control" value="<?= e($contractor['bank_branch'] ?? '') ?>"></div>
        <div class="form-group"><label>Account Name</label><input type="text" name="bank_account_name" class="form-control" value="<?= e($contractor['bank_account_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Account Number</label><input type="text" name="bank_account_number" class="form-control" value="<?= e($contractor['bank_account_number'] ?? '') ?>"></div>
        <div class="form-group"><label>Payment Terms</label><input type="text" name="bank_payment_terms" class="form-control" value="<?= e($contractor['bank_payment_terms'] ?? '') ?>" placeholder="e.g. Net 30"></div>
      </div>

      <div class="divider"></div>
      <div class="form-group"><label class="form-section-label">Capabilities & Coverage</label></div>
      <div class="form-group">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
          <?php
          $capList = [
              'cap_ftth_install'       => 'FTTH Installation',
              'cap_sme_install'        => 'SME Installation',
              'cap_enterprise_install' => 'Enterprise Installation',
              'cap_site_survey'        => 'Site Survey',
              'cap_maintenance'        => 'Maintenance & Support',
              'cap_remote_support'     => 'Remote Hands Support',
          ];
          foreach ($capList as $ck => $cl): ?>
          <label class="form-check" style="display:flex;align-items:center;gap:8px">
            <input type="checkbox" name="<?= $ck ?>" value="1" <?= !empty($contractor[$ck]) ? 'checked' : '' ?>>
            <span><?= $cl ?></span>
          </label>
          <?php endforeach; ?>
      </div>
      <div class="form-group">
        <label for="service_regions">Service Regions</label>
        <textarea id="service_regions" name="service_regions" class="form-control" rows="3" placeholder="One region per line, e.g. Dar es Salaam&#10;Morogoro"><?= e($contractor['service_regions'] ?? '') ?></textarea>
      </div>

      <div class="divider"></div>
      <div class="form-group"><label class="form-section-label">KYC Status</label></div>
      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label for="kyc_status">KYC Status</label>
          <select id="kyc_status" name="kyc_status" class="form-control">
            <?php foreach (['Draft', 'Submitted', 'Under Review', 'Approved', 'Rejected'] as $ks): ?>
            <option value="<?= $ks ?>" <?= ($contractor['kyc_status'] ?? '') === $ks ? 'selected' : '' ?>><?= $ks ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="review_notes">KYC Review Notes</label>
          <input type="text" id="review_notes" name="review_notes" class="form-control" value="<?= e($contractor['review_notes'] ?? '') ?>">
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary"><?= svgIcon('save') ?> Save Changes</button>
        <a href="<?= APP_URL ?>/?page=contractors&action=detail&id=<?= $contractor['id'] ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
