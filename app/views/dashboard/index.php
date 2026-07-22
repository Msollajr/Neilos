<?php
// ============================================================
// Neilos Portal — Executive Telecom/ISP Operations Dashboard View
// ============================================================

$activatedOrders = $orderStats['Activated'] ?? 0;
$submittedOrders = $orderStats['Submitted'] ?? 0;

// Chart color mapping — dark blue palette
$chartColors = [
  'Submitted'          => ['bg' => '#0F4C81', 'border' => '#1D70B8'],
  'Feasibility Review' => ['bg' => '#F59E0B', 'border' => '#FCD34D'],
  'Approved'           => ['bg' => '#10B981', 'border' => '#34D399'],
  'Provisioning'       => ['bg' => '#083252', 'border' => '#0F4C81'],
  'Testing'            => ['bg' => '#06B6D4', 'border' => '#22D3EE'],
  'UAT'                => ['bg' => '#3B82F6', 'border' => '#60A5FA'],
  'Activated'          => ['bg' => '#22C55E', 'border' => '#4ADE80'],
  'Cancelled'          => ['bg' => '#EF4444', 'border' => '#F87171'],
];

$pipelineSteps = ['Submitted', 'Feasibility Review', 'Approved', 'Provisioning', 'Testing', 'Activated'];
$totalInPipeline = 0;
foreach ($pipelineSteps as $s) { $totalInPipeline += $orderStats[$s] ?? 0; }
$pipelineMax = max(1, max(array_map(fn($s) => $orderStats[$s] ?? 0, $pipelineSteps)));
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
    <div class="page-subtitle"><?= date('l, d F Y') ?> · Telecom &amp; ISP Operations Support System</div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary">
      <?= svgIcon('plus') ?> New Service Order
    </a>
    <a href="<?= APP_URL ?>/?page=tickets&action=new" class="btn btn-secondary">
      <?= svgIcon('ticket') ?> New Ticket
    </a>
  </div>
</div>

<!-- Quick Actions Panel -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Quick Operational Actions</div>
    <div class="card-subtitle">Fast access to key workflows</div>
  </div>
  <div class="card-body" style="padding:16px">
    <div class="d-flex flex-wrap gap-12">
      <a href="<?= APP_URL ?>/?page=new_order" class="btn btn-primary btn-sm">
        <?= svgIcon('plus') ?> New Order
      </a>
      <a href="<?= APP_URL ?>/?page=tickets&action=new" class="btn btn-secondary btn-sm">
        <?= svgIcon('ticket') ?> Open Ticket
      </a>
      <a href="<?= APP_URL ?>/?page=coverage" class="btn btn-secondary btn-sm">
        <?= svgIcon('map') ?> Check Coverage
      </a>
      <a href="<?= APP_URL ?>/?page=bulk_upload" class="btn btn-secondary btn-sm">
        <?= svgIcon('upload') ?> Bulk Upload
      </a>
      <?php if (isAdmin()): ?>
      <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary btn-sm">
        <?= svgIcon('building') ?> Add Partner
      </a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/?page=reports" class="btn btn-outline btn-sm">
        <?= svgIcon('chart') ?> Operations Reports
      </a>
      <a href="?page=orders&export=csv" class="btn btn-outline btn-sm">
        <?= svgIcon('download') ?> Export Orders CSV
      </a>
    </div>
  </div>
</div>

