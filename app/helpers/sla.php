<?php
// ============================================================
// Neilos Partner Portal — SLA Helper
// ============================================================

/**
 * SLA matrix: minutes for response and resolution per service type + severity.
 * Calendar hours converted to minutes.
 */
function getSLAMinutes(string $serviceType, string $severity): array {
    $matrix = [
        'BIA (Broadband Internet Access)' => [
            'Sev 1' => ['response' =>  30,  'resolution' =>  480],
            'Sev 2' => ['response' =>  60,  'resolution' => 1440],
            'Sev 3' => ['response' => 120,  'resolution' => 2880],
            'Sev 4' => ['response' => 240,  'resolution' => 4320],
        ],
        'DIA'                => [
            'Sev 1' => ['response' =>  30,  'resolution' =>  480],
            'Sev 2' => ['response' =>  60,  'resolution' => 1440],
            'Sev 3' => ['response' => 120,  'resolution' => 2880],
            'Sev 4' => ['response' => 240,  'resolution' => 4320],
        ],
        'Layer 2 ( last mile)' => [
            'Sev 1' => ['response' =>  30,  'resolution' =>  480],
            'Sev 2' => ['response' =>  60,  'resolution' => 1440],
            'Sev 3' => ['response' => 120,  'resolution' => 2880],
            'Sev 4' => ['response' => 240,  'resolution' => 4320],
        ],
        'Dedicated Layer 2' => [
            'Sev 1' => ['response' =>  30,  'resolution' =>  480],
            'Sev 2' => ['response' =>  60,  'resolution' => 1440],
            'Sev 3' => ['response' => 120,  'resolution' => 2880],
            'Sev 4' => ['response' => 240,  'resolution' => 4320],
        ],
        'FTTB' => [
            'Sev 1' => ['response' =>  60,  'resolution' => 1440],
            'Sev 2' => ['response' => 120,  'resolution' => 1440],
            'Sev 3' => ['response' => 240,  'resolution' => 2880],
            'Sev 4' => ['response' => 480,  'resolution' => 4320],
        ],
        'FTTH' => [
            'Sev 1' => ['response' =>  60,  'resolution' => 1440],
            'Sev 2' => ['response' => 120,  'resolution' => 1440],
            'Sev 3' => ['response' => 240,  'resolution' => 2880],
            'Sev 4' => ['response' => 480,  'resolution' => 4320],
        ],
        'Remote Hands Only' => [
            'Critical' => ['response' =>  60,  'resolution' => null],
            'Standard' => ['response' => 240,  'resolution' => null],
            'Planned'  => ['response' => 1440, 'resolution' => null], // Next business day ~= 1 day
        ],
    ];

    return $matrix[$serviceType][$severity] ?? ['response' => 240, 'resolution' => 4320];
}



// generateOrderNumber() is defined in app/helpers/format.php (FR- prefix, v1.0)



/**
 * Generate SVC-YYMMDD-XXX format service ID.
 */
function generateServiceId(): string {
    $db   = getDB();
    $date = date('ymd');
    $stmt = $db->prepare("SELECT service_id FROM active_services WHERE service_id LIKE ? ORDER BY service_id DESC LIMIT 1");
    $stmt->execute(["SVC-$date-%"]);
    $last = $stmt->fetchColumn();
    $seq  = $last ? ((int)substr($last, -3) + 1) : 1;
    return sprintf('SVC-%s-%03d', $date, $seq);
}

/**
 * Get SLA badge CSS class from status label.
 */
function slaBadgeClass(string $status): string {
    return match($status) {
        'Critical Breach' => 'badge-danger',
        'Breached'        => 'badge-danger',
        'Warning'         => 'badge-warning',
        default           => 'badge-success',
    };
}

/**
 * Auto-accept UAT orders when 72-hour deadline expires.
 */
function checkUatAutoAccept(PDO $db): int {
    try {
        $stmt = $db->query("SELECT * FROM orders WHERE status = 'UAT' AND uat_deadline IS NOT NULL AND uat_deadline <= NOW()");
        if (!$stmt) return 0;
        $expiredOrders = $stmt->fetchAll();
        $autoAcceptedCount = 0;

        foreach ($expiredOrders as $order) {
            $orderId = (int)$order['id'];
            $today   = date('Y-m-d');

            $db->prepare("UPDATE orders SET status = 'Closed', closed_date = ?, billing_start_date = ?, activation_date = ?, uat_accepted_at = NOW(), updated_at = NOW() WHERE id = ? AND status = 'UAT'")
               ->execute([$today, $today, $today, $orderId]);

            $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,NULL)")
               ->execute([$orderId, 'Closed', "Service automatically accepted after the 72-hour UAT acceptance window expired. Order closed. Closed Date: $today. Billing Start Date: $today."]);

            // Create Active Service record if not exists
            $svcCheck = $db->prepare("SELECT id FROM active_services WHERE order_id = ?");
            $svcCheck->execute([$orderId]);
            if (!$svcCheck->fetch()) {
                $serviceId = 'SVC-' . date('ymd') . '-' . str_pad($orderId, 3, '0', STR_PAD_LEFT);
                $circuitId = 'CKT-' . $order['order_number'];
                $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE order_id=order_id")->execute([
                    $serviceId, $orderId, $order['partner_id'], $order['customer_name'], $order['service_type'],
                    $circuitId, $order['bandwidth'] ?: ($order['fttx_package'] ?? ''), $order['customer_location'] ?? '', $order['building_name'] ?? '',
                    $order['kam_id'] ?? null, $today, $today, 'Active', 'Online'
                ]);
                $db->prepare("UPDATE orders SET service_id = ?, circuit_id = ? WHERE id = ? AND service_id IS NULL")
                   ->execute([$serviceId, $circuitId, $orderId]);
            }

            queueOrderNotification($orderId, 'Partner Accepted Service');
            auditLog("UAT 72-hour deadline expired — auto-accepted order #{$order['order_number']}", 'orders', $orderId);
            $autoAcceptedCount++;
        }

        return $autoAcceptedCount;
    } catch (Exception $e) {
        return 0;
    }
}

