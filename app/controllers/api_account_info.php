<?php
// ============================================================
// Account Information API Controller
// Returns JSON account details and availability checklist for Partners / Contractors
// ============================================================
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();

if (($_GET['action'] ?? '') === 'dashboard_metrics') {
    $stmt = $db->query("SELECT o.* FROM orders o WHERE o.status = 'Closed' AND o.billing_start_date IS NOT NULL AND o.billing_start_date <= CURRENT_DATE()");
    $closedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalNrc = 0.0;
    $totalMrc = 0.0;
    foreach ($closedOrders as $o) {
        $c = getOrderCommercialSummary($o);
        $totalNrc += $c['total_nrc'];
        $totalMrc += $c['total_mrc'];
    }
    $totalRev = round($totalNrc + $totalMrc, 2);

    $pipelineStmt = $db->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
    $pipelineRaw = $pipelineStmt->fetchAll(PDO::FETCH_ASSOC);
    $pipeline = [];
    foreach ($pipelineRaw as $p) {
        $pipeline[$p['status']] = (int)$p['cnt'];
    }

    echo json_encode([
        'success' => true,
        'metrics' => [
            'total_nrc'      => $totalNrc,
            'total_mrc'      => $totalMrc,
            'total_revenue'  => $totalRev,
            'billing_active' => count($closedOrders),
            'pipeline'       => $pipeline
        ]
    ]);
    exit;
}

$id   = (int)($_GET['id'] ?? 0);
$type = strtolower($_GET['type'] ?? 'partner');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid account ID']);
    exit;
}

if ($type === 'contractor') {
    $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status,
        pka.registered_name, pka.main_contact_name, pka.main_contact_phone, pka.main_contact_email,
        pka.ops_contact_name, pka.ops_contact_phone, pka.ops_contact_email,
        pka.tech_supervisor_name, pka.tech_supervisor_phone, pka.tech_supervisor_email,
        pka.escalation_contact_name, pka.escalation_contact_phone, pka.escalation_contact_email,
        pka.bank_name, pka.bank_branch, pka.bank_account_name, pka.bank_account_number,
        pka.bank_payment_terms, pka.service_regions,
        pka.cap_ftth_install, pka.cap_sme_install, pka.cap_enterprise_install,
        pka.cap_site_survey, pka.cap_maintenance, pka.cap_remote_support
        FROM partners p
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
        WHERE p.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Contractor account not found']);
        exit;
    }

    $checklist = ['Company Details', 'Contact Information', 'Registration Information'];
    if (!empty($row['bank_name']) || !empty($row['bank_account_number'])) {
        $checklist[] = 'Bank Details';
    }
    if (!empty($row['cap_ftth_install']) || !empty($row['cap_site_survey']) || !empty($row['cap_maintenance'])) {
        $checklist[] = 'Capabilities';
    }

    echo json_encode([
        'success' => true,
        'account_name' => $row['name'],
        'type' => 'Contractor',
        'checklist' => $checklist,
        'data' => $row
    ]);
    exit;
} else {
    // Partner lookup
    $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status,
        pka.registered_name, pka.auth_signatory_name, pka.auth_signatory_email,
        pka.finance_contact_name, pka.finance_contact_phone, pka.billing_email,
        pka.tech_contact_name, pka.bank_name, pka.bank_branch, pka.bank_account_name, pka.bank_account_number
        FROM partners p
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Partner'
        WHERE p.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Partner account not found']);
        exit;
    }

    $checklist = ['Company Details', 'Contact Information', 'Registration Information'];
    if (!empty($row['bank_name']) || !empty($row['bank_account_number'])) {
        $checklist[] = 'Bank Details';
    }

    echo json_encode([
        'success' => true,
        'account_name' => $row['name'],
        'type' => 'Partner',
        'checklist' => $checklist,
        'data' => $row
    ]);
    exit;
}
