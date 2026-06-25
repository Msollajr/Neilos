<?php
// ============================================================
// KYC Application Controller
// ============================================================
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'index';

// Ensure partner has a KYC application (create draft if missing)
function ensureKycApplication(PDO $db, array $user): array {
    if (isPartnerUser()) {
        $partnerId = $user['partner_id'];
    } else {
        $partnerId = (int)($_GET['partner_id'] ?? $user['partner_id'] ?? 0);
        if (!$partnerId) {
            $partnerId = (int)($_POST['partner_id'] ?? 0);
        }
    }
    if (!$partnerId) {
        setFlash('danger', 'Partner ID not found.');
        header('Location: ' . APP_URL . '/?page=dashboard');
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM partner_kyc_applications WHERE partner_id = ?");
    $stmt->execute([$partnerId]);
    $app = $stmt->fetch();

    if (!$app) {
        $db->prepare("INSERT INTO partner_kyc_applications (partner_id, status) VALUES (?, 'Draft')")->execute([$partnerId]);
        $appId = $db->lastInsertId();

        // Create document checklist for this application
        $docs = [
            ['Signed MSA', 1],
            ['Signed SOF', 1],
            ['Certificate of Incorporation / Compliance / Registration', 1],
            ['TIN Certificate', 1],
            ['Authorized Signatory ID', 1],
            ['Business License', 0],
            ['VAT Certificate', 0],
            ['Memorandum and Articles of Association', 0],
            ['Beneficial Ownership Declaration', 0],
            ['Financial Statements', 0],
            ['Tax Clearance Certificate', 0],
        ];
        $ins = $db->prepare("INSERT INTO partner_kyc_application_documents (kyc_application_id, document_type, is_mandatory, status) VALUES (?,?,?,'Not Uploaded')");
        foreach ($docs as $d) {
            $ins->execute([$appId, $d[0], $d[1]]);
        }

        $stmt->execute([$partnerId]);
        $app = $stmt->fetch();
    }

    $app['partner_id_val'] = $partnerId;
    return $app;
}

// ------------------------------------------------------------------
// POST: Save KYC
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    verifyCsrf();
    $app = ensureKycApplication($db, $user);
    $kycId = $app['id'];

    $fields = [
        'registered_name', 'trading_name', 'partner_type', 'customer_category',
        'industry_sector', 'nature_of_business', 'registration_number', 'tin', 'vat_vrn',
        'address', 'city_region', 'country',
        'auth_signatory_name', 'auth_signatory_title', 'auth_signatory_dept',
        'auth_signatory_id_type', 'auth_signatory_id_number',
        'auth_signatory_mobile', 'auth_signatory_email',
        'finance_contact_name', 'finance_contact_title', 'finance_contact_mobile',
        'finance_contact_email', 'billing_email',
        'tech_contact_name', 'tech_contact_title', 'tech_contact_mobile', 'tech_contact_email',
    ];

    $sets = [];
    $params = [];
    foreach ($fields as $f) {
        $sets[] = "$f = ?";
        $params[] = $_POST[$f] ?? null;
    }
    $params[] = $kycId;

    $db->prepare("UPDATE partner_kyc_applications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);

    // Handle document uploads
    if (!empty($_FILES['documents']['name'])) {
        foreach ($_FILES['documents']['name'] as $docId => $fname) {
            if (!$fname) continue;
            $file = [
                'name'     => $fname,
                'tmp_name' => $_FILES['documents']['tmp_name'][$docId],
                'error'    => $_FILES['documents']['error'][$docId],
                'size'     => $_FILES['documents']['size'][$docId],
            ];
            try {
                $up = uploadFile($file, 'kyc/' . $kycId);
                $db->prepare("UPDATE partner_kyc_application_documents SET file_name = ?, file_path = ?, status = 'Uploaded', upload_date = NOW() WHERE id = ? AND kyc_application_id = ?")
                   ->execute([$up['name'], $up['path'], $docId, $kycId]);
            } catch (RuntimeException $e) {
                setFlash('danger', 'Upload error for document: ' . e($e->getMessage()));
                header('Location: ' . APP_URL . '/?page=kyc');
                exit;
            }
        }
    }

    auditLog("Updated KYC application #$kycId", 'kyc', $kycId);
    setFlash('success', 'KYC application saved.');
    header('Location: ' . APP_URL . '/?page=kyc');
    exit;
}

// ------------------------------------------------------------------
// POST: Submit KYC
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    verifyCsrf();
    $app = ensureKycApplication($db, $user);
    $kycId = $app['id'];

    // Validate mandatory fields
    $mandatory = [
        'registered_name', 'trading_name', 'partner_type',
        'registration_number', 'tin', 'address', 'city_region', 'country',
        'auth_signatory_name', 'auth_signatory_email', 'auth_signatory_mobile',
        'finance_contact_name', 'finance_contact_email', 'billing_email',
        'tech_contact_name', 'tech_contact_email',
    ];

    $missing = [];
    foreach ($mandatory as $f) {
        if (empty($app[$f])) {
            $missing[] = str_replace('_', ' ', ucfirst($f));
        }
    }

    // Check mandatory documents
    $docStmt = $db->prepare("SELECT document_type FROM partner_kyc_application_documents WHERE kyc_application_id = ? AND is_mandatory = 1 AND status != 'Uploaded'");
    $docStmt->execute([$kycId]);
    $missingDocs = $docStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($missing) || !empty($missingDocs)) {
        $err = 'Please complete all required fields.';
        if (!empty($missing)) $err .= ' Missing: ' . implode(', ', $missing) . '.';
        if (!empty($missingDocs)) $err .= ' Missing documents: ' . implode(', ', $missingDocs) . '.';
        setFlash('danger', $err);
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    $db->prepare("UPDATE partner_kyc_applications SET status = 'Submitted', submitted_at = NOW() WHERE id = ?")->execute([$kycId]);
    auditLog("Submitted KYC application #$kycId", 'kyc', $kycId);
    setFlash('success', 'KYC application submitted successfully.');
    header('Location: ' . APP_URL . '/?page=kyc');
    exit;
}

