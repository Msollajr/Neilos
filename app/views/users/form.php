<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><?= $action === 'create' ? 'New User' : 'Edit User' ?></h1>
    <div class="page-subtitle"><?= $action === 'create' ? 'Create a new portal user' : 'Update user details' ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=users" class="btn btn-secondary"><?= svgIcon('list') ?> All Users</a>
  </div>
</div>

<div class="card card-max-700">
  <div class="card-header"><div class="card-title">User Details</div></div>
  <div class="card-body">
    <form method="POST" action="<?= APP_URL ?>/?page=users&action=<?= e($action) ?>">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <?php if ($action === 'edit' && $profile): ?>
      <input type="hidden" name="id" value="<?= $profile['id'] ?>">
      <?php endif; ?>

      <div class="form-grid form-grid-2">
        <div class="form-group">
          <label for="full_name">Full Name <span class="text-danger">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control" required
                 value="<?= e($profile['full_name'] ?? '') ?>" placeholder="e.g. John Doe">
        </div>
        <div class="form-group">
          <label for="username">Username <?= $action === 'create' ? '<span class="text-danger">*</span>' : '' ?></label>
          <input type="text" id="username" name="username" class="form-control"
                 <?= $action === 'create' ? 'required' : 'readonly' ?>
                 value="<?= e($profile['username'] ?? '') ?>" placeholder="e.g. johndoe">
          <?php if ($action === 'edit'): ?>
          <small style="color:var(--text-muted)">Username cannot be changed.</small>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="email">Email <span class="text-danger">*</span></label>
          <input type="email" id="email" name="email" class="form-control" required
                 value="<?= e($profile['email'] ?? '') ?>" placeholder="e.g. john@example.com">
        </div>
        <div class="form-group">
          <label for="mobile">Mobile <span class="text-danger">*</span></label>
          <input type="text" id="mobile" name="mobile" class="form-control" required
                 value="<?= e($profile['mobile'] ?? '') ?>" placeholder="e.g. 0712000000">
        </div>
        <div class="form-group">
          <label for="role">Role <span class="text-danger">*</span></label>
          <select id="role" name="role" class="form-control" required>
            <?php foreach ($roles as $r): ?>
            <option value="<?= e($r) ?>" <?= ($profile['role'] ?? 'Partner User') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="partner_id">Partner</label>
          <select id="partner_id" name="partner_id" class="form-control">
            <option value="">— None (Internal User) —</option>
            <?php foreach ($partners as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($profile['partner_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <small style="color:var(--text-muted)">Select a partner for Partner User role.</small>
        </div>
      </div>

      <?php if ($action === 'create'): ?>
      <div class="card" style="margin-top:16px;background:var(--surface-2);border:1px solid var(--border)">
        <div class="card-body" style="padding:14px;font-size:.85rem">
          <strong style="color:var(--primary)"><?= svgIcon('info', 16) ?> Default Password</strong>
          <div style="margin-top:4px;color:var(--text-secondary)">
            New users will be created with the default password <code style="background:var(--surface-1);padding:2px 6px;border-radius:4px">Chang3Me!</code>
            and will be required to change it on first login.
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary"><?= svgIcon($action === 'create' ? 'plus' : 'edit') ?> <?= $action === 'create' ? 'Create User' : 'Save Changes' ?></button>
        <a href="<?= APP_URL ?>/?page=users" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
