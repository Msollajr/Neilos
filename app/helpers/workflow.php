<?php
// ============================================================
// Neilos Partner Portal — Workflow & Automated Status Evaluator
// ============================================================

/**
 * Ensure system_events table exists for real-time SSE event publishing.
 */
function initSystemEventsTable(): void {
    static $initialized = false;
    if ($initialized) return;
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS system_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            order_id INT UNSIGNED NULL,
            assignment_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            payload LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_order (order_id)
        ) ENGINE=InnoDB;");
        $initialized = true;
    } catch (Exception $e) {}
}

/**
 * Publish a real-time event to system_events for SSE streaming.
 */
function publishSystemEvent(string $eventType, ?int $orderId = null, ?array $extraData = null): void {
    initSystemEventsTable();
    try {
        $db = getDB();
        $user = currentUser();
        $userId = $user ? (int)$user['id'] : null;

        $payload = [
            'event_type' => $eventType,
            'order_id'   => $orderId,
            'timestamp'  => date('Y-m-d H:i:s'),
            'data'       => $extraData ?? []
        ];

        if ($orderId > 0) {
            $stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if ($order) {
                $comm = getOrderCommercialSummary($order);
                $payload['order_number']        = $order['order_number'];
                $payload['status']              = $order['status'];
                $payload['service_type']        = $order['service_type'];
                $payload['customer_name']       = $order['customer_name'];
                $payload['partner_name']        = $order['partner_name'];
                $payload['total_nrc']           = $comm['total_nrc'];
                $payload['total_mrc']           = $comm['total_mrc'];
                $payload['total_revenue']       = $comm['total_revenue'];
                $payload['billing_start_date']  = $order['billing_start_date'];
                $payload['is_billing_active']   = ($order['status'] === 'Closed' && !empty($order['billing_start_date']) && strtotime($order['billing_start_date']) <= time());
            }
        }

        $db->prepare("INSERT INTO system_events (event_type, order_id, user_id, payload) VALUES (?, ?, ?, ?)")
           ->execute([$eventType, $orderId, $userId, json_encode($payload)]);
    } catch (Exception $e) {}
}

/**
 * Single Source of Truth Workflow Status Evaluator.
 * Evaluates DB state, calculates required evidence vs uploaded evidence,
 * updates order status automatically, and publishes SSE real-time events.
 */
