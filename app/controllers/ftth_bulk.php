<?php
// ============================================================
// FTTH Bulk Upload Controller
// Allows Admin/BSA to bulk-import FTTH orders via CSV file
// CSV columns: customer_name, customer_location, gps_coordinates,
//   site_category, capacity, contract_term, special_requirements,
//   partner_name (or partner_id), kam_name
// ============================================================
requireLogin();
if (!hasRole('Partner', 'Partner User', 'Management', 'Admin', 'System Admin') && !isAdmin() && !hasPermission('orders.bulk')) {
    setFlash('danger','Access denied. Only Partner, Management, and System Admin roles can perform FTTH bulk uploads.');
    header('Location: ' . APP_URL . '/?page=orders'); exit;
}

$db   = getDB();
$user = currentUser();

// ------------------------------------------------------------------
// GET: Download CSV template
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'template') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ftth_bulk_upload_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'customer_name','customer_contact_name','customer_contact_phone','customer_contact_email',
        'customer_location','gps_coordinates','site_category',
        'capacity_mbps','contract_term_months','special_requirements',
        'partner_id','kam_name','nrc_tzs','mrc_tzs'
    ]);

    $firstPartner = $db->query("SELECT id, assigned_kam_name FROM partners ORDER BY id ASC LIMIT 1")->fetch();
    $samplePartnerId = isPartnerUser() ? (int)$user['partner_id'] : (int)($firstPartner['id'] ?? 2);
    $sampleKamName   = $firstPartner['assigned_kam_name'] ?? 'Assigned KAM';
    if (isPartnerUser()) {
        $pStmt = $db->prepare("SELECT assigned_kam_name FROM partners WHERE id = ?");
        $pStmt->execute([$samplePartnerId]);
        $sampleKamName = $pStmt->fetchColumn() ?: $sampleKamName;
    }

    fputcsv($out, [
        'Acme Telecom Ltd','John Doe','+255 712 345 678','john.doe@acme.co.tz',
        'Msasani, Dar es Salaam','−6.7835, 39.2685','Residential',
        10,12,'', (string)$samplePartnerId, $sampleKamName,'600000','120000'
    ]);
    fclose($out);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete bulk upload history record and all associated orders (Admin & Management only)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'delete_upload' || ($_POST['action'] ?? '') === 'delete_upload')) {
    verifyCsrf();
    if (isPartnerUser() || (!isAdmin() && !hasRole('Management', 'Admin', 'System Admin'))) {
        setFlash('danger', 'Access denied. Partners cannot delete bulk upload batches.');
        header('Location: ' . APP_URL . '/?page=ftth_bulk');
        exit;
    }
    $uploadId = (int)($_POST['upload_id'] ?? $_GET['id'] ?? 0);
    if ($uploadId) {
        $stmt = $db->prepare("SELECT * FROM ftth_bulk_uploads WHERE id = ?");
        $stmt->execute([$uploadId]);
        $uploadRec = $stmt->fetch();

        if ($uploadRec) {
            $deletedOrdersCount = 0;
            if (!empty($uploadRec['created_orders'])) {
                $orderIds = array_values(array_filter(array_map('intval', explode(',', $uploadRec['created_orders']))));
                foreach ($orderIds as $orderId) {
                    if ($orderId > 0) {
                        $db->prepare("DELETE FROM order_timeline WHERE order_id = ?")->execute([$orderId]);
                        $db->prepare("DELETE FROM order_documents WHERE order_id = ?")->execute([$orderId]);
                        $db->prepare("DELETE FROM contractor_assignments WHERE order_id = ?")->execute([$orderId]);
                        $db->prepare("DELETE FROM contractor_evidence WHERE order_id = ?")->execute([$orderId]);
                        $db->prepare("DELETE FROM order_returns WHERE order_id = ?")->execute([$orderId]);
                        $db->prepare("DELETE FROM active_services WHERE order_id = ?")->execute([$orderId]);
                        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
                        $deletedOrdersCount++;
                    }
                }
            }

            $db->prepare("DELETE FROM ftth_bulk_uploads WHERE id = ?")->execute([$uploadId]);
            auditLog("Deleted bulk upload log #$uploadId and removed $deletedOrdersCount associated orders", 'ftth_bulk_uploads', $uploadId);

            if ($deletedOrdersCount > 0) {
                setFlash('success', "Bulk upload batch #$uploadId and <strong>$deletedOrdersCount associated order(s)</strong> have been permanently deleted.");
            } else {
                setFlash('success', "Bulk upload history record #$uploadId deleted successfully.");
            }
        }
    }
    header('Location: ' . APP_URL . '/?page=ftth_bulk');
    exit;
}

