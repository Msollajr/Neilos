<?php
// ============================================================
// Neilos Portal — Executive Telecom/ISP Operations Dashboard View
// ============================================================

$activatedOrders = $orderStats['Closed'] ?? 0;
$submittedOrders = $totalOrders;

// Chart color mapping — dark blue & vibrant palette
$chartColors = [
  'Feasibility Review'       => ['bg' => '#F59E0B', 'border' => '#FCD34D'],
  'Await Commercial Approval' => ['bg' => '#8B5CF6', 'border' => '#A78BFA'],
  'Management Approval'       => ['bg' => '#EC4899', 'border' => '#F472B6'],
  'Pending SOF'              => ['bg' => '#3B82F6', 'border' => '#60A5FA'],
  'SOF Review'               => ['bg' => '#06B6D4', 'border' => '#22D3EE'],
  'Installation'             => ['bg' => '#0F4C81', 'border' => '#1D70B8'],
  'Testing'                  => ['bg' => '#14B8A6', 'border' => '#2DD4BF'],
  'UAT'                      => ['bg' => '#6366F1', 'border' => '#818CF8'],
  'Closed'                   => ['bg' => '#10B981', 'border' => '#34D399'],
  'Not Feasible'             => ['bg' => '#EF4444', 'border' => '#F87171'],
];

$pipelineSteps = ['Feasibility Review', 'Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed'];
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
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">
        <?= svgIcon('list') ?> Order Tracking
      </a>
      <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-secondary btn-sm">
        <?= svgIcon('users') ?> Contractor Jobs
      </a>
      <a href="<?= APP_URL ?>/?page=kyc" class="btn btn-secondary btn-sm">
        <?= svgIcon('document') ?> KYC Application
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

