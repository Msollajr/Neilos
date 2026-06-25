<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

define('ALLOWED_STATUS_TRANSITIONS', [
    'Open' => ['Assigned', 'Closed'],
    'Assigned' => ['In Progress', 'Open', 'Closed'],
    'In Progress' => ['Resolved - Awaiting Customer Confirmation', 'Open', 'Closed'],
    'Resolved - Awaiting Customer Confirmation' => ['Closed'],
    'Reopened' => ['Assigned', 'In Progress'],
]);

// ------------------------------------------------------------------
// AJAX: Get service details for auto-populate
// ------------------------------------------------------------------
if ($action === 'get_service') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
    $svcId = (int)($_GET['service_id'] ?? 0);
    if (!$svcId) { echo json_encode(['error' => 'Invalid service ID']); exit; }

    $pw = partnerWhere('s');
    $stmt = $db->prepare("SELECT s.*, p.name as partner_name, u.full_name as kam_name FROM active_services s JOIN partners p ON s.partner_id = p.id LEFT JOIN users u ON s.kam_id = u.id WHERE s.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$svcId], $pw['params']));
    $svc = $stmt->fetch();
    if (!$svc) { echo json_encode(['error' => 'Service not found']); exit; }
    echo json_encode([
        'service_id'        => $svc['service_id'],
        'partner_name'      => $svc['partner_name'],
        'partner_id'        => $svc['partner_id'],
        'customer_name'     => $svc['customer_name'],
        'service_type'      => $svc['service_type'],
        'circuit_id'        => $svc['circuit_id'],
        'bandwidth_capacity'=> $svc['bandwidth_capacity'],
        'location'          => $svc['location'],
        'kam_name'          => $svc['kam_name'],
        'activation_date'   => $svc['activation_date'],
    ]);
    exit;
}

// ------------------------------------------------------------------
// POST: Create ticket
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $activeServiceId = (int)($_POST['active_service_id'] ?? 0);
    $faultCategory   = $_POST['fault_category'] ?? '';
    $severity        = $_POST['severity'] ?? '';
    $description     = trim($_POST['description'] ?? '');

    $allowedFaults = ['Network Outage','Power Issue','Fiber Cut','High Latency','Packet Loss','Bandwidth Degradation','ONU / ONT Fault','CPE Fault','Configuration Issue','NNI Issue','IP Transit Issue','Peering Issue','Remote Hands Request','Service Activation Issue','Billing Related','Other'];
    if (!in_array($faultCategory, $allowedFaults)) {
        setFlash('danger', 'Invalid fault category.'); header('Location: ' . APP_URL . '/?page=tickets&action=create'); exit;
    }

    $allowedSev = ['Sev 1','Sev 2','Sev 3','Sev 4','Critical','Standard','Planned'];
    if (!in_array($severity, $allowedSev)) {
        setFlash('danger', 'Invalid severity.'); header('Location: ' . APP_URL . '/?page=tickets&action=create'); exit;
    }

    if (!$activeServiceId || !$description) {
        setFlash('danger', 'Service ID and description are required.'); header('Location: ' . APP_URL . '/?page=tickets&action=create'); exit;
    }

    // Verify service exists and user has access
    $pw = partnerWhere('s');
    $svcStmt = $db->prepare("SELECT s.*, p.name as partner_name FROM active_services s JOIN partners p ON s.partner_id = p.id WHERE s.id = ? AND {$pw['condition']}");
    $svcStmt->execute(array_merge([$activeServiceId], $pw['params']));
    $svc = $svcStmt->fetch();
    if (!$svc) {
        setFlash('danger', 'Service not found or access denied.'); header('Location: ' . APP_URL . '/?page=tickets&action=create'); exit;
    }

    $ticketNum = generateTicketNumber();

    // Get SLA targets
    $sla = getSLAMinutes($svc['service_type'], $severity);

    $stmt = $db->prepare("INSERT INTO trouble_tickets (
        ticket_number, active_service_id, service_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, kam_id, activation_date,
        fault_category, severity, description, current_queue, status, opened_by, opened_by_type, response_time_mins, resolution_time_mins
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $openedByType = isPartnerUser() ? 'Partner' : 'NOC';
    $currentQueue = $openedByType === 'Partner' ? 'NOC Support' : 'NOC Support';

    $stmt->execute([
        $ticketNum, $activeServiceId, $svc['service_id'], $svc['partner_id'],
        $svc['customer_name'], $svc['service_type'], $svc['circuit_id'],
        $svc['bandwidth_capacity'], $svc['location'], $svc['kam_id'], $svc['activation_date'],
        $faultCategory, $severity, $description, $currentQueue, 'Open',
        $user['id'], $openedByType, $sla['response'], $sla['resolution'],
    ]);
    $ticketId = $db->lastInsertId();

    // Timeline entry
    $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,?)")
       ->execute([$ticketId, 'Ticket created', 'Open', $currentQueue, "Ticket raised by {$user['full_name']} via portal.", $user['id']]);

    auditLog("Created ticket {$ticketNum}", 'tickets', $ticketId);
    setFlash('success', "Trouble ticket <strong>{$ticketNum}</strong> created successfully.");
    header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
    exit;
}

