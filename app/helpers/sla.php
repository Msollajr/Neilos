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
 * Generate SVC-YYMMDD-XXX format service ID matching the order number (FR-YYMMDD-XXX).
 */
function generateServiceId(string $orderNumber = ''): string {
    if (!empty($orderNumber)) {
        return preg_replace('/^FR-?/i', 'SVC-', trim($orderNumber));
    }
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
                $serviceId = generateServiceId($order['order_number'] ?? '');
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

/**
 * Authoritative Canonical SLA Stage Configuration.
 */
function getSlaStageConfig(): array {
    return [
        'feasibility' => [
            'key'             => 'feasibility',
            'name'            => 'BSA Feasibility Review',
            'short_name'      => 'Feasibility Review',
            'target_hours'    => 24,
            'target_seconds'  => 24 * 3600,
            'statuses'        => ['Feasibility Review'],
            'next_statuses'   => ['Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed', 'Not Feasible', 'Cancelled'],
        ],
        'commercial' => [
            'key'             => 'commercial',
            'name'            => 'Commercial & Mgmt Approval',
            'short_name'      => 'Commercial & Approval',
            'target_hours'    => 48,
            'target_seconds'  => 48 * 3600,
            'statuses'        => ['Await Commercial Approval', 'Management Approval'],
            'next_statuses'   => ['Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed', 'Not Feasible', 'Cancelled'],
        ],
        'sof' => [
            'key'             => 'sof',
            'name'            => 'SOF & Contract Execution',
            'short_name'      => 'SOF Execution',
            'target_hours'    => 72,
            'target_seconds'  => 72 * 3600,
            'statuses'        => ['Pending SOF', 'SOF Review'],
            'next_statuses'   => ['Installation', 'Testing', 'UAT', 'Closed', 'Not Feasible', 'Cancelled'],
        ],
        'installation' => [
            'key'             => 'installation',
            'name'            => 'Installation & Delivery',
            'short_name'      => 'Installation',
            'target_hours'    => 120,
            'target_seconds'  => 120 * 3600,
            'statuses'        => ['Installation'],
            'next_statuses'   => ['Testing', 'UAT', 'Closed', 'Not Feasible', 'Cancelled'],
        ],
        'testing_uat' => [
            'key'             => 'testing_uat',
            'name'            => 'Testing & Customer UAT',
            'short_name'      => 'Testing & UAT',
            'target_hours'    => 72,
            'target_seconds'  => 72 * 3600,
            'statuses'        => ['Testing', 'UAT'],
            'next_statuses'   => ['Closed', 'Not Feasible', 'Cancelled'],
        ],
    ];
}

/**
 * Format a duration in seconds into clean human-readable string (e.g. "2 days 4 hrs 30 min").
 */
function formatSlaDuration(?int $seconds, bool $isOngoing = false): string {
    if ($seconds === null || $seconds < 0) {
        return '—';
    }
    
    $minutes = (int)round($seconds / 60);
    if ($minutes < 1) {
        return '< 1 min' . ($isOngoing ? ' (ongoing)' : '');
    }
    
    $days = (int)floor($minutes / (24 * 60));
    $remainingMin = $minutes % (24 * 60);
    $hours = (int)floor($remainingMin / 60);
    $mins = $remainingMin % 60;
    
    $parts = [];
    if ($days > 0) {
        $parts[] = $days === 1 ? '1 day' : "$days days";
    }
    if ($hours > 0) {
        $parts[] = $hours === 1 ? '1 hr' : "$hours hrs";
    }
    if ($mins > 0 || empty($parts)) {
        $parts[] = "$mins min";
    }
    
    $formatted = implode(' ', $parts);
    return $isOngoing ? "$formatted (ongoing)" : $formatted;
}

/**
 * Evaluate stage intervals, paused time, and SLA compliance for a single order from its timeline events.
 */
function computeOrderTimelineStages(array $order, array $timeline): array {
    $now = time();
    $config = getSlaStageConfig();
    $createdTs = !empty($order['created_at']) ? strtotime($order['created_at']) : $now;
    $currentStatus = $order['status'] ?? 'Feasibility Review';
    $isClosed = in_array($currentStatus, ['Closed', 'Not Feasible', 'Cancelled']);

    // Sort timeline chronologically
    usort($timeline, function($a, $b) {
        $tA = strtotime($a['changed_at'] ?? $a['created_at'] ?? '');
        $tB = strtotime($b['changed_at'] ?? $b['created_at'] ?? '');
        if ($tA === $tB) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        }
        return $tA <=> $tB;
    });

    // Determine when status transitions occurred
    $statusTransitions = [];
    $prevStatus = 'Feasibility Review';
    $prevTs = $createdTs;

    foreach ($timeline as $t) {
        $st = $t['status'] ?? '';
        $ts = !empty($t['changed_at']) ? strtotime($t['changed_at']) : (!empty($t['created_at']) ? strtotime($t['created_at']) : null);
        if ($st && $ts) {
            $statusTransitions[] = [
                'status'     => $st,
                'changed_at' => $ts,
            ];
        }
    }

    // Helper: find earliest timestamp entering any given status
    $findEntryTs = function(array $targetStatuses) use ($statusTransitions, $createdTs, $currentStatus): ?int {
        // If initial status is in targetStatuses, entry is createdTs
        if (in_array('Feasibility Review', $targetStatuses)) {
            return $createdTs;
        }
        foreach ($statusTransitions as $trans) {
            if (in_array($trans['status'], $targetStatuses)) {
                return $trans['changed_at'];
            }
        }
        if (in_array($currentStatus, $targetStatuses)) {
            return $createdTs;
        }
        return null;
    };

    // Helper: find earliest timestamp exiting a given set of statuses to subsequent statuses
    $findExitTs = function(array $nextStatuses, ?int $entryTs) use ($statusTransitions): ?int {
        if ($entryTs === null) return null;
        foreach ($statusTransitions as $trans) {
            if ($trans['changed_at'] >= $entryTs && in_array($trans['status'], $nextStatuses)) {
                return $trans['changed_at'];
            }
        }
        return null;
    };

    // Evaluate each of the 5 canonical stages
    $stageResults = [];
    $totalStageBreaches = 0;
    $breachedStageNames = [];

    // Stage 1: BSA Feasibility Review
    $s1Entry = $createdTs;
    $s1Exit  = $findExitTs($config['feasibility']['next_statuses'], $s1Entry);
    if (!$s1Exit && !in_array($currentStatus, $config['feasibility']['statuses']) && !empty($order['bsa_reviewed_at'])) {
        $s1Exit = strtotime($order['bsa_reviewed_at']);
    }
    $s1Ongoing = ($s1Exit === null && in_array($currentStatus, $config['feasibility']['statuses']));
    $s1EndTs   = $s1Exit ?: ($s1Ongoing ? $now : null);
    $s1RawSec  = ($s1EndTs && $s1Entry) ? max(0, $s1EndTs - $s1Entry) : 0;
    $s1EffectiveSec = $s1RawSec;
    $s1Breached = ($s1EffectiveSec > $config['feasibility']['target_seconds']);
    $s1DelaySec = $s1Breached ? ($s1EffectiveSec - $config['feasibility']['target_seconds']) : 0;
    if ($s1Breached) { $totalStageBreaches++; $breachedStageNames[] = $config['feasibility']['name']; }

    $stageResults['feasibility'] = [
        'key'               => 'feasibility',
        'name'              => $config['feasibility']['name'],
        'evaluated'         => true,
        'is_ongoing'        => $s1Ongoing,
        'entry_ts'          => $s1Entry,
        'exit_ts'           => $s1Exit,
        'target_hours'      => $config['feasibility']['target_hours'],
        'target_seconds'    => $config['feasibility']['target_seconds'],
        'effective_seconds' => $s1EffectiveSec,
        'is_breached'       => $s1Breached,
        'delay_seconds'     => $s1DelaySec,
        'status_label'      => $s1Breached ? 'Breached' : 'Within SLA',
        'formatted_duration'=> formatSlaDuration($s1EffectiveSec, $s1Ongoing),
        'formatted_delay'   => formatSlaDuration($s1DelaySec),
    ];

    // Stage 2: Commercial & Management Approval
    $s2Entry = $findEntryTs($config['commercial']['statuses']);
    if (!$s2Entry && (in_array($currentStatus, array_merge($config['commercial']['statuses'], $config['commercial']['next_statuses'])))) {
        $s2Entry = $s1Exit ?: $createdTs;
    }
    $s2Evaluated = ($s2Entry !== null);
    $s2Exit  = $s2Evaluated ? $findExitTs($config['commercial']['next_statuses'], $s2Entry) : null;
    if ($s2Evaluated && !$s2Exit && !in_array($currentStatus, $config['commercial']['statuses'])) {
        if (!empty($order['management_approved_at'])) $s2Exit = strtotime($order['management_approved_at']);
        elseif (!empty($order['kam_approved_at'])) $s2Exit = strtotime($order['kam_approved_at']);
    }
    $s2Ongoing = ($s2Evaluated && $s2Exit === null && in_array($currentStatus, $config['commercial']['statuses']));
    $s2EndTs   = $s2Exit ?: ($s2Ongoing ? $now : null);
    $s2RawSec  = ($s2EndTs && $s2Entry) ? max(0, $s2EndTs - $s2Entry) : 0;
    $s2EffectiveSec = $s2RawSec;
    $s2Breached = ($s2Evaluated && $s2EffectiveSec > $config['commercial']['target_seconds']);
    $s2DelaySec = $s2Breached ? ($s2EffectiveSec - $config['commercial']['target_seconds']) : 0;
    if ($s2Breached) { $totalStageBreaches++; $breachedStageNames[] = $config['commercial']['name']; }

    $stageResults['commercial'] = [
        'key'               => 'commercial',
        'name'              => $config['commercial']['name'],
        'evaluated'         => $s2Evaluated,
        'is_ongoing'        => $s2Ongoing,
        'entry_ts'          => $s2Entry,
        'exit_ts'           => $s2Exit,
        'target_hours'      => $config['commercial']['target_hours'],
        'target_seconds'    => $config['commercial']['target_seconds'],
        'effective_seconds' => $s2EffectiveSec,
        'is_breached'       => $s2Breached,
        'delay_seconds'     => $s2DelaySec,
        'status_label'      => !$s2Evaluated ? 'Not Reached' : ($s2Breached ? 'Breached' : 'Within SLA'),
        'formatted_duration'=> $s2Evaluated ? formatSlaDuration($s2EffectiveSec, $s2Ongoing) : '—',
        'formatted_delay'   => formatSlaDuration($s2DelaySec),
    ];

    // Stage 3: SOF & Contract Execution
    $s3Entry = $findEntryTs($config['sof']['statuses']);
    if (!$s3Entry && (in_array($currentStatus, array_merge($config['sof']['statuses'], $config['sof']['next_statuses'])))) {
        $s3Entry = $s2Exit ?: ($s1Exit ?: $createdTs);
    }
    $s3Evaluated = ($s3Entry !== null);
    $s3Exit  = $s3Evaluated ? $findExitTs($config['sof']['next_statuses'], $s3Entry) : null;
    if ($s3Evaluated && !$s3Exit && !in_array($currentStatus, $config['sof']['statuses'])) {
        if (!empty($order['countersigned_sof_at'])) $s3Exit = strtotime($order['countersigned_sof_at']);
        elseif (!empty($order['sof_uploaded_at'])) $s3Exit = strtotime($order['sof_uploaded_at']);
    }
    $s3Ongoing = ($s3Evaluated && $s3Exit === null && in_array($currentStatus, $config['sof']['statuses']));
    $s3EndTs   = $s3Exit ?: ($s3Ongoing ? $now : null);
    $s3RawSec  = ($s3EndTs && $s3Entry) ? max(0, $s3EndTs - $s3Entry) : 0;
    $s3EffectiveSec = $s3RawSec;
    $s3Breached = ($s3Evaluated && $s3EffectiveSec > $config['sof']['target_seconds']);
    $s3DelaySec = $s3Breached ? ($s3EffectiveSec - $config['sof']['target_seconds']) : 0;
    if ($s3Breached) { $totalStageBreaches++; $breachedStageNames[] = $config['sof']['name']; }

    $stageResults['sof'] = [
        'key'               => 'sof',
        'name'              => $config['sof']['name'],
        'evaluated'         => $s3Evaluated,
        'is_ongoing'        => $s3Ongoing,
        'entry_ts'          => $s3Entry,
        'exit_ts'           => $s3Exit,
        'target_hours'      => $config['sof']['target_hours'],
        'target_seconds'    => $config['sof']['target_seconds'],
        'effective_seconds' => $s3EffectiveSec,
        'is_breached'       => $s3Breached,
        'delay_seconds'     => $s3DelaySec,
        'status_label'      => !$s3Evaluated ? 'Not Reached' : ($s3Breached ? 'Breached' : 'Within SLA'),
        'formatted_duration'=> $s3Evaluated ? formatSlaDuration($s3EffectiveSec, $s3Ongoing) : '—',
        'formatted_delay'   => formatSlaDuration($s3DelaySec),
    ];

    // Stage 4: Installation & Delivery
    $s4Entry = $findEntryTs($config['installation']['statuses']);
    if (!$s4Entry && (in_array($currentStatus, array_merge($config['installation']['statuses'], $config['installation']['next_statuses'])))) {
        $s4Entry = $s3Exit ?: ($s2Exit ?: ($s1Exit ?: $createdTs));
    }
    $s4Evaluated = ($s4Entry !== null);
    $s4Exit  = $s4Evaluated ? $findExitTs($config['installation']['next_statuses'], $s4Entry) : null;
    if ($s4Evaluated && !$s4Exit && !in_array($currentStatus, $config['installation']['statuses'])) {
        if (!empty($order['uat_submitted_at'])) $s4Exit = strtotime($order['uat_submitted_at']);
    }
    $s4Ongoing = ($s4Evaluated && $s4Exit === null && in_array($currentStatus, $config['installation']['statuses']));
    $s4EndTs   = $s4Exit ?: ($s4Ongoing ? $now : null);
    $s4RawSec  = ($s4EndTs && $s4Entry) ? max(0, $s4EndTs - $s4Entry) : 0;
    
    // Deduct pause hours if occurred during installation
    $pausedHours = max(0, (float)($order['sla_paused_hours'] ?? 0));
    $isCurrentlyPaused = !empty($order['sla_paused']);
    if ($isCurrentlyPaused && !empty($order['sla_paused_at'])) {
        $pausedHours += max(0, ($now - strtotime($order['sla_paused_at'])) / 3600);
    }
    $s4PausedSec = (int)round($pausedHours * 3600);
    $s4EffectiveSec = max(0, $s4RawSec - $s4PausedSec);
    $s4Breached = ($s4Evaluated && $s4EffectiveSec > $config['installation']['target_seconds']);
    $s4DelaySec = $s4Breached ? ($s4EffectiveSec - $config['installation']['target_seconds']) : 0;
    if ($s4Breached) { $totalStageBreaches++; $breachedStageNames[] = $config['installation']['name']; }

    $stageResults['installation'] = [
        'key'               => 'installation',
        'name'              => $config['installation']['name'],
        'evaluated'         => $s4Evaluated,
        'is_ongoing'        => $s4Ongoing,
        'entry_ts'          => $s4Entry,
        'exit_ts'           => $s4Exit,
        'target_hours'      => $config['installation']['target_hours'],
        'target_seconds'    => $config['installation']['target_seconds'],
        'effective_seconds' => $s4EffectiveSec,
        'is_breached'       => $s4Breached,
        'delay_seconds'     => $s4DelaySec,
        'status_label'      => !$s4Evaluated ? 'Not Reached' : ($s4Breached ? 'Breached' : 'Within SLA'),
        'formatted_duration'=> $s4Evaluated ? formatSlaDuration($s4EffectiveSec, $s4Ongoing) : '—',
        'formatted_delay'   => formatSlaDuration($s4DelaySec),
    ];

    // Stage 5: Testing & Customer UAT
    $s5Entry = $findEntryTs($config['testing_uat']['statuses']);
    if (!$s5Entry && (in_array($currentStatus, array_merge($config['testing_uat']['statuses'], $config['testing_uat']['next_statuses'])))) {
        $s5Entry = $s4Exit ?: ($s3Exit ?: ($s2Exit ?: ($s1Exit ?: $createdTs)));
    }
    $s5Evaluated = ($s5Entry !== null);
    $s5Exit  = $s5Evaluated ? $findExitTs($config['testing_uat']['next_statuses'], $s5Entry) : null;
    if ($s5Evaluated && !$s5Exit && !in_array($currentStatus, $config['testing_uat']['statuses'])) {
        if (!empty($order['uat_accepted_at'])) $s5Exit = strtotime($order['uat_accepted_at']);
        elseif (!empty($order['closed_date'])) $s5Exit = strtotime($order['closed_date']);
    }
    $s5Ongoing = ($s5Evaluated && $s5Exit === null && in_array($currentStatus, $config['testing_uat']['statuses']));
    $s5EndTs   = $s5Exit ?: ($s5Ongoing ? $now : null);
    $s5RawSec  = ($s5EndTs && $s5Entry) ? max(0, $s5EndTs - $s5Entry) : 0;
    $s5EffectiveSec = $s5RawSec;
    $s5Breached = ($s5Evaluated && $s5EffectiveSec > $config['testing_uat']['target_seconds']);
    $s5DelaySec = $s5Breached ? ($s5EffectiveSec - $config['testing_uat']['target_seconds']) : 0;
    if ($s5Breached) { $totalStageBreaches++; $breachedStageNames[] = $config['testing_uat']['name']; }

    $stageResults['testing_uat'] = [
        'key'               => 'testing_uat',
        'name'              => $config['testing_uat']['name'],
        'evaluated'         => $s5Evaluated,
        'is_ongoing'        => $s5Ongoing,
        'entry_ts'          => $s5Entry,
        'exit_ts'           => $s5Exit,
        'target_hours'      => $config['testing_uat']['target_hours'],
        'target_seconds'    => $config['testing_uat']['target_seconds'],
        'effective_seconds' => $s5EffectiveSec,
        'is_breached'       => $s5Breached,
        'delay_seconds'     => $s5DelaySec,
        'status_label'      => !$s5Evaluated ? 'Not Reached' : ($s5Breached ? 'Breached' : 'Within SLA'),
        'formatted_duration'=> $s5Evaluated ? formatSlaDuration($s5EffectiveSec, $s5Ongoing) : '—',
        'formatted_delay'   => formatSlaDuration($s5DelaySec),
    ];

    // Order-Level SLA Metrics
    $orderEndTs = $now;
    if ($isClosed) {
        if (!empty($order['closed_date'])) {
            $orderEndTs = strtotime($order['closed_date']);
        } elseif (!empty($order['uat_accepted_at'])) {
            $orderEndTs = strtotime($order['uat_accepted_at']);
        } elseif (!empty($order['updated_at'])) {
            $orderEndTs = strtotime($order['updated_at']);
        }
    }
    $rawOrderDurationSec = max(0, $orderEndTs - $createdTs);
    $totalPausedSec = (int)round($pausedHours * 3600);
    $effectiveOrderDurationSec = max(0, $rawOrderDurationSec - $totalPausedSec);

    // Standard 10-day overall fulfillment target = 240 hours
    $overallTargetHours = 240;
    $overallTargetSeconds = $overallTargetHours * 3600;

    $hasStageBreach = ($totalStageBreaches > 0);
    $isOverallBreached = ($effectiveOrderDurationSec > $overallTargetSeconds) || $hasStageBreach;

    // Mutually Exclusive 4-Bucket Order SLA Status
    $orderSlaStatus = 'Within SLA';
    if ($isCurrentlyPaused) {
        $orderSlaStatus = 'Paused';
    } elseif ($isOverallBreached) {
        $orderSlaStatus = 'Breached';
    } elseif ($effectiveOrderDurationSec >= ($overallTargetSeconds * 0.8)) {
        $orderSlaStatus = 'At Risk';
    } else {
        $orderSlaStatus = 'Within SLA';
    }

    $stDisplay = !empty($order['service_type']) ? $order['service_type'] : '';
    if (!$stDisplay) {
        if (!empty($order['fttx_package'])) {
            $stDisplay = 'FTTH';
        } elseif (!empty($order['aggregate_capacity'])) {
            $stDisplay = 'Layer 2 (last mile)';
        } elseif (!empty($order['bandwidth'])) {
            $stDisplay = 'BIA (Broadband Internet Access)';
        } elseif ((float)($order['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($order['base_nrc_usd'] ?? 0) == 80000) {
            $stDisplay = 'Remote Hands Only';
        }
    }

    return [
        'order_id'                  => (int)($order['id'] ?? 0),
        'order_number'              => $order['order_number'] ?? '',
        'customer_name'             => $order['customer_name'] ?? '',
        'service_type_display'      => $stDisplay,
        'current_status'            => $currentStatus,
        'is_closed'                 => $isClosed,
        'is_completed'              => ($currentStatus === 'Closed'),
        'is_currently_paused'       => $isCurrentlyPaused,
        'paused_hours'              => round($pausedHours, 2),
        'created_at'                => $order['created_at'] ?? '',
        'order_start_ts'            => $createdTs,
        'order_end_ts'              => $orderEndTs,
        'is_ongoing'                => !$isClosed,
        'raw_order_seconds'         => $rawOrderDurationSec,
        'effective_order_seconds'   => $effectiveOrderDurationSec,
        'overall_target_hours'      => $overallTargetHours,
        'overall_target_seconds'    => $overallTargetSeconds,
        'order_sla_status'          => $orderSlaStatus,
        'has_stage_breach'          => $hasStageBreach,
        'stage_breach_count'        => $totalStageBreaches,
        'breached_stage_names'      => $breachedStageNames,
        'formatted_total_duration'  => formatSlaDuration($effectiveOrderDurationSec, !$isClosed),
        'stages'                    => $stageResults,
        // Legacy column compatibility
        'dur_submitted_bsa'         => $stageResults['feasibility']['effective_seconds'],
        'is_dur1_ongoing'           => $stageResults['feasibility']['is_ongoing'],
        'dur_bsa_approved'          => $stageResults['commercial']['effective_seconds'],
        'is_dur2_ongoing'           => $stageResults['commercial']['is_ongoing'],
        'dur_approved_activated'    => $stageResults['sof']['effective_seconds'],
        'is_dur3_ongoing'           => $stageResults['sof']['is_ongoing'],
        'dur_total'                 => $effectiveOrderDurationSec,
        'is_total_ongoing'          => !$isClosed
    ];
}

/**
 * Compute the SLA status for a single order.
 */
function computeOrderSLA(array $order): array {
    $db = getDB();
    $tl = [];
    if (!empty($order['id'])) {
        $stmt = $db->prepare("SELECT id, order_id, status, changed_at, note FROM order_timeline WHERE order_id = ? ORDER BY changed_at ASC, id ASC");
        $stmt->execute([(int)$order['id']]);
        $tl = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return computeOrderTimelineStages($order, $tl);
}

/**
 * Compatibility wrapper for computeOrderSlaTimestamps.
 */
function computeOrderSlaTimestamps(array $o, array $timeline): array {
    return computeOrderTimelineStages($o, $timeline);
}

/**
 * Compute authoritative, mathematically reconciled SLA analytics across a filtered set of orders.
 * Single source of truth for Dashboard and SLA Tracking page.
 */
function computeComprehensiveSlaAnalytics(array $orders, PDO $db): array {
    $totalEvaluated = count($orders);
    $stageConfig    = getSlaStageConfig();

    // Mutually Exclusive Order SLA Status counts
    $withinSlaCount = 0;
    $atRiskCount    = 0;
    $breachedCount  = 0;
    $pausedCount    = 0;

    // Orthogonal Lifecycle counts
    $activeCount    = 0;
    $completedCount = 0;

    // Stage Breaches Summary
    $totalStageBreaches        = 0;
    $ordersWithStageBreaches   = 0;

    // Duration Accumulators
    $sumCompletedSec = 0;
    $countCompleted  = 0;
    $sumActiveSec    = 0;
    $countActive     = 0;
    $sumOverallSec   = 0;

    // Stage Matrix Structure
    $stages = [];
    foreach ($stageConfig as $k => $cfg) {
        $stages[$k] = [
            'key'             => $cfg['key'],
            'name'            => $cfg['name'],
            'target_hours'    => $cfg['target_hours'],
            'target_seconds'  => $cfg['target_seconds'],
            'evaluated'       => 0,
            'active'          => 0,
            'within'          => 0,
            'breached'        => 0,
            'sum_sec'         => 0,
            'dur_count'       => 0,
            'sum_delay_sec'   => 0,
            'max_delay_sec'   => 0,
            'active_overdue'  => 0,
        ];
    }

    $orderEvaluations = [];

    if ($totalEvaluated > 0) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        
        $tlStmt = $db->prepare("SELECT id, order_id, status, changed_at, note FROM order_timeline WHERE order_id IN ($placeholders) ORDER BY changed_at ASC, id ASC");
        $tlStmt->execute($orderIds);
        $allTimelines = [];
        foreach ($tlStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $allTimelines[$row['order_id']][] = $row;
        }

        foreach ($orders as $ord) {
            $oid = $ord['id'];
            $tl  = $allTimelines[$oid] ?? [];
            $eval = computeOrderTimelineStages($ord, $tl);
            $orderEvaluations[] = $eval;

            // Mutually Exclusive 4-Bucket Order SLA Classification
            match ($eval['order_sla_status']) {
                'Paused'     => $pausedCount++,
                'Breached'   => $breachedCount++,
                'At Risk'    => $atRiskCount++,
                'Within SLA' => $withinSlaCount++,
            };

            // Orthogonal Lifecycle
            if ($eval['is_completed']) {
                $completedCount++;
                $sumCompletedSec += $eval['effective_order_seconds'];
                $countCompleted++;
            }
            if (!$eval['is_closed']) {
                $activeCount++;
                $sumActiveSec += $eval['effective_order_seconds'];
                $countActive++;
            }
            $sumOverallSec += $eval['effective_order_seconds'];

            if ($eval['has_stage_breach']) {
                $ordersWithStageBreaches++;
                $totalStageBreaches += $eval['stage_breach_count'];
            }

            // Aggregate Stage Evaluations
            foreach ($eval['stages'] as $stKey => $stEval) {
                if (!$stEval['evaluated']) continue;

                $stages[$stKey]['evaluated']++;
                $stages[$stKey]['dur_count']++;
                $stages[$stKey]['sum_sec'] += $stEval['effective_seconds'];

                if ($stEval['is_ongoing']) {
                    $stages[$stKey]['active']++;
                }

                if ($stEval['is_breached']) {
                    $stages[$stKey]['breached']++;
                    $stages[$stKey]['sum_delay_sec'] += $stEval['delay_seconds'];
                    $stages[$stKey]['max_delay_sec'] = max($stages[$stKey]['max_delay_sec'], $stEval['delay_seconds']);
                    if ($stEval['is_ongoing']) {
                        $stages[$stKey]['active_overdue']++;
                    }
                } else {
                    $stages[$stKey]['within']++;
                }
            }
        }
    }

    // Format Stage Performance Metrics
    foreach ($stages as $k => &$st) {
        $st['avg_sec'] = $st['dur_count'] > 0 ? (int)round($st['sum_sec'] / $st['dur_count']) : 0;
        $st['avg_formatted'] = formatSlaDuration($st['avg_sec']);
        $st['avg_delay_sec'] = $st['breached'] > 0 ? (int)round($st['sum_delay_sec'] / $st['breached']) : 0;
        $st['avg_delay_formatted'] = formatSlaDuration($st['avg_delay_sec']);
        $st['max_delay_formatted'] = formatSlaDuration($st['max_delay_sec']);
        $st['compliance_pct'] = $st['evaluated'] > 0 ? round(($st['within'] / $st['evaluated']) * 100, 1) : 100.0;
        $st['breach_pct']     = $st['evaluated'] > 0 ? round(($st['breached'] / $st['evaluated']) * 100, 1) : 0.0;
    }
    unset($st);

    // Rank Bottlenecks: Highest Breaches DESC, Highest Breach Rate DESC, Highest Avg Delay DESC
    $bottlenecks = array_values($stages);
    usort($bottlenecks, function($a, $b) {
        if ($a['breached'] !== $b['breached']) {
            return $b['breached'] <=> $a['breached'];
        }
        if ($a['breach_pct'] !== $b['breach_pct']) {
            return $b['breach_pct'] <=> $a['breach_pct'];
        }
        return $b['avg_delay_sec'] <=> $a['avg_delay_sec'];
    });

    // Overall Compliance Percentage (Cleanly defined as orders meeting SLA / total evaluated)
    // Non-breached orders = withinSlaCount + atRiskCount
    $nonBreachedCount = $withinSlaCount + $atRiskCount;
    $overallCompliance = $totalEvaluated > 0 ? round(($nonBreachedCount / $totalEvaluated) * 100, 1) : 100.0;

    // Average Durations
    $avgCompletedSec = $countCompleted > 0 ? (int)round($sumCompletedSec / $countCompleted) : 0;
    $avgActiveSec    = $countActive > 0 ? (int)round($sumActiveSec / $countActive) : 0;
    $avgOverallSec   = $totalEvaluated > 0 ? (int)round($sumOverallSec / $totalEvaluated) : 0;

    // Consistency Check Assertion
    $sumSlaBuckets = $withinSlaCount + $atRiskCount + $breachedCount + $pausedCount;
    $isMathematicallyConsistent = ($sumSlaBuckets === $totalEvaluated);

    return [
        'total_evaluated'             => $totalEvaluated,
        'within_sla_count'            => $withinSlaCount,
        'at_risk_count'               => $atRiskCount,
        'breached_count'              => $breachedCount,
        'paused_count'                => $pausedCount,
        'non_breached_count'          => $nonBreachedCount,
        'active_count'                => $activeCount,
        'completed_count'             => $completedCount,
        'compliance_pct'              => $overallCompliance,
        'total_stage_breaches'        => $totalStageBreaches,
        'orders_with_stage_breaches'  => $ordersWithStageBreaches,
        'avg_completed_duration_sec'  => $avgCompletedSec,
        'avg_completed_formatted'     => formatSlaDuration($avgCompletedSec),
        'avg_active_duration_sec'     => $avgActiveSec,
        'avg_active_formatted'        => formatSlaDuration($avgActiveSec, true),
        'avg_overall_duration_sec'    => $avgOverallSec,
        'avg_duration_formatted'      => $countCompleted > 0 ? formatSlaDuration($avgCompletedSec) : formatSlaDuration($avgActiveSec, true),
        'stages'                      => $stages,
        'bottlenecks'                 => $bottlenecks,
        'order_evaluations'           => $orderEvaluations,
        'is_reconciled'               => $isMathematicallyConsistent,
    ];
}



