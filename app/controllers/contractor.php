<?php
// ============================================================
// Contractor Module Controller — v1.0
// Handles contractor-specific dashboard, job acceptance,
// progress updates, evidence upload, and completion submission
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'dashboard';

// Only Contractor Users, Project Managers, and Admins can access this
if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) {
    setFlash('danger', 'Access denied. Contractor module is for contractors and project managers only.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

// Build partner filter for contractor users
$partnerId = $user['partner_id'] ?? null;// ------------------------------------------------------------------
// POST: Accept Job
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'accept_job') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    // Verify assignment
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT * FROM contractor_assignments WHERE id = ? AND contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT * FROM contractor_assignments WHERE id = ?");
        $aStmt->execute([$assignmentId]);
    }
    $a = $aStmt->fetch();
    if (!$a) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $db->prepare("UPDATE contractor_assignments SET status = 'Accepted', accepted_by = ?, accepted_at = NOW() WHERE id = ?")
       ->execute([$user['id'], $assignmentId]);

    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, notes) VALUES (?,?,?,?,?)")
       ->execute([$assignmentId, $a['order_id'], $user['id'], 'In Progress', 'Job accepted and proceeding to site.']);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$a['order_id'], 'Installation', 'Job assignment accepted.', $user['id']]);

    auditLog("Accepted job assignment #$assignmentId for order #{$a['order_id']}", 'contractor_assignments', $assignmentId);
    setFlash('success', 'Job accepted successfully.');
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}

// ------------------------------------------------------------------
// POST: Post Progress Update
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'progress_update') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $orderId      = (int)($_POST['order_id'] ?? 0);
    $progressStatus = $_POST['progress_status'] ?? 'In Progress';
    $delayReason    = $_POST['delay_reason'] ?? null;
    $notes          = trim($_POST['notes'] ?? '');

    if (!$notes) { setFlash('danger','Notes are required for progress update.'); header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit; }

    // Verify ownership/access
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT id FROM contractor_assignments WHERE id = ? AND contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT id FROM contractor_assignments WHERE id = ?");
        $aStmt->execute([$assignmentId]);
    }
    if (!$aStmt->fetch()) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, delay_reason, notes) VALUES (?,?,?,?,?,?)")
       ->execute([$assignmentId, $orderId, $user['id'], $progressStatus, $delayReason ?: null, $notes]);

    $db->prepare("UPDATE contractor_assignments SET status = 'In Progress' WHERE id = ? AND status = 'Accepted'")
       ->execute([$assignmentId]);

    auditLog("Progress update on assignment #$assignmentId", 'contractor_assignments', $assignmentId);
    setFlash('success', 'Progress update submitted.');
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}

// ------------------------------------------------------------------
// POST: Upload Evidence
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_evidence') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $orderId      = (int)($_POST['order_id'] ?? 0);
    $evidenceType = trim($_POST['evidence_type'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');
    $serialNum    = trim($_POST['serial_number'] ?? '');

    // Backend Rule #2 & #10: Serial number is strictly exclusive to ONT/ONU Serial
    if ($evidenceType !== 'ONT/ONU Serial') {
        $serialNum = '';
    }

    // Verify
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT id FROM contractor_assignments WHERE id = ? AND contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
        if (!$aStmt->fetch()) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }
    }

    $filePath = null; $fileName = null; $fileSize = null;
    if (!empty($_FILES['evidence_file']['name'])) {
        try {
            $up = uploadFile($_FILES['evidence_file'], 'orders/'.$orderId.'/evidence');
            $filePath = $up['path'];
            $fileName = $up['name'];
            $fileSize = $up['size'];
        } catch (RuntimeException $e) {
            setFlash('danger', 'Upload error: ' . e($e->getMessage()));
            header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit;
        }
    }

    $db->prepare("INSERT INTO contractor_evidence (assignment_id, order_id, evidence_type, serial_number, notes, file_name, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)")
       ->execute([$assignmentId, $orderId, $evidenceType, $serialNum ?: null, $notes ?: null, $fileName, $filePath, $fileSize, $user['id']]);

    evaluateAndSyncOrderStatus($orderId, 'upload_evidence');

    auditLog("Evidence uploaded: $evidenceType for assignment #$assignmentId", 'contractor_evidence', 0);
    setFlash('success', "Evidence ($evidenceType) uploaded.");
    if ($assignmentId > 0) {
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    } else {
        header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId);
    }
    exit;
}

