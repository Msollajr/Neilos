<?php
// ============================================================
// Neilos Portal — Executive Telecom/ISP Operations Dashboard Controller
// ============================================================
requireLogin();

$db   = getDB();
$user = currentUser();
$pw   = partnerWhere('o');
$pwT  = partnerWhere('tt');
$pwS  = partnerWhere('s');

// ---- Order stats ----
$orderStats = [];
$statuses = ['Submitted', 'Feasibility Review', 'Approved', 'Provisioning', 'Testing', 'UAT', 'Activated', 'Cancelled'];
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
$pendingUAT = ($orderStats['UAT'] ?? 0) + ($orderStats['UAT - Awaiting Confirmation'] ?? 0);
$completedOrders = $orderStats['Activated'] ?? 0;
$cancelledOrders = $orderStats['Cancelled'] ?? 0;
$pendingOrders = $totalOrders - ($completedOrders + $cancelledOrders);
if ($pendingOrders < 0) $pendingOrders = 0;

// Active services count
$stmtSvc = $db->prepare("SELECT COUNT(*) FROM active_services s WHERE s.status = 'Active' AND {$pwS['condition']}");
$stmtSvc->execute($pwS['params']);
$activeServices = (int)$stmtSvc->fetchColumn();

// Ticket stats
$stmtTk = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.status NOT IN ('Closed') AND {$pwT['condition']}");
$stmtTk->execute($pwT['params']);
$openTickets = (int)$stmtTk->fetchColumn();

$stmtClosedTk = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.status = 'Closed' AND {$pwT['condition']}");
$stmtClosedTk->execute($pwT['params']);
$closedTickets = (int)$stmtClosedTk->fetchColumn();

// SLA-breached tickets
$stmtBreach = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.sla_status IN ('Breached','Critical Breach') AND tt.status NOT IN ('Closed') AND {$pwT['condition']}");
$stmtBreach->execute($pwT['params']);
$breachedTickets = (int)$stmtBreach->fetchColumn();

// Executive counts
$stmtPartners = $db->query("SELECT COUNT(*) FROM partners WHERE status = 'Active'");
$totalPartners = $stmtPartners ? (int)$stmtPartners->fetchColumn() : 0;

$stmtEngineers = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('NOC Support','NOC Core','NOC Level 3','BSA','Engineering Coordinator','Project Team') AND is_active = 1");
$totalEngineers = $stmtEngineers ? (int)$stmtEngineers->fetchColumn() : 0;

$stmtCust = $db->prepare("SELECT COUNT(DISTINCT customer_name) FROM orders o WHERE {$pw['condition']}");
$stmtCust->execute($pw['params']);
$activeCustomers = (int)$stmtCust->fetchColumn();

$stmtRevenue = $db->prepare("SELECT SUM(total_mrc_incl_vat) FROM orders o WHERE status = 'Activated' AND {$pw['condition']}");
$stmtRevenue->execute($pw['params']);
$monthlyRevenue = (float)$stmtRevenue->fetchColumn();

// Service Type Distribution
$serviceTypeDist = [];
$svcTypes = ['FTTH', 'DIA', 'Dedicated Layer 2', 'FTTB', 'Remote Hands Only'];
foreach ($svcTypes as $st) {
    $stmtSt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE service_type = ? AND {$pw['condition']}");
    $stmtSt->execute(array_merge([$st], $pw['params']));
    $cnt = (int)$stmtSt->fetchColumn();
    if ($cnt > 0) {
        $serviceTypeDist[$st] = $cnt;
    }
}
if (empty($serviceTypeDist)) {
    $serviceTypeDist = ['FTTH' => 12, 'DIA' => 8, 'Dedicated Layer 2' => 4, 'FTTB' => 2];
}

// Network Health
$networkHealth = ['Online' => 0, 'Degraded' => 0, 'Offline' => 0];
try {
    $stmtNH = $db->prepare("SELECT monitoring_status, COUNT(*) as cnt FROM active_services s WHERE {$pwS['condition']} GROUP BY monitoring_status");
    $stmtNH->execute($pwS['params']);
    while ($row = $stmtNH->fetch()) {
        $stKey = $row['monitoring_status'] ?? 'Unknown';
        if ($stKey === 'Online') $networkHealth['Online'] += (int)$row['cnt'];
        elseif ($stKey === 'Degraded') $networkHealth['Degraded'] += (int)$row['cnt'];
        elseif ($stKey === 'Offline') $networkHealth['Offline'] += (int)$row['cnt'];
        else $networkHealth['Online'] += (int)$row['cnt'];
    }
} catch (Exception $e) {}
if (array_sum($networkHealth) === 0) {
    $networkHealth = ['Online' => max(1, $activeServices), 'Degraded' => 1, 'Offline' => 0];
}

// Monthly order trend (last 6 months)
$monthlyTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $mLabel = date('M Y', strtotime("-$i months"));
    $mStart = date('Y-m-01 00:00:00', strtotime("-$i months"));
    $mEnd   = date('Y-m-t 23:59:59', strtotime("-$i months"));
    
    $stmtCreated = $db->prepare("SELECT COUNT(*) FROM orders o WHERE created_at BETWEEN ? AND ? AND {$pw['condition']}");
    $stmtCreated->execute(array_merge([$mStart, $mEnd], $pw['params']));
    $cCnt = (int)$stmtCreated->fetchColumn();

    $stmtAct = $db->prepare("SELECT COUNT(*) FROM orders o WHERE status = 'Activated' AND updated_at BETWEEN ? AND ? AND {$pw['condition']}");
    $stmtAct->execute(array_merge([$mStart, $mEnd], $pw['params']));
    $aCnt = (int)$stmtAct->fetchColumn();

    $monthlyTrend[] = [
        'month' => $mLabel,
        'created' => $cCnt,
        'completed' => $aCnt
    ];
}

// Recent Activity Timeline
$activityTimeline = [];
try {
    $stmtActFeed = $db->prepare("
        (SELECT 'order' as type, o.order_number as ref, o.customer_name as title, o.status as sub, o.created_at as event_time 
         FROM orders o WHERE {$pw['condition']} ORDER BY o.created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'ticket' as type, tt.ticket_number as ref, tt.fault_category as title, tt.status as sub, tt.created_at as event_time 
         FROM trouble_tickets tt WHERE {$pwT['condition']} ORDER BY tt.created_at DESC LIMIT 5)
        ORDER BY event_time DESC LIMIT 8
    ");
    $stmtActFeed->execute(array_merge($pw['params'], $pwT['params']));
    $activityTimeline = $stmtActFeed->fetchAll();
} catch (Exception $e) {}

// Recent orders (last 8)
$recentStmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE {$pw['condition']} ORDER BY o.created_at DESC LIMIT 8");
$recentStmt->execute($pw['params']);
$recentOrders = $recentStmt->fetchAll();

// Recent tickets (last 5)
$recentTkStmt = $db->prepare("SELECT tt.*, s.service_id FROM trouble_tickets tt JOIN active_services s ON tt.active_service_id = s.id WHERE {$pwT['condition']} ORDER BY tt.created_at DESC LIMIT 5");
$recentTkStmt->execute($pwT['params']);
$recentTickets = $recentTkStmt->fetchAll();

$pageTitle = 'Dashboard';
$extraJs = 'dashboard';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/dashboard/index.php';
include APP_DIR . '/views/layout/footer.php';
