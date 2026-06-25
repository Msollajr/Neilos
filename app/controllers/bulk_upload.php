<?php
// ============================================================
// Bulk FTTH Upload Controller
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'index';
$show   = $_GET['show'] ?? 'upload';

// ------------------------------------------------------------------
// Download CSV Template
// ------------------------------------------------------------------
if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ftth_bulk_upload_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Customer Name', 'GPS Coordinates', 'Building Name', 'Floor Number', 'Apartment Number', 'Contact Phone', 'Contact Email', 'Package']);
    fputcsv($out, ['Example Customer', '-6.7924,39.2083', 'Example Building', '3rd Floor', 'Apt 12B', '+255712345678', 'customer@example.com', '50 Mbps']);
    fputcsv($out, ['', '', '', '', '', '', '', '20 Mbps']);
    fputcsv($out, ['', '', '', '', '', '', '', '30 Mbps']);
    fputcsv($out, ['', '', '', '', '', '', '', '40 Mbps']);
    fputcsv($out, ['', '', '', '', '', '', '', '60 Mbps']);
    fputcsv($out, ['', '', '', '', '', '', '', '80 Mbps']);
    fputcsv($out, ['', '', '', '', '', '', '', '100 Mbps']);
    fclose($out);
    exit;
}

// ------------------------------------------------------------------
// POST: Upload & Process CSV
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    verifyCsrf();

    $partnerId = isPartnerUser() ? $user['partner_id'] : (int)($_POST['partner_id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Partner is required.');
        header('Location: ' . APP_URL . '/?page=bulk_upload');
        exit;
    }

    if (empty($_FILES['csv_file']['tmp_name'])) {
        setFlash('danger', 'Please select a CSV file to upload.');
        header('Location: ' . APP_URL . '/?page=bulk_upload');
        exit;
    }

    // Upload the CSV file
    try {
        $up = uploadFile($_FILES['csv_file'], 'bulk_upload');
    } catch (RuntimeException $e) {
        setFlash('danger', 'File upload error: ' . e($e->getMessage()));
        header('Location: ' . APP_URL . '/?page=bulk_upload');
        exit;
    }

    // Parse CSV
    $filePath = UPLOAD_DIR . 'bulk_upload/' . basename($up['path']);
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        setFlash('danger', 'Failed to read uploaded file.');
        header('Location: ' . APP_URL . '/?page=bulk_upload');
        exit;
    }

    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        setFlash('danger', 'Empty CSV file.');
        header('Location: ' . APP_URL . '/?page=bulk_upload');
        exit;
    }

    // Map expected columns
    $expected = ['Customer Name', 'GPS Coordinates', 'Building Name', 'Floor Number', 'Apartment Number', 'Contact Phone', 'Contact Email', 'Package'];
    $colMap = [];
    foreach ($expected as $i => $col) {
        $idx = array_search($col, $header);
        if ($idx === false && in_array($col, ['GPS Coordinates', 'Building Name', 'Floor Number', 'Apartment Number', 'Contact Email'])) {
            $colMap[$col] = null;
        } elseif ($idx === false) {
            fclose($handle);
            setFlash('danger', "Missing required column: \"$col\". Please use the template format.");
            header('Location: ' . APP_URL . '/?page=bulk_upload');
            exit;
        } else {
            $colMap[$col] = $idx;
        }
    }

    $validPackages = ['20 Mbps', '30 Mbps', '40 Mbps', '50 Mbps', '60 Mbps', '80 Mbps', '100 Mbps'];
    $rows = [];
    $errors = [];
    $validRows = [];
    $totalRows = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $totalRows++;

        $customerName = trim($row[$colMap['Customer Name']] ?? '');
        $gps = $colMap['GPS Coordinates'] !== null ? trim($row[$colMap['GPS Coordinates']] ?? '') : '';
        $building = $colMap['Building Name'] !== null ? trim($row[$colMap['Building Name']] ?? '') : '';
        $floor = $colMap['Floor Number'] !== null ? trim($row[$colMap['Floor Number']] ?? '') : '';
        $apt = $colMap['Apartment Number'] !== null ? trim($row[$colMap['Apartment Number']] ?? '') : '';
        $phone = trim($row[$colMap['Contact Phone']] ?? '');
        $email = $colMap['Contact Email'] !== null ? trim($row[$colMap['Contact Email']] ?? '') : '';
        $pkg = trim($row[$colMap['Package']] ?? '');

        $rowErrors = [];

        if (!$customerName) {
            $rowErrors[] = 'Customer Name is required';
        }
        if (!$phone) {
            $rowErrors[] = 'Contact Phone is required';
        }
        if (!$pkg) {
            $rowErrors[] = 'Package is required';
        } elseif (!in_array($pkg, $validPackages)) {
            $rowErrors[] = "Invalid package \"$pkg\". Allowed: " . implode(', ', $validPackages);
        }

        if (!empty($rowErrors)) {
            $errors[] = [
                'row' => $totalRows + 1,
                'customer_name' => $customerName,
                'errors' => $rowErrors,
            ];
        } else {
            $validRows[] = [
                'customer_name' => $customerName,
                'gps_coordinates' => $gps,
                'building_name' => $building,
                'floor_number' => $floor,
                'apartment_number' => $apt,
                'customer_contact_phone' => $phone,
                'customer_contact_email' => $email,
                'fttx_package' => $pkg,
            ];
        }
    }
    fclose($handle);

    // Create batch record
    $batchNum = 'BULK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $db->prepare("INSERT INTO bulk_upload_batches (batch_number, partner_id, uploaded_by, file_name, file_path, total_rows, valid_rows, invalid_rows, orders_created, status, error_log)
        VALUES (?,?,?,?,?,?,?,?,0,'Processing',?)")
       ->execute([$batchNum, $partnerId, $user['id'], $up['name'], $up['path'], $totalRows, count($validRows), count($errors), json_encode($errors)]);

    $batchId = $db->lastInsertId();

    // Create orders for valid rows
    $ordersCreated = 0;
    $createdOrders = []; // [ ['order_number'=>'SO-...', 'id'=>1], ... ]

    $kamStmt = $db->prepare("SELECT id, full_name FROM users WHERE role = 'KAM' AND is_active = 1 LIMIT 1");
    $kamStmt->execute();
    $defaultKam = $kamStmt->fetch();

    foreach ($validRows as $vr) {
        $postData = array_merge($vr, ['service_type' => 'FTTH']);
        $comm = calculateCommercials($postData);
        $orderNum = generateOrderNumber();

        $stmt = $db->prepare("INSERT INTO orders (
            order_number, partner_id,
            customer_name, gps_coordinates, building_name, floor_number, apartment_number,
            customer_contact_phone, customer_contact_email,
            service_type, fttx_package,
            usd_tzs_rate, base_nrc_usd, remote_hands_nrc_usd, nrc_subtotal_usd, vat_on_nrc, total_nrc_incl_vat,
            base_mrc, mrc_currency, discount_pct, discount_amount, vat_on_mrc, total_mrc_incl_vat,
            status, assigned_kam_name, kam_id, created_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            $orderNum, $partnerId,
            $vr['customer_name'], $vr['gps_coordinates'], $vr['building_name'], $vr['floor_number'], $vr['apartment_number'],
            $vr['customer_contact_phone'], $vr['customer_contact_email'],
            'FTTH', $vr['fttx_package'],
            USD_TZS_RATE,
            $comm['base_nrc_usd'], $comm['remote_hands_nrc_usd'], $comm['nrc_subtotal_usd'],
            $comm['vat_on_nrc'], $comm['total_nrc_incl_vat'],
            $comm['base_mrc'], $comm['mrc_currency'], $comm['discount_pct'],
            $comm['discount_amount'], $comm['vat_on_mrc'], $comm['total_mrc_incl_vat'],
            'Submitted',
            $defaultKam ? $defaultKam['full_name'] : null,
            $defaultKam ? $defaultKam['id'] : null,
            $user['id'],
        ]);

        $orderId = $db->lastInsertId();

        // Timeline entry
        $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
           ->execute([$orderId, 'Submitted', 'Order created via bulk FTTH upload.', $user['id']]);

        $ordersCreated++;
        $createdOrders[] = ['order_number' => $orderNum, 'id' => $orderId];
    }

    // Update batch record
    $db->prepare("UPDATE bulk_upload_batches SET orders_created = ?, status = 'Completed' WHERE id = ?")
       ->execute([$ordersCreated, $batchId]);

    auditLog("Bulk upload batch $batchNum: $ordersCreated orders created, " . count($errors) . " errors", 'bulk_upload', $batchId);

    if ($ordersCreated > 0) {
        setFlash('success', "Bulk upload complete. $ordersCreated order(s) created successfully.");
    }
    if (!empty($errors)) {
        setFlash('warning', count($errors) . ' row(s) had errors and were skipped.');
    }

    // Show results
    $pageTitle = 'Bulk Upload Results';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/bulk_upload/results.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Default: Show upload form & history
// ------------------------------------------------------------------
$pw = partnerWhere('bub');
$stmt = $db->prepare("SELECT bub.*, p.name as partner_name FROM bulk_upload_batches bub JOIN partners p ON bub.partner_id = p.id WHERE {$pw['condition']} ORDER BY bub.created_at DESC LIMIT 20");
$stmt->execute($pw['params']);
$batches = $stmt->fetchAll();

$partners = [];
if (!isPartnerUser()) {
    $partners = $db->query("SELECT id, name FROM partners WHERE status = 'Active' ORDER BY name")->fetchAll();
}

$pageTitle = 'Bulk FTTH Upload';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/bulk_upload/index.php';
include APP_DIR . '/views/layout/footer.php';