// ------------------------------------------------------------------
// GET: Download Evidence File (Content-Disposition: attachment)
// ------------------------------------------------------------------
if ($action === 'download_evidence') {
    $evidenceId = (int)($_GET['id'] ?? 0);
    if (!$evidenceId) {
        setFlash('danger', 'Invalid file request.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $eStmt = $db->prepare("SELECT ce.*, ca.contractor_partner_id FROM contractor_evidence ce LEFT JOIN contractor_assignments ca ON ce.assignment_id = ca.id WHERE ce.id = ?");
    $eStmt->execute([$evidenceId]);
    $ev = $eStmt->fetch();

    if (!$ev) {
        setFlash('danger', 'Evidence file record not found.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    if ($partnerId && $ev['contractor_partner_id'] && (int)$ev['contractor_partner_id'] !== (int)$partnerId) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $relativePath = ltrim($ev['file_path'] ?? '', '/\\');
    $fullPath = PUBLIC_DIR . '/' . $relativePath;
    if (!$relativePath || !file_exists($fullPath) || !is_file($fullPath)) {
        $fullPath = dirname(dirname(__DIR__)) . '/public/' . $relativePath;
    }

    if (!$relativePath || !file_exists($fullPath) || !is_file($fullPath)) {
        setFlash('danger', 'Physical file non-existent on server.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($ev['assignment_id'] ?? 0));
        exit;
    }

    $fileName = $ev['file_name'] ?: basename($fullPath);
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    if (ob_get_level()) { ob_end_clean(); }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "'", "\r", "\n"], '', $fileName) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));

    readfile($fullPath);
    exit;
}

// ------------------------------------------------------------------
// POST: Replace Evidence File
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'replace_evidence') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $evidenceId   = (int)($_POST['evidence_id'] ?? 0);
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $orderId      = (int)($_POST['order_id'] ?? 0);
    $notes        = trim($_POST['notes'] ?? '');
    $serialNum    = trim($_POST['serial_number'] ?? '');

    $eStmt = $db->prepare("SELECT * FROM contractor_evidence WHERE id = ?");
    $eStmt->execute([$evidenceId]);
    $oldEv = $eStmt->fetch();

    if (!$oldEv) {
        setFlash('danger', 'Original evidence file record not found.');
        header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
        exit;
    }

    $filePath = $oldEv['file_path'];
    $fileName = $oldEv['file_name'];
    $fileSize = $oldEv['file_size'];

    if (!empty($_FILES['evidence_file']['name'])) {
        try {
            $up = uploadFile($_FILES['evidence_file'], 'orders/' . ($orderId ?: $oldEv['order_id']) . '/evidence');
            if (!empty($oldEv['file_path'])) {
                $oldFullPath = PUBLIC_DIR . '/' . ltrim($oldEv['file_path'], '/\\');
                if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }
            $filePath = $up['path'];
            $fileName = $up['name'];
            $fileSize = $up['size'];
        } catch (RuntimeException $e) {
            setFlash('danger', 'Upload error during file replacement: ' . e($e->getMessage()));
            header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: $oldEv['assignment_id']));
            exit;
        }
    }

    $db->prepare("UPDATE contractor_evidence SET 
        serial_number = ?, 
        notes = ?, 
        file_name = ?, 
        file_path = ?, 
        file_size = ?, 
        uploaded_by = ?, 
        uploaded_at = NOW() 
        WHERE id = ?")
       ->execute([$serialNum ?: null, $notes ?: null, $fileName, $filePath, $fileSize, $user['id'], $evidenceId]);

    evaluateAndSyncOrderStatus($orderId ?: $oldEv['order_id'], 'replace_evidence');

    auditLog("Replaced evidence file #$evidenceId ({$oldEv['evidence_type']})", 'contractor_evidence', $evidenceId);
    setFlash('success', "Evidence file for {$oldEv['evidence_type']} replaced successfully.");
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: $oldEv['assignment_id']));
    exit;
}

// ------------------------------------------------------------------
// POST: Delete Evidence File
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_evidence') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) {
        setFlash('danger', 'Access denied.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $evidenceId   = (int)($_POST['evidence_id'] ?? 0);
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    $eStmt = $db->prepare("SELECT * FROM contractor_evidence WHERE id = ?");
    $eStmt->execute([$evidenceId]);
    $ev = $eStmt->fetch();

    if ($ev) {
        if (!empty($ev['file_path'])) {
            $relativePath = ltrim($ev['file_path'], '/\\');
            $fullPath = PUBLIC_DIR . '/' . $relativePath;
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
        $db->prepare("DELETE FROM contractor_evidence WHERE id = ?")->execute([$evidenceId]);
        evaluateAndSyncOrderStatus($ev['order_id'], 'delete_evidence');
        auditLog("Deleted evidence file #$evidenceId ({$ev['evidence_type']})", 'contractor_evidence', $evidenceId);
        setFlash('success', "Evidence file ({$ev['evidence_type']}) deleted successfully.");
    } else {
        setFlash('warning', "Evidence record not found.");
    }

    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . ($assignmentId ?: ($ev['assignment_id'] ?? 0)));
    exit;
}