function evaluateAndSyncOrderStatus(int $orderId, string $triggerAction = ''): array {
    if (!$orderId) return ['status_changed' => false];

    initSystemEventsTable();
    $db = getDB();

    $stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return ['status_changed' => false];

    $currentStatus = $order['status'];
    $newStatus     = $currentStatus;

    // 1. Service Type Evidence Checklist Matching
    $rawSvc = trim($order['service_type'] ?? '');
    $svcTypes = [];
    if (!empty($rawSvc)) {
        $svcTypes[] = $rawSvc;
        if (stripos($rawSvc, 'FTTH') !== false) { $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'FTTB') !== false) { $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'FTTE') !== false) { $svcTypes[] = 'FTTE'; $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'DIA') !== false) { $svcTypes[] = 'DIA'; }
        if (stripos($rawSvc, 'BIA') !== false || stripos($rawSvc, 'Broadband') !== false) { $svcTypes[] = 'BIA (Broadband Internet Access)'; $svcTypes[] = 'BIA'; $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'Layer 2') !== false || stripos($rawSvc, 'Layer2') !== false || stripos($rawSvc, 'Ethernet') !== false) { $svcTypes[] = 'Layer 2 ( last mile)'; $svcTypes[] = 'Dedicated Layer 2'; $svcTypes[] = 'Layer 2 (last mile)'; }
        if (stripos($rawSvc, 'Remote Hands') !== false) { $svcTypes[] = 'Remote Hands Only'; $svcTypes[] = 'Remote Hands'; }
    }
    $svcTypes = array_values(array_unique(array_filter($svcTypes)));
    if (empty($svcTypes)) {
        $svcTypes = ['FTTH'];
    }

    $placeholders = implode(',', array_fill(0, count($svcTypes), '?'));
    $chkStmt = $db->prepare("
        SELECT ecc.evidence_type, ecc.is_mandatory, ecc.is_noc_conditional,
          (SELECT COUNT(*) FROM contractor_evidence ce 
           WHERE (ce.order_id = ?) 
             AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(ecc.evidence_type))) as uploaded_count,
          (SELECT COUNT(*) FROM order_documents od
           WHERE od.order_id = ?
             AND LOWER(TRIM(od.document_type)) = LOWER(TRIM(ecc.evidence_type))) as doc_uploaded_count
        FROM evidence_checklist_config ecc 
        WHERE ecc.service_type IN ($placeholders)
        ORDER BY ecc.id ASC
    ");
    $chkStmt->execute(array_merge([$orderId, $orderId], $svcTypes));
    $rawItems = $chkStmt->fetchAll();

    // Deduplicate checklist by evidence_type
    $uniqueChecklist = [];
    foreach ($rawItems as $item) {
        $key = strtolower(trim($item['evidence_type']));
        if (!isset($uniqueChecklist[$key])) {
            $uniqueChecklist[$key] = $item;
        }
    }

    $totalMandatory    = 0;
    $completedMandatory= 0;
    $totalItems        = count($uniqueChecklist);
    $totalUploadedItems= 0;
    $nocIpConfigured   = (bool)$order['noc_ip_configured'];

    foreach ($uniqueChecklist as $item) {
        $isUploaded = ($item['uploaded_count'] > 0 || $item['doc_uploaded_count'] > 0);
        if ($isUploaded) $totalUploadedItems++;

        // Skip conditional items if NOC hasn't configured IP yet
        if ($item['is_noc_conditional'] && !$nocIpConfigured) {
            continue;
        }

        if ($item['is_mandatory']) {
            $totalMandatory++;
            if ($isUploaded) $completedMandatory++;
        }
    }

    $allMandatoryComplete = ($totalMandatory > 0 && $completedMandatory === $totalMandatory);
    $hasMissingMandatory  = ($totalMandatory > 0 && $completedMandatory < $totalMandatory);

    // 2. Automated Workflow Transitions
    // Rule A: If all required evidence is uploaded and status is in installation lifecycle -> Transition to 'Testing'
    if ($allMandatoryComplete && in_array($currentStatus, ['Installation', 'Assigned', 'Evidence Pending', 'Pending Evidence', 'Job Completed'])) {
        $newStatus = 'Testing';
    }
    // Rule B: If mandatory evidence is deleted/missing and status was 'Testing' -> Revert to 'Installation'
    elseif ($hasMissingMandatory && $currentStatus === 'Testing' && !in_array($triggerAction, ['submit_completion', 'approve_testing'])) {
        $newStatus = 'Installation';
    }

    $statusChanged = ($newStatus !== $currentStatus);

    if ($statusChanged) {
        $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$newStatus, $orderId]);
        auditLog("Automated status transition for Order #{$order['order_number']}: {$currentStatus} -> {$newStatus} (Evidence: {$completedMandatory}/{$totalMandatory} mandatory complete)", 'orders', $orderId);
        $order['status'] = $newStatus;
    }

    // 3. Emit Real-Time System Event for SSE
    publishSystemEvent('order_status_sync', $orderId, [
        'trigger_action'        => $triggerAction,
        'old_status'            => $currentStatus,
        'new_status'            => $newStatus,
        'status_changed'        => $statusChanged,
        'total_items'           => $totalItems,
        'uploaded_items'        => $totalUploadedItems,
        'total_mandatory'       => $totalMandatory,
        'completed_mandatory'   => $completedMandatory,
        'all_mandatory_complete'=> $allMandatoryComplete
    ]);

    return [
        'order_id'              => $orderId,
        'status_changed'        => $statusChanged,
        'old_status'            => $currentStatus,
        'current_status'        => $newStatus,
        'completed_mandatory'   => $completedMandatory,
        'total_mandatory'       => $totalMandatory,
        'all_mandatory_complete'=> $allMandatoryComplete
    ];
}

// ============================================================
// SLA Pause / Resume Helpers
// ============================================================

/**
 * Pause the SLA clock for an order (called when contractor posts a blocker).
 * Idempotent — if already paused, does nothing.
 */
function pauseOrderSLA(int $orderId, int $userId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT sla_paused FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if (!$row || $row['sla_paused']) return; // already paused

        $db->prepare("UPDATE orders SET sla_paused = 1, sla_paused_at = NOW(), updated_at = NOW() WHERE id = ?")
           ->execute([$orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by)
            SELECT id, status, 'SLA paused — installation blocker posted.', ? FROM orders WHERE id = ?")
           ->execute([$userId, $orderId]);

        auditLog("SLA clock paused for order #$orderId due to blocker", 'orders', $orderId);
    } catch (Exception $e) {
        error_log('SLA pause error: ' . $e->getMessage());
    }
}

/**
 * Resume the SLA clock for an order (called on "Installation Resumed" update).
 * Accumulates elapsed paused time into sla_paused_hours.
 * Idempotent — if already running, does nothing.
 */
function resumeOrderSLA(int $orderId, int $userId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT sla_paused, sla_paused_at, sla_paused_hours FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if (!$row || !$row['sla_paused']) return; // already running

        $pausedSince = $row['sla_paused_at'] ? strtotime($row['sla_paused_at']) : time();
        $elapsedHours = round((time() - $pausedSince) / 3600, 2);
        $totalPausedHours = round((float)$row['sla_paused_hours'] + $elapsedHours, 2);

        $db->prepare("UPDATE orders SET
            sla_paused = 0, sla_paused_at = NULL,
            sla_paused_hours = ?, updated_at = NOW()
            WHERE id = ?")->execute([$totalPausedHours, $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by)
            SELECT id, status, CONCAT('SLA resumed — paused for ', ?, ' hours. Total paused: ', ?, ' hrs.'), ?
            FROM orders WHERE id = ?")->execute([$elapsedHours, $totalPausedHours, $userId, $orderId]);

        auditLog("SLA clock resumed for order #$orderId (paused {$elapsedHours}h, total paused: {$totalPausedHours}h)", 'orders', $orderId);
    } catch (Exception $e) {
        error_log('SLA resume error: ' . $e->getMessage());
    }
}

