<?php // Admin KYC Detail View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">KYC Detail — <?= e($app['partner_name']) ?></div>
    <div class="page-subtitle">
      Status: <span class="badge <?= $app['status'] === 'Approved' ? 'badge-success' : ($app['status'] === 'Rejected' ? 'badge-danger' : ($app['status'] === 'Submitted' || $app['status'] === 'Under Review' ? 'badge-warning' : 'badge-secondary')) ?>"><?= e($app['status']) ?></span>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=kyc&action=admin_list" class="btn btn-secondary"><?= svgIcon('list') ?> All Applications</a>
    <a href="<?= APP_URL ?>/?page=kyc" class="btn btn-secondary"><?= svgIcon('edit') ?> Partner View</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px">
  <div class="card">
    <div class="card-header"><div class="card-title">Section 1: Partner Details</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Registered Name</label><div><?= e($app['registered_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Trading Name</label><div><?= e($app['trading_name'] ?: '—') ?></div></div>
        <div class="form-group"><label>Partner Type</label><div><?= e($app['partner_type'] ?: '—') ?></div></div>
        <div class="form-group"><label>Customer Category</label><div><?= e($app['customer_category'] ?: '—') ?></div></div>
        <div class="form-group"><label>Industry Sector</label><div><?= e($app['industry_sector'] ?: '—') ?></div></div>
        <div class="form-group"><label>Nature of Business</label><div><?= e($app['nature_of_business'] ?: '—') ?></div></div>
        <div class="form-group"><label>Registration Number</label><div><?= e($app['registration_number'] ?: '—') ?></div></div>
        <div class="form-group"><label>TIN</label><div><?= e($app['tin'] ?: '—') ?></div></div>
        <div class="form-group"><label>VAT / VRN</label><div><?= e($app['vat_vrn'] ?: '—') ?></div></div>
        <div class="form-group"><label>Address</label><div><?= e($app['address'] ?: '—') ?></div></div>
        <div class="form-group"><label>City / Region</label><div><?= e($app['city_region'] ?: '—') ?></div></div>
        <div class="form-group"><label>Country</label><div><?= e($app['country'] ?: '—') ?></div></div>
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:22px">
      <div class="card-header"><div class="card-title">Section 2: Authorized Signatory</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Full Name</label><div><?= e($app['auth_signatory_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Title</label><div><?= e($app['auth_signatory_title'] ?: '—') ?></div></div>
          <div class="form-group"><label>Department</label><div><?= e($app['auth_signatory_dept'] ?: '—') ?></div></div>
          <div class="form-group"><label>ID Type</label><div><?= e($app['auth_signatory_id_type'] ?: '—') ?></div></div>
          <div class="form-group"><label>ID Number</label><div><?= e($app['auth_signatory_id_number'] ?: '—') ?></div></div>
          <div class="form-group"><label>Mobile</label><div><?= e($app['auth_signatory_mobile'] ?: '—') ?></div></div>
          <div class="form-group"><label>Email</label><div><?= e($app['auth_signatory_email'] ?: '—') ?></div></div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:22px">
      <div class="card-header"><div class="card-title">Section 3: Finance &amp; Billing Contact</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Full Name</label><div><?= e($app['finance_contact_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Title</label><div><?= e($app['finance_contact_title'] ?: '—') ?></div></div>
          <div class="form-group"><label>Mobile</label><div><?= e($app['finance_contact_mobile'] ?: '—') ?></div></div>
          <div class="form-group"><label>Email</label><div><?= e($app['finance_contact_email'] ?: '—') ?></div></div>
          <div class="form-group"><label>Billing Email</label><div><?= e($app['billing_email'] ?: '—') ?></div></div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:22px">
      <div class="card-header"><div class="card-title">Section 4: Technical Contact</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Full Name</label><div><?= e($app['tech_contact_name'] ?: '—') ?></div></div>
          <div class="form-group"><label>Title</label><div><?= e($app['tech_contact_title'] ?: '—') ?></div></div>
          <div class="form-group"><label>Mobile</label><div><?= e($app['tech_contact_mobile'] ?: '—') ?></div></div>
          <div class="form-group"><label>Email</label><div><?= e($app['tech_contact_email'] ?: '—') ?></div></div>
        </div>
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
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($app['countersigned_kyc_file']): ?>
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

<?php if (!in_array($app['status'], ['Approved', 'Rejected'])): ?>
<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Review Actions</div></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px">
      <form method="POST" action="<?= APP_URL ?>/?page=kyc&action=approve">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="kyc_id" value="<?= $app['id'] ?>">
        <div class="form-group">
          <label>Approve Notes</label>
          <textarea name="review_notes" class="form-control" rows="3" placeholder="Optional approval notes..."></textarea>
        </div>
        <button type="submit" class="btn btn-success" style="margin-top:10px"><?= svgIcon('check') ?> Approve</button>
      </form>

      <form method="POST" action="<?= APP_URL ?>/?page=kyc&action=reject">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="kyc_id" value="<?= $app['id'] ?>">
        <div class="form-group">
          <label>Rejection Reason <span class="required">*</span></label>
          <textarea name="review_notes" class="form-control" rows="3" required placeholder="Reason for rejection..."></textarea>
        </div>
        <button type="submit" class="btn btn-danger" style="margin-top:10px"><?= svgIcon('x') ?> Reject</button>
      </form>
    </div>

    <div class="divider" style="margin:22px 0"></div>

    <form method="POST" action="<?= APP_URL ?>/?page=kyc&action=upload_countersigned" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="kyc_id" value="<?= $app['id'] ?>">
      <div class="form-group">
        <label>Upload Countersigned KYC PDF</label>
        <input type="file" name="countersigned_kyc" accept=".pdf" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:10px"><?= svgIcon('upload') ?> Upload Countersigned</button>
    </form>
  </div>
</div>

<?php elseif ($app['review_notes']): ?>
<div class="card" style="margin-bottom:22px">
  <div class="card-header"><div class="card-title">Review Notes</div></div>
  <div class="card-body">
    <p style="white-space:pre-wrap"><?= e($app['review_notes']) ?></p>
    <p class="font-sm text-muted" style="margin-top:6px">Reviewed by <?= e($app['reviewer_name'] ?: '—') ?> on <?= fmtDateTime($app['reviewed_at']) ?></p>
  </div>
</div>
<?php endif; ?>
