<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><?= $action === 'create' ? 'New Partner' : 'Edit Partner' ?></h1>
    <div class="page-subtitle"><?= $action === 'create' ? 'Add a new partner organization' : 'Update partner details' ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary"><?= svgIcon('list') ?> All Partners</a>
  </div>
</div>

<div class="card card-max-800">
  <div class="card-header"><div class="card-title"><?= $action === 'create' ? 'Partner Details' : 'Edit Partner' ?></div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=partners&action=<?= e($action) ?>">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <?php if ($action === 'edit' && $partner): ?>
      <input type="hidden" name="id" value="<?= $partner['id'] ?>">
      <?php endif; ?>

      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label for="name">Partner Name <span class="text-danger">*</span></label>
          <input type="text" id="name" name="name" class="form-control" required
                 value="<?= e($partner['name'] ?? '') ?>" placeholder="e.g. Savanna ISP Ltd">
        </div>
        <div class="form-group">
          <label for="trading_name">Trading Name</label>
          <input type="text" id="trading_name" name="trading_name" class="form-control"
                 value="<?= e($partner['trading_name'] ?? '') ?>" placeholder="e.g. Savanna ISP">
        </div>
        <div class="form-group">
          <label for="partner_type">Partner Type <span class="text-danger">*</span></label>
          <select id="partner_type" name="partner_type" class="form-control" required>
            <option value="ISP" <?= ($partner['partner_type'] ?? '') === 'ISP' ? 'selected' : '' ?>>ISP</option>
            <option value="Reseller" <?= ($partner['partner_type'] ?? '') === 'Reseller' ? 'selected' : '' ?>>Reseller</option>
            <option value="VAR" <?= ($partner['partner_type'] ?? '') === 'VAR' ? 'selected' : '' ?>>VAR</option>
            <option value="Enterprise" <?= ($partner['partner_type'] ?? '') === 'Enterprise' ? 'selected' : '' ?>>Enterprise</option>
            <option value="Government" <?= ($partner['partner_type'] ?? '') === 'Government' ? 'selected' : '' ?>>Government</option>
            <option value="Other" <?= ($partner['partner_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label for="status">Status <span class="text-danger">*</span></label>
          <select id="status" name="status" class="form-control" required>
            <option value="Active" <?= ($partner['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= ($partner['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="Suspended" <?= ($partner['status'] ?? '') === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
        <div class="form-group">
          <label for="registration_number">Registration Number</label>
          <input type="text" id="registration_number" name="registration_number" class="form-control"
                 value="<?= e($partner['registration_number'] ?? '') ?>" placeholder="e.g. REG-2019-001234">
        </div>
        <div class="form-group">
          <label for="tin">TIN</label>
          <input type="text" id="tin" name="tin" class="form-control"
                 value="<?= e($partner['tin'] ?? '') ?>" placeholder="e.g. TIN-001234567">
        </div>
        <div class="form-group">
          <label for="city_region">City / Region</label>
          <input type="text" id="city_region" name="city_region" class="form-control"
                 value="<?= e($partner['city_region'] ?? '') ?>" placeholder="e.g. Dar es Salaam">
        </div>
        <div class="form-group">
          <label for="country">Country</label>
          <input type="text" id="country" name="country" class="form-control"
                 value="<?= e($partner['country'] ?? 'Tanzania') ?>">
        </div>
      </div>
      <div class="form-group" style="margin-top:6px">
        <label for="address">Address</label>
        <textarea id="address" name="address" class="form-control" rows="3" placeholder="Street address..."><?= e($partner['address'] ?? '') ?></textarea>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary"><?= svgIcon($action === 'create' ? 'plus' : 'edit') ?> <?= $action === 'create' ? 'Create Partner' : 'Save Changes' ?></button>
        <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