// ------------------------------------------------------------------
// POST: Process CSV upload
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (empty($_FILES['csv_file']['name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger','Please select a valid CSV file.');
        header('Location: ' . APP_URL . '/?page=ftth_bulk'); exit;
    }

    $tmpFile   = $_FILES['csv_file']['tmp_name'];
    $origFile  = basename($_FILES['csv_file']['name']);
    $handle    = fopen($tmpFile, 'r');

    // Read header row
    $header = array_map('trim', fgetcsv($handle));
    $required = [
        'customer_name',
        'customer_contact_name',
        'customer_contact_phone',
        'customer_contact_email',
        'customer_location',
        'capacity_mbps'
    ];
    // If not a partner user, partner_id column is required in CSV
    if (!isPartnerUser()) {
        $required[] = 'partner_id';
    }

    $missing  = array_diff($required, $header);
    if ($missing) {
        setFlash('danger', 'CSV is missing required columns: ' . implode(', ', $missing));
        header('Location: ' . APP_URL . '/?page=ftth_bulk'); exit;
    }

    $totalRows   = 0;
    $successRows = 0;
    $errorRows   = 0;
    $errors      = [];
    $createdIds  = [];

    while (($row = fgetcsv($handle)) !== false) {
        $totalRows++;
        $data = array_combine($header, array_pad($row, count($header), ''));

        $customerName  = trim($data['customer_name'] ?? '');
        $contactName   = trim($data['customer_contact_name'] ?? '');
        $contactPhone  = trim($data['customer_contact_phone'] ?? '');
        $contactEmail  = trim($data['customer_contact_email'] ?? '');
        $customerLoc   = trim($data['customer_location'] ?? '');
        $capacity      = (int)($data['capacity_mbps'] ?? 0);
        $partnerId     = isPartnerUser() ? (int)$user['partner_id'] : (int)($data['partner_id'] ?? 0);

        // Mandatory fields validation
        if (!$customerName || !$customerLoc || !$capacity || !$partnerId) {
            $errors[] = "Row $totalRows: Missing required core fields (customer_name, customer_location, capacity_mbps, partner_id).";
            $errorRows++;
            continue;
        }

        if (!$contactName || !$contactPhone || !$contactEmail) {
            $errors[] = "Row $totalRows ($customerName): Customer contact details are mandatory (customer_contact_name, customer_contact_phone, customer_contact_email).";
            $errorRows++;
            continue;
        }

        if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row $totalRows ($customerName): Invalid customer contact email format '$contactEmail'.";
            $errorRows++;
            continue;
        }

        // Verify partner exists
        $pStmt = $db->prepare("SELECT id, kam_id, assigned_kam_name FROM partners WHERE id = ?");
        $pStmt->execute([$partnerId]);
        $partner = $pStmt->fetch();
        if (!$partner) {
            $errors[] = "Row $totalRows: Partner ID $partnerId not found.";
            $errorRows++;
            continue;
        }

        // Resolve KAM
        $kamName = trim($data['kam_name'] ?? '') ?: ($partner['assigned_kam_name'] ?? '');
        $kamId   = $partner['kam_id'] ?? null;
        if ($kamName) {
            $ks = $db->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'KAM' LIMIT 1");
            $ks->execute([$kamName]);
            $kamId = $ks->fetchColumn() ?: $kamId;
        }

        // Parse TZS amounts if provided
        $nrcTzs = 0.0;
        $mrcTzs = 0.0;
        if (!empty($data['nrc_tzs'])) {
            try {
                $nrcTzs = (float)parseTZSInput($data['nrc_tzs'], true, true, MAX_NRC_AMOUNT, "Row $totalRows NRC");
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
                $errorRows++;
                continue;
            }
        }
        if (!empty($data['mrc_tzs'])) {
            try {
                $mrcTzs = (float)parseTZSInput($data['mrc_tzs'], true, true, MAX_MRC_AMOUNT, "Row $totalRows MRC");
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
                $errorRows++;
                continue;
            }
        }

        try {
            $orderNum = generateOrderNumber();
            $inserted = false; $attempts = 0;
            while (!$inserted && $attempts < 5) {
                $attempts++;
                try {
                    $db->prepare("INSERT INTO orders (
                        order_number, partner_id, kam_id, assigned_kam_name,
                        customer_name, customer_contact_name, customer_contact_phone, customer_contact_email,
                        customer_location, gps_coordinates, site_category,
                        service_type, bandwidth, contract_term,
                        special_requirements,
                        base_nrc_usd, base_mrc, standard_nrc, standard_mrc,
                        nrc_subtotal_usd, vat_on_nrc, total_nrc_incl_vat,
                        vat_on_mrc, total_mrc_incl_vat,
                        usd_tzs_rate, mrc_currency,
                        status, created_by
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
                        $orderNum, $partnerId, $kamId, $kamName,
                        $customerName, $contactName, $contactPhone, $contactEmail,
                        $customerLoc,
                        trim($data['gps_coordinates'] ?? ''),
                        trim($data['site_category'] ?? '') ?: null,
                        'FTTH', $capacity,
                        !empty($data['contract_term_months']) ? ((int)$data['contract_term_months'] . ' Months') : '12 Months',
                        trim($data['special_requirements'] ?? '') ?: null,
                        $nrcTzs, $mrcTzs, $nrcTzs, $mrcTzs,
                        $nrcTzs, round($nrcTzs * 0.18, 2), round($nrcTzs * 1.18, 2),
                        round($mrcTzs * 0.18, 2), round($mrcTzs * 1.18, 2),
                        1, 'TZS',
                        'Feasibility Review', $user['id']
                    ]);
                    $inserted = true;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                        $orderNum = generateOrderNumber();
                    } else { throw $e; }
                }
            }

            $newOrderId = (int)$db->lastInsertId();
            $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
               ->execute([$newOrderId, 'Feasibility Review', "Order created via FTTH bulk upload by {$user['full_name']}.", $user['id']]);

            $createdIds[] = $newOrderId;
            $successRows++;
        } catch (Exception $e) {
            $errors[] = "Row $totalRows ($customerName): " . $e->getMessage();
            $errorRows++;
        }
    }
    fclose($handle);

    // Record the upload session
    $db->prepare("INSERT INTO ftth_bulk_uploads (uploaded_by, original_file, total_rows, success_rows, error_rows, errors_json, created_orders) VALUES (?,?,?,?,?,?,?)")
       ->execute([$user['id'], $origFile, $totalRows, $successRows, $errorRows, json_encode($errors), implode(',', $createdIds)]);

    auditLog("FTTH bulk upload: $successRows created, $errorRows errors from $origFile", 'ftth_bulk_uploads', (int)$db->lastInsertId());

    if ($errorRows > 0) {
        setFlash('warning', "Bulk upload complete: <strong>$successRows</strong> orders created, <strong>$errorRows</strong> rows had errors.");
    } else {
        setFlash('success', "Bulk upload complete: <strong>$successRows</strong> FTTH orders created successfully.");
    }
    header('Location: ' . APP_URL . '/?page=ftth_bulk&result=done');
    exit;
}