<!-- 7 Executive Summary Cards Grid (All Clickable Module Links) -->
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

  <!-- Closed Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Closed" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Closed Orders">
    <div class="stat-icon green"><?= svgIcon('server', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $completedOrders ?>">0</div>
      <div class="stat-label">Closed Orders</div>
      <div class="stat-change up">Billing Active</div>
    </div>
  </a>

  <!-- Pending Actions -->
  <a href="<?= APP_URL ?>/?page=orders&status=Feasibility Review" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Feasibility Review">
    <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $pendingBSA + $pendingUAT ?>">0</div>
      <div class="stat-label">Pending Actions</div>
      <div class="stat-change"><?= $pendingBSA ?> BSA · <?= $pendingUAT ?> UAT</div>
    </div>
  </a>

  <!-- In-Progress Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Installation" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view In-Progress Orders">
    <div class="stat-icon blue"><?= svgIcon('project', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= ($orderStats['Installation'] ?? 0) + ($orderStats['Testing'] ?? 0) + ($orderStats['UAT'] ?? 0) ?>">0</div>
      <div class="stat-label">In-Progress Orders</div>
      <div class="stat-change up">Fulfillment active</div>
    </div>
  </a>

  <!-- Not Feasible Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Not Feasible" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Not Feasible Orders">
    <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $cancelledOrders ?>">0</div>
      <div class="stat-label">Not Feasible</div>
      <div class="stat-change text-muted">Low churn rate</div>
    </div>
  </a>

  <!-- Active Customers -->
  <a href="<?= APP_URL ?>/?page=orders&status=Closed" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Active Customers">
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
</div>

<!-- ============================================================ -->
<!-- Dedicated Revenue Analysis Section (Single Source of Truth)   -->
<!-- ============================================================ -->
<div class="card mb-24" id="revenue-analysis-section">
  <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 16px;">
    <div>
      <div class="card-title" style="font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
        <?= svgIcon('dollar-sign', 22) ?> Dedicated Revenue Analysis
      </div>
      <div class="card-subtitle">Calculated strictly from Closed + Billing Active Orders (status = Closed and billing start date reached) using final Commercial Summary values.</div>
    </div>
  </div>
  <div class="card-body" style="padding: 20px;">
    
    <!-- 1. Revenue Summary KPI Cards -->
    <div style="margin-bottom: 24px;">
      <h3 style="font-size: 0.88rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;">1. Revenue Summary</h3>
      <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
        <!-- NRC Revenue -->
        <div class="stat-card" style="border-left: 4px solid #3B82F6; background: var(--surface-1);">
          <div class="stat-icon blue"><?= svgIcon('dollar', 22) ?></div>
          <div class="stat-info">
            <div class="stat-value" style="font-size: 1.45rem;">TZS <?= money($totalNrcRevenue) ?></div>
            <div class="stat-label" style="font-weight: 600; color: var(--text-primary);">NRC Revenue</div>
            <div class="stat-change text-muted font-sm">One-time revenue (Incl. 18% VAT)</div>
          </div>
        </div>

        <!-- MRC Revenue -->
        <div class="stat-card" style="border-left: 4px solid #10B981; background: var(--surface-1);">
          <div class="stat-icon green"><?= svgIcon('dollar-sign', 22) ?></div>
          <div class="stat-info">
            <div class="stat-value" style="font-size: 1.45rem;">TZS <?= money($totalMrcRevenue) ?></div>
            <div class="stat-label" style="font-weight: 600; color: var(--text-primary);">MRC Revenue</div>
            <div class="stat-change text-muted font-sm">Recurring monthly (Incl. 18% VAT)</div>
          </div>
        </div>

        <!-- Total Revenue -->
        <div class="stat-card" style="border-left: 4px solid #0F4C81; background: var(--surface-1);">
          <div class="stat-icon navy"><?= svgIcon('chart', 22) ?></div>
          <div class="stat-info">
            <div class="stat-value" style="font-size: 1.45rem; color: #0F4C81;">TZS <?= money($totalCombinedRevenue) ?></div>
            <div class="stat-label" style="font-weight: 600; color: var(--text-primary);">Total Revenue</div>
            <div class="stat-change up">Combined NRC + MRC (Incl. 18% VAT)</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. NRC / MRC Breakdown -->
    <div style="margin-bottom: 24px;">
      <h3 style="font-size: 0.88rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;">2. NRC / MRC Revenue Breakdown</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; background: var(--surface-2); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border);">
        <div>
          <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted);">Total NRC (Incl. VAT)</div>
          <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;">TZS <?= money($totalNrcRevenue) ?></div>
        </div>
        <div>
          <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted);">Total MRC (Incl. VAT)</div>
          <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;">TZS <?= money($totalMrcRevenue) ?></div>
        </div>
        <div>
          <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted);">Total Revenue</div>
          <div style="font-size: 1.15rem; font-weight: 700; color: #0F4C81; margin-top: 4px;">TZS <?= money($totalCombinedRevenue) ?></div>
        </div>
        <div>
          <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted);">Billing-Active Orders</div>
          <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;"><?= $billingActiveCount ?></div>
        </div>
      </div>
    </div>

    <!-- 3 & 4. Revenue Charts Grid Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 24px;">
      <!-- 3. Revenue Comparison Chart -->
      <div style="border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; background: var(--surface-1);">
        <div style="font-weight: 600; font-size: 0.92rem; margin-bottom: 2px;">3. Revenue Comparison (NRC vs MRC vs Total)</div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px;">Visual comparison matching summary KPI cards exactly</div>
        <div style="position: relative; height: 250px; width: 100%;">
          <canvas id="revenueComparisonChart"></canvas>
        </div>
      </div>

      <!-- 4. Revenue Trend Chart -->
      <div style="border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; background: var(--surface-1);">
        <div style="font-weight: 600; font-size: 0.92rem; margin-bottom: 2px;">4. Revenue Trend Over Time</div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px;">Billing activation date (NRC one-time, MRC recurring)</div>
        <div style="position: relative; height: 250px; width: 100%;">
          <canvas id="revenueTrendChart"></canvas>
        </div>
      </div>
    </div>

    <!-- 5. Detailed Revenue Table -->
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div>
          <h3 style="font-size: 0.88rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin: 0;">5. Detailed Revenue Table</h3>
          <div style="font-size: 0.78rem; color: var(--text-muted);">All Closed orders with billing start date reached</div>
        </div>
        <span class="badge badge-outline"><?= count($detailedRevenueRows) ?> billing-active order(s)</span>
      </div>
      <div class="table-responsive" style="border: 1px solid var(--border); border-radius: var(--radius-md);">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Service Type</th>
              <th>Billing Start Date</th>
              <th class="text-right">NRC (Incl. VAT)</th>
              <th class="text-right">MRC (Incl. VAT)</th>
              <th class="text-right">VAT (18%)</th>
              <th class="text-right">Total Revenue</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($detailedRevenueRows)): ?>
            <tr>
              <td colspan="9">
                <div class="empty-state p-16">
                  <div class="empty-state-title">No qualifying billing-active orders found</div>
                  <div class="empty-state-text">All financial values display TZS 0.00 until orders are Closed with active billing start dates.</div>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($detailedRevenueRows as $row): ?>
            <tr>
              <td>
                <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $row['id'] ?>" class="font-600" style="color:var(--accent)">
                  <?= e($row['order_number']) ?>
                </a>
              </td>
              <td><?= e($row['customer_name']) ?></td>
              <td><span class="badge badge-primary"><?= e($row['service_type']) ?></span></td>
              <td class="font-sm"><?= fmtDate($row['billing_start_date']) ?></td>
              <td class="text-right font-sm font-600">TZS <?= money($row['nrc']) ?></td>
              <td class="text-right font-sm font-600">TZS <?= money($row['mrc']) ?></td>
              <td class="text-right font-sm text-muted">TZS <?= money($row['vat']) ?></td>
              <td class="text-right font-sm font-700" style="color: #0F4C81;">TZS <?= money($row['total_revenue']) ?></td>
              <td class="text-center">
                <span class="badge badge-success"><?= e($row['status']) ?> · Active</span>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
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
      <div class="chart-container" style="position:relative;height:260px;width:100%;cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders'">
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
        <div class="card-subtitle"><?= $totalOrders ?> total orders by current stage</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="position:relative;height:260px;width:100%">
        <canvas id="statusDonutChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Responsive Charts Grid Row 2 -->
