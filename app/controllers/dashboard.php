<?php
// ============================================================
// Neilos Portal — Executive Telecom/ISP Operations Dashboard Controller
// Single Source of Truth Global Filter Architecture
// ============================================================
requireLogin();

$db   = getDB();
$user = currentUser();

if (isContractorUser()) {
    header('Location: ' . APP_URL . '/?page=contractor');
    exit;
}

$pw = partnerWhere('o');

// ============================================================
// 1. Unified Global Dashboard Filter State
// ============================================================
$preset    = $_GET['preset'] ?? 'all';
$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');

if ($preset === 'today') {
    $startDate = date('Y-m-d');
    $endDate   = date('Y-m-d');
} elseif ($preset === 'this_month') {
    $startDate = date('Y-m-01');
    $endDate   = date('Y-m-t');
} elseif ($preset === 'last_month') {
    $startDate = date('Y-m-01', strtotime('-1 month'));
    $endDate   = date('Y-m-t', strtotime('-1 month'));
} elseif ($preset === 'this_year') {
    $startDate = date('Y-01-01');
    $endDate   = date('Y-12-31');
} elseif ($startDate || $endDate) {
    $preset = 'custom';
} else {
    $preset = 'all';
}

$dateWhere  = "";
$dateParams = [];
if ($startDate && $endDate) {
    $dateWhere  = " AND o.created_at BETWEEN ? AND ?";
    $dateParams = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
} elseif ($startDate) {
    $dateWhere  = " AND o.created_at >= ?";
    $dateParams = [$startDate . ' 00:00:00'];
} elseif ($endDate) {
    $dateWhere  = " AND o.created_at <= ?";
    $dateParams = [$endDate . ' 23:59:59'];
}

$filterQueryParams = [];
if ($preset !== 'all') {
    $filterQueryParams['preset'] = $preset;
}
if ($startDate) {
    $filterQueryParams['start_date'] = $startDate;
}
if ($endDate) {
    $filterQueryParams['end_date'] = $endDate;
}
$filterQueryStr = !empty($filterQueryParams) ? '&' . http_build_query($filterQueryParams) : '';

// ============================================================
// 2. Global Filtered Dataset Calculations
// ============================================================

// 2.1 Total Orders in Filtered Period
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM orders o WHERE {$pw['condition']} $dateWhere");
$stmtTotal->execute(array_merge($pw['params'], $dateParams));
$totalOrders = (int)$stmtTotal->fetchColumn();

// 2.2 Order Counts by Status in Filtered Period
$allStatuses = [
    'Feasibility Review',
    'Await Commercial Approval',
    'Management Approval',
    'Pending SOF',
    'SOF Review',
    'Installation',
    'Testing',
    'UAT',
    'Closed',
    'Not Feasible',
    'Cancelled'
];
$orderStats = array_fill_keys($allStatuses, 0);

$stGroupStmt = $db->prepare("SELECT o.status, COUNT(*) as cnt FROM orders o WHERE {$pw['condition']} $dateWhere GROUP BY o.status");
$stGroupStmt->execute(array_merge($pw['params'], $dateParams));
foreach ($stGroupStmt->fetchAll() as $row) {
    if (isset($orderStats[$row['status']])) {
        $orderStats[$row['status']] = (int)$row['cnt'];
    }
}

// 2.3 Executive Summary Metric Counts
$completedOrders  = $orderStats['Closed'] ?? 0;
$pendingBSA       = $orderStats['Feasibility Review'] ?? 0;
$pendingUAT       = $orderStats['UAT'] ?? 0;
$cancelledOrders  = ($orderStats['Cancelled'] ?? 0) + ($orderStats['Not Feasible'] ?? 0);
$inProgressOrders = ($orderStats['Installation'] ?? 0)
                  + ($orderStats['Testing'] ?? 0)
                  + ($orderStats['UAT'] ?? 0)
                  + ($orderStats['SOF Review'] ?? 0)
                  + ($orderStats['Pending SOF'] ?? 0)
                  + ($orderStats['Await Commercial Approval'] ?? 0)
                  + ($orderStats['Management Approval'] ?? 0);

