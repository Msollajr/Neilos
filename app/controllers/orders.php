<?php
// ============================================================
// Orders Controller — v1.0 Full Lifecycle
// Statuses: Feasibility Review → Await Commercial Approval →
//           Management Approval → Pending SOF → SOF Review →
//           Installation → Testing → UAT → Closed
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? ($_GET['page'] === 'new_order' ? 'new' : 'list');

// Block contractor users from listing all orders
if (isContractorUser() && !in_array($action, ['list','detail'])) {
    // Contractors can only use contractor module
    header('Location: ' . APP_URL . '/?page=contractor');
    exit;
}

// ------------------------------------------------------------------
// POST: Create new feasibility request
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();
    requirePermission('orders.create');

    $serviceType = trim($_POST['service_type'] ?? '');
    $allowed = ['Layer 2 ( last mile)', 'FTTH', 'FTTB', 'BIA (Broadband Internet Access)', 'Remote Hands Only', 'DIA', 'Dedicated Layer 2', 'Remote Hands'];
    if (!in_array($serviceType, $allowed)) {
        setFlash('danger', 'Invalid service type.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    $partnerId = isPartnerUser() ? $user['partner_id'] : (int)($_POST['partner_id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Partner is required.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    // Resolve KAM: Partner Users automatically use their Partner's assigned KAM
    $pKamStmt = $db->prepare("SELECT kam_id, assigned_kam_name FROM partners WHERE id = ?");
    $pKamStmt->execute([$partnerId]);
    $pKam = $pKamStmt->fetch();

    if (isPartnerUser()) {
        $kamName = $pKam['assigned_kam_name'] ?? '';
        $kamId   = $pKam['kam_id'] ?? null;
    } else {
        $kamName = $_POST['assigned_kam'] ?? ($pKam['assigned_kam_name'] ?? '');
        $kamStmt = $db->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'KAM' LIMIT 1");
        $kamStmt->execute([$kamName]);
        $kamId = $kamStmt->fetchColumn() ?: ($pKam['kam_id'] ?? null);
    }

    $orderNum = generateOrderNumber();

    $stmt = $db->prepare("INSERT INTO orders (
        order_number, partner_id, kam_id, assigned_kam_name,
        customer_name, customer_location, gps_coordinates, site_category,
        building_name, floor_number, apartment_number,
        customer_contact_name, customer_contact_phone, customer_contact_email,
        service_type, fttx_package, bandwidth, nni_location, aggregate_capacity,
        contract_term, special_requirements,
        usd_tzs_rate, base_nrc_usd, remote_hands_nrc_usd, nrc_subtotal_usd,
        vat_on_nrc, total_nrc_incl_vat,
        base_mrc, mrc_currency, discount_pct, discount_amount, vat_on_mrc, total_mrc_incl_vat,
        standard_nrc, standard_mrc,
        status, created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $comm = calculateCommercials($_POST);

    $inserted = false;
    $attempts = 0;
    while (!$inserted && $attempts < 5) {
        $attempts++;
        try {
            $stmt->execute([
                $orderNum, $partnerId, $kamId, $kamName,
                $_POST['customer_name'] ?? '', $_POST['customer_location'] ?? '',
                $_POST['gps_coordinates'] ?? '', !empty($_POST['site_category']) ? trim($_POST['site_category']) : null,
                $_POST['building_name'] ?? '', $_POST['floor_number'] ?? '', $_POST['apartment_number'] ?? '',
                $_POST['customer_contact_name'] ?? '', $_POST['customer_contact_phone'] ?? '',
                $_POST['customer_contact_email'] ?? '',
                $serviceType,
                $_POST['fttx_package'] ?? null, $_POST['bandwidth'] ?? null,
                $_POST['nni_location'] ?? null, $_POST['aggregate_capacity'] ?? null,
                $_POST['contract_term'] ?? null, $_POST['special_requirements'] ?? null,
                USD_TZS_RATE,
                $comm['base_nrc_usd'], $comm['remote_hands_nrc_usd'], $comm['nrc_subtotal_usd'],
                $comm['vat_on_nrc'], $comm['total_nrc_incl_vat'],
                $comm['base_mrc'], $comm['mrc_currency'], $comm['discount_pct'],
                $comm['discount_amount'], $comm['vat_on_mrc'], $comm['total_mrc_incl_vat'],
                $comm['base_nrc_usd'],  // standard_nrc = system-calculated
                $comm['base_mrc'],      // standard_mrc = system-calculated
                'Feasibility Review', $user['id'],
            ]);
            $inserted = true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                $orderNum = generateOrderNumber();
            } else {
                throw $e;
            }
        }
    }
    $orderId = $db->lastInsertId();

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Feasibility Review', 'Feasibility request submitted by partner.', $user['id']]);

    // Handle file uploads
    if (!empty($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $filesCount = count($_FILES['documents']['name']);
        for ($i = 0; $i < $filesCount; $i++) {
            $fname = $_FILES['documents']['name'][$i] ?? '';
            $err   = $_FILES['documents']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if (!$fname || $err === UPLOAD_ERR_NO_FILE) continue;

            $file = [
                'name'     => $fname,
                'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                'error'    => $err,
                'size'     => $_FILES['documents']['size'][$i]
            ];
            try {
                $up = uploadFile($file, 'orders/' . $orderId);
                $db->prepare("INSERT INTO order_documents (order_id, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?)")
                   ->execute([$orderId, 'Supporting Document', $up['name'], $up['path'], $up['size'], $user['id']]);
            } catch (Exception $e) {
                setFlash('warning', 'Document upload note: ' . e($e->getMessage()));
            }
        }
    }

    queueOrderNotification($orderId, 'Feasibility Submitted');
    auditLog("Created feasibility request $orderNum", 'orders', $orderId);
    setFlash('success', "Feasibility request <strong>$orderNum</strong> submitted. BSA will review shortly.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: BSA — Technically Feasible
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bsa_feasible') {
    verifyCsrf();
    if (!hasRole('BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $revisedNrc = $_POST['revised_nrc'] !== '' ? (float)$_POST['revised_nrc'] : null;
    $nrcJustification = trim($_POST['nrc_justification'] ?? '');
    $technicalRemarks = trim($_POST['technical_remarks'] ?? '');

    // Validation
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $standardNrc = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 60.00);

    // Spec rule 7.2: If NRC increases, justification is mandatory
    if ($revisedNrc !== null && $revisedNrc > $standardNrc && !$nrcJustification) {
        setFlash('danger','NRC justification is mandatory when revised NRC is higher than standard NRC.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    if (!$technicalRemarks && $revisedNrc !== null) {
        setFlash('danger','Technical remarks are mandatory when NRC is revised.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Spec rule 7.2: Any discount in NRC triggers Director / Management approval
    if ($revisedNrc !== null && $revisedNrc < $standardNrc) {
        if (!$nrcJustification) {
            setFlash('danger','NRC justification is mandatory when offering an NRC discount.');
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }

        $effectiveNrc = ($revisedNrc !== null && $revisedNrc !== '') ? (float)$revisedNrc : $standardNrc;
        $rhNrc        = (float)($o['remote_hands_nrc_usd'] ?? 0);
        $nrcSub       = $effectiveNrc + $rhNrc;
        $vatNrc       = round($nrcSub * 0.18, 2);
        $totNrc       = round($nrcSub + $vatNrc, 2);

        $db->prepare("UPDATE orders SET
            bsa_technical_result = 'Technically Feasible',
            revised_nrc = ?, nrc_justification = ?,
            nrc_subtotal_usd = ?, vat_on_nrc = ?, total_nrc_incl_vat = ?,
            bsa_special_conditions = ?,
            bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
            status = 'Management Approval', updated_at = NOW()
            WHERE id = ?")->execute([
            $revisedNrc, $nrcJustification, $nrcSub, $vatNrc, $totNrc, $technicalRemarks, $user['id'], $orderId
        ]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Management Approval', "BSA provided NRC discount ($revisedNrc vs standard $standardNrc) — routed to Management Approval.", $user['id']]);

        evaluateAndSyncOrderStatus($orderId, 'bsa_feasible');

        queueOrderNotification($orderId, 'KAM Requires Further Approval');
        auditLog("BSA marked technically feasible with NRC discount for order #$orderId — routed to Management", 'orders', $orderId);
        setFlash('warning', 'NRC discount offered — order routed to Management Approval queue.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    $effectiveNrc = ($revisedNrc !== null && $revisedNrc !== '') ? (float)$revisedNrc : $standardNrc;
    $rhNrc        = (float)($o['remote_hands_nrc_usd'] ?? 0);
    $nrcSub       = $effectiveNrc + $rhNrc;
    $vatNrc       = round($nrcSub * 0.18, 2);
    $totNrc       = round($nrcSub + $vatNrc, 2);

    $db->prepare("UPDATE orders SET
        bsa_technical_result = 'Technically Feasible',
        revised_nrc = ?, nrc_justification = ?,
        nrc_subtotal_usd = ?, vat_on_nrc = ?, total_nrc_incl_vat = ?,
        bsa_special_conditions = ?,
        bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
        status = 'Await Commercial Approval', updated_at = NOW()
        WHERE id = ?")->execute([
        $revisedNrc, $nrcJustification, $nrcSub, $vatNrc, $totNrc, $technicalRemarks, $user['id'], $orderId
    ]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Await Commercial Approval', 'BSA marked technically feasible — pending partner commercial acceptance.', $user['id']]);

    evaluateAndSyncOrderStatus($orderId, 'bsa_feasible');

    queueOrderNotification($orderId, 'BSA Feasibility Approved');
    auditLog("BSA marked technically feasible for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Order marked Technically Feasible. KAM notified for commercial approval.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: BSA — Technically Not Feasible
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bsa_not_feasible') {
    verifyCsrf();
    if (!hasRole('BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason  = trim($_POST['not_feasible_reason'] ?? '');
    if (!$reason) { setFlash('danger','Technical remarks are mandatory for Not Feasible.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    $db->prepare("UPDATE orders SET
        bsa_technical_result = 'Technically Not Feasible',
        bsa_not_feasible_reason = ?,
        bsa_reviewed_by = ?, bsa_reviewed_at = NOW(),
        status = 'Not Feasible', updated_at = NOW()
        WHERE id = ?")->execute([$reason, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Not Feasible', "BSA: Technically Not Feasible — $reason", $user['id']]);

    queueOrderNotification($orderId, 'Technically Not Feasible');
    auditLog("BSA marked NOT feasible for order #$orderId", 'orders', $orderId);
    setFlash('warning', 'Order marked Technically Not Feasible. Partner has been notified.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM — Approve Commercial (standard or unchanged MRC)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'kam_approve') {
    verifyCsrf();
    if (!hasRole('KAM') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $revisedMrc = $_POST['revised_mrc'] !== '' ? (float)$_POST['revised_mrc'] : null;
    $mrcJustification = trim($_POST['mrc_justification'] ?? '');

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    // If revised MRC is below standard — auto-route to Management Approval
    $standardMrc = (float)($o['standard_mrc'] ?? $o['base_mrc']);
    if ($revisedMrc !== null && $revisedMrc < $standardMrc) {
        if (!$mrcJustification) {
            setFlash('danger','Commercial discount justification is mandatory when offering pricing below standard.');
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }

        $effectiveMrc = ($revisedMrc !== null && $revisedMrc !== '') ? (float)$revisedMrc : $standardMrc;
        $vatMrc       = round($effectiveMrc * 0.18, 2);
        $totMrc       = round($effectiveMrc + $vatMrc, 2);

        $db->prepare("UPDATE orders SET
            revised_mrc = ?, mrc_justification = ?,
            vat_on_mrc = ?, total_mrc_incl_vat = ?,
            kam_approved_by = ?, kam_approved_at = NOW(),
            status = 'Management Approval', updated_at = NOW()
            WHERE id = ?")->execute([$revisedMrc, $mrcJustification, $vatMrc, $totMrc, $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Management Approval', "KAM provided MRC discount ($revisedMrc vs standard $standardMrc) — automatically routed to Management Approval.", $user['id']]);

        queueOrderNotification($orderId, 'KAM Requires Further Approval');
        auditLog("KAM escalated discounted MRC to Management for order #$orderId", 'orders', $orderId);
        setFlash('warning', 'MRC pricing is below standard price book — order routed to Management Approval queue.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    if ($revisedMrc !== null && $revisedMrc != $standardMrc && !$mrcJustification) {
        setFlash('danger','Commercial justification is mandatory when MRC is changed.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $effectiveMrc = ($revisedMrc !== null && $revisedMrc !== '') ? (float)$revisedMrc : $standardMrc;
    $vatMrc       = round($effectiveMrc * 0.18, 2);
    $totMrc       = round($effectiveMrc + $vatMrc, 2);

    $db->prepare("UPDATE orders SET
        revised_mrc = ?, mrc_justification = ?,
        vat_on_mrc = ?, total_mrc_incl_vat = ?,
        kam_approved_by = ?, kam_approved_at = NOW(),
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([$revisedMrc, $mrcJustification, $vatMrc, $totMrc, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'KAM: Commercial approved. Partner must generate and upload signed SOF.', $user['id']]);

    queueOrderNotification($orderId, 'Feasibility Approved');
    auditLog("KAM approved commercial for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Commercial approved. Partner notified to sign and upload SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM — Requires Further (Management) Approval
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'kam_escalate') {
    verifyCsrf();
    if (!hasRole('KAM') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $revisedMrc = $_POST['revised_mrc'] !== '' ? (float)$_POST['revised_mrc'] : null;
    $mrcJustification = trim($_POST['mrc_justification'] ?? '');

    if (!$mrcJustification) {
        setFlash('danger','Justification required when escalating to Management.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $db->prepare("UPDATE orders SET
        revised_mrc = ?, mrc_justification = ?,
        kam_approved_by = ?, kam_approved_at = NOW(),
        status = 'Management Approval', updated_at = NOW()
        WHERE id = ?")->execute([$revisedMrc, $mrcJustification, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Management Approval', "KAM: Escalated for exception approval. Reason: $mrcJustification", $user['id']]);

    queueOrderNotification($orderId, 'KAM Requires Further Approval');
    auditLog("KAM escalated to Management for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Order escalated to Management Approval queue.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Management — Approve as requested
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'management_approve') {
    verifyCsrf();
    if (!hasRole('Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $approvedPrice = $_POST['management_approved_price'] !== '' ? (float)$_POST['management_approved_price'] : null;
    $remarks = trim($_POST['management_remarks'] ?? '');
    $remarksVisible = (int)($_POST['management_remarks_visible'] ?? 0);

    $db->prepare("UPDATE orders SET
        management_approved_price = ?, management_remarks = ?,
        management_remarks_visible = ?,
        management_approved_by = ?, management_approved_at = NOW(),
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([$approvedPrice, $remarks, $remarksVisible, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'Management approved price — routed to Partner for SOF signature.', $user['id']]);

    evaluateAndSyncOrderStatus($orderId, 'set_management_price');

    queueOrderNotification($orderId, 'Management Pricing Approved');
    auditLog("Management approved exception for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Management approval granted. Partner notified to sign SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Management — Reject exception (revert to standard price)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'management_reject') {
    verifyCsrf();
    if (!hasRole('Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $remarks = trim($_POST['management_remarks'] ?? '');
    $remarksVisible = (int)($_POST['management_remarks_visible'] ?? 0);

    // Revert to standard MRC
    $db->prepare("UPDATE orders SET
        revised_mrc = NULL, management_remarks = ?,
        management_remarks_visible = ?,
        management_approved_by = ?, management_approved_at = NOW(),
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([$remarks, $remarksVisible, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'Management: Exception rejected. Standard price applies. Proceeding to SOF.', $user['id']]);

    queueOrderNotification($orderId, 'Feasibility Approved');
    auditLog("Management rejected exception, reverted to standard for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Exception rejected. Standard price applies. Partner notified to sign SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Generate / Print SOF (mark generation time)
// ------------------------------------------------------------------
if ($action === 'generate_sof') {
    $orderId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('o');
    $stmt = $db->prepare("SELECT o.*, p.name as partner_name,
        pka.registered_name, pka.address as partner_address,
        pka.auth_signatory_name, pka.auth_signatory_email, pka.auth_signatory_mobile,
        pka.finance_contact_name, pka.finance_contact_email, pka.billing_email,
        pka.tech_contact_name, pka.tech_contact_email
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = o.partner_id
        WHERE o.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$orderId], $pw['params']));
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p>Order not found.</p>'; exit; }

    // Record that SOF was generated
    $db->prepare("UPDATE orders SET sof_generated_at = NOW() WHERE id = ? AND sof_generated_at IS NULL")
       ->execute([$orderId]);

    auditLog("SOF generated for order #$orderId", 'orders', $orderId);

    $pageTitle = 'Service Order Form — ' . $order['order_number'];
    include APP_DIR . '/views/orders/generate_sof.php';
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Upload Signed SOF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_signed_sof') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || $o['status'] !== 'Pending SOF') {
        setFlash('danger','Order must be in Pending SOF status.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    if (empty($_FILES['signed_sof']['name'])) {
        setFlash('danger','Please select a signed SOF file to upload.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    try {
        $up = uploadFile($_FILES['signed_sof'], 'orders/'.$orderId.'/sof');
        $db->prepare("UPDATE orders SET
            sof_signed_file = ?, sof_signed_filename = ?, sof_uploaded_at = NOW(),
            status = 'SOF Review', updated_at = NOW()
            WHERE id = ?")->execute([$up['path'], $up['name'], $orderId]);

        $db->prepare("INSERT INTO order_documents (order_id, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?)")
           ->execute([$orderId, 'Signed SOF', $up['name'], $up['path'], $up['size'], $user['id']]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'SOF Review', 'Partner uploaded signed SOF. Awaiting Neilos countersignature.', $user['id']]);

        queueOrderNotification($orderId, 'Signed SOF Uploaded');
        auditLog("Signed SOF uploaded for order #$orderId", 'orders', $orderId);
        setFlash('success', 'Signed SOF uploaded. Neilos will countersign and proceed to installation.');
    } catch (RuntimeException $e) {
        setFlash('danger', 'Upload failed: ' . e($e->getMessage()));
    }

    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Return to Feasibility from Pending SOF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_feasibility') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $returnAction = $_POST['return_action'] ?? '';  // back_to_survey | back_to_pricing | start_project
    $returnReason = trim($_POST['return_reason'] ?? '');
    $returnRemarks = trim($_POST['return_remarks'] ?? '');

    if (!$returnReason || !$returnRemarks) {
        setFlash('danger','Please select a reason and enter remarks.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Determine route
    $route = match($returnAction) {
        'back_to_survey'  => 'BSA',
        'back_to_pricing' => 'KAM',
        default           => 'BSA',
    };

    $newStatus = $route === 'KAM' ? 'Await Commercial Approval' : 'Feasibility Review';

    $db->prepare("UPDATE orders SET
        status = ?, return_reason = ?, return_remarks = ?,
        return_route = ?, returned_by = ?, returned_at = NOW(),
        updated_at = NOW()
        WHERE id = ?")->execute([$newStatus, $returnReason, $returnRemarks, $route, $user['id'], $orderId]);

    $orderStmt = $db->prepare("SELECT standard_nrc, revised_nrc, standard_mrc, revised_mrc FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $oRow = $orderStmt->fetch();

    // Audit return
    $db->prepare("INSERT INTO order_returns (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to, old_nrc, old_mrc) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([$orderId, $user['id'], 'Pending SOF', $newStatus, $returnReason, $returnRemarks, $route, $oRow['revised_nrc'] ?? $oRow['standard_nrc'], $oRow['revised_mrc'] ?? $oRow['standard_mrc']]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, $newStatus, "Partner returned to $route: $returnReason — $returnRemarks", $user['id']]);

    queueOrderNotification($orderId, 'Partner Returned Feasibility');
    auditLog("Partner returned order #$orderId to $route ($returnReason)", 'orders', $orderId);
    setFlash('warning', "Order returned to $route for review.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Internal — Return SOF for Correction
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_sof') {
    verifyCsrf();
    if (isPartnerUser()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $comments = trim($_POST['sof_return_comments'] ?? '');
    if (!$comments) { setFlash('danger','Comments required when returning SOF.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    $db->prepare("UPDATE orders SET status = 'Pending SOF', sof_return_comments = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$comments, $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', "SOF returned for correction: $comments", $user['id']]);

    auditLog("SOF returned for correction on order #$orderId", 'orders', $orderId);
    setFlash('warning', 'SOF returned to partner for correction.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM/Admin — Upload Countersigned SOF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_countersigned_sof') {
    verifyCsrf();
    if (!hasRole('KAM') && !isAdmin()) { setFlash('danger','Only KAM or Admin can upload countersigned SOF.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    if (empty($_FILES['countersigned_sof']['name'])) {
        setFlash('danger','Please select the countersigned SOF file.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    try {
        $up = uploadFile($_FILES['countersigned_sof'], 'orders/'.$orderId.'/sof');
        $db->prepare("UPDATE orders SET
            countersigned_sof_file = ?, countersigned_sof_filename = ?,
            countersigned_sof_by = ?, countersigned_sof_at = NOW(),
            updated_at = NOW()
            WHERE id = ?")->execute([$up['path'], $up['name'], $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_documents (order_id, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?)")
           ->execute([$orderId, 'Countersigned SOF', $up['name'], $up['path'], $up['size'], $user['id']]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'SOF Review', 'Countersigned SOF uploaded. Ready to proceed to project.', $user['id']]);

        queueOrderNotification($orderId, 'Countersigned SOF Uploaded');
        auditLog("Countersigned SOF uploaded for order #$orderId", 'orders', $orderId);
        setFlash('success', 'Countersigned SOF uploaded. You can now proceed to project.');
    } catch (RuntimeException $e) {
        setFlash('danger', 'Upload failed: ' . e($e->getMessage()));
    }

    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM/Admin — Proceed to Project (Installation)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'proceed_to_project') {
    verifyCsrf();
    if (!hasRole('KAM') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();

    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }
    if (!$o['countersigned_sof_file']) {
        setFlash('danger','Cannot proceed to project until countersigned SOF is uploaded.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $db->prepare("UPDATE orders SET status = 'Installation', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    evaluateAndSyncOrderStatus($orderId, 'proceed_to_project');

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', 'Countersigned SOF verified — order released to Project Installation.', $user['id']]);

    queueOrderNotification($orderId, 'Proceed to Project');
    auditLog("Order #$orderId moved to Installation", 'orders', $orderId);
    setFlash('success', 'Order moved to Installation. Project Manager should assign a contractor.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager — Assign Contractor
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'assign_contractor') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $contractorPartnerId = (int)($_POST['contractor_partner_id'] ?? 0);
    $contractorUserId = (int)($_POST['contractor_user_id'] ?? 0) ?: null;
    $targetDate = $_POST['target_date'] ?? null;
    $workOrderNotes = trim($_POST['work_order_notes'] ?? '');

    if (!$contractorPartnerId) {
        setFlash('danger','Please select a contractor.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Close any existing open assignments
    $db->prepare("UPDATE contractor_assignments SET status = 'Completed' WHERE order_id = ? AND status NOT IN ('Completed')")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO contractor_assignments (order_id, contractor_partner_id, contractor_user_id, assigned_by, target_date, work_order_notes) VALUES (?,?,?,?,?,?)")
       ->execute([$orderId, $contractorPartnerId, $contractorUserId, $user['id'], $targetDate ?: null, $workOrderNotes]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', "Contractor assigned (partner #$contractorPartnerId).", $user['id']]);

    queueOrderNotification($orderId, 'Contractor Assigned');
    auditLog("Contractor assigned to order #$orderId", 'orders', $orderId);
    setFlash('success', 'Contractor assigned successfully.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}


// ------------------------------------------------------------------
// POST: Project Manager/BSA — Approve Testing (Testing → UAT)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_testing') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || $o['status'] !== 'Testing') { setFlash('danger','Order must be in Testing status.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    $db->prepare("UPDATE orders SET status = 'UAT', uat_notified_at = NOW(), uat_deadline = DATE_ADD(NOW(), INTERVAL 72 HOUR), updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'UAT', 'Testing approved. Partner notified for UAT acceptance (72hr window).', $user['id']]);

    queueOrderNotification($orderId, 'Testing Approved');
    auditLog("Testing approved for order #$orderId, moved to UAT", 'orders', $orderId);
    setFlash('success', 'Testing approved. Partner notified for UAT.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Project Manager/BSA — Return to Contractor from Testing
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_contractor') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $comments = trim($_POST['return_comments'] ?? '');
    if (!$comments) { setFlash('danger','Comments required when returning to contractor.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    // Update assignment status
    $db->prepare("UPDATE contractor_assignments SET status = 'Returned' WHERE order_id = ? AND status = 'Completed Submitted'")
       ->execute([$orderId]);

    $db->prepare("UPDATE orders SET status = 'Installation', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', "Returned to contractor: $comments", $user['id']]);

    auditLog("Order #$orderId returned to contractor from Testing", 'orders', $orderId);
    setFlash('warning', 'Job returned to contractor for correction.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Accept Service (UAT → Closed)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'uat_accept') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Only the partner can accept service.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || $o['status'] !== 'UAT') { setFlash('danger','Order must be in UAT status.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    $today = date('Y-m-d');
    $db->prepare("UPDATE orders SET
        status = 'Closed',
        uat_accepted_at = NOW(), uat_accepted_by = ?,
        closed_date = ?, billing_start_date = ?,
        activation_date = ?,
        updated_at = NOW()
        WHERE id = ? AND status = 'UAT'")->execute([$user['id'], $today, $today, $today, $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Closed', "Partner accepted service. Order closed. Closed Date: $today. Billing Start Date: $today.", $user['id']]);

    // Create active service record
    $serviceId = 'SVC-' . date('ymd') . '-' . str_pad($orderId, 3, '0', STR_PAD_LEFT);
    $circuitId = 'CKT-' . $o['order_number'];
    $db->prepare("INSERT INTO active_services (service_id, order_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, building_name, kam_id, activation_date, billing_start_date, status, monitoring_status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE order_id=order_id")->execute([
        $serviceId, $orderId, $o['partner_id'], $o['customer_name'], $o['service_type'],
        $circuitId, $o['bandwidth'] ?: $o['fttx_package'], $o['customer_location'], $o['building_name'],
        $o['kam_id'], $today, $today, 'Active', 'Unknown'
    ]);

    $db->prepare("UPDATE orders SET service_id = ?, circuit_id = ? WHERE id = ? AND service_id IS NULL")
       ->execute([$serviceId, $circuitId, $orderId]);

    queueOrderNotification($orderId, 'Partner Accepted Service');
    auditLog("Partner accepted service — order #$orderId CLOSED. Billing start: $today", 'orders', $orderId, '', "closed_date=$today");
    setFlash('success', "Service accepted. Order <strong>closed</strong>. Billing start date: <strong>$today</strong>.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Return to Installation from UAT
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_installation') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Only the partner can return from UAT.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $reason  = trim($_POST['return_reason'] ?? '');
    if (!$reason) { setFlash('danger','Remarks required when returning to installation.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    $db->prepare("UPDATE orders SET status = 'Installation', uat_return_reason = ?, updated_at = NOW() WHERE id = ? AND status = 'UAT'")
       ->execute([$reason, $orderId]);

    $db->prepare("INSERT INTO order_returns (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to) VALUES (?,?,?,?,?,?,?)")
       ->execute([$orderId, $user['id'], 'UAT', 'Installation', 'Quality concern', $reason, 'Project Manager']);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Installation', "Partner returned the service to Installation during the UAT acceptance window.", $user['id']]);

    queueOrderNotification($orderId, 'Partner Returned Installation');
    auditLog("Partner returned order #$orderId from UAT to Installation", 'orders', $orderId);
    setFlash('warning', 'Order returned to Installation for correction.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Disabled Manual Billing Date Adjustment
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'adjust_billing_date') {
    setFlash('danger', 'Manual adjustment of billing start date is not permitted.');
    header('Location: ' . APP_URL . '/?page=orders');
    exit;
}

// ------------------------------------------------------------------
// POST: Update order status (Admin override)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    verifyCsrf();
    if (!isAdmin()) { setFlash('danger','Access denied. Admin only.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    $orderId        = (int)($_POST['order_id'] ?? 0);
    $newStatus      = $_POST['new_status'] ?? '';
    $newServiceType = $_POST['new_service_type'] ?? '';
    $note           = $_POST['note'] ?? 'Admin status override.';

    if (!empty($newServiceType)) {
        $db->prepare("UPDATE orders SET service_type = ? WHERE id = ?")->execute([$newServiceType, $orderId]);
    }
    if (!empty($newStatus)) {
        $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")->execute([$orderId, $newStatus, $note, $user['id']]);
    }
    auditLog("Admin status/service type override for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Order status updated by Admin.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete order (Admin / Management)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Admin or Management only.');
        header('Location: ' . APP_URL . '/?page=orders');
        exit;
    }

    $orderId = (int)($_POST['id'] ?? 0);
    if ($orderId > 0) {
        $stmt = $db->prepare("SELECT order_number FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $ordNum = $stmt->fetchColumn();

        $db->prepare("DELETE FROM order_timeline WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM order_documents WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM contractor_assignments WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM contractor_evidence WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM order_returns WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);

        auditLog("Deleted order #$ordNum (ID: $orderId)", 'orders', $orderId);
        setFlash('success', "Order <strong>" . e($ordNum) . "</strong> has been deleted.");
    }
    header('Location: ' . APP_URL . '/?page=orders');
    exit;
}

// ------------------------------------------------------------------
// Generate Service Order Form (SOF)
// ------------------------------------------------------------------
if ($action === 'generate_sof') {
    $orderId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('o');

    $stmt = $db->prepare("SELECT o.*, p.name as partner_name,
        pka.registered_name as partner_registered_name,
        pka.address as partner_address, pka.auth_signatory_name, pka.auth_signatory_email,
        pka.finance_contact_name, pka.billing_email, pka.tech_contact_name
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = o.partner_id
        WHERE o.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$orderId], $pw['params']));
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p style="padding:40px">Order not found.</p>'; exit; }

    if (isset($_GET['format']) && $_GET['format'] === 'excel') {
        require_once APP_DIR . '/helpers/sof_excel.php';
        try {
            $excelFile = generateSOFExcel($order);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($excelFile) . '"');
            header('Content-Length: ' . filesize($excelFile));
            readfile($excelFile);
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Excel generation note: ' . e($e->getMessage()));
        }
    }

    include APP_DIR . '/views/orders/generate_sof.php';
    exit;
}

// ------------------------------------------------------------------
// Order Detail
// ------------------------------------------------------------------
if ($action === 'detail' || $_GET['page'] === 'order_detail') {
    $orderId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('o');

    $stmt = $db->prepare("SELECT o.*, p.name as partner_name,
        pka.registered_name as partner_registered_name,
        pka.address as partner_address, pka.auth_signatory_name, pka.auth_signatory_email,
        pka.finance_contact_name, pka.billing_email, pka.tech_contact_name
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = o.partner_id
        WHERE o.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$orderId], $pw['params']));
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p style="padding:40px">Order not found.</p>'; exit; }

    $timeline = $db->prepare("SELECT ot.*, u.full_name FROM order_timeline ot LEFT JOIN users u ON ot.changed_by = u.id WHERE ot.order_id = ? ORDER BY ot.changed_at DESC");
    $timeline->execute([$orderId]);
    $timeline = $timeline->fetchAll();

    $docs = $db->prepare("SELECT od.*, u.full_name FROM order_documents od LEFT JOIN users u ON od.uploaded_by = u.id WHERE od.order_id = ? ORDER BY od.uploaded_at DESC");
    $docs->execute([$orderId]);
    $docs = $docs->fetchAll();

    // Contractor assignment for this order
    $assignmentStmt = $db->prepare("SELECT ca.*, p.name as contractor_name, u.full_name as assigned_by_name, u2.full_name as contractor_user_name
        FROM contractor_assignments ca
        JOIN partners p ON ca.contractor_partner_id = p.id
        LEFT JOIN users u ON ca.assigned_by = u.id
        LEFT JOIN users u2 ON ca.contractor_user_id = u2.id
        WHERE ca.order_id = ? ORDER BY ca.assigned_at DESC LIMIT 1");
    $assignmentStmt->execute([$orderId]);
    $assignment = $assignmentStmt->fetch();

    // Contractor evidence
    $evidenceStmt = $db->prepare("SELECT ce.*, u.full_name FROM contractor_evidence ce LEFT JOIN users u ON ce.uploaded_by = u.id WHERE ce.order_id = ? ORDER BY ce.uploaded_at DESC");
    $evidenceStmt->execute([$orderId]);
    $evidence = $evidenceStmt->fetch() ? $evidenceStmt->fetchAll() : [];
    // Re-fetch since we called fetch() above
    $evidenceStmt->execute([$orderId]);
    $evidence = $evidenceStmt->fetchAll();

    // Contractor progress updates
    $progressStmt = $db->prepare("SELECT cpu.*, u.full_name FROM contractor_progress_updates cpu LEFT JOIN users u ON cpu.updated_by = u.id WHERE cpu.order_id = ? ORDER BY cpu.created_at DESC");
    $progressStmt->execute([$orderId]);
    $progressUpdates = $progressStmt->fetchAll();

    // Contractor list for assignment (only contractor partners)
    $contractorList = $db->query("SELECT p.id, p.name FROM partners p WHERE p.kyc_type = 'Contractor' AND p.status = 'Active' ORDER BY p.name")->fetchAll();

    // Return audit
    $returnsStmt = $db->prepare("SELECT r.*, u.full_name FROM order_returns r LEFT JOIN users u ON r.returned_by = u.id WHERE r.order_id = ? ORDER BY r.returned_at DESC");
    $returnsStmt->execute([$orderId]);
    $orderReturns = $returnsStmt->fetchAll();

    $allStatuses = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed','Not Feasible','Cancelled'];

    $pageTitle = 'Order ' . $order['order_number'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/orders/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// New Order / Feasibility Request form
// ------------------------------------------------------------------
if ($_GET['page'] === 'new_order') {
    $partners = [];
    $partnerKamName = null;
    if (!isPartnerUser()) {
        $partners = $db->query("SELECT id, name FROM partners WHERE status = 'Active' AND kyc_type = 'Partner' ORDER BY name")->fetchAll();
    } else {
        $pKamStmt = $db->prepare("SELECT assigned_kam_name FROM partners WHERE id = ?");
        $pKamStmt->execute([$user['partner_id'] ?? 0]);
        $partnerKamName = $pKamStmt->fetchColumn() ?: null;
    }
    $kamList = $db->query("SELECT id, full_name FROM users WHERE role = 'KAM' AND is_active = 1 ORDER BY full_name")->fetchAll();

    $pageTitle = 'New Feasibility Request';
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

$allStatuses  = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed','Not Feasible','Cancelled'];
$serviceTypes = ['Layer 2 ( last mile)', 'FTTH', 'FTTB', 'BIA (Broadband Internet Access)', 'Remote Hands Only', 'DIA', 'Dedicated Layer 2'];

$pageTitle = 'Order Tracking';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/orders/tracking.php';
include APP_DIR . '/views/layout/footer.php';
