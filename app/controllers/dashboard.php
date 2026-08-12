<?php
// ============================================================
// Neilos Portal — Executive Telecom/ISP Operations Dashboard Controller
// ============================================================
requireLogin();

$db   = getDB();
$user = currentUser();

if (isContractorUser()) {
    header('Location: ' . APP_URL . '/?page=contractor');
    exit;
}

$pw   = partnerWhere('o');
$pwT  = partnerWhere('tt');
$pwS  = partnerWhere('s');

// ---- Order stats ----
$orderStats = [];
$statuses = ['Feasibility Review', 'Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed', 'Not Feasible', 'Cancelled'];
foreach ($statuses as $s) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE o.status = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$s], $pw['params']));
    $orderStats[$s] = (int)$stmt->fetchColumn();
}
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM orders o WHERE {$pw['condition']}");
$stmtTotal->execute($pw['params']);
$totalOrders = (int)$stmtTotal->fetchColumn();

// Pending internal actions
$pendingBSA = $orderStats['Feasibility Review'] ?? 0;
$pendingUAT = $orderStats['UAT'] ?? 0;
$completedOrders = $orderStats['Closed'] ?? 0;
$cancelledOrders = ($orderStats['Cancelled'] ?? 0) + ($orderStats['Not Feasible'] ?? 0);
$pendingOrders = $totalOrders - ($completedOrders + $cancelledOrders);
if ($pendingOrders < 0) $pendingOrders = 0;

// Active services (Closed orders) count
$stmtSvc = $db->prepare("SELECT COUNT(*) FROM orders o WHERE o.status = 'Closed' AND {$pw['condition']}");
$stmtSvc->execute($pw['params']);
$activeServices = (int)$stmtSvc->fetchColumn();

// Executive counts
$stmtPartners = $db->query("SELECT COUNT(*) FROM partners WHERE status = 'Active' AND kyc_type = 'Partner'");
$totalPartners = $stmtPartners ? (int)$stmtPartners->fetchColumn() : 0;

$stmtCust = $db->prepare("SELECT COUNT(DISTINCT customer_name) FROM orders o WHERE {$pw['condition']}");
$stmtCust->execute($pw['params']);
$activeCustomers = (int)$stmtCust->fetchColumn();

// Dedicated Revenue Analysis (Source of Truth for Dashboard)
// Qualifying condition: status = 'Closed' AND billing_start_date IS NOT NULL AND billing_start_date <= CURDATE()
$revenueWhereCond = "o.status = 'Closed' AND o.billing_start_date IS NOT NULL AND o.billing_start_date <= CURRENT_DATE() AND {$pw['condition']}";

$revenueStmt = $db->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.service_type,
        o.fttx_package,
        o.aggregate_capacity,
        o.bandwidth,
        o.remote_hands_nrc_usd,
        o.base_nrc_usd,
        o.status,
        o.billing_start_date,
        o.closed_date,
        o.vat_on_nrc,
        o.vat_on_mrc,
        o.total_nrc_incl_vat,
        o.total_mrc_incl_vat,
        o.nrc_subtotal_usd,
        o.base_mrc,
        o.revised_mrc,
        o.management_approved_price,
        o.standard_nrc,
        o.revised_nrc
    FROM orders o
    WHERE {$revenueWhereCond}
    ORDER BY o.billing_start_date DESC, o.id DESC
");
$revenueStmt->execute($pw['params']);
$billingActiveOrders = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);

$totalNrcRevenue = 0.0;
$totalMrcRevenue = 0.0;
$totalVatAmount  = 0.0;
$detailedRevenueRows = [];

foreach ($billingActiveOrders as $ord) {
    // Single source of truth: calculate revenue using the order's Commercial Summary
    $comm = getOrderCommercialSummary($ord);

    // Determine Service Type display
    $stVal = !empty($ord['service_type']) ? $ord['service_type'] : '';
    if (!$stVal) {
        if (!empty($ord['fttx_package'])) {
            $stVal = 'FTTH';
        } elseif (!empty($ord['aggregate_capacity'])) {
            $stVal = 'Layer 2 ( last mile)';
        } elseif (!empty($ord['bandwidth'])) {
            $stVal = 'BIA (Broadband Internet Access)';
        } else {
            $stVal = 'Telecom Service';
        }
    }

    $nrcIncl  = $comm['total_nrc'];
    $mrcIncl  = $comm['total_mrc'];
    $ordVat   = $comm['vat_total'];
    $ordTotal = $comm['total_revenue'];

    $totalNrcRevenue += $nrcIncl;
    $totalMrcRevenue += $mrcIncl;
    $totalVatAmount  += $ordVat;

    $detailedRevenueRows[] = [
        'id'                 => $ord['id'],
        'order_number'       => $ord['order_number'],
        'customer_name'      => $ord['customer_name'],
        'service_type'       => $stVal,
        'billing_start_date' => $ord['billing_start_date'],
        'nrc'                => $nrcIncl,
        'mrc'                => $mrcIncl,
        'vat'                => $ordVat,
        'total_revenue'      => $ordTotal,
        'status'             => $ord['status']
    ];
}