// Unique Active Customers represented in filtered period
$stmtCust = $db->prepare("SELECT COUNT(DISTINCT o.customer_name) FROM orders o WHERE {$pw['condition']} $dateWhere");
$stmtCust->execute(array_merge($pw['params'], $dateParams));
$activeCustomers = (int)$stmtCust->fetchColumn();

// Registered Partners represented in filtered period (or total active if all-time)
if ($dateWhere === '') {
    $stmtPartners = $db->query("SELECT COUNT(*) FROM partners WHERE status = 'Active' AND kyc_type = 'Partner'");
    $totalPartners = $stmtPartners ? (int)$stmtPartners->fetchColumn() : 0;
} else {
    $stmtPartners = $db->prepare("SELECT COUNT(DISTINCT o.partner_id) FROM orders o WHERE {$pw['condition']} $dateWhere");
    $stmtPartners->execute(array_merge($pw['params'], $dateParams));
    $totalPartners = (int)$stmtPartners->fetchColumn();
}

// 2.4 SLA Health & Comprehensive Analytics in Filtered Period
$allFilteredOrdersStmt = $db->prepare("SELECT o.* FROM orders o WHERE {$pw['condition']} $dateWhere");
$allFilteredOrdersStmt->execute(array_merge($pw['params'], $dateParams));
$allFilteredOrders = $allFilteredOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

$slaAnalytics = computeComprehensiveSlaAnalytics($allFilteredOrders, $db);
$slaCompliancePct        = $slaAnalytics['compliance_pct'];
$slaWithinCount          = $slaAnalytics['within_sla_count'];
$slaAtRiskCount          = $slaAnalytics['at_risk_count'];
$slaBreachedCount        = $slaAnalytics['breached_count'];
$slaPausedCount          = $slaAnalytics['paused_count'];
$slaNonBreachedCount     = $slaAnalytics['non_breached_count'];
$slaActiveCount          = $slaAnalytics['active_count'];
$slaCompletedCount       = $slaAnalytics['completed_count'];
$slaTotalEvaluated       = $slaAnalytics['total_evaluated'];
$slaTotalStageBreaches   = $slaAnalytics['total_stage_breaches'];
$slaOrdersWithBreaches   = $slaAnalytics['orders_with_stage_breaches'];
$slaAvgDuration          = $slaAnalytics['avg_duration_formatted'];
$slaAvgCompletedDuration = $slaAnalytics['avg_completed_formatted'];
$slaAvgActiveDuration    = $slaAnalytics['avg_active_formatted'];
$slaStages               = $slaAnalytics['stages'];
$slaBottlenecks          = $slaAnalytics['bottlenecks'];



// ============================================================
// 3. Dedicated Revenue Analysis (Filtered Dataset)
// Qualifying: Closed + billing_start_date set & reached within filtered orders
// ============================================================
$revenueStmt = $db->prepare("
    SELECT o.* FROM orders o
    WHERE o.status = 'Closed'
      AND o.billing_start_date IS NOT NULL
      AND o.billing_start_date <= CURRENT_DATE()
      AND {$pw['condition']}
      $dateWhere
    ORDER BY o.billing_start_date DESC, o.id DESC
");
$revenueStmt->execute(array_merge($pw['params'], $dateParams));
$billingActiveOrders = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);

$totalNrcRevenue = 0.0;
$totalMrcRevenue = 0.0;
$totalVatAmount  = 0.0;
$serviceTypeRevenue = [];

