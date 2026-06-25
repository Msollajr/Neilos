<?php
// ============================================================
// Users Controller — Manage portal users
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

$roles = ['System Admin','KAM','BSA','Commercial','Director','NOC Support','NOC Core','NOC Level 3','Billing','Project Team','Engineering Coordinator','Partner User'];

// ------------------------------------------------------------------
// POST: Create user
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $role     = $_POST['role'] ?? 'Partner User';
    $partnerId = (int)($_POST['partner_id'] ?? 0);

    if (!$fullName || !$username || !$email || !$mobile) {
        setFlash('danger', 'All required fields must be filled.');
        header('Location: ' . APP_URL . '/?page=users&action=create');
        exit;
    }

    if (!in_array($role, $roles)) {
        setFlash('danger', 'Invalid role selected.');
        header('Location: ' . APP_URL . '/?page=users&action=create');
        exit;
    }

    // Check unique username / email
    $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->fetch()) {
        setFlash('danger', 'Username or email already exists.');
        header('Location: ' . APP_URL . '/?page=users&action=create');
        exit;
    }

    $hash = password_hash('Chang3Me!', PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password, mobile, role, partner_id, is_first_login, created_by) VALUES (?,?,?,?,?,?,?,1,?)");
    $stmt->execute([$fullName, $username, $email, $hash, $mobile, $role, $partnerId ?: null, $user['id']]);
    $userId = $db->lastInsertId();

    auditLog("Created user $fullName ($username)", 'users', $userId);
    setFlash('success', "User <strong>" . e($fullName) . "</strong> created successfully. Default password: <strong>Chang3Me!</strong>");
    header('Location: ' . APP_URL . '/?page=users&action=detail&id=' . $userId);
    exit;
}

// ------------------------------------------------------------------
// POST: Edit user
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    verifyCsrf();

    $userId   = (int)($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $role     = $_POST['role'] ?? 'Partner User';
    $partnerId = (int)($_POST['partner_id'] ?? 0);

    if (!$userId || !$fullName || !$email || !$mobile) {
        setFlash('danger', 'Required fields missing.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    if (!in_array($role, $roles)) {
        setFlash('danger', 'Invalid role selected.');
        header('Location: ' . APP_URL . '/?page=users&action=edit&id=' . $userId);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, mobile=?, role=?, partner_id=? WHERE id=?");
    $stmt->execute([$fullName, $email, $mobile, $role, $partnerId ?: null, $userId]);

    auditLog("Updated user $fullName", 'users', $userId);
    setFlash('success', 'User updated successfully.');
    header('Location: ' . APP_URL . '/?page=users&action=detail&id=' . $userId);
    exit;
}

// ------------------------------------------------------------------
// POST: Toggle active status
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle_status') {
    verifyCsrf();

    $userId = (int)($_POST['id'] ?? 0);
    if (!$userId) {
        setFlash('danger', 'Invalid user ID.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    $stmt = $db->prepare("SELECT is_active, full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if (!$target) {
        setFlash('danger', 'User not found.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    $newStatus = $target['is_active'] ? 0 : 1;
    $db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $userId]);

    $actionLabel = $newStatus ? 'activated' : 'deactivated';
    auditLog("{$actionLabel} user {$target['full_name']}", 'users', $userId);
    setFlash('success', "User <strong>" . e($target['full_name']) . "</strong> {$actionLabel}.");
    header('Location: ' . APP_URL . '/?page=users');
    exit;
}

// ------------------------------------------------------------------
// POST: Reset password
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset_password') {
    verifyCsrf();

    $userId = (int)($_POST['id'] ?? 0);
    if (!$userId) {
        setFlash('danger', 'Invalid user ID.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if (!$target) {
        setFlash('danger', 'User not found.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    $hash = password_hash('Chang3Me!', PASSWORD_BCRYPT);
    $db->prepare("UPDATE users SET password = ?, is_first_login = 1, otp_verified = 0 WHERE id = ?")->execute([$hash, $userId]);

    auditLog("Reset password for {$target['full_name']}", 'users', $userId);
    setFlash('success', "Password reset for <strong>" . e($target['full_name']) . "</strong>. Default password: <strong>Chang3Me!</strong>");
    header('Location: ' . APP_URL . '/?page=users');
    exit;
}

// ------------------------------------------------------------------
// User Detail
// ------------------------------------------------------------------
if ($action === 'detail') {
    $userId = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT u.*, p.name as partner_name, u2.full_name as created_by_name FROM users u LEFT JOIN partners p ON u.partner_id = p.id LEFT JOIN users u2 ON u.created_by = u2.id WHERE u.id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    if (!$profile) {
        http_response_code(404);
        echo '<p style="padding:40px">User not found.</p>';
        exit;
    }

    // Audit log entries for this user
    $auditStmt = $db->prepare("SELECT * FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $auditStmt->execute([$userId]);
    $auditLogs = $auditStmt->fetchAll();

    $pageTitle = 'User: ' . $profile['full_name'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/users/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// Create / Edit form
// ------------------------------------------------------------------
if ($action === 'create' || $action === 'edit') {
    $profile = null;
    $userId = (int)($_GET['id'] ?? 0);
    if ($action === 'edit' && $userId) {
        $stmt = $db->prepare("SELECT u.*, p.name as partner_name FROM users u LEFT JOIN partners p ON u.partner_id = p.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        if (!$profile) {
            setFlash('danger', 'User not found.');
            header('Location: ' . APP_URL . '/?page=users');
            exit;
        }
    }

    // Partner list for dropdown
    $partners = $db->query("SELECT id, name FROM partners WHERE status = 'Active' ORDER BY name")->fetchAll();

    $pageTitle = $action === 'create' ? 'New User' : 'Edit User';
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/users/form.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List users
// ------------------------------------------------------------------
$where  = 'WHERE 1=1';
$params = [];

$filterRole   = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['q'] ?? '';

if ($filterRole)   { $where .= " AND u.role = ?";        $params[] = $filterRole; }
if ($filterStatus !== '') { $where .= " AND u.is_active = ?"; $params[] = (int)$filterStatus; }
if ($filterSearch) { $where .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)"; $pS = "%$filterSearch%"; $params[] = $pS; $params[] = $pS; $params[] = $pS; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM users u $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT u.*, p.name as partner_name FROM users u LEFT JOIN partners p ON u.partner_id = p.id $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'User Management';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/users/list.php';
include APP_DIR . '/views/layout/footer.php';
