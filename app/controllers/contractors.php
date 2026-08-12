<?php
// ============================================================
// Contractors Management Controller — Admin only
// Manages contractor organizations (partners with kyc_type='Contractor'):
// listing, details, editing details, and status updates
// ============================================================
requireLogin();

if (!isAdmin() && !hasRole('Management')) {
    setFlash('danger', 'Access denied. Admin or Management only.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// Ensure the kyc_type column exists on partners (in case migration not applied)
try {
    $pCols = $db->query("SHOW COLUMNS FROM partners")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kyc_type', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN kyc_type ENUM('Partner','Contractor') NOT NULL DEFAULT 'Partner' AFTER partner_type");
    }
} catch (Exception $e) {}

// KYC columns that may or may not exist depending on migration status
$kycCols = [];
try {
    $kycCols = $db->query("SHOW COLUMNS FROM partner_kyc_applications")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

/**
 * Fetch existing Contractor KYC application row for a partner (if any).
 * Does NOT auto-insert a row if missing.
 */
function ensureContractorKyc(PDO $db, int $partnerId): ?array {
    $stmt = $db->prepare("SELECT * FROM partner_kyc_applications WHERE partner_id = ? AND kyc_type = 'Contractor'");
    $stmt->execute([$partnerId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ------------------------------------------------------------------
// POST: Update contractor details & status
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    verifyCsrf();

    $contractorId = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM partners WHERE id = ? AND kyc_type = 'Contractor'");
    $stmt->execute([$contractorId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        setFlash('danger', 'Contractor not found.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    $status = $_POST['status'] ?? 'Active';
    if (!in_array($status, ['Active', 'Inactive', 'Suspended'])) { $status = 'Active'; }

    $db->prepare("UPDATE partners SET
        name = ?, trading_name = ?, partner_type = ?, status = ?,
        customer_category = ?, industry_sector = ?, nature_of_business = ?,
        registration_number = ?, tin = ?, vat_vrn = ?,
        address = ?, city_region = ?, country = ?
        WHERE id = ?")
        ->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['trading_name'] ?? ''),
            $_POST['partner_type'] ?? 'Other',
            $status,
            trim($_POST['customer_category'] ?? ''),
            trim($_POST['industry_sector'] ?? ''),
            trim($_POST['nature_of_business'] ?? ''),
            trim($_POST['registration_number'] ?? ''),
            trim($_POST['tin'] ?? ''),
            trim($_POST['vat_vrn'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['city_region'] ?? ''),
            trim($_POST['country'] ?? 'Tanzania'),
            $contractorId,
        ]);

    // Update contractor-specific KYC details
    $kyc = ensureContractorKyc($db, $contractorId);

    $kycTextFields = [
        'registered_name', 'trading_name', 'customer_category', 'industry_sector',
        'nature_of_business', 'registration_number', 'tin', 'vat_vrn',
        'address', 'city_region', 'country',
        'main_contact_name', 'main_contact_phone', 'main_contact_email',
        'ops_contact_name', 'ops_contact_phone', 'ops_contact_email',
        'tech_supervisor_name', 'tech_supervisor_phone', 'tech_supervisor_email',
        'escalation_contact_name', 'escalation_contact_phone', 'escalation_contact_email',
        'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        'bank_payment_terms', 'service_regions', 'review_notes',
    ];
    $kycCapFields = [
        'cap_ftth_install', 'cap_sme_install', 'cap_enterprise_install',
        'cap_site_survey', 'cap_maintenance', 'cap_remote_support',
    ];

    $sets = [];
    $params = [];
    foreach ($kycTextFields as $f) {
        if (!in_array($f, $kycCols)) continue;
        $sets[] = "$f = ?";
        $params[] = trim($_POST[$f] ?? '') ?: null;
    }
    foreach ($kycCapFields as $f) {
        if (!in_array($f, $kycCols)) continue;
        $sets[] = "$f = ?";
        $params[] = isset($_POST[$f]) ? 1 : 0;
    }
    if (in_array('status', $kycCols)) {
        $kycStatus = $_POST['kyc_status'] ?? $kyc['status'];
        if (!in_array($kycStatus, ['Draft', 'Submitted', 'Under Review', 'Approved', 'Rejected'])) {
            $kycStatus = $kyc['status'];
        }
        $sets[] = "status = ?";
        $params[] = $kycStatus;
    }

    if (!empty($sets)) {
        $params[] = $kyc['id'];
        $db->prepare("UPDATE partner_kyc_applications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    }

    auditLog("Updated contractor #$contractorId ({$_POST['name']}) status to '$status'", 'partners', $contractorId);
    setFlash('success', 'Contractor details and status updated successfully.');
    header('Location: ' . APP_URL . '/?page=contractors&action=detail&id=' . $contractorId);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete contractor (Admin / Management)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Admin or Management only.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    $contractorId = (int)($_POST['id'] ?? 0);
    if ($contractorId > 0) {
        $stmt = $db->prepare("SELECT name FROM partners WHERE id = ? AND kyc_type = 'Contractor'");
        $stmt->execute([$contractorId]);
        $cName = $stmt->fetchColumn();

        if ($cName) {
            $db->prepare("DELETE FROM partner_kyc_applications WHERE partner_id = ? AND kyc_type = 'Contractor'")->execute([$contractorId]);
            $db->prepare("DELETE FROM contractor_assignments WHERE contractor_partner_id = ?")->execute([$contractorId]);
            $db->prepare("DELETE FROM partners WHERE id = ? AND kyc_type = 'Contractor'")->execute([$contractorId]);
            auditLog("Deleted contractor $cName (ID: $contractorId)", 'contractors', $contractorId);
            setFlash('success', "Contractor <strong>" . e($cName) . "</strong> deleted successfully.");
        }
    }
    header('Location: ' . APP_URL . '/?page=contractors');
    exit;
}

// ------------------------------------------------------------------
// Contractor Detail
// ------------------------------------------------------------------
if ($action === 'detail') {
    $contractorId = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status, pka.review_notes,
        pka.main_contact_name, pka.main_contact_phone, pka.main_contact_email,
        pka.ops_contact_name, pka.ops_contact_phone, pka.ops_contact_email,
        pka.tech_supervisor_name, pka.tech_supervisor_phone, pka.tech_supervisor_email,
        pka.escalation_contact_name, pka.escalation_contact_phone, pka.escalation_contact_email,
        pka.bank_name, pka.bank_branch, pka.bank_account_name, pka.bank_account_number,
        pka.bank_payment_terms, pka.service_regions,
        pka.cap_ftth_install, pka.cap_sme_install, pka.cap_enterprise_install,
        pka.cap_site_survey, pka.cap_maintenance, pka.cap_remote_support
        FROM partners p
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
        WHERE p.id = ? AND p.kyc_type = 'Contractor'");
    $stmt->execute([$contractorId]);
    $contractor = $stmt->fetch();

    if (!$contractor) {
        http_response_code(404);
        echo '<p style="padding:40px">Contractor not found.</p>';
        exit;
    }

    $ucStmt = $db->prepare("SELECT u.id, u.full_name, u.username, u.email, u.mobile, u.is_active, u.role FROM users u WHERE u.partner_id = ? ORDER BY u.id");
    $ucStmt->execute([$contractorId]);
    $users = $ucStmt->fetchAll();

    $jcStmt = $db->prepare("SELECT ca.*, o.order_number, o.customer_name, o.customer_location, o.service_type
        FROM contractor_assignments ca JOIN orders o ON ca.order_id = o.id
        WHERE ca.contractor_partner_id = ? ORDER BY ca.assigned_at DESC LIMIT 25");
    $jcStmt->execute([$contractorId]);
    $assignments = $jcStmt->fetchAll();

    $stats = [
        'total'   => 0, 'active' => 0, 'submitted' => 0, 'completed' => 0, 'returned' => 0,
    ];
    foreach ($assignments as $a) {
        $stats['total']++;
        if (in_array($a['status'], ['Assigned', 'Accepted', 'In Progress'])) $stats['active']++;
        if ($a['status'] === 'Completed Submitted') $stats['submitted']++;
        if ($a['status'] === 'Completed') $stats['completed']++;
        if ($a['status'] === 'Returned') $stats['returned']++;
    }

    $pageTitle = 'Contractor: ' . $contractor['name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractors/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Edit form
// ------------------------------------------------------------------
if ($action === 'edit') {
    $contractorId = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT p.*, pka.id as kyc_id, pka.status as kyc_status, pka.review_notes,
        pka.registered_name, pka.trading_name, pka.customer_category, pka.industry_sector,
        pka.nature_of_business, pka.registration_number, pka.tin, pka.vat_vrn,
        pka.address, pka.city_region, pka.country,
        pka.main_contact_name, pka.main_contact_phone, pka.main_contact_email,
        pka.ops_contact_name, pka.ops_contact_phone, pka.ops_contact_email,
        pka.tech_supervisor_name, pka.tech_supervisor_phone, pka.tech_supervisor_email,
        pka.escalation_contact_name, pka.escalation_contact_phone, pka.escalation_contact_email,
        pka.bank_name, pka.bank_branch, pka.bank_account_name, pka.bank_account_number,
        pka.bank_payment_terms, pka.service_regions,
        pka.cap_ftth_install, pka.cap_sme_install, pka.cap_enterprise_install,
        pka.cap_site_survey, pka.cap_maintenance, pka.cap_remote_support
        FROM partners p
        LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
        WHERE p.id = ? AND p.kyc_type = 'Contractor'");
    $stmt->execute([$contractorId]);
    $contractor = $stmt->fetch();

    if (!$contractor) {
        setFlash('danger', 'Contractor not found.');
        header('Location: ' . APP_URL . '/?page=contractors');
        exit;
    }

    // Ensure a KYC row exists so the form always has an id to save against
    ensureContractorKyc($db, $contractorId);

    $pageTitle = 'Edit Contractor: ' . $contractor['name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/contractors/form.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List contractors
// ------------------------------------------------------------------
// Auto-sync: Ensure every contractor user & contractor record appears in Contractors Management
try {
    $db->exec("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE partner_type = 'Contractor' OR id IN (SELECT DISTINCT partner_id FROM users WHERE role LIKE '%Contractor%' AND partner_id IS NOT NULL AND partner_id > 0)");

    $unlinkedContractorUsers = $db->query("SELECT u.id, u.full_name, u.email FROM users u WHERE role LIKE '%Contractor%' AND (u.partner_id IS NULL OR u.partner_id = 0 OR u.partner_id NOT IN (SELECT id FROM partners WHERE kyc_type = 'Contractor' OR partner_type = 'Contractor'))")->fetchAll();

    foreach ($unlinkedContractorUsers as $cu) {
        $cName = trim($cu['full_name']);
        if (!$cName) continue;

        $cCheck = $db->prepare("SELECT id FROM partners WHERE name = ? LIMIT 1");
        $cCheck->execute([$cName]);
        $cId = $cCheck->fetchColumn();

        if (!$cId) {
            $ins = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, country) VALUES (?,?,'Contractor','Contractor','Active','Tanzania')");
            $ins->execute([$cName, $cName]);
            $cId = (int)$db->lastInsertId();
        } else {
            $db->prepare("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE id = ?")->execute([$cId]);
        }

        $db->prepare("UPDATE users SET partner_id = ? WHERE id = ?")->execute([$cId, $cu['id']]);
    }
} catch (Exception $e) {}

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = "(p.kyc_type = 'Contractor' OR p.partner_type = 'Contractor')";
$params = [];
if ($search !== '') {
    $where .= " AND (p.name LIKE ? OR p.trading_name LIKE ? OR p.registration_number LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if (in_array($statusFilter, ['Active', 'Inactive', 'Suspended'])) {
    $where .= " AND p.status = ?";
    $params[] = $statusFilter;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$totalStmt = $db->prepare("SELECT COUNT(*) FROM partners p WHERE $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT p.*, pka.status as kyc_status,
        (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id) as total_jobs,
        (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id AND ca.status IN ('Assigned','Accepted','In Progress')) as active_jobs,
        (SELECT COUNT(*) FROM contractor_assignments ca WHERE ca.contractor_partner_id = p.id AND ca.status = 'Completed') as completed_jobs,
        (SELECT COUNT(*) FROM users u WHERE u.partner_id = p.id AND u.role IN ('Contractor', 'Contractor User') AND u.is_active = 1) as user_count
    FROM partners p
    LEFT JOIN partner_kyc_applications pka ON pka.partner_id = p.id AND pka.kyc_type = 'Contractor'
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$contractors = $stmt->fetchAll();

$pageTitle = 'Contractors Management';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/contractors/list.php';
include APP_DIR . '/views/layout/footer.php';
