<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

if ($action === 'export') {
    $pw = partnerWhere('o');
    $where  = "WHERE {$pw['condition']}";
    $params = $pw['params'];

    $filterSearch = $_GET['q'] ?? '';
    $filterStatus = $_GET['status'] ?? '';
    if ($filterSearch) { $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ?)"; $pS = "%$filterSearch%"; $params[] = $pS; $params[] = $pS; }
    if ($filterStatus) { $where .= " AND o.status = ?"; $params[] = $filterStatus; }

    $stmt = $db->prepare("SELECT o.id, o.order_number, o.customer_name, o.service_type, o.status, o.created_at, o.updated_at FROM orders o $where ORDER BY o.created_at DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $now = date('Y-m-d H:i:s');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sla_tracking.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Order #','Customer','Service Type','Status','Created At','Submitted→BSA Review (hrs)','BSA→Approved (hrs)','Approved→Activated (hrs)','Total Duration (hrs)']);

    foreach ($orders as $o) {
        $tlSt = $db->prepare("SELECT status, changed_at FROM order_timeline WHERE order_id = ? ORDER BY changed_at ASC");
        $tlSt->execute([$o['id']]);
        $tl = $tlSt->fetchAll();

        $submitted = $bsaStart = $approved = $activated = null;
        foreach ($tl as $t) {
            if ($t['status'] === 'Submitted') $submitted = strtotime($t['changed_at']);
            if (in_array($t['status'], ['Feasibility Review','Awaiting BSA Approval']) && !$bsaStart) $bsaStart = strtotime($t['changed_at']);
            if ($t['status'] === 'Approved') $approved = strtotime($t['changed_at']);
            if ($t['status'] === 'Activated') $activated = strtotime($t['changed_at']);
        }

        $end = $activated ?: strtotime($o['updated_at'] ?: $now);

        $t1 = $bsaStart && $submitted ? round(($bsaStart - $submitted) / 3600, 1) : '';
        $t2 = $approved && $bsaStart ? round(($approved - $bsaStart) / 3600, 1) : '';
        $t3 = $activated && $approved ? round(($activated - $approved) / 3600, 1) : '';
        $total = $end && $submitted ? round(($end - $submitted) / 3600, 1) : '';

        fputcsv($f, [$o['order_number'], $o['customer_name'], $o['service_type'], $o['status'], $o['created_at'], $t1, $t2, $t3, $total]);
    }
    fclose($f);
    exit;
}

$pw = partnerWhere('o');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

$filterSearch = $_GET['q'] ?? '';
$filterStatus = $_GET['status'] ?? '';

if ($filterSearch) { $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ?)"; $pS = "%$filterSearch%"; $params[] = $pS; $params[] = $pS; }
if ($filterStatus) { $where .= " AND o.status = ?"; $params[] = $filterStatus; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM orders o $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 25;
$pg  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT o.id, o.order_number, o.customer_name, o.service_type, o.status, o.created_at, o.updated_at FROM orders o $where ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$now = date('Y-m-d H:i:s');
$slaData = [];
foreach ($orders as $o) {
    $tlSt = $db->prepare("SELECT status, changed_at FROM order_timeline WHERE order_id = ? ORDER BY changed_at ASC");
    $tlSt->execute([$o['id']]);
    $tl = $tlSt->fetchAll();

    $submitted = $bsaStart = $approved = $activated = null;
    foreach ($tl as $t) {
        if ($t['status'] === 'Submitted') $submitted = strtotime($t['changed_at']);
        if (in_array($t['status'], ['Feasibility Review','Awaiting BSA Approval']) && !$bsaStart) $bsaStart = strtotime($t['changed_at']);
        if ($t['status'] === 'Approved') $approved = strtotime($t['changed_at']);
        if ($t['status'] === 'Activated') $activated = strtotime($t['changed_at']);
    }

    $end = $activated ?: strtotime($o['updated_at'] ?: $now);
    $o['submitted_ts'] = $submitted;
    $o['bsa_start_ts'] = $bsaStart;
    $o['approved_ts']  = $approved;
    $o['activated_ts'] = $activated;
    $o['end_ts'] = $end;
    $slaData[] = $o;
}

$allStatuses = ['Submitted','Feasibility Review','Awaiting BSA Approval','Awaiting Commercial Approval','Awaiting Management Approval','Approved','Provisioning','Installation','Testing','UAT','UAT - Awaiting Confirmation','Activated','Billing Triggered','Closed','Cancelled'];

$pageTitle = 'SLA Tracking';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/sla_tracking/index.php';
include APP_DIR . '/views/layout/footer.php';