// ------------------------------------------------------------------
// POST: Update ticket status (assign, start progress, resolve, close)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    verifyCsrf();

    if (isPartnerUser()) {
        setFlash('danger', 'Partners cannot change ticket status directly.'); header('Location: ' . APP_URL . '/?page=tickets'); exit;
    }

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $assignTo = (int)($_POST['assign_to'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM trouble_tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        setFlash('danger', 'Ticket not found.'); header('Location: ' . APP_URL . '/?page=tickets'); exit;
    }

    // Validate transition
    $validTransitions = ALLOWED_STATUS_TRANSITIONS;
    $allowedNext = $validTransitions[$ticket['status']] ?? [];
    if (!in_array($newStatus, $allowedNext) && $newStatus !== $ticket['status']) {
        setFlash('danger', "Cannot change status from '{$ticket['status']}' to '{$newStatus}'."); header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId); exit;
    }

    // Handle SLA clock for resolution
    $extraFields = '';
    $extraParams = [];
    if ($newStatus === 'Resolved - Awaiting Customer Confirmation') {
        $extraFields = ', resolved_at = NOW(), sla_clock_stopped_at = NOW(), awaiting_confirmation_since = NOW(), auto_close_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)';
        $extraFields .= ', sla_clock_consumed_mins = GREATEST(sla_clock_consumed_mins, TIMESTAMPDIFF(MINUTE, created_at, NOW()))';
    }

    // Handle assignment
    if ($assignTo > 0) {
        $extraFields .= ', assigned_to = ?';
        $extraParams[] = $assignTo;
    }

    $params = array_merge([$newStatus, $ticketId], $extraParams);
    $db->prepare("UPDATE trouble_tickets SET status = ?, updated_at = NOW() {$extraFields} WHERE id = ?")->execute($params);

    // Timeline
    $actionLabel = "Status changed to {$newStatus}";
    $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,?)")
       ->execute([$ticketId, $actionLabel, $newStatus, $ticket['current_queue'], $note ?: $actionLabel, $user['id']]);

    // If resolved and awaiting confirmation, evaluate SLA for clock stop
    if ($newStatus === 'Resolved - Awaiting Customer Confirmation') {
        $evaluatedStatus = evaluateTicketSLA($ticketId);
    }

    auditLog("Updated ticket {$ticket['ticket_number']} to {$newStatus}", 'tickets', $ticketId);
    setFlash('success', "Ticket status updated to <strong>{$newStatus}</strong>.");
    header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
    exit;
}

