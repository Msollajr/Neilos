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

    $custName  = trim($_POST['customer_name'] ?? '');
    $contName  = trim($_POST['customer_contact_name'] ?? '');
    $contPhone = trim($_POST['customer_contact_phone'] ?? '');
    $contEmail = trim($_POST['customer_contact_email'] ?? '');

    if (!$custName) {
        setFlash('danger', 'Customer name is required.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    if (!$contName || !$contPhone || !$contEmail) {
        setFlash('danger', 'Customer contact name, phone number, and email address are all mandatory.');
        header('Location: ' . APP_URL . '/?page=new_order');
        exit;
    }

    if (!filter_var($contEmail, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Please provide a valid customer contact email address.');
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

    // Handle file uploads (with backend deduplication)
    if (!empty($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $filesCount = count($_FILES['documents']['name']);
        $processedFiles = [];
        for ($i = 0; $i < $filesCount; $i++) {
            $fname = $_FILES['documents']['name'][$i] ?? '';
            $err   = $_FILES['documents']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $fsize = $_FILES['documents']['size'][$i] ?? 0;
            if (!$fname || $err === UPLOAD_ERR_NO_FILE) continue;

            $fileDedupeKey = $fname . '_' . $fsize;
            if (isset($processedFiles[$fileDedupeKey])) continue;
            $processedFiles[$fileDedupeKey] = true;

            $file = [
                'name'     => $fname,
                'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                'error'    => $err,
                'size'     => $fsize
            ];
            try {
                $up = uploadFile($file, 'orders/' . $orderId);
                $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$orderId, 'other', 'Supporting Document', $up['name'], $up['path'], $up['size'], $user['id']]);
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

    $orderId          = (int)($_POST['order_id'] ?? 0);
    $revisedNrcRaw    = trim($_POST['revised_nrc'] ?? '');
    $nrcJustification = trim($_POST['nrc_justification'] ?? '');
    $technicalRemarks = trim($_POST['technical_remarks'] ?? '');

    // Validation
    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    // Parse TZS input — allow zero for Remote Hands-only NRC
    $revisedNrc = null;
    if ($revisedNrcRaw !== '') {
        try {
            $revisedNrc = parseTZSInput($revisedNrcRaw, true, true, MAX_NRC_AMOUNT, 'Revised NRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage());
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
    }

    $standardNrc = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 0);

    // Justification mandatory when NRC differs from standard
    if ($revisedNrc !== null && $revisedNrc != $standardNrc && !$nrcJustification) {
        setFlash('danger','NRC justification is mandatory when revising the standard NRC.');
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
       ->execute([$orderId, 'Await Commercial Approval', 'BSA marked technically feasible — pending KAM commercial approval.', $user['id']]);

    // Audit price change
    if ($revisedNrc !== null) {
        recordPriceChange($orderId, 'revised_nrc', $standardNrc, $revisedNrc, $user['id'], 'Feasibility Review', $nrcJustification);
    }

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
// POST: KAM — Commercial approval (standard, exception, or escalate)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'kam_approve') {
    verifyCsrf();
    if (!hasRole('KAM') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId            = (int)($_POST['order_id'] ?? 0);
    $proposedNrcRaw     = trim($_POST['kam_proposed_nrc'] ?? '');
    $proposedMrcRaw     = trim($_POST['kam_proposed_mrc'] ?? '');
    $kamJustification   = trim($_POST['kam_commercial_justification'] ?? '');
    $kamRemarks         = trim($_POST['kam_remarks'] ?? '');

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    // Ensure all required KAM exception columns exist
    $cols = array_keys($o);
    if (!in_array('kam_remarks', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_remarks TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('kam_proposed_nrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_proposed_nrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('kam_proposed_mrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_proposed_mrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('kam_commercial_justification', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN kam_commercial_justification TEXT NULL"); } catch (Throwable $e) {}
    }


    $standardNrc = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 0);
    $standardMrc = (float)($o['standard_mrc'] ?? $o['base_mrc'] ?? 0);
    $bsaRevNrc   = ($o['revised_nrc'] !== null && $o['revised_nrc'] !== '') ? (float)$o['revised_nrc'] : $standardNrc;

    // Parse KAM proposed values (optional — blank means use existing/standard)
    $proposedNrc = null;
    $proposedMrc = null;
    if ($proposedNrcRaw !== '') {
        try {
            $proposedNrc = parseTZSInput($proposedNrcRaw, true, true, MAX_NRC_AMOUNT, 'Proposed NRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage()); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
    }
    if ($proposedMrcRaw !== '') {
        try {
            $proposedMrc = parseTZSInput($proposedMrcRaw, true, true, MAX_MRC_AMOUNT, 'Proposed MRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage()); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
    }

    // Exception = any price that deviates from the BSA-set or standard value
    $nrcException = ($proposedNrc !== null && $proposedNrc != $bsaRevNrc);
    $mrcException = ($proposedMrc !== null && $proposedMrc < $standardMrc);
    $isException  = $nrcException || $mrcException;

    if ($isException && !$kamJustification) {
        setFlash('danger','Commercial justification is mandatory when proposing a pricing exception.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    if ($isException) {
        // Store KAM exception proposals and route to Management
        $db->prepare("UPDATE orders SET
            kam_proposed_nrc = ?, kam_proposed_mrc = ?,
            kam_commercial_justification = ?, kam_remarks = ?,
            kam_approved_by = ?, kam_approved_at = NOW(),
            status = 'Management Approval', updated_at = NOW()
            WHERE id = ?")->execute([$proposedNrc, $proposedMrc, $kamJustification, $kamRemarks, $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Management Approval', "KAM submitted pricing exception (NRC: " . ($proposedNrc ? formatTZS($proposedNrc) : '—') . ", MRC: " . ($proposedMrc ? formatTZS($proposedMrc) : '—') . ") — routed to Management Approval.", $user['id']]);

        // Audit price changes
        if ($nrcException) recordPriceChange($orderId, 'kam_proposed_nrc', $bsaRevNrc, $proposedNrc, $user['id'], 'Await Commercial Approval', $kamJustification);
        if ($mrcException) recordPriceChange($orderId, 'kam_proposed_mrc', $standardMrc, $proposedMrc, $user['id'], 'Await Commercial Approval', $kamJustification);

        queueOrderNotification($orderId, 'KAM Requires Further Approval');
        auditLog("KAM escalated pricing exception to Management for order #$orderId", 'orders', $orderId);
        setFlash('warning', 'Pricing exception submitted — order routed to Management Approval queue.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Standard approval — no exception. Use BSA NRC, standard MRC (or KAM proposed MRC if above standard)
    $effectiveMrc = ($proposedMrc !== null && $proposedMrc >= $standardMrc) ? $proposedMrc : $standardMrc;
    $vatMrc       = round($effectiveMrc * 0.18, 2);
    $totMrc       = round($effectiveMrc + $vatMrc, 2);

    $db->prepare("UPDATE orders SET
        revised_mrc = ?, mrc_justification = ?,
        kam_remarks = ?,
        kam_approved_by = ?, kam_approved_at = NOW(),
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([$effectiveMrc != $standardMrc ? $effectiveMrc : null, $kamJustification ?: null, $kamRemarks, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'KAM: Commercial approved at standard pricing. Partner notified for SOF signature.', $user['id']]);

    queueOrderNotification($orderId, 'Feasibility Approved');
    auditLog("KAM approved standard commercial for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Commercial approved at standard pricing. Partner notified to sign and upload SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// Removed: kam_escalate (merged into kam_approve)

// ------------------------------------------------------------------
// POST: Management — 4-option decision on pricing exception
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'management_decide') {
    verifyCsrf();
    if (!hasRole('Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId       = (int)($_POST['order_id'] ?? 0);
    $decision      = trim($_POST['management_decision'] ?? '');
    $remarks       = trim($_POST['management_remarks'] ?? '');
    $returnRemarks = trim($_POST['management_return_remarks'] ?? '');

    $allowedDecisions = ['Approve as Requested','Approve with Revised Price','Keep Standard Price','Return to KAM'];
    if (!in_array($decision, $allowedDecisions)) {
        setFlash('danger','Invalid decision. Please choose one of the available options.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $cols = array_keys($o);
    if (!in_array('management_decision', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_decision VARCHAR(100) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_remarks', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_remarks TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_return_remarks', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_return_remarks TEXT NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_final_nrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_final_nrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }
    if (!in_array('management_final_mrc', $cols)) {
        try { $db->exec("ALTER TABLE orders ADD COLUMN management_final_mrc DECIMAL(14,2) NULL"); } catch (Throwable $e) {}
    }


    $standardNrc  = (float)($o['standard_nrc'] ?? $o['base_nrc_usd'] ?? 0);
    $standardMrc  = (float)($o['standard_mrc'] ?? $o['base_mrc'] ?? 0);
    $kamNrc       = $o['kam_proposed_nrc'] !== null ? (float)$o['kam_proposed_nrc'] : null;
    $kamMrc       = $o['kam_proposed_mrc'] !== null ? (float)$o['kam_proposed_mrc'] : null;
    $bsaRevNrc    = ($o['revised_nrc'] !== null) ? (float)$o['revised_nrc'] : $standardNrc;

    if ($decision === 'Return to KAM') {
        if (!$returnRemarks) {
            setFlash('danger','Return remarks are mandatory when returning to Account Manager.');
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
        $db->prepare("UPDATE orders SET
            management_decision = ?, management_return_remarks = ?,
            management_approved_by = ?, management_approved_at = NOW(),
            kam_proposed_nrc = NULL, kam_proposed_mrc = NULL, kam_commercial_justification = NULL,
            status = 'Await Commercial Approval', updated_at = NOW()
            WHERE id = ?")->execute([$decision, $returnRemarks, $user['id'], $orderId]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Await Commercial Approval', "Management returned to KAM for revision. Remarks: $returnRemarks", $user['id']]);

        queueOrderNotification($orderId, 'Management Returned to KAM');
        auditLog("Management returned order #$orderId to KAM: $returnRemarks", 'orders', $orderId);
        setFlash('warning', 'Order returned to Account Manager for revision.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Determine final NRC and MRC based on decision
    if ($decision === 'Approve as Requested') {
        $finalNrc = $kamNrc ?? $bsaRevNrc;
        $finalMrc = $kamMrc ?? $standardMrc;
        $note     = 'Management approved pricing as requested by KAM.';
    } elseif ($decision === 'Approve with Revised Price') {
        // Read management's own revised values (both NRC and MRC required when setting revised price)
        try {
            $finalNrc = parseTZSInput(trim($_POST['management_final_nrc'] ?? ''), false, true, MAX_NRC_AMOUNT, 'Management Final NRC');
            $finalMrc = parseTZSInput(trim($_POST['management_final_mrc'] ?? ''), false, true, MAX_MRC_AMOUNT, 'Management Final MRC');
        } catch (RuntimeException $ex) {
            setFlash('danger', $ex->getMessage());
            header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
        }
        $note = 'Management approved with revised pricing: NRC ' . formatTZS($finalNrc) . ', MRC ' . formatTZS($finalMrc) . '.';
    } else { // Keep Standard Price
        $finalNrc = $bsaRevNrc; // BSA-set NRC
        $finalMrc = $standardMrc;
        $note     = 'Management kept standard pricing. KAM exception not approved.';
    }

    $vatMrc = round($finalMrc * 0.18, 2);
    $totMrc = round($finalMrc + $vatMrc, 2);

    $db->prepare("UPDATE orders SET
        management_final_nrc = ?, management_final_mrc = ?,
        management_approved_price = ?, management_decision = ?,
        management_remarks = ?, management_remarks_visible = 0,
        management_approved_by = ?, management_approved_at = NOW(),
        revised_nrc = ?, vat_on_mrc = ?, total_mrc_incl_vat = ?,
        status = 'Pending SOF', updated_at = NOW()
        WHERE id = ?")->execute([
        $finalNrc, $finalMrc,
        $finalMrc, $decision,
        $remarks, $user['id'],
        $finalNrc !== $standardNrc ? $finalNrc : null,
        $vatMrc, $totMrc, $orderId
    ]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', $note, $user['id']]);

    recordPriceChange($orderId, 'management_final_nrc', $kamNrc ?? $bsaRevNrc, $finalNrc, $user['id'], 'Management Approval', $remarks);
    recordPriceChange($orderId, 'management_final_mrc', $kamMrc ?? $standardMrc, $finalMrc, $user['id'], 'Management Approval', $remarks);

    queueOrderNotification($orderId, 'Management Pricing Approved');
    auditLog("Management decision '$decision' for order #$orderId", 'orders', $orderId);
    setFlash('success', 'Decision recorded: ' . $decision . '. Partner notified to sign SOF.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// Legacy alias — management_approve now maps to management_decide internally
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'management_approve' || $action === 'management_reject')) {
    $_POST['management_decision'] = ($action === 'management_approve') ? 'Approve as Requested' : 'Keep Standard Price';
    $_GET['action'] = $action = 'management_decide';
    // Fall through handled below — redirect
    header('Location: ' . APP_URL . '/?page=orders&action=management_decide&order_id=' . (int)($_POST['order_id'] ?? 0));
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Generate / Print SOF (mark generation time)
// ------------------------------------------------------------------
if ($action === 'generate_sof') {
    $orderId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('o');
    $stmt = $db->prepare("SELECT o.*, p.name as partner_name
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        WHERE o.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$orderId], $pw['params']));
    $order = $stmt->fetch();
    if (!$order) { http_response_code(404); echo '<p style="padding:40px">Order not found.</p>'; exit; }

    $partnerId = (int)$order['partner_id'];
    $partnerKyc = getAuthoritativePartnerKyc($db, $partnerId);

    // Validate KYC completeness before SOF generation
    if (!$partnerKyc || !$partnerKyc['is_complete']) {
        $missingList = !empty($partnerKyc['missing_fields']) ? implode(', ', $partnerKyc['missing_fields']) : 'KYC application not found';
        setFlash('danger', "Partner KYC is incomplete (Missing: $missingList). Please complete the required customer and contact information in Partner KYC before generating the SOF.");
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Record that SOF was generated and which KYC record was used
    $kycId = $partnerKyc['id'];
    $db->prepare("UPDATE orders SET sof_generated_at = NOW() WHERE id = ? AND sof_generated_at IS NULL")
       ->execute([$orderId]);

    auditLog("SOF generated for order #$orderId (Partner: {$partnerKyc['company_name']}, KYC Application ID: " . ($kycId ?: 'N/A') . ")", 'orders', $orderId);

    if (isset($_GET['format']) && $_GET['format'] === 'excel') {
        require_once APP_DIR . '/helpers/sof_excel.php';
        try {
            $excelFile = generateSOFExcel($order, $partnerKyc);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($excelFile) . '"');
            header('Content-Length: ' . filesize($excelFile));
            readfile($excelFile);
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Excel generation note: ' . e($e->getMessage()));
        }
    }

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

        $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$orderId, 'sof', 'Signed SOF', $up['name'], $up['path'], $up['size'], $user['id']]);

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
// POST: Partner — Not Satisfied (return from Pending SOF to KAM)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'return_to_feasibility') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId       = (int)($_POST['order_id'] ?? 0);
    $returnRemarks = trim($_POST['return_remarks'] ?? '');

    if (!$returnRemarks) {
        setFlash('danger','Please enter your remarks to explain why you are returning this order.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Partner return ALWAYS routes back to KAM (Await Commercial Approval)
    // KAM can then internally route to BSA or Management
    $newStatus = 'Await Commercial Approval';

    $orderStmt = $db->prepare("SELECT standard_nrc, revised_nrc, standard_mrc, revised_mrc FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $oRow = $orderStmt->fetch();

    $db->prepare("UPDATE orders SET
        status = ?, sof_return_comments = ?,
        return_route = 'KAM', returned_by = ?, returned_at = NOW(),
        updated_at = NOW()
        WHERE id = ?")->execute([$newStatus, $returnRemarks, $user['id'], $orderId]);

    $db->prepare("INSERT INTO order_returns (order_id, returned_by, from_status, to_status, return_reason, return_remarks, routed_to, old_nrc, old_mrc) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([$orderId, $user['id'], 'Pending SOF', $newStatus, 'Partner not satisfied with pricing', $returnRemarks, 'KAM', $oRow['revised_nrc'] ?? $oRow['standard_nrc'], $oRow['revised_mrc'] ?? $oRow['standard_mrc']]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, $newStatus, "Partner not satisfied — returned to Account Manager: $returnRemarks", $user['id']]);

    queueOrderNotification($orderId, 'Partner Returned Feasibility');
    auditLog("Partner returned order #$orderId to KAM: $returnRemarks", 'orders', $orderId);
    setFlash('warning', 'Order returned to your Account Manager for revision.');
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Partner — Delete uploaded signed SOF (to re-upload)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_signed_sof') {
    verifyCsrf();
    if (!isPartnerUser() && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $db->prepare("SELECT status, sof_signed_file FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();

    if (!$o || $o['status'] !== 'Pending SOF') {
        setFlash('danger','SOF can only be deleted while the order is in Pending SOF status.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    // Clear signed SOF from DB (file stays on disk for audit purposes)
    $db->prepare("UPDATE orders SET sof_signed_file = NULL, sof_signed_filename = NULL, sof_uploaded_at = NULL, updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);
    $db->prepare("DELETE FROM order_documents WHERE order_id = ? AND document_type = 'Signed SOF'")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Pending SOF', 'Partner deleted uploaded SOF. Please re-upload the correct signed document.', $user['id']]);

    auditLog("Signed SOF deleted for order #$orderId by partner", 'orders', $orderId);
    setFlash('info', 'Signed SOF removed. Please upload the correct signed document.');
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
// POST: KAM or Management — Upload Countersigned SOF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_countersigned_sof') {
    verifyCsrf();
    if (!hasRole('KAM','Management') && !isAdmin()) { setFlash('danger','Only KAM, Management, or Admin can upload countersigned SOF.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

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

        $db->prepare("INSERT INTO order_documents (order_id, doc_type, document_type, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$orderId, 'countersigned_sof', 'Countersigned SOF', $up['name'], $up['path'], $up['size'], $user['id']]);

        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'SOF Review', 'Countersigned SOF uploaded by ' . ($user['role'] ?? 'internal') . '. Ready to proceed to project.', $user['id']]);

        queueOrderNotification($orderId, 'Countersigned SOF Uploaded');
        auditLog("Countersigned SOF uploaded for order #$orderId by {$user['role']}", 'orders', $orderId);
        setFlash('success', 'Countersigned SOF uploaded successfully. You can now proceed to project.');
    } catch (RuntimeException $e) {
        setFlash('danger', 'Upload failed: ' . e($e->getMessage()));
    }

    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: KAM or Management — Proceed to Project (Installation)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'proceed_to_project') {
    verifyCsrf();
    if (!hasRole('KAM','Management') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

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
    auditLog("Order #$orderId moved to Installation by {$user['role']}", 'orders', $orderId);
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
// POST: Project Manager/BSA — Set NOC IP Configured flag
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle_noc_ip') {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $value   = (int)($_POST['noc_ip_configured'] ?? 0) ? 1 : 0;

    $db->prepare("UPDATE orders SET noc_ip_configured = ?, updated_at = NOW() WHERE id = ?")
       ->execute([$value, $orderId]);

    $label = $value ? 'NOC IP configuration marked as COMPLETE — Speed Test and Ping Test evidence are now required.' : 'NOC IP configuration flag cleared.';
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) SELECT id, status, ?, ? FROM orders WHERE id = ?")
       ->execute([$label, $user['id'], $orderId]);

    auditLog("NOC IP configured flag set to $value for order #$orderId", 'orders', $orderId);
    setFlash('success', $label);
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}


// ------------------------------------------------------------------
// POST: Project Manager/BSA — Submit Job as Complete (Installation → Testing)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['submit_installation_complete', 'submit_completion'])) {
    verifyCsrf();
    if (!hasRole('Project Manager','BSA') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $remarks = trim($_POST['completion_remarks'] ?? '');

    $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o) { setFlash('danger','Order not found.'); header('Location:'.APP_URL.'/?page=orders'); exit; }

    // Update assignment if any exists
    $db->prepare("UPDATE contractor_assignments SET status = 'Completed Submitted', completed_at = NOW(), completion_remarks = ? WHERE order_id = ? AND status != 'Completed'")
       ->execute([$remarks, $orderId]);

    // Move order to Testing status
    $db->prepare("UPDATE orders SET status = 'Testing', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    evaluateAndSyncOrderStatus($orderId, 'submit_completion');

    $note = "Job completion submitted by Project Manager. " . ($remarks ? "Remarks: $remarks" : "Awaiting Testing — Internal Review.");
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Testing', $note, $user['id']]);

    auditLog("Project Manager submitted job as complete for order #$orderId, moved to Testing", 'orders', $orderId);
    setFlash('success', 'Installation submitted. Order is now in <strong>Testing — Internal Review</strong>.');
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
    $orderStmt = $db->prepare("SELECT status, noc_ip_configured FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $o = $orderStmt->fetch();
    if (!$o || $o['status'] !== 'Testing') { setFlash('danger','Order must be in Testing status.'); header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit; }

    // Gate: NOC must have configured IP before moving to UAT (business rule)
    if (!$o['noc_ip_configured']) {
        setFlash('danger','Cannot proceed to UAT until NOC IP Configuration is marked as complete. Please set the NOC IP Configured flag first.');
        header('Location:'.APP_URL.'/?page=order_detail&id='.$orderId); exit;
    }

    $db->prepare("UPDATE orders SET status = 'UAT', uat_notified_at = NOW(), uat_deadline = DATE_ADD(NOW(), INTERVAL 72 HOUR), updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'UAT', 'Testing approved (NOC IP configured). Partner notified for UAT acceptance (72hr window).', $user['id']]);

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

    // Create active service record (Service ID SVC-YYMMDD-XXX mirrors Order Number FR-YYMMDD-XXX)
    $serviceId = generateServiceId($o['order_number'] ?? '');
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
// POST: Edit Closed Order (Admin and Management only with mandatory audit reason)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_closed_order') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Closed orders can only be edited by Admin and Management.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . (int)($_POST['order_id'] ?? 0));
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('danger', 'Order not found.');
        header('Location: ' . APP_URL . '/?page=orders');
        exit;
    }

    if ($order['status'] !== 'Closed') {
        setFlash('danger', 'This action is reserved for Closed orders.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    $auditReason = trim($_POST['audit_reason'] ?? '');
    if (empty($auditReason)) {
        setFlash('danger', 'An audit reason is strictly mandatory when editing a closed order.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    // Editable fields
    $customerName    = trim($_POST['customer_name'] ?? $order['customer_name']);
    $contactName     = trim($_POST['customer_contact_name'] ?? $order['customer_contact_name']);
    $contactPhone    = trim($_POST['customer_contact_phone'] ?? $order['customer_contact_phone']);
    $contactEmail    = trim($_POST['customer_contact_email'] ?? $order['customer_contact_email']);
    $location        = trim($_POST['customer_location'] ?? $order['customer_location']);
    $buildingName    = trim($_POST['building_name'] ?? $order['building_name']);
    $gpsCoordinates  = trim($_POST['gps_coordinates'] ?? $order['gps_coordinates']);
    $bandwidth       = trim($_POST['bandwidth'] ?? $order['bandwidth']);
    $circuitId       = trim($_POST['circuit_id'] ?? $order['circuit_id']);
    $serviceId       = trim($_POST['service_id'] ?? $order['service_id']);
    $specialReqs     = trim($_POST['special_requirements'] ?? $order['special_requirements']);

    // Track changes for timeline and audit
    $changes = [];
    if ($customerName !== $order['customer_name']) $changes[] = "Customer Name: '{$order['customer_name']}' → '{$customerName}'";
    if ($contactName !== $order['customer_contact_name']) $changes[] = "Contact Name: '{$order['customer_contact_name']}' → '{$contactName}'";
    if ($contactPhone !== $order['customer_contact_phone']) $changes[] = "Contact Phone: '{$order['customer_contact_phone']}' → '{$contactPhone}'";
    if ($contactEmail !== $order['customer_contact_email']) $changes[] = "Contact Email: '{$order['customer_contact_email']}' → '{$contactEmail}'";
    if ($location !== $order['customer_location']) $changes[] = "Location: '{$order['customer_location']}' → '{$location}'";
    if ($buildingName !== $order['building_name']) $changes[] = "Building: '{$order['building_name']}' → '{$buildingName}'";
    if ($gpsCoordinates !== $order['gps_coordinates']) $changes[] = "GPS: '{$order['gps_coordinates']}' → '{$gpsCoordinates}'";
    if ($bandwidth !== $order['bandwidth']) $changes[] = "Bandwidth: '{$order['bandwidth']}' → '{$bandwidth}'";
    if ($circuitId !== $order['circuit_id']) $changes[] = "Circuit ID: '{$order['circuit_id']}' → '{$circuitId}'";
    if ($serviceId !== $order['service_id']) $changes[] = "Service ID: '{$order['service_id']}' → '{$serviceId}'";
    if ($specialReqs !== $order['special_requirements']) $changes[] = "Special Requirements updated";

    $changeSummary = !empty($changes) ? implode('; ', $changes) : 'Metadata re-verified (no field changes)';

    // Update order
    $db->prepare("UPDATE orders SET
        customer_name = ?, customer_contact_name = ?, customer_contact_phone = ?,
        customer_contact_email = ?, customer_location = ?, building_name = ?,
        gps_coordinates = ?, bandwidth = ?, circuit_id = ?, service_id = ?,
        special_requirements = ?, updated_at = NOW()
        WHERE id = ?")->execute([
        $customerName, $contactName, $contactPhone,
        $contactEmail, $location, $buildingName,
        $gpsCoordinates, $bandwidth, $circuitId, $serviceId,
        $specialReqs, $orderId
    ]);

    // Update corresponding active service if exists
    $db->prepare("UPDATE active_services SET
        customer_name = ?, location = ?, building_name = ?,
        circuit_id = ?, bandwidth_capacity = ?
        WHERE order_id = ?")->execute([
        $customerName, $location, $buildingName,
        $circuitId, $bandwidth, $orderId
    ]);

    // Timeline entry
    $timelineNote = "Closed order modified by {$user['full_name']} (" . (isAdmin() ? 'Admin' : 'Management') . ").\nAudit Reason: {$auditReason}\nModifications: {$changeSummary}";
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Closed', $timelineNote, $user['id']]);

    // System audit log
    auditLog("Closed order #{$order['order_number']} edited by {$user['full_name']}. Reason: $auditReason. Changes: $changeSummary", 'orders', $orderId);

    setFlash('success', "Closed order <strong>" . e($order['order_number']) . "</strong> updated successfully with audit reason recorded.");
    header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    exit;
}

// ------------------------------------------------------------------
// POST: Update order status (Admin override)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) { 
        setFlash('danger','Access denied. Admin or Management only.'); 
        header('Location: ' . APP_URL . '/?page=orders'); 
        exit; 
    }

    $orderId        = (int)($_POST['order_id'] ?? 0);
    $newStatus      = $_POST['new_status'] ?? '';
    $newServiceType = $_POST['new_service_type'] ?? '';
    $note           = trim($_POST['note'] ?? '');

    $curStmt = $db->prepare("SELECT status, order_number FROM orders WHERE id = ?");
    $curStmt->execute([$orderId]);
    $curOrder = $curStmt->fetch();

    if ($curOrder && $curOrder['status'] === 'Closed' && empty($note)) {
        setFlash('danger', 'Audit reason / note is strictly required when modifying a Closed order.');
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
        exit;
    }

    if (empty($note)) {
        $note = 'Admin status override.';
    }

    if (!empty($newServiceType)) {
        $db->prepare("UPDATE orders SET service_type = ? WHERE id = ?")->execute([$newServiceType, $orderId]);
    }
    if (!empty($newStatus)) {
        $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")->execute([$orderId, $newStatus, $note, $user['id']]);
    }
    auditLog("Admin/Management status override for order #$orderId (Reason: $note)", 'orders', $orderId);
    setFlash('success', 'Order status updated successfully.');
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

    // RBAC: Partner users receive a sanitized timeline — the `note` column is never
    // selected, so it cannot appear in the HTTP response, page source, or DevTools.
    // Authorized internal users receive the full query including event descriptions.
    if (isPartnerUser()) {
        $timelineStmt = $db->prepare("SELECT ot.id, ot.order_id, ot.status, ot.changed_at, u.full_name FROM order_timeline ot LEFT JOIN users u ON ot.changed_by = u.id WHERE ot.order_id = ? ORDER BY ot.changed_at DESC");
    } else {
        $timelineStmt = $db->prepare("SELECT ot.*, u.full_name FROM order_timeline ot LEFT JOIN users u ON ot.changed_by = u.id WHERE ot.order_id = ? ORDER BY ot.changed_at DESC");
    }
    $timelineStmt->execute([$orderId]);
    $timeline = $timelineStmt->fetchAll();



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

    // Price change audit trail
    $priceAudit = getOrderPriceAudit($orderId);

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
    requirePermission('orders.create');
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
$filterSla     = $_GET['sla'] ?? '';
$filterPreset  = $_GET['preset'] ?? '';
$filterStart   = trim($_GET['start_date'] ?? '');
$filterEnd     = trim($_GET['end_date'] ?? '');

if ($filterPreset === 'today') {
    $filterStart = date('Y-m-d');
    $filterEnd   = date('Y-m-d');
} elseif ($filterPreset === 'this_month') {
    $filterStart = date('Y-m-01');
    $filterEnd   = date('Y-m-t');
} elseif ($filterPreset === 'last_month') {
    $filterStart = date('Y-m-01', strtotime('-1 month'));
    $filterEnd   = date('Y-m-t', strtotime('-1 month'));
} elseif ($filterPreset === 'this_year') {
    $filterStart = date('Y-01-01');
    $filterEnd   = date('Y-12-31');
}

if ($filterStart && $filterEnd) {
    $where .= " AND o.created_at BETWEEN ? AND ?";
    $params[] = $filterStart . ' 00:00:00';
    $params[] = $filterEnd . ' 23:59:59';
} elseif ($filterStart) {
    $where .= " AND o.created_at >= ?";
    $params[] = $filterStart . ' 00:00:00';
} elseif ($filterEnd) {
    $where .= " AND o.created_at <= ?";
    $params[] = $filterEnd . ' 23:59:59';
}

if ($filterStatus)  { $where .= " AND o.status = ?";       $params[] = $filterStatus; }
if ($filterService) { $where .= " AND o.service_type = ?"; $params[] = $filterService; }
if ($filterSla === 'paused') {
    $where .= " AND o.sla_paused = 1";
} elseif ($filterSla === 'breached') {
    $candStmt = $db->prepare("SELECT o.* FROM orders o JOIN partners p ON o.partner_id = p.id $where");
    $candStmt->execute($params);
    $cands = $candStmt->fetchAll(PDO::FETCH_ASSOC);
    $breachedIds = [];
    if (!empty($cands)) {
        $analytics = computeComprehensiveSlaAnalytics($cands, $db);
        foreach ($analytics['order_evaluations'] as $ev) {
            if ($ev['order_sla_status'] === 'Breached') {
                $breachedIds[] = $ev['order_id'];
            }
        }
    }
    if (!empty($breachedIds)) {
        $inList = implode(',', $breachedIds);
        $where .= " AND o.id IN ($inList)";
    } else {
        $where .= " AND 1=0";
    }
}
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
