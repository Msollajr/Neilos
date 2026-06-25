<?php
// ============================================================
// Dashboard Controller
// ============================================================
requireLogin();

$db   = getDB();
$user = currentUser();
$pw   = partnerWhere('o');
$pwT  = partnerWhere('tt');

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

// Active services count
$pwS = partnerWhere('s');
$stmtSvc = $db->prepare("SELECT COUNT(*) FROM active_services s WHERE s.status = 'Active' AND {$pwS['condition']}");
$stmtSvc->execute($pwS['params']);
$activeServices = (int)$stmtSvc->fetchColumn();

// Open tickets
$stmtTk = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.status NOT IN ('Closed') AND {$pwT['condition']}");
$stmtTk->execute($pwT['params']);
$openTickets = (int)$stmtTk->fetchColumn();

// SLA-breached tickets
$stmtBreach = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.sla_status IN ('Breached','Critical Breach') AND tt.status NOT IN ('Closed') AND {$pwT['condition']}");
$stmtBreach->execute($pwT['params']);
$breachedTickets = (int)$stmtBreach->fetchColumn();

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