// ------------------------------------------------------------------
// GET: Cron — Evaluate SLA for all open tickets
// ------------------------------------------------------------------
if ($action === 'cron_evaluate_sla') {
    requireLogin();
    $stmt = $db->query("SELECT id FROM trouble_tickets WHERE status NOT IN ('Closed','Resolved - Awaiting Customer Confirmation')");
    $count = 0;
    while ($row = $stmt->fetch()) {
        evaluateTicketSLA($row['id']);
        $count++;
    }
    echo "SLA evaluated for $count tickets.";
    exit;
}

// ------------------------------------------------------------------
// GET: Cron — Auto-close tickets awaiting confirmation >24hrs
// ------------------------------------------------------------------
if ($action === 'cron_auto_close') {
    requireLogin();
    $stmt = $db->query("SELECT id, ticket_number FROM trouble_tickets WHERE status = 'Resolved - Awaiting Customer Confirmation' AND auto_close_at IS NOT NULL AND auto_close_at <= NOW()");
    $count = 0;
    while ($row = $stmt->fetch()) {
        $db->prepare("UPDATE trouble_tickets SET status = 'Closed', sla_clock_consumed_mins = sla_clock_consumed_mins + TIMESTAMPDIFF(MINUTE, awaiting_confirmation_since, NOW()), sla_clock_stopped_at = NULL, updated_at = NOW() WHERE id = ?")->execute([$row['id']]);
        $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, note, changed_by) VALUES (?, 'Auto-closed after 24hr customer confirmation timeout', 'Closed', 'Auto-closed by system.', NULL)")->execute([$row['id']]);
        $count++;
    }
    echo "Auto-closed $count tickets.";
    exit;
}

// ------------------------------------------------------------------
// POST: Add note to ticket
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_note') {
    verifyCsrf();

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $noteType = $_POST['note_type'] ?? 'Internal';

    if (!$note) { setFlash('danger', 'Note cannot be empty.'); header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId); exit; }

    // Verify access
    $pw = partnerWhere('tt');
    $stmt = $db->prepare("SELECT id, ticket_number FROM trouble_tickets tt WHERE tt.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$ticketId], $pw['params']));
    if (!$stmt->fetch()) { setFlash('danger', 'Ticket not found.'); header('Location: ' . APP_URL . '/?page=tickets'); exit; }

    $db->prepare("INSERT INTO ticket_notes (ticket_id, note, note_type, created_by) VALUES (?,?,?,?)")
       ->execute([$ticketId, $note, $noteType, $user['id']]);

    $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,?)")
       ->execute([$ticketId, 'Note added', '', '', $noteType === 'Internal' ? 'Internal note added.' : 'Partner-visible note added.', $user['id']]);

    auditLog("Added note to ticket", 'tickets', $ticketId);
    setFlash('success', 'Note added.');
    header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
    exit;
}

