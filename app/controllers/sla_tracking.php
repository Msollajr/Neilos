<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// ------------------------------------------------------------------
// Date Range & Query Filtering
// ------------------------------------------------------------------
$pw = partnerWhere('o');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

$filterPreset = $_GET['preset'] ?? '';
$filterStart  = trim($_GET['start_date'] ?? '');
$filterEnd    = trim($_GET['end_date'] ?? '');

if ($filterPreset === 'today') {
    $filterStart = date('Y-m-d');
    $filterEnd   = date('Y-m-d');
} elseif ($filterPreset === 'this_month') {
    $filterStart = date('Y-m-01');
    $filterEnd   = date('Y-m-t');
} elseif ($filterPreset === 'last_month') {
    $filterStart = date('Y-m-01', strtotime('-1 month'));
    $filterEnd   = date('Y-m-t', strtotime('-1 month'));
} elseif ($filterPreset === 'this_year') {
    $filterStart = date('Y-01-01');
    $filterEnd   = date('Y-12-31');
}

if ($filterStart && $filterEnd) {
    $where .= " AND o.created_at BETWEEN ? AND ?";
    $params[] = $filterStart . ' 00:00:00';
    $params[] = $filterEnd . ' 23:59:59';
} elseif ($filterStart) {
    $where .= " AND o.created_at >= ?";
    $params[] = $filterStart . ' 00:00:00';
} elseif ($filterEnd) {
    $where .= " AND o.created_at <= ?";
    $params[] = $filterEnd . ' 23:59:59';
}

$filterSearch = $_GET['q'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$sortKey      = $_GET['sort'] ?? 'newest';

if ($filterSearch) { 
    $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ?)"; 
    $pS = "%$filterSearch%"; 
    $params[] = $pS; 
    $params[] = $pS; 
}
if ($filterStatus) { 
    $where .= " AND o.status = ?"; 
    $params[] = $filterStatus; 
}

// ------------------------------------------------------------------
// High-Level SLA Summary Metrics (Filtered)
// ------------------------------------------------------------------
$allMatchingStmt = $db->prepare("SELECT o.* FROM orders o $where");
$allMatchingStmt->execute($params);
$allMatchingOrders = $allMatchingStmt->fetchAll();

$slaAnalytics = computeComprehensiveSlaAnalytics($allMatchingOrders, $db);
$totalEvaluated         = $slaAnalytics['total_evaluated'];
$totalWithinSla         = $slaAnalytics['within_sla_count'];
$totalAtRisk            = $slaAnalytics['at_risk_count'];
$totalBreached          = $slaAnalytics['breached_count'];
$totalPaused            = $slaAnalytics['paused_count'];
$nonBreachedCount       = $slaAnalytics['non_breached_count'];
$slaOverallCompliance   = $slaAnalytics['compliance_pct'];
$avgDurationFormatted   = $slaAnalytics['avg_duration_formatted'];
$totalStageBreaches     = $slaAnalytics['total_stage_breaches'];
$ordersWithStageBreaches= $slaAnalytics['orders_with_stage_breaches'];
$completedCount         = $slaAnalytics['completed_count'];
$activeCount            = $slaAnalytics['active_count'];

// ------------------------------------------------------------------
// CSV Export
// ------------------------------------------------------------------
if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sla_tracking_' . date('Ymd_His') . '.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, [
        'Order #',
        'Customer',
        'Service Type',
        'Current Status',
        'Order SLA Status',
        'Created At',
        'SLA End / Closed Date',
        'Total Duration',
        'Paused Duration',
        'Feasibility Duration',
        'Feasibility SLA',
        'Commercial Approval Duration',
        'Commercial SLA',
        'SOF Execution Duration',
        'SOF SLA',
        'Installation Duration',
        'Installation SLA',
        'Testing/UAT Duration',
        'Testing/UAT SLA',
        'Total Stage Breaches'
    ]);

    foreach ($slaAnalytics['order_evaluations'] as $eval) {
        $stgs = $eval['stages'];
        fputcsv($f, [
            $eval['order_number'],
            $eval['customer_name'],
            $eval['service_type_display'] ?: '—',
            $eval['current_status'],
            $eval['order_sla_status'],
            $eval['created_at'],
            $eval['is_closed'] ? date('Y-m-d H:i:s', $eval['order_end_ts']) : 'Active (In-Flight)',
            $eval['formatted_total_duration'],
            $eval['paused_hours'] > 0 ? "{$eval['paused_hours']} hrs" : '0 hrs',
            $stgs['feasibility']['formatted_duration'],
            $stgs['feasibility']['status_label'],
            $stgs['commercial']['formatted_duration'],
            $stgs['commercial']['status_label'],
            $stgs['sof']['formatted_duration'],
            $stgs['sof']['status_label'],
            $stgs['installation']['formatted_duration'],
            $stgs['installation']['status_label'],
            $stgs['testing_uat']['formatted_duration'],
            $stgs['testing_uat']['status_label'],
            $eval['stage_breach_count']
        ]);
    }
    fclose($f);
    exit;
}

// ------------------------------------------------------------------
// Paginated Order Listing
// ------------------------------------------------------------------
$total = $totalEvaluated;
$limit = 25;
$pg  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$sqlOrder = "ORDER BY o.created_at DESC";
if ($sortKey === 'oldest') {
    $sqlOrder = "ORDER BY o.created_at ASC";
}

$stmt = $db->prepare("SELECT o.* FROM orders o $where $sqlOrder LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$slaData = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $tlSt = $db->prepare("SELECT id, order_id, status, changed_at, note FROM order_timeline WHERE order_id IN ($placeholders) ORDER BY changed_at ASC, id ASC");
    $tlSt->execute($orderIds);
    $timelines = [];
    foreach ($tlSt->fetchAll(PDO::FETCH_ASSOC) as $t) {
        $timelines[$t['order_id']][] = $t;
    }

    foreach ($orders as $o) {
        $tl = $timelines[$o['id']] ?? [];
        $eval = computeOrderTimelineStages($o, $tl);
        $slaData[] = $eval;
    }
}

if (in_array($sortKey, ['total_desc', 'total_asc', 'bsa_desc', 'bsa_asc', 'approved_desc', 'approved_asc', 'activated_desc', 'activated_asc'])) {
    usort($slaData, function($a, $b) use ($sortKey) {
        $keyMap = [
            'total_desc'      => ['effective_order_seconds', -1],
            'total_asc'       => ['effective_order_seconds', 1],
            'bsa_desc'        => ['dur_submitted_bsa', -1],
            'bsa_asc'         => ['dur_submitted_bsa', 1],
            'approved_desc'   => ['dur_bsa_approved', -1],
            'approved_asc'    => ['dur_bsa_approved', 1],
            'activated_desc'  => ['dur_approved_activated', -1],
            'activated_asc'   => ['dur_approved_activated', 1],
        ];
        [$field, $dir] = $keyMap[$sortKey];
        $valA = $a[$field] ?? -1;
        $valB = $b[$field] ?? -1;
        if ($valA === $valB) return 0;
        return ($valA < $valB ? -1 : 1) * $dir;
    });
}

$allStatuses = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed','Not Feasible','Cancelled'];

$pageTitle = 'SLA Tracking & Delay Analytics';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/sla_tracking/index.php';
include APP_DIR . '/views/layout/footer.php';

