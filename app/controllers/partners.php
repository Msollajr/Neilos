<?php
// ============================================================
// Partners Controller — Manage partner organizations
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

// Ensure kam_id and assigned_kam_name columns exist
try {
    $pCols = $db->query("SHOW COLUMNS FROM partners")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kam_id', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN kam_id INT NULL");
    }
    if (!in_array('assigned_kam_name', $pCols)) {
        $db->exec("ALTER TABLE partners ADD COLUMN assigned_kam_name VARCHAR(100) NULL");
    }
} catch (Exception $e) {}

// ------------------------------------------------------------------
// POST: Create partner
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $kamId = (int)($_POST['kam_id'] ?? 0) ?: null;
    $kamName = null;
    if ($kamId) {
        $kStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $kStmt->execute([$kamId]);
        $kamName = $kStmt->fetchColumn() ?: null;
    }

    $stmt = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, status, address, city_region, country, registration_number, tin, kam_id, assigned_kam_name) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['name'] ?? '',
        $_POST['trading_name'] ?? '',
        $_POST['partner_type'] ?? 'ISP',
        $_POST['status'] ?? 'Active',
        $_POST['address'] ?? '',
        $_POST['city_region'] ?? '',
        $_POST['country'] ?? 'Tanzania',
        $_POST['registration_number'] ?? '',
        $_POST['tin'] ?? '',
        $kamId,
        $kamName
    ]);
    $partnerId = $db->lastInsertId();

    auditLog("Created partner {$_POST['name']}", 'partners', $partnerId);
    setFlash('success', "Partner <strong>" . e($_POST['name']) . "</strong> created successfully.");
    header('Location: ' . APP_URL . '/?page=partners&action=detail&id=' . $partnerId);
    exit;
}

// ------------------------------------------------------------------
// POST: Edit partner
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    verifyCsrf();

    $partnerId = (int)($_POST['id'] ?? 0);
    if (!$partnerId) {
        setFlash('danger', 'Invalid partner ID.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    $kamId = (int)($_POST['kam_id'] ?? 0) ?: null;
    $kamName = null;
    if ($kamId) {
        $kStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $kStmt->execute([$kamId]);
        $kamName = $kStmt->fetchColumn() ?: null;
    }

    $stmt = $db->prepare("UPDATE partners SET name=?, trading_name=?, partner_type=?, status=?, address=?, city_region=?, country=?, registration_number=?, tin=?, kam_id=?, assigned_kam_name=? WHERE id=?");
    $stmt->execute([
        $_POST['name'] ?? '',
        $_POST['trading_name'] ?? '',
        $_POST['partner_type'] ?? 'ISP',
        $_POST['status'] ?? 'Active',
        $_POST['address'] ?? '',
        $_POST['city_region'] ?? '',
        $_POST['country'] ?? 'Tanzania',
        $_POST['registration_number'] ?? '',
        $_POST['tin'] ?? '',
        $kamId,
        $kamName,
        $partnerId,
    ]);

    auditLog("Updated partner {$_POST['name']}", 'partners', $partnerId);
    setFlash('success', 'Partner updated successfully.');
    header('Location: ' . APP_URL . '/?page=partners&action=detail&id=' . $partnerId);
    exit;
}

// ------------------------------------------------------------------
// POST: Delete partner (Admin / Management)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Admin or Management only.');
        header('Location: ' . APP_URL . '/?page=partners');
        exit;
    }

    $partnerId = (int)($_POST['id'] ?? 0);
    if ($partnerId > 0) {
        $stmt = $db->prepare("SELECT name FROM partners WHERE id = ?");
        $stmt->execute([$partnerId]);
        $pName = $stmt->fetchColumn();

        if ($pName) {
            $db->prepare("DELETE FROM partners WHERE id = ?")->execute([$partnerId]);
            $db->prepare("UPDATE users SET partner_id = NULL WHERE partner_id = ?")->execute([$partnerId]);
            auditLog("Deleted partner $pName (ID: $partnerId)", 'partners', $partnerId);
            setFlash('success', "Partner <strong>" . e($pName) . "</strong> deleted successfully.");
        }
    }
    header('Location: ' . APP_URL . '/?page=partners');
    exit;
}

// ------------------------------------------------------------------
// Partner Detail
// ------------------------------------------------------------------
if ($action === 'detail') {
    $partnerId = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$partnerId]);
    $partner = $stmt->fetch();
    if (!$partner) {
        http_response_code(404);
        echo '<p style="padding:40px">Partner not found.</p>';
        exit;
    }

    $ucStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE partner_id = ?");
    $ucStmt->execute([$partnerId]);
    $userCount = (int)$ucStmt->fetchColumn();
    $ocStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE partner_id = ?");
    $ocStmt->execute([$partnerId]);
    $orderCount = (int)$ocStmt->fetchColumn();

    $pageTitle = 'Partner: ' . $partner['name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/partners/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Create / Edit form
// ------------------------------------------------------------------
if ($action === 'create' || $action === 'edit') {
    $partner = null;
    $partnerId = (int)($_GET['id'] ?? 0);
    if ($action === 'edit' && $partnerId) {
        $stmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
        $stmt->execute([$partnerId]);
        $partner = $stmt->fetch();
        if (!$partner) {
            setFlash('danger', 'Partner not found.');
            header('Location: ' . APP_URL . '/?page=partners');
            exit;
        }
    }

    $kamList = $db->query("SELECT id, full_name FROM users WHERE role = 'KAM' AND is_active = 1 ORDER BY full_name")->fetchAll();

    $pageTitle = $action === 'create' ? 'New Partner' : 'Edit Partner';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/partners/form.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List partners
// ------------------------------------------------------------------
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "(kyc_type IS NULL OR kyc_type != 'Contractor') AND (partner_type IS NULL OR partner_type != 'Contractor')";

$totalStmt = $db->query("SELECT COUNT(*) FROM partners WHERE $where");
$total = (int)$totalStmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->query("SELECT * FROM partners WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$partners = $stmt->fetchAll();

$pageTitle = 'Partner Management';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/partners/list.php';
include APP_DIR . '/views/layout/footer.php';




