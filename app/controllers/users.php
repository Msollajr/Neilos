<?php
// ============================================================
// Users Controller — Manage portal users
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

// Auto-heal blank / legacy partner and contractor roles in database based on partner table relationships
try {
    $db->exec("UPDATE users SET role = 'Partner' WHERE (role IS NULL OR role = '' OR role = 'Partner User') AND partner_id IN (SELECT id FROM partners WHERE kyc_type = 'Partner' OR partner_type IN ('ISP','Reseller'))");
    $db->exec("UPDATE users SET role = 'Contractor' WHERE (role IS NULL OR role = '' OR role = 'Contractor User') AND partner_id IN (SELECT id FROM partners WHERE kyc_type = 'Contractor' OR partner_type = 'Contractor')");
} catch (Exception $e) {}

$roles = ['System Admin', 'BSA', 'KAM', 'Management', 'Project Manager', 'Contractor', 'Billing', 'Partner'];

// ------------------------------------------------------------------
// POST: Create user
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $role     = $_POST['role'] ?? 'Partner';

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

    $partnerId = null;

    $db->beginTransaction();
    try {
        if ($role === 'Partner') {
            $pCheck = $db->prepare("SELECT id FROM partners WHERE (name = ? OR trading_name = ?) AND (kyc_type = 'Partner' OR partner_type IN ('ISP','Reseller')) LIMIT 1");
            $pCheck->execute([$fullName, $fullName]);
            $existingPId = $pCheck->fetchColumn();

            if ($existingPId) {
                $partnerId = (int)$existingPId;
            } else {
                $pInsert = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, country) VALUES (?,?,'ISP','Partner','Active','Tanzania')");
                $pInsert->execute([$fullName, $fullName]);
                $partnerId = (int)$db->lastInsertId();
                auditLog("Auto-created partner record '$fullName' for Partner user", 'partners', $partnerId);
            }
        } elseif ($role === 'Contractor') {
            $cCheck = $db->prepare("SELECT id FROM partners WHERE (name = ? OR trading_name = ?) AND (kyc_type = 'Contractor' OR partner_type = 'Contractor') LIMIT 1");
            $cCheck->execute([$fullName, $fullName]);
            $existingCId = $cCheck->fetchColumn();

            if ($existingCId) {
                $partnerId = (int)$existingCId;
                $db->prepare("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE id = ?")->execute([$partnerId]);
            } else {
                $cInsert = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, country) VALUES (?,?,'Contractor','Contractor','Active','Tanzania')");
                $cInsert->execute([$fullName, $fullName]);
                $partnerId = (int)$db->lastInsertId();
                auditLog("Auto-created contractor record '$fullName' for Contractor user", 'partners', $partnerId);
            }
        }

        $permissionsJson = null;
        if (!empty($_POST['has_custom_permissions']) && !empty($_POST['toggle_custom_perms'])) {
            $rawPerms = $_POST['permissions'] ?? [];
            $permissionsJson = json_encode(array_values($rawPerms));
        }

        $hash = password_hash('Chang3Me!', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password, mobile, role, partner_id, permissions, is_first_login, created_by) VALUES (?,?,?,?,?,?,?,?,1,?)");
        $stmt->execute([$fullName, $username, $email, $hash, $mobile, $role, $partnerId, $permissionsJson, $user['id']]);
        $userId = $db->lastInsertId();

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Failed to create user: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/?page=users&action=create');
        exit;
    }

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
    $role     = $_POST['role'] ?? 'Partner';

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

    $db->beginTransaction();
    try {
        $uStmt = $db->prepare("SELECT role, partner_id FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $currUser = $uStmt->fetch();
        $partnerId = $currUser['partner_id'] ?? null;

        if ($role === 'Partner') {
            if ($partnerId) {
                $db->prepare("UPDATE partners SET name = COALESCE(NULLIF(?, ''), name) WHERE id = ?")->execute([$fullName, $partnerId]);
            } else {
                $pCheck = $db->prepare("SELECT id FROM partners WHERE (name = ? OR trading_name = ?) AND (kyc_type = 'Partner' OR partner_type IN ('ISP','Reseller')) LIMIT 1");
                $pCheck->execute([$fullName, $fullName]);
                $existingPId = $pCheck->fetchColumn();

                if ($existingPId) {
                    $partnerId = (int)$existingPId;
                } else {
                    $pInsert = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, country) VALUES (?,?,'ISP','Partner','Active','Tanzania')");
                    $pInsert->execute([$fullName, $fullName]);
                    $partnerId = (int)$db->lastInsertId();
                }
            }
        } elseif ($role === 'Contractor') {
            if ($partnerId) {
                $db->prepare("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE id = ?")->execute([$partnerId]);
            } else {
                $cCheck = $db->prepare("SELECT id FROM partners WHERE (name = ? OR trading_name = ?) AND (kyc_type = 'Contractor' OR partner_type = 'Contractor') LIMIT 1");
                $cCheck->execute([$fullName, $fullName]);
                $existingCId = $cCheck->fetchColumn();

                if ($existingCId) {
                    $partnerId = (int)$existingCId;
                    $db->prepare("UPDATE partners SET kyc_type = 'Contractor', partner_type = 'Contractor' WHERE id = ?")->execute([$partnerId]);
                } else {
                    $cInsert = $db->prepare("INSERT INTO partners (name, trading_name, partner_type, kyc_type, status, country) VALUES (?,?,'Contractor','Contractor','Active','Tanzania')");
                    $cInsert->execute([$fullName, $fullName]);
                    $partnerId = (int)$db->lastInsertId();
                }
            }
        } else {
            $partnerId = null;
        }

        $permissionsJson = null;
        if (!empty($_POST['has_custom_permissions']) && !empty($_POST['toggle_custom_perms'])) {
            $rawPerms = $_POST['permissions'] ?? [];
            $permissionsJson = json_encode(array_values($rawPerms));
        }

        $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, mobile=?, role=?, partner_id=?, permissions=? WHERE id=?");
        $stmt->execute([$fullName, $email, $mobile, $role, $partnerId, $permissionsJson, $userId]);

        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['permissions'] = $permissionsJson;
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Failed to update user: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/?page=users&action=edit&id=' . $userId);
        exit;
    }

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
// POST: Delete user (Admin / Management)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrf();
    if (!isAdmin() && !hasRole('Management')) {
        setFlash('danger', 'Access denied. Admin or Management only.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    $targetId = (int)($_POST['id'] ?? 0);
    if ($targetId === $user['id']) {
        setFlash('danger', 'You cannot delete your own account.');
        header('Location: ' . APP_URL . '/?page=users');
        exit;
    }

    if ($targetId > 0) {
        $stmt = $db->prepare("SELECT full_name, username FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();
        if ($target) {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            auditLog("Deleted user {$target['full_name']} ({$target['username']})", 'users', $targetId);
            setFlash('success', "User <strong>" . e($target['full_name']) . "</strong> deleted successfully.");
        }
    }
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

if ($filterRole) {
    if ($filterRole === 'Partner') {
        $where .= " AND (u.role = 'Partner' OR u.role = 'Partner User' OR (p.id IS NOT NULL AND (p.kyc_type = 'Partner' OR p.partner_type IN ('ISP','Reseller'))))";
    } elseif ($filterRole === 'Contractor') {
        $where .= " AND (u.role = 'Contractor' OR u.role = 'Contractor User' OR (p.id IS NOT NULL AND (p.kyc_type = 'Contractor' OR p.partner_type = 'Contractor')))";
    } else {
        $where .= " AND u.role = ?";
        $params[] = $filterRole;
    }
}
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
