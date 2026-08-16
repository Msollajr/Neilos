<?php
// ============================================================
// Neilos Company Settings Controller
// Allows Admin to maintain Neilos company information (used in SOF)
// ============================================================
requireLogin();
requirePermission('admin.settings');

$db   = getDB();
$user = currentUser();

// ------------------------------------------------------------------
// POST: Save company info
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fields = [
        'company_name', 'trading_name', 'registration_number',
        'tin', 'vat_vrn', 'address', 'city_region', 'country',
        'phone', 'email', 'website',
        'authorized_signatory', 'signatory_title',
        'finance_contact', 'finance_email',
        'tech_contact', 'tech_email',
    ];

    $set  = [];
    $vals = [];
    foreach ($fields as $f) {
        $set[]  = "$f = ?";
        $vals[] = trim($_POST[$f] ?? '');
    }
    $set[]  = 'updated_by = ?';
    $vals[] = $user['id'];

    // Handle logo upload
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        try {
            $up = uploadFile($_FILES['logo'], 'neilos/logo');
            $set[]  = 'logo_path = ?';
            $vals[] = $up['path'];
        } catch (RuntimeException $e) {
            setFlash('warning', 'Logo upload note: ' . e($e->getMessage()));
        }
    }

    // Upsert row id=1
    $existing = $db->prepare("SELECT id FROM neilos_company_info WHERE id = 1");
    $existing->execute();
    if ($existing->fetch()) {
        $sql = "UPDATE neilos_company_info SET " . implode(', ', $set) . " WHERE id = 1";
        $db->prepare($sql)->execute($vals);
    } else {
        $allCols = array_merge($fields, ['updated_by']);
        $sql = "INSERT INTO neilos_company_info (" . implode(',', $allCols) . ") VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ")";
        $db->prepare($sql)->execute($vals);
    }

    auditLog('Updated Neilos company settings', 'neilos_company_info', 1);
    setFlash('success', 'Company settings saved successfully.');
    header('Location: ' . APP_URL . '/?page=settings');
    exit;
}

// ------------------------------------------------------------------
// GET: Load company info for display
// ------------------------------------------------------------------
$company = $db->query("SELECT * FROM neilos_company_info WHERE id = 1")->fetch() ?: [];

$pageTitle = 'Neilos Company Settings';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/settings/company.php';
include APP_DIR . '/views/layout/footer.php';
