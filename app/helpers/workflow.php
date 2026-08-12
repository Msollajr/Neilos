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
    $svcTypes = array_filter(array_map('trim', explode('/', $order['service_type'])));
    if (empty($svcTypes)) {
        $svcTypes = [$order['service_type'] ?: 'FTTH'];
    }

    $placeholders = implode(',', array_fill(0, count($svcTypes), '?'));
    $chkStmt = $db->prepare("
        SELECT ecc.evidence_type, ecc.is_mandatory,
          (SELECT COUNT(*) FROM contractor_evidence ce 
           WHERE (ce.order_id = ?) 
             AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(ecc.evidence_type))) as uploaded_count
        FROM evidence_checklist_config ecc 
        WHERE ecc.service_type IN ($placeholders)
        ORDER BY ecc.id ASC
    ");
    $chkStmt->execute(array_merge([$orderId], $svcTypes));
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

    foreach ($uniqueChecklist as $item) {
        $isUploaded = $item['uploaded_count'] > 0;
        if ($isUploaded) $totalUploadedItems++;
        if ($item['is_mandatory']) {
            $totalMandatory++;
            if ($isUploaded) $completedMandatory++;
        }
    }

    $allMandatoryComplete = ($totalMandatory > 0 && $completedMandatory === $totalMandatory);
    $hasMissingMandatory  = ($totalMandatory > 0 && $completedMandatory < $totalMandatory);

    // 2. Automated Workflow Transitions
    // Rule A: If all required evidence is uploaded and status is in installation lifecycle -> Transition to 'Job Completed'
    if ($allMandatoryComplete && in_array($currentStatus, ['Installation', 'Assigned', 'Evidence Pending', 'Pending Evidence'])) {
        $newStatus = 'Job Completed';
    }
    // Rule B: If mandatory evidence is deleted/missing and status was 'Job Completed' -> Revert to 'Installation'
    elseif ($hasMissingMandatory && $currentStatus === 'Job Completed') {
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
