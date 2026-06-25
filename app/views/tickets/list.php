<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Trouble Tickets</div>
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
<div class="card" style="margin-bottom:20px">
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

<!-- Tickets Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ticket #</th>
          <th>Service ID</th>
          <th>Customer</th>
          <th>Fault</th>
          <th>Severity</th>
          <th>Queue</th>
          <th>SLA</th>
          <th>Status</th>
          <th>Created</th>
          <th class="text-right">Actions</th>
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
        <tr>
          <td><a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($tk['ticket_number']) ?></a></td>
          <td class="font-sm"><?= e($tk['service_id']) ?></td>
          <td class="font-sm"><?= e($tk['customer_name']) ?></td>
          <td class="font-sm"><?= e($tk['fault_category']) ?></td>
          <td>
            <span class="badge badge-<?= in_array($tk['severity'], ['Sev 1','Critical']) ? 'danger' : (in_array($tk['severity'], ['Sev 2','Standard']) ? 'warning' : 'secondary') ?>">
              <?= e($tk['severity']) ?>
            </span>
          </td>
          <td class="font-sm"><?= e($tk['current_queue']) ?></td>
          <td style="min-width:100px">
            <div style="display:flex;align-items:center;gap:6px">
              <span style="font-size:.72rem;font-weight:700;color:<?= $slaPct >= 100 ? 'var(--danger)' : ($slaPct >= 80 ? 'var(--warning)' : 'var(--success)') ?>">
                <?= number_format($slaPct, 0) ?>%
              </span>
              <span class="badge <?= slaBadgeClass($slaLabel) ?>" style="font-size:.65rem"><?= e($slaLabel) ?></span>
            </div>
            <div class="sla-bar">
              <div class="sla-bar-fill <?= $slaPct >= 100 ? 'breach' : ($slaPct >= 80 ? 'warning' : 'normal') ?>" style="width:<?= min(100, $slaPct) ?>%"></div>
            </div>
          </td>
          <td><span class="badge <?= ticketStatusClass($tk['status']) ?>"><?= e($tk['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($tk['created_at']) ?></td>
          <td class="text-right">
            <a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="btn btn-sm btn-secondary" title="View"><?= svgIcon('eye') ?></a>
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