// ------------------------------------------------------------------
// POST: Customer action — confirm resolved or reopen
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'customer_action') {
    verifyCsrf();

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $customerAction = $_POST['customer_action'] ?? '';

    // Only partners can do this for their own tickets
    $pw = partnerWhere('tt');
    $stmt = $db->prepare("SELECT * FROM trouble_tickets tt WHERE tt.id = ? AND tt.status = 'Resolved - Awaiting Customer Confirmation' AND {$pw['condition']}");
    $stmt->execute(array_merge([$ticketId], $pw['params']));
    $ticket = $stmt->fetch();
    if (!$ticket) {
        setFlash('danger', 'Ticket not found or not awaiting your confirmation.'); header('Location: ' . APP_URL . '/?page=tickets'); exit;
    }

    if ($customerAction === 'confirm') {
        // Customer confirms issue resolved
        $resolvedAt = strtotime($ticket['resolved_at'] ?: $ticket['created_at']);
        $confirmedAt = time();
        $nocResolutionMins = (int)floor(($confirmedAt - $resolvedAt) / 60);
        $customerWaitMins = (int)floor(($confirmedAt - $resolvedAt) / 60);

        // Calculate NOC resolution time (excludes customer wait)
        $totalConsumed = (int)$ticket['sla_clock_consumed_mins'];
        $nocMins = $totalConsumed;

        $db->prepare("UPDATE trouble_tickets SET status = 'Closed', sla_clock_consumed_mins = ?, noc_resolution_time_mins = ?, customer_wait_time_mins = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$totalConsumed, $nocMins, $customerWaitMins, $ticketId]);

        $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, note, changed_by) VALUES (?,?,?,?,?)")
           ->execute([$ticketId, 'Customer confirmed issue resolved', 'Closed', 'Customer confirmed the issue is resolved. Ticket closed.', $user['id']]);

        auditLog("Customer confirmed ticket {$ticket['ticket_number']} resolved", 'tickets', $ticketId);
        setFlash('success', 'Thank you. The ticket has been closed.');

    } elseif ($customerAction === 'reopen') {
        $reopenReason = trim($_POST['reopen_reason'] ?? '');
        if (!$reopenReason) {
            setFlash('danger', 'Please provide a reason for reopening the ticket.'); header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId); exit;
        }

        // Resume SLA clock from previous consumed time
        $consumed = (int)$ticket['sla_clock_consumed_mins'];
        $db->prepare("UPDATE trouble_tickets SET status = 'Reopened', reopen_reason = ?, sla_clock_stopped_at = NULL, awaiting_confirmation_since = NULL, auto_close_at = NULL, updated_at = NOW() WHERE id = ?")
           ->execute([$reopenReason, $ticketId]);

        $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,?)")
           ->execute([$ticketId, 'Customer reopened ticket', 'Reopened', 'NOC Support', "Reason: {$reopenReason}", $user['id']]);

        auditLog("Customer reopened ticket {$ticket['ticket_number']}", 'tickets', $ticketId);
        setFlash('success', 'Ticket has been reopened and returned to NOC Support.');
    } else {
        setFlash('danger', 'Invalid action.');
    }

    header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
    exit;
}

// ------------------------------------------------------------------
// POST: Assign ticket
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'assign') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=tickets'); exit; }

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $assignTo = (int)($_POST['assign_to'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM trouble_tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    if (!$ticket) { setFlash('danger', 'Ticket not found.'); header('Location: ' . APP_URL . '/?page=tickets'); exit; }

    $assignedUser = null;
    if ($assignTo > 0) {
        $uStmt = $db->prepare("SELECT full_name FROM users WHERE id = ? AND is_active = 1");
        $uStmt->execute([$assignTo]);
        $assignedUser = $uStmt->fetchColumn();
    }

    $db->prepare("UPDATE trouble_tickets SET assigned_to = ?, status = CASE WHEN status = 'Open' THEN 'Assigned' ELSE status END, updated_at = NOW() WHERE id = ?")
       ->execute([$assignTo ?: null, $ticketId]);

    $noteVal = $assignTo ? "Assigned to {$assignedUser}" : 'Unassigned';
    $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,?)")
       ->execute([$ticketId, $noteVal, $ticket['status'], $ticket['current_queue'], $noteVal, $user['id']]);

    auditLog("Assigned ticket {$ticket['ticket_number']}", 'tickets', $ticketId);
    setFlash('success', $noteVal);
    header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
    exit;
}

// ------------------------------------------------------------------
// POST: Change queue (NOC action)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_queue') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=tickets'); exit; }

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $newQueue = $_POST['new_queue'] ?? '';

    $allowedQueues = ['NOC Support','NOC Core','NOC Level 3','Director'];
    if (!in_array($newQueue, $allowedQueues)) { setFlash('danger', 'Invalid queue.'); header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId); exit; }

    $stmt = $db->prepare("SELECT ticket_number FROM trouble_tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticketNum = $stmt->fetchColumn();
    if (!$ticketNum) { setFlash('danger', 'Ticket not found.'); exit; }

    $db->prepare("UPDATE trouble_tickets SET current_queue = ?, updated_at = NOW() WHERE id = ?")->execute([$newQueue, $ticketId]);
    $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, queue, note, changed_by) VALUES (?,?,?,?,?,?)")
       ->execute([$ticketId, "Queue changed to {$newQueue}", '', $newQueue, "Manually moved to {$newQueue}", $user['id']]);

    auditLog("Changed queue for {$ticketNum} to {$newQueue}", 'tickets', $ticketId);
    setFlash('success', "Queue changed to {$newQueue}.");
    header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
    exit;
}

