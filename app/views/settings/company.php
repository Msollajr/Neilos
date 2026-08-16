<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Neilos Company Settings</div>
    <div class="page-subtitle">Configure company information, contacts, and KYC details used on Service Order Forms (SOF)</div>
  </div>
</div>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

  <div class="grid-2col">
    <!-- General Information -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= svgIcon('building') ?> Company Identity</div>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Legal Company Name <span class="text-danger">*</span></label>
          <input type="text" name="company_name" class="form-control" required value="<?= e($company['company_name'] ?? 'Neilos Network Ltd') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Trading / Brand Name</label>
          <input type="text" name="trading_name" class="form-control" value="<?= e($company['trading_name'] ?? 'Neilos') ?>" placeholder="e.g. Neilos Network">
        </div>

        <div class="grid-2col" style="gap:12px">
          <div class="form-group">
            <label class="form-label">TIN (Tax Identification No.)</label>
            <input type="text" name="tin" class="form-control" value="<?= e($company['tin'] ?? '') ?>" placeholder="e.g. 123-456-789">
          </div>
          <div class="form-group">
            <label class="form-label">VAT / VRN</label>
            <input type="text" name="vat_vrn" class="form-control" value="<?= e($company['vat_vrn'] ?? '') ?>" placeholder="e.g. 40-001234-X">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Certificate of Incorporation / Reg. No.</label>
          <input type="text" name="registration_number" class="form-control" value="<?= e($company['registration_number'] ?? '') ?>" placeholder="e.g. 145982">
        </div>

        <div class="form-group">
          <label class="form-label">Physical Address</label>
          <textarea name="address" class="form-control" rows="2" placeholder="e.g. Plot 42, Ali Hassan Mwinyi Rd, Victoria"><?= e($company['address'] ?? '') ?></textarea>
        </div>

        <div class="grid-2col" style="gap:12px">
          <div class="form-group">
            <label class="form-label">City / Region</label>
            <input type="text" name="city_region" class="form-control" value="<?= e($company['city_region'] ?? 'Dar es Salaam') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="country" class="form-control" value="<?= e($company['country'] ?? 'Tanzania') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Contact & Signatory Details -->
    <div>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header">
          <div class="card-title"><?= svgIcon('user-check') ?> Authorized Signatory (For Countersigning SOF)</div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Authorized Signatory Full Name</label>
            <input type="text" name="authorized_signatory" class="form-control" value="<?= e($company['authorized_signatory'] ?? '') ?>" placeholder="e.g. John Doe">
          </div>
          <div class="form-group">
            <label class="form-label">Designation / Title</label>
            <input type="text" name="signatory_title" class="form-control" value="<?= e($company['signatory_title'] ?? '') ?>" placeholder="e.g. Chief Executive Officer / Managing Director">
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header">
          <div class="card-title"><?= svgIcon('phone') ?> Official Contacts</div>
        </div>
        <div class="card-body">
          <div class="grid-2col" style="gap:12px">
            <div class="form-group">
              <label class="form-label">Official Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($company['phone'] ?? '') ?>" placeholder="+255 22 ...">
            </div>
            <div class="form-group">
              <label class="form-label">General Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($company['email'] ?? '') ?>" placeholder="info@neilos.co.tz">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Website URL</label>
            <input type="text" name="website" class="form-control" value="<?= e($company['website'] ?? 'https://neilos.co.tz') ?>">
          </div>

          <div class="grid-2col" style="gap:12px">
            <div class="form-group">
              <label class="form-label">Finance Billing Contact</label>
              <input type="text" name="finance_contact" class="form-control" value="<?= e($company['finance_contact'] ?? '') ?>" placeholder="Finance Dept">
            </div>
            <div class="form-group">
              <label class="form-label">Finance Email</label>
              <input type="email" name="finance_email" class="form-control" value="<?= e($company['finance_email'] ?? '') ?>" placeholder="billing@neilos.co.tz">
            </div>
          </div>

          <div class="grid-2col" style="gap:12px">
            <div class="form-group">
              <label class="form-label">NOC / Technical Contact</label>
              <input type="text" name="tech_contact" class="form-control" value="<?= e($company['tech_contact'] ?? '') ?>" placeholder="NOC Support Desk">
            </div>
            <div class="form-group">
              <label class="form-label">Technical Email</label>
              <input type="email" name="tech_email" class="form-control" value="<?= e($company['tech_email'] ?? '') ?>" placeholder="noc@neilos.co.tz">
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><?= svgIcon('image') ?> Company Logo</div>
        </div>
        <div class="card-body">
          <?php if (!empty($company['logo_path'])): ?>
            <div style="margin-bottom:12px;padding:12px;background:#f8f9fa;border-radius:6px;text-align:center">
              <img src="<?= e(APP_URL . '/' . $company['logo_path']) ?>" alt="Neilos Logo" style="max-height:60px;max-width:200px;object-fit:contain">
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Upload New Logo (PNG / JPG / SVG)</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <div class="form-hint">Shown in header of all printed Service Order Forms</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:1.5rem;text-align:right">
    <button type="submit" class="btn btn-primary btn-lg"><?= svgIcon('check') ?> Save Company Settings</button>
  </div>
</form>
