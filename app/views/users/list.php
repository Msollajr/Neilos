<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">User Management</h1>
    <div class="page-subtitle"><?= $total ?> user(s) found</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=users&action=create" class="btn btn-primary"><?= svgIcon('plus') ?> New User</a>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" action="">
      <input type="hidden" name="page" value="users">
      <div class="filter-bar">
        <div class="search-box" style="flex:1;max-width:320px">
          <?= svgIcon('search') ?>
          <input type="text" name="q" placeholder="Search name, username, email..." value="<?= e($filterSearch) ?>">
        </div>
        <select name="role" class="form-control">
          <option value="">All Roles</option>
          <?php foreach ($roles as $r): ?>
          <option value="<?= e($r) ?>" <?= $filterRole === $r ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= $filterStatus === '0' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><?= svgIcon('filter') ?> Filter</button>
        <a href="?page=users" class="btn btn-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Partner</th>
          <th>Status</th>
          <th>Last Login</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr><td colspan="9"><div class="empty-state"><?= svgIcon('users', 32) ?><div class="empty-state-title">No users found</div><div class="empty-state-text">Try adjusting your filters or create a new user.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=users&action=detail&id=<?= $u['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($u['full_name']) ?></a></td>
          <td class="font-sm"><?= e($u['username']) ?></td>
          <td class="font-sm"><?= e($u['email']) ?></td>
          <td><span class="badge badge-primary"><?= e($u['role']) ?></span></td>
          <td class="font-sm"><?= e($u['partner_name'] ?: '—') ?></td>
          <td>
            <?php if ($u['is_active']): ?>
            <span class="badge badge-success">Active</span>
            <?php else: ?>
            <span class="badge badge-danger">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-muted font-sm"><?= $u['last_login'] ? fmtDateTime($u['last_login']) : 'Never' ?></td>
          <td class="text-muted font-sm"><?= fmtDate($u['created_at']) ?></td>
          <td>
            <div class="actions">
              <a href="<?= APP_URL ?>/?page=users&action=detail&id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View"><?= svgIcon('eye') ?></a>
              <a href="<?= APP_URL ?>/?page=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit"><?= svgIcon('edit') ?></a>
              <form method="POST" action="<?= APP_URL ?>/?page=users&action=toggle_status" style="display:inline" onsubmit="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-secondary' : 'btn-primary' ?> btn-icon" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                  <?= svgIcon($u['is_active'] ? 'x' : 'check') ?>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;gap:8px;align-items:center;justify-content:center">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