// ------------------------------------------------------------------
// POST: Admin Approve / Reject
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['approve', 'reject'])) {
    if (isPartnerUser()) {
        setFlash('danger', 'Permission denied.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }
    verifyCsrf();

    $kycId = (int)($_POST['kyc_id'] ?? 0);
    $notes = $_POST['review_notes'] ?? '';

    $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
    $db->prepare("UPDATE partner_kyc_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?")
       ->execute([$newStatus, $user['id'], $notes, $kycId]);

    auditLog("KYC #$kycId $newStatus", 'kyc', $kycId);
    setFlash('success', "KYC application $newStatus.");
    header('Location: ' . APP_URL . '/?page=kyc&action=admin_detail&id=' . $kycId);
    exit;
}

// ------------------------------------------------------------------
// POST: Upload Countersigned KYC PDF
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_countersigned') {
    if (isPartnerUser()) {
        setFlash('danger', 'Permission denied.');
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }
    verifyCsrf();

    $kycId = (int)($_POST['kyc_id'] ?? 0);

    if (!empty($_FILES['countersigned_kyc']['name'])) {
        try {
            $up = uploadFile($_FILES['countersigned_kyc'], 'kyc/countersigned');
            $db->prepare("UPDATE partner_kyc_applications SET countersigned_kyc_file = ?, countersigned_kyc_filename = ?, countersigned_kyc_date = NOW() WHERE id = ?")
               ->execute([$up['path'], $up['name'], $kycId]);
            auditLog("Uploaded countersigned KYC for #$kycId", 'kyc', $kycId);
            setFlash('success', 'Countersigned KYC uploaded.');
        } catch (RuntimeException $e) {
            setFlash('danger', 'Upload error: ' . e($e->getMessage()));
        }
    }

    header('Location: ' . APP_URL . '/?page=kyc&action=admin_detail&id=' . $kycId);
    exit;
}

// ------------------------------------------------------------------
// Admin List
// ------------------------------------------------------------------
if ($action === 'admin_list') {
    if (isPartnerUser()) {
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    $pw = partnerWhere('pka');
    $stmt = $db->prepare("SELECT pka.*, p.name as partner_name, u.full_name as reviewer_name
        FROM partner_kyc_applications pka
        JOIN partners p ON pka.partner_id = p.id
        LEFT JOIN users u ON pka.reviewed_by = u.id
        WHERE {$pw['condition']}
        ORDER BY pka.updated_at DESC");
    $stmt->execute($pw['params']);
    $applications = $stmt->fetchAll();

    $pageTitle = 'KYC Applications';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/kyc/admin_list.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Admin Detail
// ------------------------------------------------------------------
if ($action === 'admin_detail') {
    if (isPartnerUser()) {
        header('Location: ' . APP_URL . '/?page=kyc');
        exit;
    }

    $kycId = (int)($_GET['id'] ?? 0);
    $pw = partnerWhere('pka');
    $stmt = $db->prepare("SELECT pka.*, p.name as partner_name, u.full_name as reviewer_name
        FROM partner_kyc_applications pka
        JOIN partners p ON pka.partner_id = p.id
        LEFT JOIN users u ON pka.reviewed_by = u.id
        WHERE pka.id = ? AND {$pw['condition']}");
    $stmt->execute(array_merge([$kycId], $pw['params']));
    $app = $stmt->fetch();

    if (!$app) {
        http_response_code(404);
        echo '<p style="padding:40px">KYC application not found.</p>';
        exit;
    }

    $docs = $db->prepare("SELECT * FROM partner_kyc_application_documents WHERE kyc_application_id = ? ORDER BY is_mandatory DESC, document_type ASC");
    $docs->execute([$kycId]);
    $docs = $docs->fetchAll();

    $pageTitle = 'KYC Detail - ' . e($app['partner_name']);
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/kyc/admin_detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Default: KYC form for current partner (admin → list)
// ------------------------------------------------------------------
if (!isPartnerUser()) {
    header('Location: ' . APP_URL . '/?page=kyc&action=admin_list');
    exit;
}
$app = ensureKycApplication($db, $user);
$kycId = $app['id'];

$docs = $db->prepare("SELECT * FROM partner_kyc_application_documents WHERE kyc_application_id = ? ORDER BY is_mandatory DESC, document_type ASC");
$docs->execute([$kycId]);
$docs = $docs->fetchAll();

$partner = $db->prepare("SELECT * FROM partners WHERE id = ?");
$partner->execute([$app['partner_id_val']]);
$partner = $partner->fetch();

$isSubmitted = in_array($app['status'], ['Submitted', 'Under Review', 'Approved', 'Rejected']);

$pageTitle = 'KYC Application';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/kyc/form.php';
include APP_DIR . '/views/layout/footer.php';
