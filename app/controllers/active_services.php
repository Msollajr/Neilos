<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// ------------------------------------------------------------------
// Detail view
// ------------------------------------------------------------------
if ($action === 'detail' || $_GET['page'] === 'service_detail') {
    $serviceId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('s');
    $stmt = $db->prepare("SELECT s.*, p.name as partner_name, u.full_name as kam_name FROM active_services s JOIN partners p ON s.partner_id = p.id LEFT JOIN users u ON s.kam_id = u.id WHERE s.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$serviceId], $pw['params']));
    $service = $stmt->fetch();
    if (!$service) { http_response_code(404); echo '<p style="padding:40px">Service not found.</p>'; exit; }

    // Count linked tickets
    $tkStmt = $db->prepare("SELECT COUNT(*) FROM trouble_tickets WHERE active_service_id = ?");
    $tkStmt->execute([$serviceId]);
    $ticketCount = (int)$tkStmt->fetchColumn();

    // Assets for this service
    $pwA = partnerWhere('a');
    $aStmt = $db->prepare("SELECT * FROM assets a WHERE a.active_service_id = ? AND {$pwA['condition']} ORDER BY a.created_at DESC");
    $aStmt->execute(array_merge([$serviceId], $pwA['params']));
    $assets = $aStmt->fetchAll();

    $pageTitle = 'Service ' . $service['service_id'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/active_services/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List
// ------------------------------------------------------------------
$pw = partnerWhere('s');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

$filterSearch = $_GET['q'] ?? '';
$filterType   = $_GET['service_type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterMon    = $_GET['monitoring_status'] ?? '';

if ($filterSearch) { $where .= " AND (s.service_id LIKE ? OR s.customer_name LIKE ? OR s.circuit_id LIKE ?)"; $pS = "%$filterSearch%"; $params[] = $pS; $params[] = $pS; $params[] = $pS; }
if ($filterType)   { $where .= " AND s.service_type = ?"; $params[] = $filterType; }
if ($filterStatus) { $where .= " AND s.status = ?"; $params[] = $filterStatus; }
if ($filterMon)    { $where .= " AND s.monitoring_status = ?"; $params[] = $filterMon; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM active_services s $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 20;
$pg  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT s.*, p.name as partner_name FROM active_services s JOIN partners p ON s.partner_id = p.id $where ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$services = $stmt->fetchAll();

// Ticket counts for each service
$svcIds = array_column($services, 'id');
$ticketCounts = [];
if (!empty($svcIds)) {
    $in = implode(',', array_fill(0, count($svcIds), '?'));
    $tcStmt = $db->prepare("SELECT active_service_id, COUNT(*) as cnt FROM trouble_tickets WHERE active_service_id IN ($in) GROUP BY active_service_id");
    $tcStmt->execute($svcIds);
    foreach ($tcStmt->fetchAll() as $row) {
        $ticketCounts[(int)$row['active_service_id']] = (int)$row['cnt'];
    }
}

$serviceTypes = ['FTTH','FTTB','DIA','Dedicated Layer 2','Remote Hands Only'];
$statusOptions = ['Active','Suspended','Terminated'];
$monitoringOptions = ['Online','Offline','Degraded','Unknown'];

$pageTitle = 'Active Services';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/active_services/list.php';
include APP_DIR . '/views/layout/footer.php';
