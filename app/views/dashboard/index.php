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

<!-- Date Filter Bar -->
<div class="card mb-24" style="background:var(--surface-1);border:1px solid var(--border)">
  <div class="card-body" style="padding:12px 18px">
    <form method="GET" action="<?= APP_URL ?>/" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <input type="hidden" name="page" value="dashboard">
      
      <!-- Preset Buttons -->
      <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        <span style="font-size:0.82rem;font-weight:600;color:var(--text-secondary);margin-right:4px">Period:</span>
        <a href="<?= APP_URL ?>/?page=dashboard&preset=all" class="btn btn-xs <?= ($preset === 'all') ? 'btn-primary' : 'btn-outline-secondary' ?>">All Time</a>
        <a href="<?= APP_URL ?>/?page=dashboard&preset=today" class="btn btn-xs <?= ($preset === 'today') ? 'btn-primary' : 'btn-outline-secondary' ?>">Today</a>
        <a href="<?= APP_URL ?>/?page=dashboard&preset=this_month" class="btn btn-xs <?= ($preset === 'this_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">This Month</a>
        <a href="<?= APP_URL ?>/?page=dashboard&preset=last_month" class="btn btn-xs <?= ($preset === 'last_month') ? 'btn-primary' : 'btn-outline-secondary' ?>">Last Month</a>
        <a href="<?= APP_URL ?>/?page=dashboard&preset=this_year" class="btn btn-xs <?= ($preset === 'this_year') ? 'btn-primary' : 'btn-outline-secondary' ?>">This Year</a>
      </div>

      <!-- Custom Date Inputs -->
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:4px">
          <label style="font-size:0.8rem;color:var(--text-secondary);margin:0">From:</label>
          <input type="date" name="start_date" class="form-control" style="padding:4px 8px;font-size:0.82rem;height:30px" value="<?= e($startDate) ?>">
        </div>
        <div style="display:flex;align-items:center;gap:4px">
          <label style="font-size:0.8rem;color:var(--text-secondary);margin:0">To:</label>
          <input type="date" name="end_date" class="form-control" style="padding:4px 8px;font-size:0.82rem;height:30px" value="<?= e($endDate) ?>">
        </div>
        <button type="submit" class="btn btn-secondary btn-xs" style="height:30px;padding:0 12px">Apply Filter</button>
        <?php if ($startDate || $endDate || $preset !== 'all'): ?>
        <a href="<?= APP_URL ?>/?page=dashboard" class="btn btn-outline btn-xs" style="height:30px;padding:0 8px" title="Reset date filter">✕ Reset</a>
        <?php endif; ?>
      </div>
    </form>
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
      <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">
        <?= svgIcon('list') ?> Order Tracking
      </a>
      <?php if (hasRole('BSA','Admin') || isAdmin()): ?>
      <a href="<?= APP_URL ?>/?page=ftth_bulk" class="btn btn-secondary btn-sm">
        <?= svgIcon('upload') ?> FTTH Bulk Upload
      </a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/?page=contractor" class="btn btn-secondary btn-sm">
        <?= svgIcon('users') ?> Contractor Jobs
      </a>
      <a href="<?= APP_URL ?>/?page=sla_tracking<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">
        <?= svgIcon('clock') ?> SLA Tracking
      </a>
      <a href="<?= APP_URL ?>/?page=kyc" class="btn btn-secondary btn-sm">
        <?= svgIcon('document') ?> KYC Application
      </a>
      <?php if (isAdmin()): ?>
      <a href="<?= APP_URL ?>/?page=settings" class="btn btn-secondary btn-sm">
        <?= svgIcon('settings') ?> Company Settings
      </a>
      <a href="<?= APP_URL ?>/?page=partners" class="btn btn-secondary btn-sm">
        <?= svgIcon('building') ?> Add Partner
      </a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/?page=reports" class="btn btn-outline btn-sm">
        <?= svgIcon('chart') ?> Operations Reports
      </a>
      <a href="?page=orders&export=csv<?= $filterQueryStr ?>" class="btn btn-outline btn-sm">
        <?= svgIcon('download') ?> Export Orders CSV
      </a>
    </div>
  </div>
</div>

