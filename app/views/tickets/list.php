<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Trouble Tickets</h1>
    <div class="page-subtitle"><?= $total ?> total &middot; <?= $openCount ?> open &middot; <?= $breachCount ?> breached SLA</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=tickets&action=create" class="btn btn-primary">
      <?= svgIcon('plus') ?> New Ticket
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon red"><?= svgIcon('ticket', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= $openCount ?></div>
      <div class="stat-label">Open Tickets</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value"><?= $breachCount ?></div>
      <div class="stat-label">SLA Breached</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><?= svgIcon('server', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value">
        <?php
        $queueStmt = $db->prepare("SELECT current_queue, COUNT(*) as cnt FROM trouble_tickets tt WHERE tt.status NOT IN ('Closed') AND {$pw['condition']} GROUP BY current_queue ORDER BY FIELD(current_queue, 'NOC Support','NOC Core','NOC Level 3','Director')");
        $queueStmt->execute($pw['params']);
        $queueStats = $queueStmt->fetchAll();
        echo count($queueStats);
        ?>
      </div>
      <div class="stat-label">Queues Active</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><?= svgIcon('check', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value">
        <?php
        $closedStmt = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.status = 'Closed' AND {$pw['condition']}");
        $closedStmt->execute($pw['params']);
        echo (int)$closedStmt->fetchColumn();
        ?>
      </div>
      <div class="stat-label">Closed Tickets</div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card mb-20">
  <div class="card-body" style="padding:16px 22px">
    <form method="GET" class="filter-bar">
      <input type="hidden" name="page" value="tickets">
      <div class="search-box">
        <?= svgIcon('search') ?>
        <input type="text" name="q" placeholder="Search tickets..." value="<?= e($filterSearch) ?>">
      </div>
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach ($statusOptions as $s): ?>
        <option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="queue" class="form-control" onchange="this.form.submit()">
        <option value="">All Queues</option>
        <?php foreach ($queues as $q): ?>
        <option value="<?= e($q) ?>" <?= $filterQueue === $q ? 'selected' : '' ?>><?= e($q) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="severity" class="form-control" onchange="this.form.submit()">
        <option value="">All Severities</option>
        <?php foreach ($severityOptions as $s): ?>
        <option value="<?= e($s) ?>" <?= $filterSeverity === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm"><?= svgIcon('filter') ?> Filter</button>
      <a href="<?= APP_URL ?>/?page=tickets" class="btn btn-secondary btn-sm">Clear</a>
    </form>
  </div>
</div>

<!-- Enterprise Trouble Tickets Data Table -->
<div class="card">
  <div class="table-responsive ticket-table-wrap">
    <table class="data-table ticket-table">
      <thead>
        <tr>
          <th class="col-ticket-id">Ticket ID</th>
          <th class="col-service-id">Service ID</th>
          <th class="col-customer">Customer</th>
          <th class="col-fault">Fault</th>
          <th class="col-severity text-center">Severity</th>
          <th class="col-queue">Queue</th>
          <th class="col-sla">SLA</th>
          <th class="col-status text-center">Status</th>
          <th class="col-created">Created</th>
          <th class="col-actions text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
        <tr><td colspan="10"><div class="empty-state"><div class="empty-state-title">No tickets found</div><div class="empty-state-text">All services are operating normally.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($tickets as $tk):
          $slaPct = calculateSLAPct($tk);
          $slaLabel = getSLAStatusLabel($slaPct);
        ?>
        <tr class="ticket-row">
          <td class="col-ticket-id">
            <a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="font-600" style="color:var(--accent)">
              <?= e($tk['ticket_number']) ?>
            </a>
          </td>
          <td class="col-service-id font-sm text-secondary">
            <?= e($tk['service_id']) ?>
          </td>
          <td class="col-customer font-sm" title="<?= e($tk['customer_name']) ?>">
            <?= e($tk['customer_name']) ?>
          </td>
          <td class="col-fault font-sm" title="<?= e($tk['fault_category']) ?>">
            <?= e($tk['fault_category']) ?>
          </td>
          <td class="col-severity text-center">
            <span class="badge badge-<?= in_array($tk['severity'], ['Sev 1','Critical']) ? 'danger' : (in_array($tk['severity'], ['Sev 2','Standard']) ? 'warning' : 'secondary') ?>">
              <?= e($tk['severity']) ?>
            </span>
          </td>
          <td class="col-queue font-sm text-secondary">
            <?= e($tk['current_queue']) ?>
          </td>
          <td class="col-sla">
            <div class="sla-container">
              <div class="sla-header-row">
                <span style="font-size:.72rem;font-weight:700;color:<?= $slaPct >= 100 ? 'var(--danger-text)' : ($slaPct >= 80 ? 'var(--warning-text)' : 'var(--success-text)') ?>">
                  <?= number_format($slaPct, 0) ?>%
                </span>
                <span class="badge <?= slaBadgeClass($slaLabel) ?>" style="font-size:.65rem;height:18px;min-width:auto;padding:0 6px">
                  <?= e($slaLabel) ?>
                </span>
              </div>
              <div class="sla-bar-block">
                <div class="sla-bar-fill <?= $slaPct >= 100 ? 'breach' : ($slaPct >= 80 ? 'warning' : 'normal') ?>" style="width:<?= min(100, $slaPct) ?>%"></div>
              </div>
            </div>
          </td>
          <td class="col-status text-center">
            <span class="badge <?= ticketStatusClass($tk['status']) ?>">
              <?= e($tk['status']) ?>
            </span>
          </td>
          <td class="col-created text-muted font-sm">
            <?= fmtDate($tk['created_at']) ?>
          </td>
          <td class="col-actions text-right">
            <a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="btn btn-sm btn-secondary btn-icon" title="View Ticket Details">
              <?= svgIcon('eye') ?>
            </a>
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
    <a href="?page=tickets&p=<?= $i ?><?= $filterStatus ? '&status='.e($filterStatus) : '' ?><?= $filterQueue ? '&queue='.e($filterQueue) : '' ?><?= $filterSeverity ? '&severity='.e($filterSeverity) : '' ?><?= $filterSearch ? '&q='.e($filterSearch) : '' ?>" class="btn btn-sm <?= $i === $pg ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
