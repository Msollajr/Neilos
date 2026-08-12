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
            <?php 
              $userRoleVal = $profile['role'] ?? '';
              if ($userRoleVal === 'Partner User' || ($userRoleVal === '' && !empty($profile['partner_id']))) {
                  $userRoleVal = 'Partner';
              } elseif ($userRoleVal === 'Contractor User') {
                  $userRoleVal = 'Contractor';
              }
              if (!$userRoleVal) {
                  $userRoleVal = 'Partner';
              }
            ?>
            <?php foreach ($roles as $r): ?>
            <option value="<?= e($r) ?>" <?= $userRoleVal === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
          </select>
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

      <div style="margin-top:28px;border-top:1px solid var(--border);padding-top:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <div>
            <h3 style="font-size:1.05rem;font-weight:700;margin:0;color:var(--text-primary)">MODULE ACCESS &amp; PERMISSIONS</h3>
            <p style="font-size:0.83rem;color:var(--text-secondary);margin:2px 0 0">
              Grant explicit module and action permissions to this user. Unchecking modules completely hides them from the sidebar and blocks backend access.
            </p>
          </div>
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllPermissions()">[ Select All ]</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearAllPermissions()">[ Clear All ]</button>
          </div>
        </div>

        <input type="hidden" name="has_custom_permissions" value="1">

        <?php
        $catalog = getAllPermissionCatalog();
        $userPerms = getUserPermissions($profile ?? []);
        $isCustom = !empty($profile['permissions']);
        ?>

        <div style="margin-bottom:14px">
          <label class="form-check" style="display:inline-flex;align-items:center;gap:8px;font-size:0.88rem;font-weight:600">
            <input type="checkbox" id="toggle_custom_perms" name="toggle_custom_perms" value="1" onchange="toggleCustomPermissions(this.checked)" <?= $isCustom ? 'checked' : '' ?>>
            <span>Enable Custom User Permission Overrides (Overrides Role Defaults)</span>
          </label>
        </div>

        <div id="permissions_container" style="<?= $isCustom ? '' : 'display:none;' ?>background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:20px">
          <?php foreach ($catalog as $category => $permList): ?>
          <div style="margin-bottom:16px">
            <div style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--primary);margin-bottom:8px;border-bottom:1px solid var(--border);padding-bottom:4px">
              <?= e($category) ?>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px 16px">
              <?php foreach ($permList as $pKey => $pLabel): ?>
              <label class="form-check" style="display:flex;align-items:center;gap:8px;font-size:0.85rem">
                <input type="checkbox" class="perm-cb" name="permissions[]" value="<?= e($pKey) ?>" <?= in_array($pKey, $userPerms, true) ? 'checked' : '' ?>>
                <span><?= e($pLabel) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <script>
      function selectAllPermissions() {
          document.getElementById('toggle_custom_perms').checked = true;
          toggleCustomPermissions(true);
          document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = true);
      }
      function clearAllPermissions() {
          document.getElementById('toggle_custom_perms').checked = true;
          toggleCustomPermissions(true);
          document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = false);
      }
      function toggleCustomPermissions(enabled) {
          const container = document.getElementById('permissions_container');
          if (container) container.style.display = enabled ? 'block' : 'none';
      }
      </script>

      <div style="display:flex;gap:10px;margin-top:24px">
        <button type="submit" class="btn btn-primary"><?= svgIcon($action === 'create' ? 'plus' : 'edit') ?> <?= $action === 'create' ? 'Create User' : 'Save Changes' ?></button>
        <a href="<?= APP_URL ?>/?page=users" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