<!-- 6 Executive Summary Cards Grid (All Clickable Module Links) -->
<div class="stats-grid mb-24" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr))">
  <!-- Total Service Orders -->
  <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view all Service Orders in selected period">
    <div class="stat-icon blue"><?= svgIcon('list', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalOrders ?>"><?= $totalOrders ?></div>
      <div class="stat-label">Total Orders</div>
      <div class="stat-change up"><?= $totalOrders ?> recorded</div>
    </div>
  </a>

  <!-- Closed Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Closed<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Closed Orders in selected period">
    <div class="stat-icon green"><?= svgIcon('server', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $completedOrders ?>"><?= $completedOrders ?></div>
      <div class="stat-label">Closed Orders</div>
      <div class="stat-change up">Billing Active</div>
    </div>
  </a>

  <!-- Pending Actions -->
  <a href="<?= APP_URL ?>/?page=orders&status=Feasibility+Review<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Feasibility Review">
    <div class="stat-icon yellow"><?= svgIcon('clock', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $pendingBSA + $pendingUAT ?>"><?= $pendingBSA + $pendingUAT ?></div>
      <div class="stat-label">Pending Actions</div>
      <div class="stat-change"><?= $pendingBSA ?> BSA · <?= $pendingUAT ?> UAT</div>
    </div>
  </a>

  <!-- In-Progress Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Installation<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view In-Progress Orders">
    <div class="stat-icon blue"><?= svgIcon('project', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $inProgressOrders ?>"><?= $inProgressOrders ?></div>
      <div class="stat-label">In-Progress Orders</div>
      <div class="stat-change up">Fulfillment active</div>
    </div>
  </a>

  <!-- Not Feasible Orders -->
  <a href="<?= APP_URL ?>/?page=orders&status=Not+Feasible<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Not Feasible Orders">
    <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $cancelledOrders ?>"><?= $cancelledOrders ?></div>
      <div class="stat-label">Not Feasible</div>
      <div class="stat-change text-muted"><?= $cancelledOrders ?> order(s)</div>
    </div>
  </a>

  <!-- Partners -->
  <a href="<?= APP_URL ?>/?page=partners" class="stat-card" style="text-decoration:none;color:inherit" title="Click to view Partners">
    <div class="stat-icon navy"><?= svgIcon('building', 22) ?></div>
    <div class="stat-info">
      <div class="stat-value" data-count="<?= $totalPartners ?>"><?= $totalPartners ?></div>
      <div class="stat-label">Registered Partners</div>
      <div class="stat-change up">Active partners</div>
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
      <div class="card-subtitle">Calculated strictly from Closed + Billing Active Orders in the selected period (status = Closed and billing start date reached).</div>
    </div>
  </div>
  <div class="card-body" style="padding: 20px;">
    
    <!-- 1. Revenue Summary KPI Cards -->
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

    <!-- 2. Revenue Breakdown by Service Type -->
    <div style="margin-top: 24px; border-top: 1px solid var(--border); padding-top: 20px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-weight: 700; font-size: 1.05rem; color: var(--text-primary);">Revenue by Service Type</span>
          <span class="badge badge-secondary" style="font-size: 0.75rem; padding: 4px 8px;"><?= count($serviceTypeRevenue) ?> Active Service <?= count($serviceTypeRevenue) === 1 ? 'Type' : 'Types' ?></span>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-secondary);">
          Total Active Billing Orders: <strong style="color: var(--text-primary);"><?= (int)$billingActiveCount ?></strong>
        </div>
      </div>

      <?php if (empty($serviceTypeRevenue)): ?>
        <div style="padding: 24px; text-align: center; background: var(--surface-2); border-radius: var(--radius-sm); border: 1px solid var(--border); color: var(--text-muted); font-size: 0.88rem;">
          No billing active orders recorded for the selected period.
        </div>
      <?php else: ?>
        <div class="table-responsive" style="overflow-x: auto; border: 1px solid var(--border); border-radius: var(--radius-sm);">
          <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
            <thead>
              <tr style="background: var(--surface-2); border-bottom: 1px solid var(--border); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">
                <th style="padding: 10px 14px; text-align: left;">Service Type</th>
                <th style="padding: 10px 14px; text-align: center;">Active Orders</th>
                <th style="padding: 10px 14px; text-align: right;">NRC Revenue (TZS)</th>
                <th style="padding: 10px 14px; text-align: right;">MRC Revenue (TZS)</th>
                <th style="padding: 10px 14px; text-align: right;">Total Revenue (TZS)</th>
                <th style="padding: 10px 14px; text-align: left; min-width: 140px;">Revenue Share</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($serviceTypeRevenue as $stName => $stData): ?>
                <?php
                  $sharePct = $totalCombinedRevenue > 0 ? round(($stData['total_revenue'] / $totalCombinedRevenue) * 100, 1) : 0;
                ?>
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s ease;">
                  <td style="padding: 12px 14px; font-weight: 600; color: var(--text-primary);">
                    <span class="badge badge-primary" style="font-size: 0.8rem; padding: 4px 8px;"><?= e($stName) ?></span>
                  </td>
                  <td style="padding: 12px 14px; text-align: center; font-weight: 600; color: var(--text-primary);">
                    <?= (int)$stData['order_count'] ?>
                  </td>
                  <td style="padding: 12px 14px; text-align: right; color: #2563EB; font-weight: 600; font-size: 0.9rem;">
                    <?= money($stData['nrc_revenue']) ?>
                  </td>
                  <td style="padding: 12px 14px; text-align: right; color: #059669; font-weight: 600; font-size: 0.9rem;">
                    <?= money($stData['mrc_revenue']) ?>
                  </td>
                  <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: var(--text-primary); font-size: 0.92rem;">
                    <?= money($stData['total_revenue']) ?>
                  </td>
                  <td style="padding: 12px 14px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="flex: 1; background: var(--surface-3); border-radius: 4px; height: 6px; overflow: hidden; min-width: 60px;">
                        <div style="height: 100%; width: <?= $sharePct ?>%; background: var(--accent);"></div>
                      </div>
                      <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); min-width: 40px; text-align: right;"><?= $sharePct ?>%</span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Responsive Charts Grid Row 1 -->
<div class="grid-dashboard mb-24">
  <!-- Line Chart: Monthly Orders Created vs Completed -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Order Volume &amp; Fulfillment Trend <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle">Orders created vs completed in active filter period</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="position:relative;height:260px;width:100%;cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>'">
        <?php if ($totalOrders === 0): ?>
        <div class="empty-state p-16" style="padding:40px 16px;text-align:center">
          <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary)">No data available for the selected period</div>
          <div class="empty-state-text" style="font-size:0.85rem;color:var(--text-muted)">No orders were created or completed in this date range.</div>
        </div>
        <?php else: ?>
        <canvas id="monthlyTrendChart"></canvas>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Column Bar Chart: Order Status Distribution -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Order Status Distribution <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle"><?= $totalOrders ?> total order(s) by current stage</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="position:relative;height:260px;width:100%">
        <?php if ($totalOrders === 0): ?>
        <div class="empty-state p-16" style="padding:40px 16px;text-align:center">
          <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary)">No data available for the selected period</div>
          <div class="empty-state-text" style="font-size:0.85rem;color:var(--text-muted)">No orders match the selected filter.</div>
        </div>
        <?php else: ?>
        <canvas id="statusDonutChart"></canvas>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Responsive Charts Grid Row 2: Service Type Distribution -->