// ------------------------------------------------------------------
// POST: Submit Completion
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_completion') {
    verifyCsrf();
    if (!isContractorUser() && !hasRole('Project Manager') && !isAdmin()) { setFlash('danger','Access denied.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $orderId      = (int)($_POST['order_id'] ?? 0);
    $remarks      = trim($_POST['completion_remarks'] ?? '');

    // Verify assignment
    if ($partnerId) {
        $aStmt = $db->prepare("SELECT ca.*, o.service_type FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ? AND ca.contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT ca.*, o.service_type FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ?");
        $aStmt->execute([$assignmentId]);
    }
    $a = $aStmt->fetch();
    if (!$a) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    // Check if contractor uploaded evidence
    $evStmt = $db->prepare("SELECT COUNT(*) FROM contractor_evidence WHERE assignment_id = ?");
    $evStmt->execute([$assignmentId]);
    $evCount = (int)$evStmt->fetchColumn();

    if ($evCount === 0 && isContractorUser()) {
        setFlash('danger', 'Cannot submit completion. Mandatory evidence checklist is not satisfied (at least one installation photo, ONT serial, or signal test is required).');
        header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit;
    }

    // Mark assignment as completed submitted
    $cols = $db->query("SHOW COLUMNS FROM contractor_assignments")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('completion_remarks', $cols)) {
        $db->prepare("UPDATE contractor_assignments SET status = 'Completed Submitted', completed_at = NOW(), completion_remarks = ? WHERE id = ?")
           ->execute([$remarks, $assignmentId]);
    } else {
        $db->prepare("UPDATE contractor_assignments SET status = 'Completed', completion_notes = ?, completed_at = NOW() WHERE id = ?")
           ->execute([$remarks, $assignmentId]);
    }
    
    $db->prepare("UPDATE orders SET status = 'Testing', updated_at = NOW() WHERE id = ?")
       ->execute([$orderId]);

    evaluateAndSyncOrderStatus($orderId, 'submit_completion');

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Testing', "Job completion submitted. " . ($remarks ? "Remarks: $remarks" : "Awaiting PM/BSA testing review."), $user['id']]);

    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, notes) VALUES (?,?,?,?,?)")
       ->execute([$assignmentId, $orderId, $user['id'], 'Completed', "Installation completed and submitted for review. $remarks"]);

    queueOrderNotification($orderId, 'Contractor Completed');
    auditLog("Submitted completion for assignment #$assignmentId, order #$orderId moved to Testing", 'contractor_assignments', $assignmentId);
    setFlash('success', 'Installation submitted. Project Manager will review for testing approval.');
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}

// ------------------------------------------------------------------
// POST: Admin / PM Update Contractor Job Status & Details
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin_update_job') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Project Manager')) {
        setFlash('danger', 'Access denied. Admin or Project Manager only.');
        header('Location: ' . APP_URL . '/?page=contractor');
        exit;
    }

    $assignmentId   = (int)($_POST['assignment_id'] ?? 0);
    $status         = $_POST['status'] ?? '';
    $targetDate     = $_POST['target_date'] ?? null;
    $workOrderNotes = trim($_POST['work_order_notes'] ?? '');

    $aStmt = $db->prepare("SELECT ca.*, o.status as order_status FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id WHERE ca.id = ?");
    $aStmt->execute([$assignmentId]);
    $a = $aStmt->fetch();
    if (!$a) { setFlash('danger','Assignment not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $allowedStatuses = ['Assigned','Accepted','In Progress','Completed Submitted','Completed','Returned','Cancelled'];
    if (!in_array($status, $allowedStatuses)) {
        setFlash('danger','Invalid status.');
        header('Location:'.APP_URL.'/?page=contractor&action=job&id='.$assignmentId); exit;
    }

    $completedAtSql = ($status === 'Completed' || $status === 'Completed Submitted') ? ", completed_at = IFNULL(completed_at, NOW())" : "";

    $db->prepare("UPDATE contractor_assignments SET status = ?, target_date = ?, work_order_notes = ? $completedAtSql WHERE id = ?")
       ->execute([$status, $targetDate ?: null, $workOrderNotes, $assignmentId]);

    // Log progress update
    $db->prepare("INSERT INTO contractor_progress_updates (assignment_id, order_id, updated_by, progress_status, notes) VALUES (?,?,?,?,?)")
       ->execute([$assignmentId, $a['order_id'], $user['id'], $status, "Admin/PM updated job status to '$status'." . ($workOrderNotes ? " Notes: $workOrderNotes" : '')]);

    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$a['order_id'], $a['order_status'] ?? 'Installation', "Admin/PM updated contractor assignment status to '$status'.", $user['id']]);

    auditLog("Admin updated assignment #$assignmentId status to '$status'", 'contractor_assignments', $assignmentId);
    setFlash('success', 'Contractor job status and details updated successfully.');
    header('Location: ' . APP_URL . '/?page=contractor&action=job&id=' . $assignmentId);
    exit;
}


