<?php
$exportParams = array_filter([
  'page'      => 'activity_logs',
  'action'    => 'export',
  'user_id'   => $filterUser > 0 ? $filterUser : null,
  'module'    => $filterModule !== '' ? $filterModule : null,
  'q'         => $filterSearch !== '' ? $filterSearch : null,
  'date_from' => $filterDateFrom !== '' ? $filterDateFrom : null,
  'date_to'   => $filterDateTo !== '' ? $filterDateTo : null,
]);
$exportUrl = APP_URL . '/?' . http_build_query($exportParams);
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Activity & Audit Logs</h1>
    <div class="page-subtitle"><?= number_format($total) ?> total logs &middot; Real-time system activity tracking &amp; user attribution</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= $exportUrl ?>" class="btn btn-primary btn-sm">
      <?= svgIcon('download') ?> Download CSV
    </a>
    <a href="<?= APP_URL ?>/?page=activity_logs" class="btn btn-secondary btn-sm">
      <?= svgIcon('refresh') ?> Refresh Logs
    </a>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid mb-20">
  <div class="stat-card">
    <div class="stat-icon blue"><?= svgIcon('activity', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($statsTotalLogs) ?></div>
      <div class="stat-label">Total System Logs</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($statsTodayLogs) ?></div>
      <div class="stat-label">Today's Actions</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><?= svgIcon('users', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($statsActiveUsers) ?></div>
      <div class="stat-label">Active Users Tracked</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon cyan"><?= svgIcon('shield', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($statsIpCount) ?></div>
      <div class="stat-label">IP Addresses Logged</div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card mb-20">
  <div class="card-body" style="padding:16px 22px">
    <form method="GET" class="filter-bar" style="flex-wrap:wrap;gap:12px">
      <input type="hidden" name="page" value="activity_logs">
      
      <div class="search-box" style="flex:1;min-width:220px">
        <?= svgIcon('search') ?>
        <input type="text" name="q" placeholder="Search action, user, IP..." value="<?= e($filterSearch) ?>">
      </div>

      <select name="user_id" class="form-control" style="width:auto;min-width:180px" onchange="this.form.submit()">
        <option value="">All Users</option>
        <?php foreach ($usersList as $u): ?>
        <option value="<?= $u['id'] ?>" <?= $filterUser === (int)$u['id'] ? 'selected' : '' ?>>
          <?= e($u['full_name']) ?> (<?= e($u['username']) ?>)
        </option>
        <?php endforeach; ?>
      </select>

      <select name="module" class="form-control" style="width:auto;min-width:150px" onchange="this.form.submit()">
        <option value="">All Modules</option>
        <?php foreach ($modulesList as $m): ?>
        <option value="<?= e($m) ?>" <?= $filterModule === $m ? 'selected' : '' ?>>
          <?= e(ucfirst(str_replace('_', ' ', $m))) ?>
        </option>
        <?php endforeach; ?>
      </select>

      <div style="display:flex;align-items:center;gap:6px">
        <input type="date" name="date_from" class="form-control" value="<?= e($filterDateFrom) ?>" title="From Date" onchange="this.form.submit()">
        <span style="color:var(--text-muted);font-size:.8rem">to</span>
        <input type="date" name="date_to" class="form-control" value="<?= e($filterDateTo) ?>" title="To Date" onchange="this.form.submit()">
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-secondary btn-sm"><?= svgIcon('filter') ?> Filter</button>
        <a href="<?= APP_URL ?>/?page=activity_logs" class="btn btn-secondary btn-sm">Clear</a>
        <a href="<?= $exportUrl ?>" class="btn btn-outline btn-sm"><?= svgIcon('download') ?> Export CSV</a>
      </div>
    </form>
  </div>
</div>

<!-- Activity Logs Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th style="width:160px">Date &amp; Time</th>
          <th style="width:240px">User / Performed By</th>
          <th style="width:130px">Module</th>
          <th>Action Description</th>
          <th style="width:130px">IP Address</th>
          <th style="width:70px;text-align:right">Details</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-state-title">No activity logs found</div>
              <div class="empty-state-text">No user activity matches the selected filter criteria.</div>
            </div>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="font-sm text-muted">#<?= $log['id'] ?></td>
          <td class="font-sm white-space-nowrap">
            <div style="font-weight:600"><?= fmtDate($log['created_at'], 'd M Y, H:i:s') ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted)"><?= timeAgo($log['created_at']) ?></div>
          </td>
          <td>
            <?php if (!empty($log['user_name'])): ?>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="sidebar-avatar" style="width:30px;height:30px;font-size:0.75rem;flex-shrink:0">
                <?= strtoupper(substr($log['user_name'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-weight:600;font-size:0.85rem;color:var(--text-primary)">
                  <a href="<?= APP_URL ?>/?page=users&action=detail&id=<?= $log['user_id'] ?>" style="color:inherit">
                    <?= e($log['user_name']) ?>
                  </a>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted)">
                  @<?= e($log['username']) ?> &middot; <span class="badge badge-secondary" style="font-size:0.65rem;padding:1px 6px"><?= e($log['user_role']) ?></span>
                </div>
              </div>
            </div>
            <?php else: ?>
            <span class="text-muted font-sm" style="font-style:italic">System / Anonymous (ID: <?= $log['user_id'] ?? 'N/A' ?>)</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($log['module'])): ?>
            <span class="badge badge-primary" style="text-transform:capitalize">
              <?= e(str_replace('_', ' ', $log['module'])) ?>
            </span>
            <?php else: ?>
            <span class="text-muted font-sm">&mdash;</span>
            <?php endif; ?>
          </td>
          <td style="line-height:1.45">
            <div style="font-size:0.85rem;color:var(--text-primary);font-weight:500">
              <?= e($log['action']) ?>
            </div>
            <?php if (!empty($log['record_id'])): ?>
            <div style="font-size:0.75rem;color:var(--text-muted)">
              Record ID: <code>#<?= $log['record_id'] ?></code>
            </div>
            <?php endif; ?>
          </td>
          <td class="font-sm text-secondary">
            <code><?= e($log['ip_address'] ?: '127.0.0.1') ?></code>
          </td>
          <td class="text-right">
            <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
            <button type="button" class="btn btn-sm btn-secondary btn-icon" title="View Payload Diffs" onclick="showLogDetails(<?= htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8') ?>)">
              <?= svgIcon('eye') ?>
            </button>
            <?php else: ?>
            <span class="text-muted">&mdash;</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;justify-content:center;gap:8px">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?page=activity_logs&p=<?= $i ?><?= $filterUser ? '&user_id='.$filterUser : '' ?><?= $filterModule ? '&module='.e($filterModule) : '' ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?><?= $filterDateFrom ? '&date_from='.e($filterDateFrom) : '' ?><?= $filterDateTo ? '&date_to='.e($filterDateTo) : '' ?>" class="btn btn-sm <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal for Audit Payload Details -->
<div id="logDetailModal" class="modal-backdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
  <div class="card" style="width:90%;max-width:640px;margin:auto;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.4)">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="font-weight:700;font-size:1rem;color:var(--text-primary)">
        Audit Log Payload Details <span id="modalLogId" style="color:var(--accent)"></span>
      </div>
      <button type="button" class="btn btn-secondary btn-sm btn-icon" onclick="closeLogModal()">
        <?= svgIcon('x') ?>
      </button>
    </div>
    <div class="card-body" style="padding:20px;overflow-y:auto;flex:1">
      <div style="margin-bottom:16px">
        <div style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px">Action &amp; Performed By</div>
        <div id="modalActionText" style="font-weight:600;font-size:0.95rem;color:var(--text-primary)"></div>
        <div id="modalUserText" style="font-size:0.82rem;color:var(--text-secondary);margin-top:2px"></div>
      </div>
      
      <div id="oldValueBlock" style="margin-bottom:16px;display:none">
        <div style="font-size:0.78rem;font-weight:700;color:var(--danger-text);text-transform:uppercase;margin-bottom:4px">Previous State (Old Value)</div>
        <pre id="modalOldValue" style="background:var(--surface-3);padding:12px;border-radius:6px;font-size:0.8rem;white-space:pre-wrap;word-break:break-all;color:var(--text-primary)"></pre>
      </div>

      <div id="newValueBlock" style="margin-bottom:16px;display:none">
        <div style="font-size:0.78rem;font-weight:700;color:var(--success-text);text-transform:uppercase;margin-bottom:4px">New State (New Value)</div>
        <pre id="modalNewValue" style="background:var(--surface-3);padding:12px;border-radius:6px;font-size:0.8rem;white-space:pre-wrap;word-break:break-all;color:var(--text-primary)"></pre>
      </div>
    </div>
    <div class="card-footer" style="padding:12px 20px;border-top:1px solid var(--border);text-align:right">
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeLogModal()">Close</button>
    </div>
  </div>
</div>

<script>
function showLogDetails(log) {
  document.getElementById('modalLogId').innerText = '#' + log.id;
  document.getElementById('modalActionText').innerText = log.action;
  document.getElementById('modalUserText').innerText = (log.user_name || 'System User') + ' (@' + (log.username || 'system') + ') • ' + log.created_at + ' • IP: ' + (log.ip_address || '127.0.0.1');

  var oldBlock = document.getElementById('oldValueBlock');
  var newBlock = document.getElementById('newValueBlock');

  if (log.old_value) {
    oldBlock.style.display = 'block';
    document.getElementById('modalOldValue').innerText = formatJsonOrRaw(log.old_value);
  } else {
    oldBlock.style.display = 'none';
  }

  if (log.new_value) {
    newBlock.style.display = 'block';
    document.getElementById('modalNewValue').innerText = formatJsonOrRaw(log.new_value);
  } else {
    newBlock.style.display = 'none';
  }

  var modal = document.getElementById('logDetailModal');
  modal.style.display = 'flex';
}

function closeLogModal() {
  document.getElementById('logDetailModal').style.display = 'none';
}

function formatJsonOrRaw(val) {
  try {
    var parsed = JSON.parse(val);
    return JSON.stringify(parsed, null, 2);
  } catch(e) {
    return val;
  }
}
</script>
