<?php
// ============================================================
// Partners Controller — Manage partner organizations
// ============================================================
requireLogin();

if (!isAdmin()) {
    setFlash('danger', 'Access denied. Admin only.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// ------------------------------------------------------------------
// POST: Create partner
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $stmt = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, status, address, city_region, country, registration_number, tin) VALUES (?,?,?,?,?,?,?,?,?)");
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

    $stmt = $db->prepare("UPDATE partners SET name=?, trading_name=?, partner_type=?, status=?, address=?, city_region=?, country=?, registration_number=?, tin=? WHERE id=?");
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
        $partnerId,
    ]);

    auditLog("Updated partner {$_POST['name']}", 'partners', $partnerId);
    setFlash('success', 'Partner updated successfully.');
    header('Location: ' . APP_URL . '/?page=partners&action=detail&id=' . $partnerId);
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

$totalStmt = $db->query("SELECT COUNT(*) FROM partners");
$total = (int)$totalStmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->query("SELECT * FROM partners ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$partners = $stmt->fetchAll();

$pageTitle = 'Partner Management';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/partners/list.php';
include APP_DIR . '/views/layout/footer.php';
