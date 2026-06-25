<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'index';

$pw = partnerWhere('');

// ------------------------------------------------------------------
// Orders Report
// ------------------------------------------------------------------
if ($action === 'orders') {
    $cond = $pw['condition'] ? "WHERE {$pw['condition']}" : '';
    $sql = "SELECT o.order_number, o.customer_name, o.customer_location, o.service_type, o.status, o.assigned_kam_name, p.name as partner_name, o.created_at, o.updated_at FROM orders o JOIN partners p ON o.partner_id = p.id {$cond} ORDER BY o.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($pw['params']);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders_report.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Order #','Customer','Location','Service Type','Status','KAM','Partner','Created At','Updated At']);
    foreach ($rows as $r) fputcsv($f, $r);
    fclose($f);
    exit;
}

// ------------------------------------------------------------------
// Order SLA Report
// ------------------------------------------------------------------
if ($action === 'order_sla') {
    $cond = $pw['condition'] ? "WHERE {$pw['condition']}" : '';
    $sql = "SELECT o.id, o.order_number, o.customer_name, o.service_type, o.status, o.created_at FROM orders o {$cond} ORDER BY o.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($pw['params']);
    $orders = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="order_sla_report.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Order #','Customer','Service Type','Status','Created','Submitted→BSA (hrs)','BSA→Approved (hrs)','Approved→Activated (hrs)','Total (hrs)']);

    $now = date('Y-m-d H:i:s');
    foreach ($orders as $o) {
        $tl = $db->prepare("SELECT status, changed_at FROM order_timeline WHERE order_id = ? ORDER BY changed_at ASC");
        $tl->execute([$o['id']]);
        $tlRows = $tl->fetchAll();

        $submitted = $bsaStart = $approved = $activated = null;
        foreach ($tlRows as $t) {
            if ($t['status'] === 'Submitted') $submitted = strtotime($t['changed_at']);
            if (in_array($t['status'], ['Feasibility Review','Awaiting BSA Approval']) && !$bsaStart) $bsaStart = strtotime($t['changed_at']);
            if ($t['status'] === 'Approved') $approved = strtotime($t['changed_at']);
            if ($t['status'] === 'Activated') $activated = strtotime($t['changed_at']);
        }
        $end = $activated ?: strtotime($now);
        $t1 = $bsaStart && $submitted ? round(($bsaStart - $submitted) / 3600, 1) : '';
        $t2 = $approved && $bsaStart ? round(($approved - $bsaStart) / 3600, 1) : '';
        $t3 = $activated && $approved ? round(($activated - $approved) / 3600, 1) : '';
        $total = $end && $submitted ? round(($end - $submitted) / 3600, 1) : '';
        fputcsv($f, [$o['order_number'], $o['customer_name'], $o['service_type'], $o['status'], $o['created_at'], $t1, $t2, $t3, $total]);
    }
    fclose($f);
    exit;
}

// ------------------------------------------------------------------
// KYC Report
// ------------------------------------------------------------------
if ($action === 'kyc') {
    $cond = $pw['condition'] ? "WHERE k.{$pw['condition']}" : '';
    $sql = "SELECT k.id, p.name as partner_name, k.registered_name, k.trading_name, k.partner_type, k.status, k.submitted_at, k.reviewed_at, k.review_notes FROM partner_kyc_applications k JOIN partners p ON k.partner_id = p.id {$cond} ORDER BY k.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($pw['params']);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kyc_report.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['KYC ID','Partner','Registered Name','Trading Name','Partner Type','Status','Submitted At','Reviewed At','Review Notes']);
    foreach ($rows as $r) fputcsv($f, $r);
    fclose($f);
    exit;
}

// ------------------------------------------------------------------
// Tickets Report
// ------------------------------------------------------------------
if ($action === 'tickets') {
    $cond = $pw['condition'] ? "WHERE {$pw['condition']}" : '';
    $sql = "SELECT tt.ticket_number, tt.service_id, tt.customer_name, tt.fault_category, tt.severity, tt.current_queue, tt.sla_status, tt.status, p.name as partner_name, tt.opened_by_type, tt.created_at, tt.updated_at FROM trouble_tickets tt JOIN partners p ON tt.partner_id = p.id {$cond} ORDER BY tt.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($pw['params']);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tickets_report.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Ticket #','Service ID','Customer','Fault Category','Severity','Queue','SLA Status','Status','Partner','Opened By','Created At','Updated At']);
    foreach ($rows as $r) fputcsv($f, $r);
    fclose($f);
    exit;
}

// ------------------------------------------------------------------
// Ticket SLA Report
// ------------------------------------------------------------------
if ($action === 'ticket_sla') {
    $cond = $pw['condition'] ? "WHERE {$pw['condition']}" : '';
    $sql = "SELECT tt.ticket_number, tt.service_id, tt.customer_name, tt.service_type, tt.severity, tt.response_time_mins, tt.resolution_time_mins, tt.sla_status, tt.sla_pct_consumed, tt.noc_resolution_time_mins, tt.customer_wait_time_mins, tt.status, tt.created_at, tt.resolved_at FROM trouble_tickets tt {$cond} ORDER BY tt.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($pw['params']);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ticket_sla_report.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Ticket #','Service ID','Customer','Service Type','Severity','Response Target (min)','Resolution Target (min)','SLA Status','SLA Consumed %','NOC Resolution (min)','Customer Wait (min)','Status','Created At','Resolved At']);
    foreach ($rows as $r) fputcsv($f, $r);
    fclose($f);
    exit;
}

// ------------------------------------------------------------------
// Report selection page
// ------------------------------------------------------------------
$pageTitle = 'Reports';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/reports/index.php';
include APP_DIR . '/views/layout/footer.php';