<div class="mb-24">
  <!-- Bar Chart: Services Distribution by Type -->
  <div class="card chart-card">
    <div class="card-header" style="cursor:pointer" onclick="window.location.href='<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>'">
      <div>
        <div class="card-title" style="display:flex;align-items:center;gap:6px">
          Service Type Distribution <?= svgIcon('chevron-right', 14) ?>
        </div>
        <div class="card-subtitle"><?= $totalOrders ?> total order(s) grouped by service type</div>
      </div>
      <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body">
      <div class="chart-container" style="position:relative;height:260px;width:100%">
        <?php if ($totalOrders === 0 || empty($serviceTypeDist)): ?>
        <div class="empty-state p-16" style="padding:40px 16px;text-align:center">
          <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary)">No data available for the selected period</div>
          <div class="empty-state-text" style="font-size:0.85rem;color:var(--text-muted)">No services recorded in this date range.</div>
        </div>
        <?php else: ?>
        <canvas id="serviceTypeChart"></canvas>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- SLA Tracking & Delay Analytics Section (Single Filtered State) -->
<!-- ============================================================ -->
<div class="card mb-24" id="sla-analytics-section" style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
  <div class="card-header" style="border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--surface-1)">
    <div>
      <div class="card-title" style="font-size:1.25rem;font-weight:700;display:flex;align-items:center;gap:8px">
        <?= svgIcon('clock', 22) ?> SLA Tracking &amp; Delay Analytics
      </div>
      <div class="card-subtitle" style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px">
        <?= $slaTotalEvaluated ?> order(s) evaluated &middot; Lifecycle stage duration, compliance monitoring, and bottleneck analysis for the active filter period
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <a href="<?= APP_URL ?>/?page=sla_tracking<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
        <?= svgIcon('download', 14) ?> Full SLA Tracking &amp; Export
      </a>
    </div>
  </div>

  <div class="card-body" style="padding:20px">
    
    <!-- 1. Executive SLA KPI Cards -->
    <div class="stats-grid mb-24" style="grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px">
      <!-- SLA Compliance Rate -->
      <div class="stat-card" style="border-left:4px solid <?= $slaCompliancePct >= 90 ? '#10B981' : '#EF4444' ?>;background:var(--surface-1)">
        <div class="stat-icon <?= $slaCompliancePct >= 90 ? 'green' : 'red' ?>"><?= svgIcon('clock', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value"><?= $slaCompliancePct ?>%</div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Overall SLA Compliance</div>
          <div class="stat-change <?= $slaBreachedCount > 0 ? 'text-danger' : 'up' ?>">
            <?= $slaNonBreachedCount ?>/<?= $slaTotalEvaluated ?> compliant orders
          </div>
        </div>
      </div>

      <!-- Breached Orders (Actionable Drilldown) -->
      <a href="<?= APP_URL ?>/?page=orders&sla=breached<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit;border-left:4px solid #EF4444;background:var(--surface-1);cursor:pointer" title="Click to view breached orders">
        <div class="stat-icon red"><?= svgIcon('x-circle', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value"><?= $slaBreachedCount ?></div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Breached Orders</div>
          <div class="stat-change text-danger font-sm">
            <?= $slaTotalStageBreaches ?> stage breach(es) across <?= $slaOrdersWithBreaches ?> order(s)
          </div>
        </div>
      </a>

      <!-- Paused Orders (Actionable Drilldown) -->
      <a href="<?= APP_URL ?>/?page=orders&sla=paused<?= $filterQueryStr ?>" class="stat-card" style="text-decoration:none;color:inherit;border-left:4px solid #F59E0B;background:var(--surface-1);cursor:pointer" title="Click to view paused orders">
        <div class="stat-icon yellow"><?= svgIcon('pause-circle', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value"><?= $slaPausedCount ?></div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Currently Paused</div>
          <div class="stat-change text-muted font-sm">
            <?= $slaPausedCount > 0 ? 'Active customer/access blockers →' : 'No paused installations' ?>
          </div>
        </div>
      </a>

      <!-- Average Lifecycle Duration -->
      <div class="stat-card" style="border-left:4px solid #0F4C81;background:var(--surface-1)">
        <div class="stat-icon blue"><?= svgIcon('calendar', 22) ?></div>
        <div class="stat-info">
          <div class="stat-value" style="font-size:1.15rem"><?= $slaAvgDuration ?></div>
          <div class="stat-label" style="font-weight:600;color:var(--text-primary)">Avg. Duration</div>
          <div class="stat-change text-muted font-sm">
            <?= $slaCompletedCount > 0 ? 'Closed: ' . $slaAvgCompletedDuration : 'Active: ' . $slaAvgActiveDuration ?>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Two-Column SLA Detailed Breakdown: Stages & Bottlenecks -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:20px">
      
      <!-- Stage-by-Stage Performance Table -->
      <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
            <?= svgIcon('activity', 16) ?> Performance by Lifecycle Stage
          </div>
          <span class="badge badge-info" style="font-size:0.75rem">Target Matrix</span>
        </div>

        <?php if ($slaTotalEvaluated === 0): ?>
        <div class="empty-state p-16" style="padding:24px 12px;text-align:center">
          <div class="empty-state-title" style="font-weight:600;color:var(--text-secondary);font-size:0.9rem">No SLA orders in selected period</div>
          <div class="empty-state-text" style="font-size:0.8rem;color:var(--text-muted)">Stage metrics will appear once orders are placed within this date range.</div>
        </div>
        <?php else: ?>
        <div class="table-responsive" style="overflow-x:auto">
          <table style="width:100%;border-collapse:collapse;font-size:0.82rem">
            <thead>
              <tr style="border-bottom:1px solid var(--border);color:var(--text-secondary);text-align:left">
                <th style="padding:8px 6px;font-weight:600">STAGE (TARGET)</th>
                <th style="padding:8px 6px;text-align:center;font-weight:600">EVAL</th>
                <th style="padding:8px 6px;text-align:right;font-weight:600">AVG DURATION</th>
                <th style="padding:8px 6px;text-align:right;font-weight:600">AVG DELAY</th>
                <th style="padding:8px 6px;text-align:center;font-weight:600">SLA STATUS</th>
                <th style="padding:8px 6px;text-align:right;font-weight:600">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($slaStages as $stg): ?>
              <tr style="border-bottom:1px solid rgba(0,0,0,0.04)">
                <td style="padding:10px 6px;vertical-align:middle">
                  <div style="font-weight:600;color:var(--text-primary)"><?= e($stg['name']) ?></div>
                  <div style="font-size:0.72rem;color:var(--text-muted)">Target: <?= $stg['target_hours'] ?> hrs</div>
                </td>
                <td style="padding:10px 6px;text-align:center;vertical-align:middle">
                  <span style="font-weight:600"><?= $stg['evaluated'] ?></span>
                  <?php if ($stg['active'] > 0): ?>
                  <span class="badge badge-warning" style="font-size:0.68rem;padding:2px 4px" title="Active in stage"><?= $stg['active'] ?> act</span>
                  <?php endif; ?>
                </td>
                <td style="padding:10px 6px;text-align:right;vertical-align:middle;font-weight:500">
                  <?= $stg['avg_formatted'] ?>
                </td>
                <td style="padding:10px 6px;text-align:right;vertical-align:middle;font-weight:500;color:<?= $stg['breached'] > 0 ? '#EF4444' : 'var(--text-muted)' ?>">
                  <?= $stg['breached'] > 0 ? '+' . $stg['avg_delay_formatted'] : '—' ?>
                </td>
                <td style="padding:10px 6px;text-align:center;vertical-align:middle">
                  <?php if ($stg['breached'] > 0): ?>
                  <span class="badge badge-danger" style="font-size:0.72rem"><?= $stg['breached'] ?> breached</span>
                  <?php elseif ($stg['evaluated'] > 0): ?>
                  <span class="badge badge-success" style="font-size:0.72rem">100% OK</span>
                  <?php else: ?>
                  <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td style="padding:10px 6px;text-align:right;vertical-align:middle">
                  <?php 
                    $stageFilterStatus = match($stg['key']) {
                      'feasibility'  => 'Feasibility Review',
                      'commercial'   => 'Await Commercial Approval',
                      'sof'          => 'Pending SOF',
                      'installation' => 'Installation',
                      'testing_uat'  => 'UAT',
                      default        => ''
                    };
                  ?>
                  <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($stageFilterStatus) ?><?= $filterQueryStr ?>" class="btn btn-outline btn-xs" style="padding:2px 8px;font-size:0.75rem" title="View orders in <?= e($stg['name']) ?>">
                    View →
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- SLA Bottlenecks & Distribution Highlight -->
      <div style="display:flex;flex-direction:column;gap:16px">
        
        <!-- Bottlenecks Card -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:16px;flex:1">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary);display:flex;align-items:center;gap:6px">
              <?= svgIcon('alert-triangle', 16) ?> SLA Bottlenecks &amp; Delay Risks
            </div>
            <span class="badge <?= $slaTotalStageBreaches > 0 ? 'badge-danger' : 'badge-success' ?>" style="font-size:0.75rem">
              <?= $slaTotalStageBreaches > 0 ? $slaTotalStageBreaches . ' Stage Delay(s)' : 'On Track' ?>
            </span>
          </div>

          <?php 
          $hasBottlenecks = false;
          foreach ($slaBottlenecks as $bn) {
              if ($bn['breached'] > 0 || ($bn['active'] > 0 && $bn['avg_sec'] > ($bn['target_hours'] * 3600))) {
                  $hasBottlenecks = true;
                  break;
              }
          }
          ?>

          <?php if (!$hasBottlenecks): ?>
          <div style="background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:16px;display:flex;align-items:flex-start;gap:12px">
            <div style="color:#10B981;font-size:1.3rem;line-height:1">✓</div>
            <div>
              <div style="font-weight:600;color:#065F46;font-size:0.88rem">No SLA breaches in selected period</div>
              <div style="font-size:0.8rem;color:#047857;margin-top:2px">All fulfillment stages are operating within targeted response and delivery times.</div>
            </div>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($slaBottlenecks as $bn): 
              if ($bn['breached'] === 0 && ($bn['active'] === 0 || $bn['avg_sec'] <= ($bn['target_hours'] * 3600))) continue;
              $stageFilterStatus = match($bn['key']) {
                'feasibility'  => 'Feasibility Review',
                'commercial'   => 'Await Commercial Approval',
                'sof'          => 'Pending SOF',
                'installation' => 'Installation',
                'testing_uat'  => 'UAT',
                default        => ''
              };
            ?>
            <div style="background:var(--surface-1);border:1px solid <?= $bn['breached'] > 0 ? '#EF4444' : '#F59E0B' ?>;border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between">
              <div>
                <div style="font-weight:600;font-size:0.85rem;color:var(--text-primary)"><?= e($bn['name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)">
                  Avg Delay: <strong style="color:<?= $bn['breached'] > 0 ? '#EF4444' : '#D97706' ?>"><?= $bn['breached'] > 0 ? '+' . $bn['avg_delay_formatted'] : 'On Track' ?></strong> &middot; Target: <?= $bn['target_hours'] ?>h
                </div>
              </div>
              <div style="text-align:right">
                <?php if ($bn['breached'] > 0): ?>
                <span class="badge badge-danger" style="font-size:0.75rem"><?= $bn['breached'] ?>/<?= $bn['evaluated'] ?> Breached (<?= $bn['breach_pct'] ?>%)</span>
                <?php else: ?>
                <span class="badge badge-warning" style="font-size:0.75rem">Attention</span>
                <?php endif; ?>
                <div style="margin-top:4px">
                  <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($stageFilterStatus) ?><?= $filterQueryStr ?>" style="font-size:0.72rem;color:var(--primary);font-weight:600;text-decoration:none">
                    Investigate →
                  </a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- SLA Status Mutually Exclusive Distribution Breakdown -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:10px;padding:14px 16px">
          <div style="font-weight:600;font-size:0.85rem;color:var(--text-secondary);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
            <span>SLA Status Distribution (Mutually Exclusive)</span>
            <span>Total: <?= $slaTotalEvaluated ?></span>
          </div>
          
          <div style="display:flex;height:10px;border-radius:5px;overflow:hidden;background:#E5E7EB;margin-bottom:10px">
            <?php if ($slaTotalEvaluated > 0): 
              $pWithin = round(($slaWithinCount / $slaTotalEvaluated) * 100);
              $pAtRisk = round(($slaAtRiskCount / $slaTotalEvaluated) * 100);
              $pBreach = round(($slaBreachedCount / $slaTotalEvaluated) * 100);
              $pPaused = round(($slaPausedCount / $slaTotalEvaluated) * 100);
            ?>
              <div style="width:<?= $pWithin ?>%;background:#10B981" title="<?= $slaWithinCount ?> Within SLA (<?= $pWithin ?>%)"></div>
              <div style="width:<?= $pAtRisk ?>%;background:#3B82F6" title="<?= $slaAtRiskCount ?> At Risk (<?= $pAtRisk ?>%)"></div>
              <div style="width:<?= $pBreach ?>%;background:#EF4444" title="<?= $slaBreachedCount ?> Breached (<?= $pBreach ?>%)"></div>
              <div style="width:<?= $pPaused ?>%;background:#F59E0B" title="<?= $slaPausedCount ?> Paused (<?= $pPaused ?>%)"></div>
            <?php else: ?>
              <div style="width:100%;background:#D1D5DB"></div>
            <?php endif; ?>
          </div>

          <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--text-secondary);flex-wrap:wrap;gap:8px">
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block"></span>
              Within SLA: <strong style="color:var(--text-primary)"><?= $slaWithinCount ?></strong>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#3B82F6;display:inline-block"></span>
              At Risk: <strong style="color:var(--text-primary)"><?= $slaAtRiskCount ?></strong>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#EF4444;display:inline-block"></span>
              Breached: <strong style="color:var(--text-primary)"><?= $slaBreachedCount ?></strong>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
              Paused: <strong style="color:var(--text-primary)"><?= $slaPausedCount ?></strong>
            </span>
          </div>

          <div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--border);display:flex;justify-content:space-between;font-size:0.72rem;color:var(--text-muted)">
            <span>Active In-Flight: <strong style="color:var(--text-primary)"><?= $slaActiveCount ?></strong></span>
            <span>Completed / Closed: <strong style="color:var(--text-primary)"><?= $slaCompletedCount ?></strong></span>
            <span>Total Stage Breaches: <strong style="color:<?= $slaTotalStageBreaches > 0 ? '#EF4444' : 'var(--text-primary)' ?>"><?= $slaTotalStageBreaches ?></strong></span>
          </div>
        </div>

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
      <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">View Orders</a>
    </div>
    <div class="card-body" style="padding:16px">
      <div class="pipeline-flow">
        <?php foreach ($pipelineSteps as $st):
          $cnt = $orderStats[$st] ?? 0;
          $pct = $pipelineMax > 0 ? round(($cnt / $pipelineMax) * 100) : 0;
          $c = $chartColors[$st] ?? ['bg' => '#0F4C81', 'border' => '#0A365C'];
        ?>
        <a href="<?= APP_URL ?>/?page=orders&status=<?= urlencode($st) ?><?= $filterQueryStr ?>" class="pipeline-step" style="text-decoration:none;color:inherit;cursor:pointer" title="View <?= e($st) ?> orders">
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
              <div class="pipeline-step-fill" style="width:<?= $pct ?>%;background:<?= $c['bg'] ?>" data-width="<?= $pct ?>"></div>
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
        <div class="card-subtitle">Latest events in active filter period</div>
      </div>
    </div>
    <div class="card-body">
      <?php if (empty($activityTimeline)): ?>
      <div class="empty-state p-16">
        <div class="empty-state-title">No recent activity</div>
        <div class="empty-state-text">No operational events recorded for the selected period.</div>
      </div>
      <?php else: ?>
      <div class="timeline">
        <?php foreach ($activityTimeline as $act):
          $targetUrl = APP_URL . '/?page=orders' . $filterQueryStr;
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

<!-- Data Table: Recent Orders -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <div class="card-title">Recent Service Orders</div>
      <div class="card-subtitle">Orders submitted in the active filter period</div>
    </div>
    <a href="<?= APP_URL ?>/?page=orders<?= $filterQueryStr ?>" class="btn btn-secondary btn-sm">View All Filtered Orders</a>
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
        <tr><td colspan="7"><div class="empty-state"><div class="empty-state-title">No service orders found</div><div class="empty-state-text">No service orders match the selected filter.</div></div></td></tr>
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

  // 1. Line Chart: Orders Created vs Completed
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
            backgroundColor: 'rgba(15,76,129,0.12)',
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
        onClick: function() { window.location.href = APP_URL + '/?page=orders<?= $filterQueryStr ?>'; },
        plugins: {
          legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Inter', size: 11 } } }
        },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Inter', size: 11 } } },
          y: { 
            beginAtZero: true,
            grid: { color: gridColor }, 
            ticks: { color: textColor, font: { family: 'Inter', size: 11 }, precision: 0 } 
          }
        }
      }
    });
  }

  // 2. Vertical Column Bar Chart: Order Status Distribution
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
            window.location.href = APP_URL + '/?page=orders&status=' + encodeURIComponent(st) + '<?= $filterQueryStr ?>';
          } else {
            window.location.href = APP_URL + '/?page=orders<?= $filterQueryStr ?>';
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
            max: maxStatusVal > 0 ? maxStatusVal + Math.ceil(maxStatusVal * 0.25) + 1 : 5,
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

  // 3. Vertical Column Bar Chart: Service Type Distribution
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
        onClick: function() { window.location.href = APP_URL + '/?page=orders<?= $filterQueryStr ?>'; },
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
            max: maxSvcVal > 0 ? maxSvcVal + Math.ceil(maxSvcVal * 0.25) + 1 : 5,
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
});
</script>