foreach ($billingActiveOrders as $ord) {
    $comm = getOrderCommercialSummary($ord);
    $totalNrcRevenue += $comm['total_nrc'];
    $totalMrcRevenue += $comm['total_mrc'];
    $totalVatAmount  += $comm['vat_total'];

    $stVal = trim($ord['service_type'] ?? '');
    if ($stVal === '') {
        if (!empty($ord['fttx_package'])) {
            $stVal = 'FTTH';
        } elseif (!empty($ord['aggregate_capacity'])) {
            $stVal = 'Layer 2 ( last mile)';
        } elseif (!empty($ord['bandwidth'])) {
            $stVal = 'BIA (Broadband Internet Access)';
        } elseif ((float)($ord['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($ord['base_nrc_usd'] ?? 0) == 80000) {
            $stVal = 'Remote Hands Only';
        } else {
            $stVal = 'Other';
        }
    }

    if (!isset($serviceTypeRevenue[$stVal])) {
        $serviceTypeRevenue[$stVal] = [
            'service_type'   => $stVal,
            'order_count'    => 0,
            'nrc_revenue'    => 0.0,
            'mrc_revenue'    => 0.0,
            'total_revenue'  => 0.0,
            'vat_amount'     => 0.0
        ];
    }

    $serviceTypeRevenue[$stVal]['order_count']   += 1;
    $serviceTypeRevenue[$stVal]['nrc_revenue']   += $comm['total_nrc'];
    $serviceTypeRevenue[$stVal]['mrc_revenue']   += $comm['total_mrc'];
    $serviceTypeRevenue[$stVal]['total_revenue'] += $comm['total_revenue'];
    $serviceTypeRevenue[$stVal]['vat_amount']    += $comm['vat_total'];
}

// Sort by total revenue descending
uasort($serviceTypeRevenue, function($a, $b) {
    return $b['total_revenue'] <=> $a['total_revenue'];
});

$totalCombinedRevenue = round($totalNrcRevenue + $totalMrcRevenue, 2);
$billingActiveCount   = count($billingActiveOrders);

// ============================================================
// 4. Service Type Distribution (Filtered Dataset)
// ============================================================
$serviceTypeDist = [];
$allOrdersStmt = $db->prepare("SELECT id, service_type, fttx_package, aggregate_capacity, bandwidth, remote_hands_nrc_usd, base_nrc_usd, status FROM orders o WHERE {$pw['condition']} $dateWhere");
$allOrdersStmt->execute(array_merge($pw['params'], $dateParams));
$allOrders = $allOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allOrders as $ord) {
    $stVal = trim($ord['service_type'] ?? '');
    if ($stVal === '') {
        if (!empty($ord['fttx_package'])) {
            $stVal = 'FTTH';
        } elseif (!empty($ord['aggregate_capacity'])) {
            $stVal = 'Layer 2 ( last mile)';
        } elseif (!empty($ord['bandwidth'])) {
            $stVal = 'BIA (Broadband Internet Access)';
        } elseif ((float)($ord['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($ord['base_nrc_usd'] ?? 0) == 80000) {
            $stVal = 'Remote Hands Only';
        } else {
            $stVal = 'Other';
        }
    }
    $serviceTypeDist[$stVal] = ($serviceTypeDist[$stVal] ?? 0) + 1;
}

// ============================================================
// 5. Order Volume & Fulfillment Trend (Filtered Time Buckets)
// ============================================================
$monthlyTrend = [];

if ($preset === 'today') {
    // 6 time blocks for today
    $timeBlocks = [
        ['00:00:00', '03:59:59', '00:00 - 04:00'],
        ['04:00:00', '07:59:59', '04:00 - 08:00'],
        ['08:00:00', '11:59:59', '08:00 - 12:00'],
        ['12:00:00', '15:59:59', '12:00 - 16:00'],
        ['16:00:00', '19:59:59', '16:00 - 20:00'],
        ['20:00:00', '23:59:59', '20:00 - 23:59'],
    ];
    $todayDate = date('Y-m-d');
    foreach ($timeBlocks as $tb) {
        $bStart = "$todayDate {$tb[0]}";
        $bEnd   = "$todayDate {$tb[1]}";
        
        $stC = $db->prepare("SELECT COUNT(*) FROM orders o WHERE created_at BETWEEN ? AND ? AND {$pw['condition']}");
        $stC->execute(array_merge([$bStart, $bEnd], $pw['params']));
        $cCnt = (int)$stC->fetchColumn();

        $stA = $db->prepare("SELECT COUNT(*) FROM orders o WHERE status = 'Closed' AND (closed_date = ? OR updated_at BETWEEN ? AND ?) AND {$pw['condition']}");
        $stA->execute(array_merge([$todayDate, $bStart, $bEnd], $pw['params']));
        $aCnt = (int)$stA->fetchColumn();

        $monthlyTrend[] = [
            'month'     => $tb[2],
            'created'   => $cCnt,
            'completed' => $aCnt
        ];
    }
} elseif ($preset === 'this_month' || $preset === 'last_month' || ($startDate && $endDate && (strtotime($endDate) - strtotime($startDate)) <= 35 * 86400)) {
    // 4-5 interval blocks across the month/date range
    $startTs = strtotime($startDate ?: date('Y-m-01'));
    $endTs   = strtotime($endDate ?: date('Y-m-t'));
    $totalDays = max(1, (int)round(($endTs - $startTs) / 86400) + 1);
    $numBuckets = min(6, max(3, (int)ceil($totalDays / 6)));
    $daysPerBucket = max(1, (int)ceil($totalDays / $numBuckets));

    for ($b = 0; $b < $numBuckets; $b++) {
        $curStartTs = $startTs + ($b * $daysPerBucket * 86400);
        $curEndTs   = min($endTs, $curStartTs + (($daysPerBucket - 1) * 86400));
        if ($curStartTs > $endTs) break;

        $bStart = date('Y-m-d 00:00:00', $curStartTs);
        $bEnd   = date('Y-m-d 23:59:59', $curEndTs);
        $label  = date('d M', $curStartTs) . ($curStartTs !== $curEndTs ? ' - ' . date('d M', $curEndTs) : '');

        $stC = $db->prepare("SELECT COUNT(*) FROM orders o WHERE created_at BETWEEN ? AND ? AND {$pw['condition']}");
        $stC->execute(array_merge([$bStart, $bEnd], $pw['params']));
        $cCnt = (int)$stC->fetchColumn();

        $stA = $db->prepare("SELECT COUNT(*) FROM orders o WHERE status = 'Closed' AND (closed_date BETWEEN ? AND ? OR updated_at BETWEEN ? AND ?) AND {$pw['condition']}");
        $stA->execute(array_merge([date('Y-m-d', $curStartTs), date('Y-m-d', $curEndTs), $bStart, $bEnd], $pw['params']));
        $aCnt = (int)$stA->fetchColumn();

        $monthlyTrend[] = [
            'month'     => $label,
            'created'   => $cCnt,
            'completed' => $aCnt
        ];
    }
} elseif ($preset === 'this_year') {
    // 12 calendar months of current year
    $curYear = date('Y');
    for ($m = 1; $m <= 12; $m++) {
        $mStr   = str_pad($m, 2, '0', STR_PAD_LEFT);
        $mLabel = date('M', strtotime("$curYear-$mStr-01"));
        $mStart = "$curYear-$mStr-01 00:00:00";
        $mEnd   = date('Y-m-t 23:59:59', strtotime("$curYear-$mStr-01"));

        $stC = $db->prepare("SELECT COUNT(*) FROM orders o WHERE created_at BETWEEN ? AND ? AND {$pw['condition']}");
        $stC->execute(array_merge([$mStart, $mEnd], $pw['params']));
        $cCnt = (int)$stC->fetchColumn();

        $stA = $db->prepare("SELECT COUNT(*) FROM orders o WHERE status = 'Closed' AND (closed_date BETWEEN ? AND ? OR updated_at BETWEEN ? AND ?) AND {$pw['condition']}");
        $stA->execute(array_merge(["$curYear-$mStr-01", date('Y-m-t', strtotime("$curYear-$mStr-01")), $mStart, $mEnd], $pw['params']));
        $aCnt = (int)$stA->fetchColumn();

        $monthlyTrend[] = [
            'month'     => $mLabel,
            'created'   => $cCnt,
            'completed' => $aCnt
        ];
    }
} else {
    // All time / custom > 35 days: Last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $mLabel = date('M Y', strtotime("-$i months"));
        $mStart = date('Y-m-01 00:00:00', strtotime("-$i months"));
        $mEnd   = date('Y-m-t 23:59:59', strtotime("-$i months"));
        
        $stmtCreated = $db->prepare("SELECT COUNT(*) FROM orders o WHERE created_at BETWEEN ? AND ? AND {$pw['condition']}");
        $stmtCreated->execute(array_merge([$mStart, $mEnd], $pw['params']));
        $cCnt = (int)$stmtCreated->fetchColumn();

        $stmtAct = $db->prepare("SELECT COUNT(*) FROM orders o WHERE status = 'Closed' AND (closed_date BETWEEN ? AND ? OR updated_at BETWEEN ? AND ?) AND {$pw['condition']}");
        $stmtAct->execute(array_merge([date('Y-m-01', strtotime("-$i months")), date('Y-m-t', strtotime("-$i months")), $mStart, $mEnd], $pw['params']));
        $aCnt = (int)$stmtAct->fetchColumn();

        $monthlyTrend[] = [
            'month'     => $mLabel,
            'created'   => $cCnt,
            'completed' => $aCnt
        ];
    }
}

// ============================================================
// 6. Fulfillment Pipeline & Activity Timeline
// ============================================================
$pipelineSteps = [
    'Feasibility Review',
    'Await Commercial Approval',
    'Management Approval',
    'Pending SOF',
    'SOF Review',
    'Installation',
    'Testing',
    'UAT',
    'Closed'
];
$pipelineMax = !empty($orderStats) ? max(array_values($orderStats)) : 1;
if ($pipelineMax <= 0) $pipelineMax = 1;
$totalInPipeline = 0;
foreach ($pipelineSteps as $ps) {
    $totalInPipeline += ($orderStats[$ps] ?? 0);
}

// Recent Operational Activity (Order events in filtered period)
$activityTimeline = [];
try {
    $stmtActFeed = $db->prepare("
        SELECT 'order' as type, o.order_number as ref, o.customer_name as title, o.status as sub, o.created_at as event_time 
        FROM orders o WHERE {$pw['condition']} $dateWhere ORDER BY o.created_at DESC LIMIT 8
    ");
    $stmtActFeed->execute(array_merge($pw['params'], $dateParams));
    $activityTimeline = $stmtActFeed->fetchAll();
} catch (Exception $e) {}

// Recent orders in filtered period (last 8)
$recentStmt = $db->prepare("
    SELECT o.*, p.name as partner_name 
    FROM orders o 
    JOIN partners p ON o.partner_id = p.id 
    WHERE {$pw['condition']} $dateWhere 
    ORDER BY o.created_at DESC 
    LIMIT 8
");
$recentStmt->execute(array_merge($pw['params'], $dateParams));
$recentOrders = $recentStmt->fetchAll();

$chartColors = [
    'Feasibility Review'        => ['bg' => '#3B82F6', 'border' => '#2563EB'],
    'Await Commercial Approval' => ['bg' => '#F59E0B', 'border' => '#D97706'],
    'Awaiting Commercial Approval' => ['bg' => '#F59E0B', 'border' => '#D97706'],
    'Management Approval'       => ['bg' => '#8B5CF6', 'border' => '#7C3AED'],
    'Pending SOF'               => ['bg' => '#06B6D4', 'border' => '#0891B2'],
    'SOF Review'                => ['bg' => '#6366F1', 'border' => '#4F46E5'],
    'Installation'              => ['bg' => '#10B981', 'border' => '#059669'],
    'Testing'                   => ['bg' => '#14B8A6', 'border' => '#0D9488'],
    'UAT'                       => ['bg' => '#84CC16', 'border' => '#65A30D'],
    'Closed'                    => ['bg' => '#0F4C81', 'border' => '#0A365C'],
    'Not Feasible'              => ['bg' => '#EF4444', 'border' => '#DC2626'],
    'Cancelled'                 => ['bg' => '#64748B', 'border' => '#475569'],
];

$pageTitle = 'Dashboard';
$extraJs = 'dashboard';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/dashboard/index.php';
include APP_DIR . '/views/layout/footer.php';