// ------------------------------------------------------------------
// GET: Show upload form + recent upload history
// ------------------------------------------------------------------
if (isPartnerUser()) {
    $recentUploads = $db->prepare("
        SELECT fbu.*, u.full_name AS uploaded_by_name
        FROM ftth_bulk_uploads fbu
        LEFT JOIN users u ON fbu.uploaded_by = u.id
        WHERE fbu.uploaded_by = ?
        ORDER BY fbu.created_at DESC LIMIT 20
    ");
    $recentUploads->execute([$user['id']]);
} else {
    $recentUploads = $db->prepare("
        SELECT fbu.*, u.full_name AS uploaded_by_name
        FROM ftth_bulk_uploads fbu
        LEFT JOIN users u ON fbu.uploaded_by = u.id
        ORDER BY fbu.created_at DESC LIMIT 20
    ");
    $recentUploads->execute();
}
$uploads = $recentUploads->fetchAll();

// Map created order IDs to order numbers
$allOrderIds = [];
foreach ($uploads as $u) {
    if (!empty($u['created_orders'])) {
        foreach (explode(',', $u['created_orders']) as $oid) {
            $oid = (int)trim($oid);
            if ($oid > 0) $allOrderIds[$oid] = $oid;
        }
    }
}
$ordersMap = [];
if (!empty($allOrderIds)) {
    $placeholders = implode(',', array_fill(0, count($allOrderIds), '?'));
    $stmt = $db->prepare("SELECT id, order_number FROM orders WHERE id IN ($placeholders)");
    $stmt->execute(array_values($allOrderIds));
    $ordersMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}


$pageTitle = 'FTTH Bulk Upload';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/orders/ftth_bulk.php';
include APP_DIR . '/views/layout/footer.php';