/**
 * Retrieve the authoritative active/approved Partner KYC record for a partner.
 * Prioritizes: Approved > Submitted for Approval > Under Review > Submitted > Draft.
 * Returns structured KYC contact & company details.
 */
function getAuthoritativePartnerKyc(PDO $db, int $partnerId): ?array {
    if (!$partnerId) return null;

    $stmt = $db->prepare("SELECT pka.*, p.name as partner_company_name, p.tin as partner_tin, p.registration_number as partner_reg_no
        FROM partner_kyc_applications pka
        JOIN partners p ON p.id = pka.partner_id
        WHERE pka.partner_id = ?
        ORDER BY 
            CASE pka.status 
                WHEN 'Approved' THEN 1 
                WHEN 'Submitted for Approval' THEN 2 
                WHEN 'Under Review' THEN 3 
                WHEN 'Submitted' THEN 4 
                WHEN 'Draft' THEN 5 
                ELSE 6 
            END,
            pka.updated_at DESC
        LIMIT 1");
    $stmt->execute([$partnerId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kyc) {
        $pStmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
        $pStmt->execute([$partnerId]);
        $partner = $pStmt->fetch(PDO::FETCH_ASSOC);
        if (!$partner) return null;

        return [
            'id'                     => null,
            'partner_id'             => $partnerId,
            'status'                 => 'Missing KYC',
            'company_name'           => trim($partner['name'] ?? ''),
            'tech_contact_name'      => '',
            'tech_contact_phone'     => '',
            'tech_contact_email'     => '',
            'billing_contact_name'   => '',
            'billing_contact_phone'  => '',
            'billing_contact_email'  => '',
            'auth_signatory_name'    => '',
            'auth_signatory_mobile'  => '',
            'auth_signatory_email'   => '',
            'is_complete'            => false,
            'missing_fields'         => ['Technical Contact Name', 'Technical Contact Phone', 'Technical Contact Email', 'Billing Contact Name', 'Billing Contact Phone', 'Billing Contact Email']
        ];
    }

    // 1. Company Name
    $companyName = trim(($kyc['registered_name'] ?? '') ?: (($kyc['trading_name'] ?? '') ?: (($kyc['partner_company_name'] ?? '') ?: '')));

    // 2. Technical Contact (tech_supervisor_* or tech_contact_*)
    $techName  = trim(($kyc['tech_supervisor_name'] ?? '') ?: (($kyc['tech_contact_name'] ?? '') ?: ''));
    $techPhone = trim(($kyc['tech_supervisor_phone'] ?? '') ?: (($kyc['tech_contact_mobile'] ?? '') ?: (($kyc['tech_contact_phone'] ?? '') ?: '')));
    $techEmail = trim(($kyc['tech_supervisor_email'] ?? '') ?: (($kyc['tech_contact_email'] ?? '') ?: ''));

    // 3. Billing Contact (billing_contact_* or finance_contact_*)
    $billingName  = trim(($kyc['billing_contact_name'] ?? '') ?: (($kyc['finance_contact_name'] ?? '') ?: ''));
    $billingPhone = trim(($kyc['billing_contact_phone'] ?? '') ?: (($kyc['finance_contact_mobile'] ?? '') ?: (($kyc['finance_contact_phone'] ?? '') ?: '')));
    $billingEmail = trim(($kyc['billing_contact_email'] ?? '') ?: (($kyc['finance_contact_email'] ?? '') ?: (($kyc['billing_email'] ?? '') ?: '')));

    // Validate completeness
    $missing = [];
    if (!$companyName)  $missing[] = 'Company Name';
    if (!$techName)     $missing[] = 'Technical Contact Name';
    if (!$techPhone)    $missing[] = 'Technical Contact Phone';
    if (!$techEmail)    $missing[] = 'Technical Contact Email';
    if (!$billingName)  $missing[] = 'Billing Contact Name';
    if (!$billingPhone) $missing[] = 'Billing Contact Phone';
    if (!$billingEmail) $missing[] = 'Billing Contact Email';

    return [
        'id'                     => (int)$kyc['id'],
        'partner_id'             => (int)$kyc['partner_id'],
        'status'                 => $kyc['status'],
        'company_name'           => $companyName,
        'tech_contact_name'      => $techName,
        'tech_contact_phone'     => $techPhone,
        'tech_contact_email'     => $techEmail,
        'billing_contact_name'   => $billingName,
        'billing_contact_phone'  => $billingPhone,
        'billing_contact_email'  => $billingEmail,
        'auth_signatory_name'    => trim($kyc['auth_signatory_name'] ?? ''),
        'auth_signatory_mobile'  => trim($kyc['auth_signatory_mobile'] ?? ''),
        'auth_signatory_email'   => trim($kyc['auth_signatory_email'] ?? ''),
        'is_complete'            => empty($missing),
        'missing_fields'         => $missing,
        'raw'                    => $kyc
    ];
}