<div class="grid-dashboard mb-24">
  <!-- Pie Chart: Services Distribution by Type -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Service Type Distribution <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle"><?= $totalOrders ?> total orders grouped by service type</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="position:relative;height:260px;width:100%">
        <canvas id="serviceTypeChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Stacked Bar: Network & Service Health -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Network &amp; Service Health <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle"><?= $totalOrders ?> total orders across operational stages</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="position:relative;height:260px;width:100%">
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
              $st === 'Feasibility Review' ? 'search' :
              ($st === 'Await Commercial Approval' || $st === 'Awaiting Commercial Approval' ? 'dollar' :
              ($st === 'Management Approval' ? 'users' :
              ($st === 'Pending SOF' ? 'document' :
              ($st === 'SOF Review' ? 'edit' :
              ($st === 'Installation' ? 'project' :
              ($st === 'Testing' ? 'check' :
              ($st === 'UAT' ? 'check' : 'server'))))))), 14
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
          $targetUrl = APP_URL . '/?page=orders';
        ?>
        <a href="<?= $targetUrl ?>" class="timeline-item" style="text-decoration:none;color:inherit;display:block;cursor:pointer">
          <div class="timeline-dot success"></div>
          <div class="timeline-time"><?= timeAgo($act['event_time']) ?></div>
          <div class="timeline-label">
            Order #<?= e($act['ref']) ?>
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
          <td><?= e($ord['customer_name'] ?? $ord['end_user_name'] ?? '') ?></td>
          <?php
            $stVal = !empty($ord['service_type']) ? $ord['service_type'] : '';
            if (!$stVal) {
                if (!empty($ord['fttx_package'])) {
                    $stVal = 'FTTH';
                } elseif (!empty($ord['aggregate_capacity'])) {
                    $stVal = 'Layer 2 ( last mile)';
                } elseif (!empty($ord['bandwidth'])) {
                    $stVal = 'BIA (Broadband Internet Access)';
                } elseif ((float)($ord['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($ord['base_nrc_usd'] ?? 0) == 80000) {
                    $stVal = 'Remote Hands Only';
                } else {
                    $stVal = 'Not specified';
                }
            }
          ?>
          <td><span class="badge badge-primary"><?= e($stVal) ?></span></td>
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



<script>
document.addEventListener('DOMContentLoaded', function() {
  var textColor = '#52525B';
  var gridColor = 'rgba(0,0,0,0.06)';

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

  // Shared Plugin to render exact numerical counts ABOVE vertical column bars
  var barTopValuePlugin = {
    id: 'barTopValuePlugin',
    afterDraw: function(chart) {
      var ctx = chart.ctx;
      chart.data.datasets.forEach(function(dataset, i) {
        var meta = chart.getDatasetMeta(i);
        if (!meta.hidden) {
          meta.data.forEach(function(bar, index) {
            var val = dataset.data[index];
            if (val !== null && val !== undefined && val >= 0) {
              ctx.save();
              ctx.fillStyle = textColor || '#475569';
              ctx.font = '700 11px Inter, sans-serif';
              ctx.textAlign = 'center';
              ctx.textBaseline = 'bottom';
              ctx.fillText(val.toString(), bar.x, bar.y - 4);
              ctx.restore();
            }
          });
        }
      });
    }
  };

  // 1. Vertical Column Bar Chart: Order Status Distribution (Sorted Highest to Lowest)
  var ctxDonut = document.getElementById('statusDonutChart');
  if (ctxDonut && typeof Chart !== 'undefined') {
    <?php
    $sortedStatusStats = array_filter($orderStats, function($cnt) { return $cnt > 0; });
    arsort($sortedStatusStats);
    if (empty($sortedStatusStats)) {
        $sortedStatusStats = $orderStats;
        arsort($sortedStatusStats);
    }
    $statusLabels = array_keys($sortedStatusStats);
    $statusCounts = array_values($sortedStatusStats);

    $statusBgColors = [];
    foreach ($statusLabels as $sl) {
        $statusBgColors[] = $chartColors[$sl]['bg'] ?? '#0F4C81';
    }
    ?>

    var statusLabels = <?= json_encode($statusLabels) ?>;
    var statusCounts = <?= json_encode($statusCounts) ?>;
    var statusColors = <?= json_encode($statusBgColors) ?>;
    var maxStatusVal = Math.max.apply(null, statusCounts.concat([0]));

    new Chart(ctxDonut, {
      type: 'bar',
      data: {
        labels: statusLabels,
        datasets: [{
          label: 'Orders',
          data: statusCounts,
          backgroundColor: statusColors,
          borderRadius: { topLeft: 6, topRight: 6 },
          maxBarThickness: 45
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: function(evt, activeEls) {
          if (activeEls.length > 0) {
            var idx = activeEls[0].index;
            var st = statusLabels[idx];
            window.location.href = APP_URL + '/?page=orders&status=' + encodeURIComponent(st);
          } else {
            window.location.href = APP_URL + '/?page=orders';
          }
        },
        layout: {
          padding: { top: 22, left: 10, right: 10 }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: textColor,
              font: { family: 'Inter', size: 10, weight: '600' },
              maxRotation: 30,
              autoSkip: false
            }
          },
          y: {
            beginAtZero: true,
            max: maxStatusVal + Math.ceil(maxStatusVal * 0.25) + 1,
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              font: { family: 'Inter', size: 11 },
              precision: 0
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return ' ' + context.label + ': ' + context.raw + ' order(s)';
              }
            }
          }
        }
      },
      plugins: [barTopValuePlugin]
    });
  }

  // 2. Vertical Column Bar Chart: Service Type Distribution (Sorted Highest to Lowest)
  var ctxSvc = document.getElementById('serviceTypeChart');
  if (ctxSvc && typeof Chart !== 'undefined') {
    <?php
    $sortedSvcDist = $serviceTypeDist;
    arsort($sortedSvcDist);
    $svcLabels = array_keys($sortedSvcDist);
    $svcCounts = array_values($sortedSvcDist);
    ?>

    var svcLbls = <?= json_encode($svcLabels) ?>;
    var svcVals = <?= json_encode($svcCounts) ?>;
    var svcPalette = ['#0F4C81', '#06B6D4', '#10B981', '#F59E0B', '#1D70B8', '#8B5CF6', '#EC4899', '#6366F1'];

    var svcColors = svcLbls.map(function(_, i) {
      return svcPalette[i % svcPalette.length];
    });

    var maxSvcVal = Math.max.apply(null, svcVals.concat([0]));

    new Chart(ctxSvc, {
      type: 'bar',
      data: {
        labels: svcLbls,
        datasets: [{
          label: 'Orders',
          data: svcVals,
          backgroundColor: svcColors,
          borderRadius: { topLeft: 6, topRight: 6 },
          maxBarThickness: 50
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: function() { window.location.href = APP_URL + '/?page=orders'; },
        layout: {
          padding: { top: 22, left: 10, right: 10 }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: textColor,
              font: { family: 'Inter', size: 10, weight: '600' },
              maxRotation: 30,
              autoSkip: false
            }
          },
          y: {
            beginAtZero: true,
            max: maxSvcVal + Math.ceil(maxSvcVal * 0.25) + 1,
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              font: { family: 'Inter', size: 11 },
              precision: 0
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                var val = context.raw || 0;
                var tot = <?= (int)$totalOrders ?>;
                var pct = tot > 0 ? ((val / tot) * 100).toFixed(1) : '0.0';
                return ' ' + context.label + ': ' + val + ' order(s) (' + pct + '%)';
              }
            }
          }
        }
      },
      plugins: [barTopValuePlugin]
    });
  }

  // 3. Vertical Column Bar Chart: Network & Service Health (Dynamic mapping of operational stages)
  var ctxNH = document.getElementById('networkHealthChart');
  if (ctxNH && typeof Chart !== 'undefined') {
    var totOrders  = <?= (int)$totalOrders ?>;
    var onlineCnt  = <?= (int)($networkHealth['Online (Closed)'] ?? 0) ?>;
    var inProgCnt  = <?= (int)($networkHealth['In Progress / Pending'] ?? 0) ?>;
    var notFeasCnt = <?= (int)($networkHealth['Not Feasible / Cancelled'] ?? 0) ?>;

    var nhLabels = ['Online / Closed', 'In Progress / Pending', 'Not Feasible / Cancelled'];
    var nhCounts = [onlineCnt, inProgCnt, notFeasCnt];
    var nhColors = ['#10B981', '#3B82F6', '#EF4444'];
    var maxNhVal = Math.max.apply(null, nhCounts.concat([0]));

    new Chart(ctxNH, {
      type: 'bar',
      data: {
        labels: nhLabels,
        datasets: [{
          label: 'Orders',
          data: nhCounts,
          backgroundColor: nhColors,
          borderRadius: { topLeft: 6, topRight: 6 },
          maxBarThickness: 50
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: function() { window.location.href = APP_URL + '/?page=orders'; },
        layout: {
          padding: { top: 22, left: 10, right: 10 }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: textColor,
              font: { family: 'Inter', size: 11, weight: '600' },
              autoSkip: false
            }
          },
          y: {
            beginAtZero: true,
            max: maxNhVal + Math.ceil(maxNhVal * 0.25) + 1,
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              font: { family: 'Inter', size: 11 },
              precision: 0
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                var val = context.raw || 0;
                var pct = totOrders > 0 ? ((val / totOrders) * 100).toFixed(1) : '0.0';
                return ' ' + context.label + ': ' + val + ' order(s) (' + pct + '%)';
              }
            }
          }
        }
      },
      plugins: [barTopValuePlugin]
    });
  }

  // 5. Revenue Comparison Chart (Bar Chart: NRC vs MRC vs Total)
  var ctxRevComp = document.getElementById('revenueComparisonChart');
  if (ctxRevComp && typeof Chart !== 'undefined') {
    new Chart(ctxRevComp, {
      type: 'bar',
      data: {
        labels: ['NRC Revenue', 'MRC Revenue', 'Total Revenue'],
        datasets: [{
          label: 'Revenue (TZS Incl. VAT)',
          data: [<?= $totalNrcRevenue ?>, <?= $totalMrcRevenue ?>, <?= $totalCombinedRevenue ?>],
          backgroundColor: ['#3B82F6', '#10B981', '#0F4C81'],
          borderColor: ['#2563EB', '#059669', '#0A365C'],
          borderWidth: 1.5,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                var val = context.raw || 0;
                return context.dataset.label + ': TZS ' + val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              }
            }
          }
        },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Inter', size: 11, weight: '600' } } },
          y: { 
            grid: { color: gridColor }, 
            ticks: { 
              color: textColor, 
              font: { family: 'Inter', size: 11 },
              callback: function(value) { return 'TZS ' + value.toLocaleString(); }
            } 
          }
        }
      }
    });
  }

  // 6. Revenue Trend Chart (Bar + Line Chart over time)
  var ctxRevTrend = document.getElementById('revenueTrendChart');
  if (ctxRevTrend && typeof Chart !== 'undefined') {
    var trendMonths = <?= json_encode(array_column($revenueTrend, 'month')) ?>;
    var trendNrc    = <?= json_encode(array_column($revenueTrend, 'nrc')) ?>;
    var trendMrc    = <?= json_encode(array_column($revenueTrend, 'mrc')) ?>;
    var trendTotal  = <?= json_encode(array_column($revenueTrend, 'total')) ?>;

    new Chart(ctxRevTrend, {
      type: 'bar',
      data: {
        labels: trendMonths,
        datasets: [
          {
            type: 'bar',
            label: 'NRC (One-time)',
            data: trendNrc,
            backgroundColor: 'rgba(59, 130, 246, 0.85)',
            borderColor: '#2563EB',
            borderWidth: 1,
            borderRadius: 4,
            stack: 'combined'
          },
          {
            type: 'bar',
            label: 'MRC (Recurring)',
            data: trendMrc,
            backgroundColor: 'rgba(16, 185, 129, 0.85)',
            borderColor: '#059669',
            borderWidth: 1,
            borderRadius: 4,
            stack: 'combined'
          },
          {
            type: 'line',
            label: 'Total Revenue',
            data: trendTotal,
            borderColor: '#0F4C81',
            backgroundColor: 'transparent',
            borderWidth: 2.5,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#0F4C81'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Inter', size: 11 } } },
          tooltip: {
            callbacks: {
              label: function(context) {
                var val = context.raw || 0;
                return context.dataset.label + ': TZS ' + val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              }
            }
          }
        },
        scales: {
          x: { stacked: true, grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Inter', size: 11 } } },
          y: { 
            stacked: false, 
            grid: { color: gridColor }, 
            ticks: { 
              color: textColor, 
              font: { family: 'Inter', size: 11 },
              callback: function(value) { return 'TZS ' + value.toLocaleString(); }
            } 
          }
        }
      }
    });
  }
});
</script>
