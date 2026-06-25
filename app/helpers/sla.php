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
        'DIA'                => [
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

/**
 * Calculate current SLA consumption percentage for a ticket.
 * Accounts for clock pause (awaiting customer confirmation).
 */
function calculateSLAPct(array $ticket): float {
    if (!$ticket['resolution_time_mins']) return 0.0;

    // Minutes already consumed before any pause
    $consumed = (int)($ticket['sla_clock_consumed_mins'] ?? 0);

    // If clock is running (not paused), add elapsed since ticket created (or last resume)
    if ($ticket['status'] !== 'Resolved - Awaiting Customer Confirmation' && $ticket['status'] !== 'Closed') {
        $createdAt = strtotime($ticket['created_at']);
        $pausedAt  = $ticket['sla_clock_stopped_at'] ? strtotime($ticket['sla_clock_stopped_at']) : null;

        if ($pausedAt) {
            // We already counted up to sla_clock_stopped_at in sla_clock_consumed_mins
            // Clock is paused so don't add more
        } else {
            $elapsed   = (int)floor((time() - $createdAt) / 60);
            $consumed  = $elapsed;
        }
    }

    return round(($consumed / $ticket['resolution_time_mins']) * 100, 2);
}

/**
 * Determine SLA status label from percentage consumed.
 */
function getSLAStatusLabel(float $pct): string {
    if ($pct >= 125) return 'Critical Breach';
    if ($pct >= 100) return 'Breached';
    if ($pct >= 80)  return 'Warning';
    return 'Normal';
}

/**
 * Evaluate SLA escalation for a ticket and trigger escalations if needed.
 * Returns the updated SLA status.
 */
function evaluateTicketSLA(int $ticketId): string {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM trouble_tickets WHERE id = ?');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket || in_array($ticket['status'], ['Closed', 'Resolved - Awaiting Customer Confirmation'])) {
        return $ticket['sla_status'] ?? 'Normal';
    }

    $pct = calculateSLAPct($ticket);
    $status = getSLAStatusLabel($pct);

    // Determine escalation needed
    $currentQueue = $ticket['current_queue'];
    $newQueue = $currentQueue;

    if ($pct >= 125 && $currentQueue !== 'Director') {
        $newQueue = 'Director';
        createEscalation($ticketId, $ticket, 3, $currentQueue, 'Director', $pct);
    } elseif ($pct >= 100 && !in_array($currentQueue, ['NOC Level 3', 'Director'])) {
        $newQueue = 'NOC Level 3';
        createEscalation($ticketId, $ticket, 2, $currentQueue, 'NOC Level 3', $pct);
    } elseif ($pct >= 80 && $currentQueue === 'NOC Support') {
        $newQueue = 'NOC Core';
        createEscalation($ticketId, $ticket, 1, $currentQueue, 'NOC Core', $pct);
    }

    // Update ticket
    $update = $db->prepare('UPDATE trouble_tickets SET sla_pct_consumed = ?, sla_status = ?, current_queue = ? WHERE id = ?');
    $update->execute([$pct, $status, $newQueue, $ticketId]);

    return $status;
}

/**
 * Create an escalation record and notification queue entries.
 */
function createEscalation(int $ticketId, array $ticket, int $level, string $fromQueue, string $toQueue, float $pct): void {
    $db = getDB();

    // Generate ESC number
    $escNum = generateEscNumber();

    // Check if this escalation level already exists
    $check = $db->prepare('SELECT id FROM ticket_escalations WHERE ticket_id = ? AND escalation_level = ?');
    $check->execute([$ticketId, $level]);
    if ($check->fetch()) return; // Already escalated at this level

    $stmt = $db->prepare('INSERT INTO ticket_escalations (esc_number, ticket_id, escalation_level, from_queue, to_queue, sla_pct) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$escNum, $ticketId, $level, $fromQueue, $toQueue, $pct]);
    $escId = $db->lastInsertId();

    // Log timeline
    $tl = $db->prepare('INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,NULL)');
    $tl->execute([$ticketId, "Escalated to $toQueue", $ticket['status'], $toQueue, "SLA at {$pct}% — escalation $escNum created."]);

    // Queue notification
    $msg = "Ticket {$ticket['ticket_number']} has been escalated to $toQueue. SLA consumed: {$pct}%. ESC: $escNum.";
    $notif = $db->prepare('INSERT INTO ticket_notifications (ticket_id, escalation_id, notification_type, recipient, subject, message) VALUES (?,?,?,?,?,?)');
    $notif->execute([$ticketId, $escId, 'Email', 'noc@neilosnetwork.co.tz', "Escalation: {$ticket['ticket_number']}", $msg]);
    $notif->execute([$ticketId, $escId, 'WhatsApp', 'noc@neilosnetwork.co.tz', "Escalation: {$ticket['ticket_number']}", $msg]);
}

/**
 * Generate TT-YYMMDD-XXX format ticket number.
 */
function generateTicketNumber(): string {
    $db   = getDB();
    $date = date('ymd');
    $stmt = $db->prepare("SELECT ticket_number FROM trouble_tickets WHERE ticket_number LIKE ? ORDER BY ticket_number DESC LIMIT 1");
    $stmt->execute(["TT-$date-%"]);
    $last = $stmt->fetchColumn();
    $seq  = $last ? ((int)substr($last, -3) + 1) : 1;
    return sprintf('TT-%s-%03d', $date, $seq);
}

/**
 * Generate ESC-YYMMDD-XXX format escalation number.
 */
function generateEscNumber(): string {
    $db   = getDB();
    $date = date('ymd');
    $stmt = $db->prepare("SELECT esc_number FROM ticket_escalations WHERE esc_number LIKE ? ORDER BY esc_number DESC LIMIT 1");
    $stmt->execute(["ESC-$date-%"]);
    $last = $stmt->fetchColumn();
    $seq  = $last ? ((int)substr($last, -3) + 1) : 1;
    return sprintf('ESC-%s-%03d', $date, $seq);
}

/**
 * Generate SO-YYMMDD-XXX format order number.
 */
function generateOrderNumber(): string {
    $db   = getDB();
    $date = date('ymd');
    $stmt = $db->prepare("SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY order_number DESC LIMIT 1");
    $stmt->execute(["SO-$date-%"]);
    $last = $stmt->fetchColumn();
    $seq  = $last ? ((int)substr($last, -3) + 1) : 1;
    return sprintf('SO-%s-%03d', $date, $seq);
}

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