<!-- 12 Executive Summary Cards Grid (All Clickable Module Links) -->
<div class="stats-grid mb-24">
  <!-- Total Service Orders -->
  <a href="<?= APP_URL ?>/?page=orders" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view all Service Orders">
    <div class="stat-icon blue"><?= svgIcon('list', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalOrders ?>">0</div>
      <div class="stat-label">Total Orders</div>
      <div class="stat-change up">+<?= $submittedOrders ?> new</div>
    </div>
  </a>

  <!-- Active Services -->
  <a href="<?= APP_URL ?>/?page=active_services" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Active Services">
    <div class="stat-icon green"><?= svgIcon('server', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $activeServices ?>">0</div>
      <div class="stat-label">Active Services</div>
      <div class="stat-change up">99.8% Uptime</div>
    </div>
  </a>

  <!-- Pending Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Submitted" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Pending Orders">
    <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $pendingBSA + $pendingUAT ?>">0</div>
      <div class="stat-label">Pending Actions</div>
      <div class="stat-change"><?= $pendingBSA ?> BSA · <?= $pendingUAT ?> UAT</div>
    </div>
  </a>

  <!-- Completed Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Activated" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Completed Orders">
    <div class="stat-icon green"><?= svgIcon('check-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $completedOrders ?>">0</div>
      <div class="stat-label">Completed Orders</div>
      <div class="stat-change up">+<?= $completedOrders ?> activated</div>
    </div>
  </a>

  <!-- Cancelled Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Cancelled" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Cancelled Orders">
    <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $cancelledOrders ?>">0</div>
      <div class="stat-label">Cancelled Orders</div>
      <div class="stat-change text-muted">Low churn rate</div>
    </div>
  </a>

  <!-- Open Tickets -->
  <a href="<?= APP_URL ?>/?page=tickets" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Trouble Tickets">
    <div class="stat-icon <?= $openTickets > 0 ? 'red' : 'green' ?>"><?= svgIcon('ticket', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $openTickets ?>">0</div>
      <div class="stat-label">Open Tickets</div>
      <?php if ($breachedTickets > 0): ?>
      <div class="stat-change down"><?= $breachedTickets ?> SLA breached</div>
      <?php else: ?>
      <div class="stat-change up">All within SLA</div>
      <?php endif; ?>
    </div>
  </a>

  <!-- Closed Tickets -->
  <a href="<?= APP_URL ?>/?page=tickets&status=Closed" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Resolved Tickets">
    <div class="stat-icon blue"><?= svgIcon('check', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $closedTickets ?>">0</div>
      <div class="stat-label">Resolved Tickets</div>
      <div class="stat-change up">MTTR &lt; 4 hrs</div>
    </div>
  </a>

  <!-- SLA Breaches -->
  <a href="<?= APP_URL ?>/?page=sla_tracking" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view SLA Tracking">
    <div class="stat-icon <?= $breachedTickets > 0 ? 'red' : 'green' ?>"><?= svgIcon('alert-triangle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $breachedTickets ?>">0</div>
      <div class="stat-label">SLA Breaches</div>
      <div class="stat-change <?= $breachedTickets > 0 ? 'down' : 'up' ?>"><?= $breachedTickets == 0 ? 'Zero breaches' : 'Attention required' ?></div>
    </div>
  </a>

  <!-- Active Customers -->
  <a href="<?= APP_URL ?>/?page=active_services" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Active Customers">
    <div class="stat-icon cyan"><?= svgIcon('users', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $activeCustomers ?>">0</div>
      <div class="stat-label">Active Customers</div>
      <div class="stat-change up">Growing account base</div>
    </div>
  </a>

  <!-- Partners -->
  <a href="<?= APP_URL ?>/?page=partners" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Partners">
    <div class="stat-icon navy"><?= svgIcon('building', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalPartners ?>">0</div>
      <div class="stat-label">Registered Partners</div>
      <div class="stat-change up">Verified ISPs &amp; VARs</div>
    </div>
  </a>

  <!-- Engineers -->
  <a href="<?= APP_URL ?>/?page=users" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Staff &amp; Users">
    <div class="stat-icon blue"><?= svgIcon('shield', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalEngineers ?>">0</div>
      <div class="stat-label">NOC &amp; Eng Staff</div>
      <div class="stat-change up">24/7 Coverage</div>
    </div>
  </a>

  <!-- Revenue / Total MRC -->
  <a href="<?= APP_URL ?>/?page=reports" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Financial Reports">
    <div class="stat-icon green"><?= svgIcon('dollar-sign', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value">$<?= money($monthlyRevenue) ?></div>
      <div class="stat-label">Monthly Active MRC</div>
      <div class="stat-change up">Recurring revenue</div>
    </div>
  </a>
</div>

<!-- Responsive Charts Grid Row 1 -->
<div class="grid-dashboard mb-24">
  <!-- Line Chart: Monthly Orders Created vs Completed -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Order Volume &amp; Fulfillment Trend <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle">Orders created vs completed per month</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders'">
        <canvas id="monthlyTrendChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Donut Chart: Order Status Distribution -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Order Status Distribution <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle">Breakdown by current stage</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="statusDonutChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Responsive Charts Grid Row 2 -->
<div class="grid-dashboard mb-24">
  <!-- Pie Chart: Services Distribution by Type -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=active_services'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Service Type Distribution <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle">FTTH, DIA, Layer 2 &amp; FTTB</div>
      </div>
      <a href="<?= APP_URL ?>/?page=active_services" class="btn btn-secondary btn-sm">View Services</a>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="serviceTypeChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Stacked Bar: Network & Service Health -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=active_services'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Network &amp; Service Health <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle">Online, degraded &amp; offline circuits</div>
      </div>
      <a href="<?= APP_URL ?>/?page=active_services" class="btn btn-secondary btn-sm">View Services</a>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="networkHealthChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Fulfillment Pipeline & Activity Timeline Row -->
<div class="grid-dashboard mb-24">
  <!-- Order Pipeline Summary -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Order Fulfillment Pipeline</div>
        <div class="card-subtitle"><?= $totalInPipeline ?> active orders progressing through stages</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body" style="padding:16px">
      <div class="pipeline-flow">
        <?php foreach ($pipelineSteps as $st):
          $cnt = $orderStats[$st] ?? 0;
          $pct = $pipelineMax > 0 ? round(($cnt / $pipelineMax) * 100) : 0;
          $c = $chartColors[$st];
        ?>
        <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($st) ?>" class="pipeline-step" style="text-decoration:none;color:inherit;cursor:pointer" title="View <?= e($st) ?> orders">
          <div class="pipeline-step-icon" style="background:<?= $c['bg'] ?>20;color:<?= $c['border'] ?>">
            <?= svgIcon(
              $st === 'Submitted' ? 'plus-circle' :
              ($st === 'Feasibility Review' ? 'search' :
              ($st === 'Approved' ? 'check' :
              ($st === 'Provisioning' ? 'refresh' :
              ($st === 'Testing' ? 'shield' : 'server')))), 14
            ) ?>
          </div>
          <div class="pipeline-step-info">
            <div class="pipeline-step-label"><?= e($st) ?></div>
            <div class="pipeline-step-bar">
              <div class="pipeline-step-fill" style="width:0%;background:<?= $c['bg'] ?>" data-width="<?= $pct ?>"></div>
            </div>
          </div>
          <div class="pipeline-step-count"><?= $cnt ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Activity Timeline -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Recent Operational Activity</div>
        <div class="card-subtitle">Latest events &amp; lifecycle updates</div>
      </div>
    </div>
    <div class="card-body">
      <?php if (empty($activityTimeline)): ?>
      <div class="empty-state p-16">
        <div class="empty-state-title">No recent activity</div>
        <div class="empty-state-text">Operational events will appear here.</div>
      </div>
      <?php else: ?>
      <div class="timeline">
        <?php foreach ($activityTimeline as $act):
          $targetUrl = $act['type'] === 'order' 
            ? APP_URL . '/?page=orders'
            : APP_URL . '/?page=tickets';
        ?>
        <a href="<?= $targetUrl ?>" class="timeline-item" style="text-decoration:none;color:inherit;display:block;cursor:pointer">
          <div class="timeline-dot <?= $act['type'] === 'order' ? 'success' : 'warning' ?>"></div>
          <div class="timeline-time"><?= timeAgo($act['event_time']) ?></div>
          <div class="timeline-label">
            <?= $act['type'] === 'order' ? 'Order' : 'Ticket' ?> #<?= e($act['ref']) ?>
          </div>
          <div class="timeline-note">
            <?= e($act['title']) ?> — <span class="font-600"><?= e($act['sub']) ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Data Table 1: Recent Orders -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <div class="card-title">Recent Service Orders</div>
      <div class="card-subtitle">Latest orders submitted across all partners</div>
    </div>
    <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View All Orders</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Partner</th>
          <th>Customer</th>
          <th>Service Type</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentOrders)): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="empty-state-title">No orders found</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($recentOrders as $ord): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=order_detail&id=<?= $ord['id'] ?>" class="font-600" style="color:var(--accent)"><?= e($ord['order_number']) ?></a></td>
          <td class="font-sm"><?= e($ord['partner_name']) ?></td>
          <td><?= e($ord['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($ord['service_type']) ?></span></td>
          <td><span class="badge <?= orderStatusClass($ord['status']) ?>"><?= e($ord['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($ord['created_at']) ?></td>
          <td>
            <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Detail">
              <?= svgIcon('eye') ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Data Table 2: Trouble Tickets -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Active Trouble Tickets</div>
      <div class="card-subtitle">Open support tickets needing NOC attention</div>
    </div>
    <a href="<?= APP_URL ?>/?page=tickets" class="btn btn-secondary btn-sm">View All Tickets</a>
  </div>
  <div class="table-responsive ticket-table-wrap">
    <table class="data-table ticket-table">
      <thead>
        <tr>
          <th class="col-ticket-id">Ticket ID</th>
          <th class="col-service-id">Service ID</th>
          <th class="col-fault">Fault</th>
          <th class="col-severity text-center">Severity</th>
          <th class="col-queue">Queue</th>
          <th class="col-sla">SLA</th>
          <th class="col-status text-center">Status</th>
          <th class="col-actions text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentTickets)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-title">No active tickets</div><div class="empty-state-text">All services are operating normally.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($recentTickets as $tk):
          $slaPct = calculateSLAPct($tk);
          $slaLabel = getSLAStatusLabel($slaPct);
        ?>
        <tr class="ticket-row">
          <td class="col-ticket-id"><a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="font-600" style="color:var(--accent)"><?= e($tk['ticket_number']) ?></a></td>
          <td class="col-service-id font-sm text-secondary"><?= e($tk['service_id']) ?></td>
          <td class="col-fault font-sm" title="<?= e($tk['fault_category']) ?>"><?= e($tk['fault_category']) ?></td>
          <td class="col-severity text-center"><span class="badge badge-<?= in_array($tk['severity'], ['Sev 1','Critical']) ? 'danger' : (in_array($tk['severity'], ['Sev 2','Standard']) ? 'warning' : 'secondary') ?>"><?= e($tk['severity']) ?></span></td>
          <td class="col-queue font-sm text-secondary"><?= e($tk['current_queue']) ?></td>
          <td class="col-sla">
            <div class="sla-container">
              <div class="sla-header-row">
                <span style="font-size:.72rem;font-weight:700;color:<?= $slaPct >= 100 ? 'var(--danger-text)' : ($slaPct >= 80 ? 'var(--warning-text)' : 'var(--success-text)') ?>"><?= number_format($slaPct, 0) ?>%</span>
                <span class="badge <?= slaBadgeClass($slaLabel) ?>" style="font-size:.65rem;height:18px;min-width:auto;padding:0 6px"><?= e($slaLabel) ?></span>
              </div>
              <div class="sla-bar-block">
                <div class="sla-bar-fill <?= $slaPct >= 100 ? 'breach' : ($slaPct >= 80 ? 'warning' : 'normal') ?>" style="width:<?= min(100, $slaPct) ?>%"></div>
              </div>
            </div>
          </td>
          <td class="col-status text-center"><span class="badge <?= ticketStatusClass($tk['status']) ?>"><?= e($tk['status']) ?></span></td>
          <td class="col-actions text-right">
            <a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Manage Ticket">
              <?= svgIcon('eye') ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
  var textColor = isDark ? '#A1A1AA' : '#52525B';
  var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

  // 1. Line Chart: Monthly Orders Created vs Completed (Clickable)
  var ctxTrend = document.getElementById('monthlyTrendChart');
  if (ctxTrend && typeof Chart !== 'undefined') {
    var months = <?= json_encode(array_column($monthlyTrend, 'month')) ?>;
    var createdData = <?= json_encode(array_column($monthlyTrend, 'created')) ?>;
    var completedData = <?= json_encode(array_column($monthlyTrend, 'completed')) ?>;

    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: months,
        datasets: [
          {
            label: 'Orders Created',
            data: createdData,
            borderColor: '#0F4C81',
            backgroundColor: 'rgba(15,76,129,0.15)',
            borderWidth: 2.5,
            tension: 0.35,
            fill: true,
            pointRadius: 4
          },
          {
            label: 'Orders Completed',
            data: completedData,
            borderColor: '#10B981',
            backgroundColor: 'rgba(16,185,129,0.05)',
            borderWidth: 2.5,
            tension: 0.35,
            fill: true,
            pointRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: function() { window.location.href = APP_URL + '/?page=orders'; },
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Inter', size: 11 } } }
        },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Inter', size: 11 } } },
          y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Inter', size: 11 }, stepSize: 1 } }
        }
      }
    });
  }

  // 2. Donut Chart: Order Status Distribution (Clickable Slices)
  var ctxDonut = document.getElementById('statusDonutChart');
  if (ctxDonut && typeof Chart !== 'undefined') {
    var labels = [];
    var data = [];
    var bgColors = [];
    var borderColors = [];
    <?php foreach ($chartColors as $status => $c):
      $cnt = $orderStats[$status] ?? 0;
      if ($cnt > 0): ?>
    labels.push('<?= e($status) ?>');
    data.push(<?= $cnt ?>);
    bgColors.push('<?= $c['bg'] ?>');
    borderColors.push('<?= $c['border'] ?>');
    <?php endif; endforeach; ?>

    new Chart(ctxDonut, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: bgColors,
          borderColor: borderColors,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: function(evt, activeEls) {
          if (activeEls.length > 0) {
            var idx = activeEls[0].index;
            var st = labels[idx];
            window.location.href = APP_URL + '/?page=orders&status=' + encodeURIComponent(st);
          } else {
            window.location.href = APP_URL + '/?page=orders';
          }
        },
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Inter', size: 11 } } }
        },
        cutout: '65%'
      }
    });
  }

  // 3. Pie Chart: Service Type Distribution (Clickable Slices)
  var ctxSvc = document.getElementById('serviceTypeChart');
  if (ctxSvc && typeof Chart !== 'undefined') {
    var svcLbls = <?= json_encode(array_keys($serviceTypeDist)) ?>;
    var svcVals = <?= json_encode(array_values($serviceTypeDist)) ?>;
    new Chart(ctxSvc, {
      type: 'pie',
      data: {
        labels: svcLbls,
        datasets: [{
          data: svcVals,
          backgroundColor: ['#0F4C81', '#06B6D4', '#10B981', '#F59E0B', '#1D70B8']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: function() { window.location.href = APP_URL + '/?page=active_services'; },
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Inter', size: 11 } } }
        }
      }
    });
  }

  // 4. Stacked Bar: Network Health (Clickable)
  var ctxNH = document.getElementById('networkHealthChart');
  if (ctxNH && typeof Chart !== 'undefined') {
    new Chart(ctxNH, {
      type: 'bar',
      data: {
        labels: ['Circuits Status'],
        datasets: [
          { label: 'Online', data: [<?= $networkHealth['Online'] ?>], backgroundColor: '#10B981' },
          { label: 'Degraded', data: [<?= $networkHealth['Degraded'] ?>], backgroundColor: '#F59E0B' },
          { label: 'Offline', data: [<?= $networkHealth['Offline'] ?>], backgroundColor: '#EF4444' }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        onClick: function() { window.location.href = APP_URL + '/?page=active_services'; },
        scales: {
          x: { stacked: true, grid: { color: gridColor }, ticks: { color: textColor } },
          y: { stacked: true, grid: { color: gridColor }, ticks: { color: textColor } }
        },
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Inter', size: 11 } } }
        }
      }
    });
  }
});
</script>