// ------------------------------------------------------------------
// POST: Evaluate SLA for a ticket (cron or manual trigger)
// ------------------------------------------------------------------
if ($action === 'evaluate_sla') {
    $ticketId = (int)($_GET['id'] ?? 0);
    if ($ticketId) {
        $status = evaluateTicketSLA($ticketId);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            setFlash('success', "SLA evaluated: {$status}");
            header('Location: ' . APP_URL . '/?page=ticket_detail&id=' . $ticketId);
            exit;
        }
        echo "SLA evaluated for ticket {$ticketId}: {$status}";
        exit;
    }
    // Evaluate all open tickets
    $stmt = $db->prepare("SELECT id FROM trouble_tickets WHERE status NOT IN ('Closed','Resolved - Awaiting Customer Confirmation')");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        evaluateTicketSLA($row['id']);
    }
    echo "All open tickets evaluated.";
    exit;
}

// ------------------------------------------------------------------
// POST: Auto-close tickets awaiting confirmation for >24 hrs
// ------------------------------------------------------------------
if ($action === 'auto_close') {
    $stmt = $db->prepare("SELECT * FROM trouble_tickets WHERE status = 'Resolved - Awaiting Customer Confirmation' AND auto_close_at IS NOT NULL AND auto_close_at <= NOW()");
    $stmt->execute();
    $count = 0;
    while ($ticket = $stmt->fetch()) {
        $totalConsumed = (int)$ticket['sla_clock_consumed_mins'];
        $resolvedAt = strtotime($ticket['resolved_at'] ?: $ticket['created_at']);
        $customerWaitMins = (int)floor((time() - $resolvedAt) / 60);

        $db->prepare("UPDATE trouble_tickets SET status = 'Closed', noc_resolution_time_mins = ?, customer_wait_time_mins = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$totalConsumed, $customerWaitMins, $ticket['id']]);

        $db->prepare("INSERT INTO ticket_timeline (ticket_id, action, status, note, changed_by) VALUES (?,?,?,?,NULL)")
           ->execute([$ticket['id'], 'Auto-closed after 24 hours', 'Closed', 'Customer did not respond within 24 hours. Ticket auto-closed.']);

        $count++;
    }
    echo "Auto-closed {$count} ticket(s).";
    exit;
}

// ------------------------------------------------------------------
// Ticket Detail
// ------------------------------------------------------------------
if ($action === 'detail' || $_GET['page'] === 'ticket_detail') {
    $ticketId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('tt');
    $stmt = $db->prepare("SELECT tt.*, p.name as partner_name, u1.full_name as opened_by_name, u2.full_name as assigned_to_name FROM trouble_tickets tt JOIN partners p ON tt.partner_id = p.id LEFT JOIN users u1 ON tt.opened_by = u1.id LEFT JOIN users u2 ON tt.assigned_to = u2.id WHERE tt.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$ticketId], $pw['params']));
    $ticket = $stmt->fetch();
    if (!$ticket) { http_response_code(404); echo '<p style="padding:40px">Ticket not found.</p>'; exit; }

    // Evaluate SLA on view
    evaluateTicketSLA($ticketId);
    $ticket['sla_pct_consumed'] = calculateSLAPct($ticket);
    $ticket['sla_status'] = getSLAStatusLabel($ticket['sla_pct_consumed']);

    // Timeline
    $timeline = $db->prepare("SELECT tl.*, u.full_name FROM ticket_timeline tl LEFT JOIN users u ON tl.changed_by = u.id WHERE tl.ticket_id = ? ORDER BY tl.changed_at DESC");
    $timeline->execute([$ticketId]);
    $timeline = $timeline->fetchAll();

    // Notes
    $notesQuery = isPartnerUser()
        ? "SELECT tn.*, u.full_name FROM ticket_notes tn LEFT JOIN users u ON tn.created_by = u.id WHERE tn.ticket_id = ? AND tn.note_type = 'Partner Visible' ORDER BY tn.created_at DESC"
        : "SELECT tn.*, u.full_name FROM ticket_notes tn LEFT JOIN users u ON tn.created_by = u.id WHERE tn.ticket_id = ? ORDER BY tn.created_at DESC";
    $notes = $db->prepare($notesQuery);
    $notes->execute([$ticketId]);
    $notes = $notes->fetchAll();

    // Available NOC staff for assignment
    $nocStaff = $db->query("SELECT id, full_name FROM users WHERE role IN ('NOC Support','NOC Core','NOC Level 3') AND is_active = 1 ORDER BY full_name")->fetchAll();

    // SLA matrix for display
    $slaTargets = getSLAMinutes($ticket['service_type'], $ticket['severity']);

    // Queue list for modals
    $queues = ['NOC Support','NOC Core','NOC Level 3','Director'];

    $pageTitle = 'Ticket ' . $ticket['ticket_number'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/tickets/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Create ticket form
// ------------------------------------------------------------------
if ($action === 'create' || $action === 'new') {
    // Get active services for this partner (or all for NOC)
    $pw = partnerWhere('s');
    $services = $db->prepare("SELECT s.id, s.service_id, s.customer_name, s.service_type, s.circuit_id, s.bandwidth_capacity, s.location, p.name as partner_name FROM active_services s JOIN partners p ON s.partner_id = p.id WHERE s.status = 'Active' AND {$pw['condition']} ORDER BY s.service_id");
    $services->execute($pw['params']);
    $activeServices = $services->fetchAll();

    $pageTitle = 'New Trouble Ticket';
    $extraJs   = 'tickets';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/tickets/create.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Ticket List / Dashboard
// ------------------------------------------------------------------
$pw = partnerWhere('tt');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterQueue  = $_GET['queue'] ?? '';
$filterSeverity = $_GET['severity'] ?? '';
$filterSearch = $_GET['q'] ?? '';

if ($filterStatus)   { $where .= " AND tt.status = ?";       $params[] = $filterStatus; }
if ($filterQueue)    { $where .= " AND tt.current_queue = ?"; $params[] = $filterQueue; }
if ($filterSeverity) { $where .= " AND tt.severity = ?";      $params[] = $filterSeverity; }
if ($filterSearch)   { $where .= " AND (tt.ticket_number LIKE ? OR tt.service_id LIKE ? OR tt.customer_name LIKE ? OR tt.fault_category LIKE ?)"; $pS = "%{$filterSearch}%"; $params = array_merge($params, [$pS, $pS, $pS, $pS]); }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 20;
$pg  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT tt.*, p.name as partner_name FROM trouble_tickets tt JOIN partners p ON tt.partner_id = p.id $where ORDER BY tt.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Stats for header
$totalOpen = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.status NOT IN ('Closed') AND {$pw['condition']}");
$totalOpen->execute($pw['params']);
$openCount = (int)$totalOpen->fetchColumn();

$breachCount = $db->prepare("SELECT COUNT(*) FROM trouble_tickets tt WHERE tt.sla_status IN ('Breached','Critical Breach') AND tt.status NOT IN ('Closed') AND {$pw['condition']}");
$breachCount->execute($pw['params']);
$breachCount = (int)$breachCount->fetchColumn();

$queues = ['NOC Support','NOC Core','NOC Level 3','Director'];
$severityOptions = ['Sev 1','Sev 2','Sev 3','Sev 4','Critical','Standard','Planned'];
$statusOptions = ['Open','Assigned','In Progress','Resolved - Awaiting Customer Confirmation','Closed','Reopened'];

$pageTitle = 'Trouble Tickets';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/tickets/list.php';
include APP_DIR . '/views/layout/footer.php';
