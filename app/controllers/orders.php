<?php
// ============================================================
// Orders Controller — new order, tracking, order detail
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? ($_GET['page'] === 'new_order' ? 'new' : 'list');

// ------------------------------------------------------------------
// GET: Cron — Process pending notifications
// ------------------------------------------------------------------
if ($action === 'cron_process_notifications') {
    requireLogin();
    $processed = processNotificationQueue(20);
    echo "Processed $processed notifications.";
    exit;
}

// ------------------------------------------------------------------
// POST: Create new order
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $serviceType = $_POST['service_type'] ?? '';
    $allowed = ['FTTH','FTTB','DIA','Dedicated Layer 2','Remote Hands Only'];
    if (!in_array($serviceType, $allowed)) {
        setFlash('danger', 'Invalid service type.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    // Server-side commercial calculation
    $comm = calculateCommercials($_POST);
    $orderNum = generateOrderNumber();

    $partnerId = isPartnerUser() ? $user['partner_id'] : (int)($_POST['partner_id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Partner is required.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    // Resolve KAM id from name
    $kamName = $_POST['assigned_kam'] ?? '';
    $kamStmt = $db->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'KAM' LIMIT 1");
    $kamStmt->execute([$kamName]);
    $kamId = $kamStmt->fetchColumn() ?: null;

    $stmt = $db->prepare("INSERT INTO orders (
        order_number, partner_id, kam_id, assigned_kam_name,
        customer_name, customer_location, gps_coordinates, building_name, floor_number, apartment_number,
        customer_contact_name, customer_contact_phone, customer_contact_email,
        service_type, fttx_package, bandwidth, nni_location, aggregate_capacity, contract_term, special_requirements,
        usd_tzs_rate, base_nrc_usd, remote_hands_nrc_usd, nrc_subtotal_usd, vat_on_nrc, total_nrc_incl_vat,
        base_mrc, mrc_currency, discount_pct, discount_amount, vat_on_mrc, total_mrc_incl_vat,
        status, created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $orderNum, $partnerId, $kamId, $kamName,
        $_POST['customer_name'] ?? '', $_POST['customer_location'] ?? '', $_POST['gps_coordinates'] ?? '',
        $_POST['building_name'] ?? '', $_POST['floor_number'] ?? '', $_POST['apartment_number'] ?? '',
        $_POST['customer_contact_name'] ?? '', $_POST['customer_contact_phone'] ?? '', $_POST['customer_contact_email'] ?? '',
        $serviceType,
        $_POST['fttx_package'] ?? null, $_POST['bandwidth'] ?? null,
        $_POST['nni_location'] ?? null, $_POST['aggregate_capacity'] ?? null,
        $_POST['contract_term'] ?? null, $_POST['special_requirements'] ?? null,
        USD_TZS_RATE,
        $comm['base_nrc_usd'], $comm['remote_hands_nrc_usd'], $comm['nrc_subtotal_usd'],
        $comm['vat_on_nrc'], $comm['total_nrc_incl_vat'],
        $comm['base_mrc'], $comm['mrc_currency'], $comm['discount_pct'],
        $comm['discount_amount'], $comm['vat_on_mrc'], $comm['total_mrc_incl_vat'],
        'Submitted', $user['id'],
    ]);
    $orderId = $db->lastInsertId();

    // Timeline entry
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Submitted', 'Order submitted via partner portal.', $user['id']]);

    // Handle file uploads
    if (!empty($_FILES['documents']['name'][0])) {
        foreach ($_FILES['documents']['name'] as $i => $fname) {
            if (!$fname) continue;
            $file = [
                'name'     => $fname,
                'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                'error'    => $_FILES['documents']['error'][$i],
                'size'     => $_FILES['documents']['size'][$i],
            ];
            try {
                $up = uploadFile($file, 'orders/' . $orderId);
                $db->prepare("INSERT INTO order_documents (order_id, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?)")
                   ->execute([$orderId, 'Supporting Document', $up['name'], $up['path'], $up['size'], $user['id']]);
            } catch (RuntimeException $e) {
                // Non-fatal but warn user
                setFlash('warning', 'Document upload failed: ' . e($e->getMessage()));
            }
        }
    }

    queueOrderNotification($orderId, 'Order Submitted');
    auditLog("Created order $orderNum", 'orders', $orderId);
    setFlash('success', "Service order <strong>$orderNum</strong> submitted successfully.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Save BSA Solution Design
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_bsa_design') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderSt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $orderSt->execute([$orderId]);
    if (!$orderSt->fetch()) { setFlash('danger', 'Order not found.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $db->prepare("UPDATE orders SET
        bsa_feasibility_status = ?, bsa_delivery_method = ?, bsa_delivery_cost = ?,
        bsa_sla_level = ?, bsa_lead_time = ?, bsa_special_conditions = ?,
        bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
        status = 'Awaiting BSA Approval', bsa_approved_at = NULL, bsa_revision_note = NULL, updated_at = NOW()
        WHERE id = ?")->execute([
        $_POST['bsa_feasibility_status'] ?? null,
        $_POST['bsa_delivery_method'] ?? null,
        $_POST['bsa_delivery_cost'] ?: null,
        $_POST['bsa_sla_level'] ?? null,
        $_POST['bsa_lead_time'] ?? null,
        $_POST['bsa_special_conditions'] ?? null,
        $user['id'],
        $orderId
    ]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Awaiting BSA Approval', 'BSA solution design completed, pending approval.', $user['id']]);

    queueOrderNotification($orderId, 'BSA Design Submitted');
    auditLog("BSA design saved, pending approval", 'orders', $orderId);
    setFlash('success', 'BSA solution design saved, pending approval.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: BSA Approve
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bsa_approve') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $db->prepare("UPDATE orders SET status = 'Awaiting Commercial Approval', bsa_approved_at = NOW(), bsa_approved_by = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$user['id'], $orderId]);
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Awaiting Commercial Approval', 'BSA design approved.', $user['id']]);
    queueOrderNotification($orderId, 'BSA Design Approved');
    auditLog("BSA approved for order", 'orders', $orderId);
    setFlash('success', 'BSA design approved. Proceeding to commercial approval.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: BSA Request Revision
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bsa_revision') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason  = $_POST['bsa_revision_note'] ?? 'Revision requested.';
    $db->prepare("UPDATE orders SET status = 'Feasibility Review', bsa_revision_note = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$reason, $orderId]);
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Feasibility Review', "BSA revision requested: $reason", $user['id']]);
    queueOrderNotification($orderId, 'BSA Revision Requested');
    auditLog("BSA revision requested for order", 'orders', $orderId);
    setFlash('warning', 'BSA revision requested. Order returned to Feasibility Review.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Activate Service (order → active_service)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'activate_service') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    if (!$o) { setFlash('danger', 'Order not found.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }
    if ($o['status'] !== 'Activated') { setFlash('danger', 'Order must be in Activated status.'); header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId); exit; }

    $serviceId = generateServiceId();
    $circuitId = $_POST['circuit_id'] ?? 'CKT-' . $o['order_number'];

    // Create active service record
    $db->prepare("INSERT INTO active_services (
        service_id, order_id, partner_id, customer_name, service_type,
        circuit_id, bandwidth_capacity, location, building_name, kam_id,
        activation_date, billing_start_date, status, monitoring_status,
        onu_serial, router_serial, ip_address, notes
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
        $serviceId, $orderId, $o['partner_id'], $o['customer_name'], $o['service_type'],
        $circuitId, $o['bandwidth'] ?: $o['fttx_package'], $o['customer_location'], $o['building_name'], $o['kam_id'],
        $_POST['activation_date'] ?: date('Y-m-d'),
        $_POST['billing_start_date'] ?: date('Y-m-d'),
        'Active', 'Unknown',
        $_POST['onu_serial'] ?? null, $_POST['router_serial'] ?? null,
        $_POST['ip_address'] ?? null, $_POST['notes'] ?? null
    ]);

    // Update order with service/circuit IDs
    $db->prepare("UPDATE orders SET service_id = ?, circuit_id = ?, activation_date = ?, billing_trigger_date = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$serviceId, $circuitId, $_POST['activation_date'] ?: date('Y-m-d'), $_POST['billing_start_date'] ?: date('Y-m-d'), $orderId]);

    // Link assets if serials provided
    if (!empty($_POST['onu_serial'])) {
        $db->prepare("INSERT INTO assets (partner_id, active_service_id, order_id, asset_type, serial_number, model, customer_name, site_location, status)
            VALUES (?,?,?,?,?,?,?,?,'Deployed') ON DUPLICATE KEY UPDATE active_service_id = VALUES(active_service_id), status = 'Deployed'")
            ->execute([$o['partner_id'], $db->lastInsertId(), $orderId, 'ONU', $_POST['onu_serial'], $_POST['onu_model'] ?? '', $o['customer_name'], $o['customer_location']]);
    }
    if (!empty($_POST['router_serial'])) {
        $svcId = $db->lastInsertId();
        $db->prepare("INSERT INTO assets (partner_id, active_service_id, order_id, asset_type, serial_number, model, customer_name, site_location, status)
            VALUES (?,?,?,?,?,?,?,?,'Deployed') ON DUPLICATE KEY UPDATE active_service_id = VALUES(active_service_id), status = 'Deployed'")
            ->execute([$o['partner_id'], $svcId, $orderId, 'Router', $_POST['router_serial'], $_POST['router_model'] ?? '', $o['customer_name'], $o['customer_location']]);
    }

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Activated', "Service activated — ID: $serviceId, Circuit: $circuitId", $user['id']]);

    queueOrderNotification($orderId, 'Service Activated');
    auditLog("Activated service $serviceId for order", 'orders', $orderId);
    setFlash('success', "Service activated — <strong>$serviceId</strong> created.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Trigger Billing
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'trigger_billing') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'Access denied.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    if (!$o) { setFlash('danger', 'Order not found.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }
    if (!in_array($o['status'], ['Activated', 'Billing Triggered'])) { setFlash('danger', 'Order must be Activated first.'); header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId); exit; }

    $db->prepare("UPDATE orders SET status = 'Billing Triggered', billing_trigger_date = CURDATE(), updated_at = NOW() WHERE id = ?")->execute([$orderId]);
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Billing Triggered', 'Billing triggered.', $user['id']]);

    // Update active service billing start date
    $db->prepare("UPDATE active_services SET billing_start_date = CURDATE() WHERE order_id = ?")->execute([$orderId]);

    queueOrderNotification($orderId, 'Billing Triggered');
    auditLog("Billing triggered for order", 'orders', $orderId);
    setFlash('success', 'Billing triggered successfully.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: UAT Accept
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'uat_accept') {
    verifyCsrf();

    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    if (!$o) { setFlash('danger', 'Order not found.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }
    if ($o['status'] !== 'UAT - Awaiting Confirmation') { setFlash('danger', 'UAT confirmation not pending.'); header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId); exit; }

    $db->prepare("UPDATE orders SET status = 'Activated', uat_accepted_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$orderId]);
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Activated', 'UAT accepted by partner.', $user['id']]);

    auditLog("UAT accepted for order", 'orders', $orderId);
    setFlash('success', 'UAT accepted. Order is now Activated.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: UAT Reject
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'uat_reject') {
    verifyCsrf();

    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $o = $stmt->fetch();
    if (!$o) { setFlash('danger', 'Order not found.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }
    if ($o['status'] !== 'UAT - Awaiting Confirmation') { setFlash('danger', 'UAT confirmation not pending.'); header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId); exit; }

    $reason = $_POST['rejection_reason'] ?? 'No reason provided.';
    $db->prepare("UPDATE orders SET status = 'Testing', uat_rejected_at = NOW(), uat_rejection_reason = ?, updated_at = NOW() WHERE id = ?")->execute([$reason, $orderId]);
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Testing', "UAT rejected: $reason", $user['id']]);

    auditLog("UAT rejected for order", 'orders', $orderId);
    setFlash('warning', 'UAT rejected. Order returned to Testing.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Update order status
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger', 'You do not have permission to update order status.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $note      = $_POST['note'] ?? 'Status updated.';

    $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")->execute([$orderId, $newStatus, $note, $user['id']]);
    auditLog("Updated order status to $newStatus", 'orders', $orderId);
    setFlash('success', 'Order status updated.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// Order Detail
// ------------------------------------------------------------------
if ($action === 'detail' || $_GET['page'] === 'order_detail') {
    $orderId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('o');
    $stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id WHERE o.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$orderId], $pw['params']));
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p style="padding:40px">Order not found.</p>'; exit; }

    $timeline = $db->prepare("SELECT ot.*, u.full_name FROM order_timeline ot LEFT JOIN users u ON ot.changed_by = u.id WHERE ot.order_id = ? ORDER BY ot.changed_at DESC");
    $timeline->execute([$orderId]);
    $timeline = $timeline->fetchAll();

    $docs = $db->prepare("SELECT od.*, u.full_name FROM order_documents od LEFT JOIN users u ON od.uploaded_by = u.id WHERE od.order_id = ? ORDER BY od.uploaded_at DESC");
    $docs->execute([$orderId]);
    $docs = $docs->fetchAll();

    $allStatuses = ['Submitted','Feasibility Review','Awaiting BSA Approval','Awaiting Commercial Approval','Awaiting Management Approval','Approved','Provisioning','Installation','Testing','UAT','UAT - Awaiting Confirmation','Activated','Billing Triggered','Closed','Cancelled'];

    $pageTitle = 'Order ' . $order['order_number'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/orders/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// New Order form
// ------------------------------------------------------------------
if ($_GET['page'] === 'new_order') {
    $partners = [];
    if (!isPartnerUser()) {
        $partners = $db->query("SELECT id, name FROM partners WHERE status = 'Active' ORDER BY name")->fetchAll();
    }
    $kamList = $db->query("SELECT id, full_name FROM users WHERE role = 'KAM' AND is_active = 1 ORDER BY full_name")->fetchAll();

    $pageTitle = 'New Service Order';
    $extraJs   = 'orders';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/orders/new_order.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Order Tracking List
// ------------------------------------------------------------------
$pw = partnerWhere('o');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

// Filters
$filterStatus  = $_GET['status'] ?? '';
$filterService = $_GET['service_type'] ?? '';
$filterSearch  = $_GET['q'] ?? '';

if ($filterStatus)  { $where .= " AND o.status = ?";       $params[] = $filterStatus; }
if ($filterService) { $where .= " AND o.service_type = ?"; $params[] = $filterService; }
if ($filterSearch)  { $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.circuit_id LIKE ?)"; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN partners p ON o.partner_id = p.id $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 20;
$page  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT o.*, p.name as partner_name FROM orders o JOIN partners p ON o.partner_id = p.id $where ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$allStatuses  = ['Submitted','Feasibility Review','Awaiting BSA Approval','Awaiting Commercial Approval','Awaiting Management Approval','Approved','Provisioning','Installation','Testing','UAT','UAT - Awaiting Confirmation','Activated','Billing Triggered','Closed','Cancelled'];
$serviceTypes = ['FTTH','FTTB','DIA','Dedicated Layer 2','Remote Hands Only'];

$pageTitle = 'Order Tracking';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/orders/tracking.php';
include APP_DIR . '/views/layout/footer.php';
