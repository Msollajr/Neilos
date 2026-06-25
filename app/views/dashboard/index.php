<?php
// Dashboard View
$activatedOrders = $orderStats['Activated'] ?? 0;
$submittedOrders = $orderStats['Submitted'] ?? 0;

// Chart color mapping
$chartColors = [
  'Submitted'          => ['bg' => '#6CABDD', 'border' => '#4A8FC7'],
  'Feasibility Review' => ['bg' => '#FDBB30', 'border' => '#E5A520'],
  'Approved'           => ['bg' => '#4CAF50', 'border' => '#388E3C'],
  'Provisioning'       => ['bg' => '#FF9800', 'border' => '#E68900'],
  'Testing'            => ['bg' => '#9C27B0', 'border' => '#7B1FA2'],
  'UAT'                => ['bg' => '#00BCD4', 'border' => '#0097A7'],
  'Activated'          => ['bg' => '#22C55E', 'border' => '#16A34A'],
  'Cancelled'          => ['bg' => '#EF4444', 'border' => '#DC2626'],
];

$pipelineSteps = ['Submitted', 'Feasibility Review', 'Approved', 'Provisioning', 'Testing', 'Activated'];
$totalInPipeline = 0;
foreach ($pipelineSteps as $s) { $totalInPipeline += $orderStats[$s] ?? 0; }
$pipelineMax = max(1, max(array_map(fn($s) => $orderStats[$s] ?? 0, $pipelineSteps)));
?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title">Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></div>
    <div class="page-subtitle"><?= date('l, d F Y') ?> · <?= e($user['role']) ?></div>
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

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><?= svgIcon('list', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalOrders ?>">0</div>
      <div class="stat-label">Total Orders</div>
      <div class="stat-change up">+<?= $submittedOrders ?> pending</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><?= svgIcon('server', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $activeServices ?>">0</div>
      <div class="stat-label">Active Services</div>
      <div class="stat-change up"><?= $activatedOrders ?> orders activated</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $pendingBSA + $pendingUAT ?>">0</div>
      <div class="stat-label">Pending Actions</div>
      <div class="stat-change"><?= $pendingBSA ?> BSA · <?= $pendingUAT ?> UAT</div>
    </div>
  </div>
  <div class="stat-card">
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
  </div>
</div>

<!-- Charts Row -->
<div class="grid-dashboard" style="margin-bottom:24px">

  <!-- Order Status Distribution (Donut) -->
  <div class="card chart-card">
    <div class="card-header">
      <div class="card-title">Order Status Distribution</div>
      <div class="card-subtitle">Current breakdown by stage</div>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="statusDonutChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Order Pipeline Summary -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Order Pipeline</div>
      <div class="card-subtitle"><?= $totalInPipeline ?> total orders in pipeline</div>
    </div>
    <div class="card-body" style="padding:16px">
      <div class="pipeline-flow">
        <?php foreach ($pipelineSteps as $st):
          $cnt = $orderStats[$st] ?? 0;
          $pct = $pipelineMax > 0 ? round(($cnt / $pipelineMax) * 100) : 0;
          $c = $chartColors[$st];
        ?>
        <div class="pipeline-step">
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
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var ctx = document.getElementById('statusDonutChart');
  if (ctx && typeof Chart !== 'undefined') {
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

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: bgColors,
          borderColor: borderColors,
          borderWidth: 2,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 14,
              usePointStyle: true,
              pointStyle: 'circle',
              font: { size: 11 }
            }
          },
          tooltip: {
            backgroundColor: '#1C2F5A',
            titleFont: { size: 12 },
            bodyFont: { size: 12 },
            padding: 10,
            cornerRadius: 8,
            callbacks: {
              label: function(context) {
                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                var pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                return ' ' + context.parsed + ' orders (' + pct + '%)';
              }
            }
          }
        },
        cutout: '65%',
        animation: {
          animateRotate: true,
          duration: 800
        }
      }
    });
  }
});
</script>

<!-- Recent Orders -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Recent Orders</div>
      <div class="card-subtitle">Latest service order activity</div>
    </div>
    <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View All</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Service</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentOrders)): ?>
        <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No orders yet</div><div class="empty-state-text">Submit your first service order.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($recentOrders as $ord): ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=order_detail&id=<?= $ord['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($ord['order_number']) ?></a></td>
          <td><?= e($ord['customer_name']) ?></td>
          <td><span class="badge badge-primary"><?= e($ord['service_type']) ?></span></td>
          <td><span class="badge <?= orderStatusClass($ord['status']) ?>"><?= e($ord['status']) ?></span></td>
          <td class="text-muted font-sm"><?= fmtDate($ord['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Trouble Tickets -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">Recent Trouble Tickets</div>
      <div class="card-subtitle">Open and in-progress tickets</div>
    </div>
    <a href="<?= APP_URL ?>/?page=tickets" class="btn btn-secondary btn-sm">View All</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ticket #</th>
          <th>Service ID</th>
          <th>Fault</th>
          <th>Severity</th>
          <th>Queue</th>
          <th>SLA</th>
          <th>Status</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentTickets)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-title">No open tickets</div><div class="empty-state-text">All services are operating normally.</div></div></td></tr>
        <?php else: ?>
        <?php foreach ($recentTickets as $tk): ?>
        <?php $slaPct = calculateSLAPct($tk); ?>
        <tr>
          <td><a href="<?= APP_URL ?>/?page=ticket_detail&id=<?= $tk['id'] ?>" class="font-600" style="color:var(--primary)"><?= e($tk['ticket_number']) ?></a></td>
          <td><?= e($tk['service_id']) ?></td>
          <td class="font-sm"><?= e($tk['fault_category']) ?></td>
          <td><span class="badge badge-<?= in_array($tk['severity'], ['Sev 1','Critical']) ? 'danger' : (in_array($tk['severity'], ['Sev 2','Standard']) ? 'warning' : 'secondary') ?>"><?= e($tk['severity']) ?></span></td>
          <td class="font-sm"><?= e($tk['current_queue']) ?></td>
          <td>
            <div style="font-size:.72rem;font-weight:600;color:<?= $slaPct >= 100 ? 'var(--danger)' : ($slaPct >= 80 ? 'var(--warning)' : 'var(--success)') ?>"><?= number_format($slaPct, 0) ?>%</div>
            <div class="sla-bar" style="width:80px">
              <div class="sla-bar-fill <?= $slaPct >= 100 ? 'breach' : ($slaPct >= 80 ? 'warning' : 'normal') ?>" style="width:<?= min(100, $slaPct) ?>%"></div>
            </div>
          </td>
          <td><span class="badge <?= ticketStatusClass($tk['status']) ?>"><?= e($tk['status']) ?></span></td>
          <td class="text-muted font-sm"><?= timeAgo($tk['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