// ------------------------------------------------------------------
// Job Detail View
// ------------------------------------------------------------------
if ($action === 'job') {
    $assignmentId = (int)($_GET['id'] ?? 0);

    if ($partnerId) {
        $aStmt = $db->prepare("SELECT ca.*, p.name as contractor_name, o.order_number, o.status as order_status,
            o.customer_name, o.customer_location, o.service_type, o.gps_coordinates, o.building_name,
            o.special_requirements, o.bsa_delivery_method, o.bsa_special_conditions,
            u.full_name as assigned_by_name, u2.full_name as accepted_by_name,
            partner.name as partner_name
            FROM contractor_assignments ca
            JOIN partners p ON ca.contractor_partner_id = p.id
            JOIN orders o ON ca.order_id = o.id
            JOIN partners partner ON o.partner_id = partner.id
            LEFT JOIN users u ON ca.assigned_by = u.id
            LEFT JOIN users u2 ON ca.accepted_by = u2.id
            WHERE ca.id = ? AND ca.contractor_partner_id = ?");
        $aStmt->execute([$assignmentId, $partnerId]);
    } else {
        $aStmt = $db->prepare("SELECT ca.*, p.name as contractor_name, o.order_number, o.status as order_status,
            o.customer_name, o.customer_location, o.service_type, o.gps_coordinates, o.building_name,
            o.special_requirements, o.bsa_delivery_method, o.bsa_special_conditions,
            u.full_name as assigned_by_name, u2.full_name as accepted_by_name,
            partner.name as partner_name
            FROM contractor_assignments ca
            JOIN partners p ON ca.contractor_partner_id = p.id
            JOIN orders o ON ca.order_id = o.id
            JOIN partners partner ON o.partner_id = partner.id
            LEFT JOIN users u ON ca.assigned_by = u.id
            LEFT JOIN users u2 ON ca.accepted_by = u2.id
            WHERE ca.id = ?");
        $aStmt->execute([$assignmentId]);
    }

    $assignment = $aStmt->fetch();
    if (!$assignment) { setFlash('danger','Job not found.'); header('Location:'.APP_URL.'/?page=contractor'); exit; }

    $evidenceStmt = $db->prepare("SELECT ce.*, u.full_name FROM contractor_evidence ce LEFT JOIN users u ON ce.uploaded_by = u.id WHERE ce.assignment_id = ? ORDER BY ce.uploaded_at DESC");
    $evidenceStmt->execute([$assignmentId]);
    $evidence = $evidenceStmt->fetchAll();

    $progressStmt = $db->prepare("SELECT cpu.*, u.full_name FROM contractor_progress_updates cpu LEFT JOIN users u ON cpu.updated_by = u.id WHERE cpu.assignment_id = ? ORDER BY cpu.created_at DESC");
    $progressStmt->execute([$assignmentId]);
    $progressUpdates = $progressStmt->fetchAll();

    // Evidence checklist for this service type (order-specific)
    $rawSvc = trim($assignment['service_type'] ?? '');
    $svcTypes = [];
    if (!empty($rawSvc)) {
        $svcTypes[] = $rawSvc;
        if (stripos($rawSvc, 'FTTH') !== false) { $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'FTTB') !== false) { $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'FTTE') !== false) { $svcTypes[] = 'FTTE'; $svcTypes[] = 'FTTB'; }
        if (stripos($rawSvc, 'DIA') !== false) { $svcTypes[] = 'DIA'; }
        if (stripos($rawSvc, 'BIA') !== false || stripos($rawSvc, 'Broadband') !== false) { $svcTypes[] = 'BIA (Broadband Internet Access)'; $svcTypes[] = 'BIA'; $svcTypes[] = 'FTTH'; }
        if (stripos($rawSvc, 'Layer 2') !== false || stripos($rawSvc, 'Layer2') !== false || stripos($rawSvc, 'Ethernet') !== false) { $svcTypes[] = 'Layer 2 ( last mile)'; $svcTypes[] = 'Dedicated Layer 2'; }
        if (stripos($rawSvc, 'Remote Hands') !== false) { $svcTypes[] = 'Remote Hands Only'; $svcTypes[] = 'Remote Hands'; }
    }
    $svcTypes = array_values(array_unique(array_filter($svcTypes)));
    if (empty($svcTypes)) {
        $svcTypes = ['FTTH'];
    }

    $placeholders = implode(',', array_fill(0, count($svcTypes), '?'));
    $checklistStmt = $db->prepare("
        SELECT ecc.*, 
          (SELECT COUNT(*) FROM contractor_evidence ce 
           WHERE (ce.assignment_id = ? OR ce.order_id = ?) 
             AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(ecc.evidence_type))) as uploaded 
        FROM evidence_checklist_config ecc 
        WHERE ecc.service_type IN ($placeholders)
        ORDER BY ecc.id ASC
    ");
    $params = array_merge([$assignmentId, $assignment['order_id']], $svcTypes);
    $checklistStmt->execute($params);
    $rawChecklist = $checklistStmt->fetchAll();

    // Deduplicate by evidence_type if multiple match and load detailed uploaded files list
    $uniqueChecklist = [];
    foreach ($rawChecklist as $item) {
        $key = strtolower(trim($item['evidence_type']));
        if (!isset($uniqueChecklist[$key])) {
            $uniqueChecklist[$key] = $item;
        }
    }
    $checklist = array_values($uniqueChecklist);

    foreach ($checklist as &$item) {
        $filesStmt = $db->prepare("
            SELECT ce.*, u.full_name as uploader_name 
            FROM contractor_evidence ce 
            LEFT JOIN users u ON ce.uploaded_by = u.id 
            WHERE (ce.assignment_id = ? OR ce.order_id = ?) 
              AND LOWER(TRIM(ce.evidence_type)) = LOWER(TRIM(?))
            ORDER BY ce.uploaded_at DESC, ce.id DESC
        ");
        $filesStmt->execute([$assignmentId, $assignment['order_id'], $item['evidence_type']]);
        $item['files'] = $filesStmt->fetchAll();
        $item['uploaded'] = count($item['files']);
    }
    unset($item);

    $pageTitle = 'Job ' . $assignment['order_number'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractor/job_detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Contractor Dashboard (list all assignments)
// ------------------------------------------------------------------
if ($partnerId) {
    $myJobs = $db->prepare("SELECT ca.*, o.order_number, o.customer_name, o.service_type, o.customer_location,
        o.status as order_status, partner.name as partner_name
        FROM contractor_assignments ca
        JOIN orders o ON ca.order_id = o.id
        JOIN partners partner ON o.partner_id = partner.id
        WHERE ca.contractor_partner_id = ?
        ORDER BY ca.assigned_at DESC");
    $myJobs->execute([$partnerId]);
} else {
    // PM sees all
    $myJobs = $db->prepare("SELECT ca.*, o.order_number, o.customer_name, o.service_type, o.customer_location,
        o.status as order_status, partner.name as partner_name, cp.name as contractor_name
        FROM contractor_assignments ca
        JOIN orders o ON ca.order_id = o.id
        JOIN partners partner ON o.partner_id = partner.id
        JOIN partners cp ON ca.contractor_partner_id = cp.id
        ORDER BY ca.assigned_at DESC");
    $myJobs->execute([]);
}
$assignments = $myJobs->fetchAll();

// Counts
$counts = ['Assigned'=>0,'Accepted'=>0,'In Progress'=>0,'Completed Submitted'=>0,'Completed'=>0,'Returned'=>0,'SLA Due Today'=>0];
$todayStr = date('Y-m-d');
foreach ($assignments as $a) {
    $counts[$a['status']] = ($counts[$a['status']] ?? 0) + 1;
    if (!empty($a['target_date']) && $a['target_date'] <= $todayStr && !in_array($a['status'], ['Completed', 'Completed Submitted'])) {
        $counts['SLA Due Today']++;
    }
}

$pageTitle = isContractorUser() ? 'My Jobs' : 'Contractor Management';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/contractor/dashboard.php';
include APP_DIR . '/views/layout/footer.php';