$billingActiveCount   = count($detailedRevenueRows);
$totalCombinedRevenue = round($totalNrcRevenue + $totalMrcRevenue, 2);
$avgMrcPerOrder       = $billingActiveCount > 0 ? round($totalMrcRevenue / $billingActiveCount, 2) : 0.0;

// Revenue trend over time (last 6 months based on billing_start_date)
$revenueTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $mTime     = strtotime("-$i months");
    $mLabel    = date('M Y', $mTime);
    $mFirstDay = date('Y-m-01', $mTime);
    $mLastDay  = date('Y-m-t', $mTime);

    $mNrc = 0.0;
    $mMrc = 0.0;

    foreach ($detailedRevenueRows as $row) {
        $bDate = $row['billing_start_date'];
        if (!$bDate) continue;

        // NRC counted ONCE in the activation month when order becomes billing-active
        if ($bDate >= $mFirstDay && $bDate <= $mLastDay) {
            $mNrc += $row['nrc'];
        }

        // MRC counted as recurring monthly revenue after the billing start date
        if ($bDate <= $mLastDay) {
            $mMrc += $row['mrc'];
        }
    }

    $revenueTrend[] = [
        'month' => $mLabel,
        'nrc'   => round($mNrc, 2),
        'mrc'   => round($mMrc, 2),
        'total' => round($mNrc + $mMrc, 2)
    ];
}

// Single Source of Truth for Service Type Distribution & Network Health
$serviceTypeDist  = [];
$onlineCount      = 0;
$inProgressCount  = 0;
$notFeasibleCount = 0;

$allOrdersStmt = $db->prepare("SELECT id, service_type, fttx_package, aggregate_capacity, bandwidth, remote_hands_nrc_usd, base_nrc_usd, status FROM orders o WHERE {$pw['condition']}");
$allOrdersStmt->execute($pw['params']);
$allOrders = $allOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allOrders as $ord) {
    // 1. Service Type determination (Every order counted exactly once)
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
            $stVal = 'Unspecified';
        }
    }

    if (!isset($serviceTypeDist[$stVal])) {
        $serviceTypeDist[$stVal] = 0;
    }
    $serviceTypeDist[$stVal]++;

    // 2. Network & Service Health mapping (Every order counted exactly once)
    $st = $ord['status'] ?? '';
    if (in_array($st, ['Closed', 'Activated', 'Billing Triggered'])) {
        $onlineCount++;
    } elseif (in_array($st, ['Not Feasible', 'Cancelled'])) {
        $notFeasibleCount++;
    } else {
        $inProgressCount++;
    }
}

$networkHealth = [
    'Online (Closed)'          => $onlineCount,
    'In Progress / Pending'    => $inProgressCount,
    'Not Feasible / Cancelled' => $notFeasibleCount
];

// Monthly order trend (last 6 months)
$monthlyTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $mLabel = date('M Y', strtotime("-$i months"));
    $mStart = date('Y-m-01 00:00:00', strtotime("-$i months"));
    $mEnd   = date('Y-m-t 23:59:59', strtotime("-$i months"));
    
    $stmtCreated = $db->prepare("SELECT COUNT(*) FROM orders o WHERE created_at BETWEEN ? AND ? AND {$pw['condition']}");
    $stmtCreated->execute(array_merge([$mStart, $mEnd], $pw['params']));
    $cCnt = (int)$stmtCreated->fetchColumn();

    $stmtAct = $db->prepare("SELECT COUNT(*) FROM orders o WHERE status = 'Closed' AND updated_at BETWEEN ? AND ? AND {$pw['condition']}");
    $stmtAct->execute(array_merge([$mStart, $mEnd], $pw['params']));
    $aCnt = (int)$stmtAct->fetchColumn();

    $monthlyTrend[] = [
        'month' => $mLabel,
        'created' => $cCnt,
        'completed' => $aCnt
    ];
}

// Recent Activity Timeline (Order transitions only)
$activityTimeline = [];
try {
    $stmtActFeed = $db->prepare("
        SELECT 'order' as type, o.order_number as ref, o.customer_name as title, o.status as sub, o.created_at as event_time 
        FROM orders o WHERE {$pw['condition']} ORDER BY o.created_at DESC LIMIT 8
    ");
    $stmtActFeed->execute($pw['params']);
    $activityTimeline = $stmtActFeed->fetchAll();
} catch (Exception $e) {}

// Recent orders (last 8)
$recentStmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE {$pw['condition']} ORDER BY o.created_at DESC LIMIT 8");
$recentStmt->execute($pw['params']);
$recentOrders = $recentStmt->fetchAll();

$pageTitle = 'Dashboard';
$extraJs = 'dashboard';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/dashboard/index.php';
include APP_DIR . '/views/layout/footer.php';
