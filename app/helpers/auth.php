<?php
// ============================================================
// Neilos Partner Portal — Auth Helper
// ============================================================

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
}

function requireLogin(): void {
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/?page=login');
        exit;
    }
    // Check session freshness
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        header('Location: ' . APP_URL . '/?page=login&reason=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();

    // Enforce first-login flow
    $page = $_GET['page'] ?? 'dashboard';
    if (!empty($_SESSION['is_first_login']) && !in_array($page, ['change_password', 'logout'])) {
        header('Location: ' . APP_URL . '/?page=change_password');
        exit;
    }
    // Enforce OTP
    if (empty($_SESSION['otp_verified']) && !in_array($page, ['otp_verify', 'change_password', 'logout'])) {
        header('Location: ' . APP_URL . '/?page=otp_verify');
        exit;
    }
}

function requireRole(array $allowedRoles): void {
    requireLogin();
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $allowedRoles) && $role !== 'System Admin') {
        http_response_code(403);
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

function currentUser(): array {
    return [
        'id'              => $_SESSION['user_id']         ?? 0,
        'full_name'       => $_SESSION['full_name']       ?? '',
        'username'        => $_SESSION['username']        ?? '',
        'email'           => $_SESSION['email']           ?? '',
        'role'            => $_SESSION['user_role']       ?? '',
        'partner_id'      => $_SESSION['partner_id']      ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null,
    ];
}

function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'System Admin';
}

function isPartnerUser(): bool {
    return ($_SESSION['user_role'] ?? '') === 'Partner User';
}

function hasRole(string ...$roles): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'System Admin' || in_array($role, $roles);
}

/**
 * Returns a SQL snippet to restrict queries to the current partner's data.
 * Admin/internal roles: no restriction (returns '1=1').
 * Partner User: restricts to their partner_id.
 *
 * @param string $alias Table alias prefix, e.g. 'o' -> 'o.partner_id = ?'
 * @return array ['condition' => string, 'params' => array]
 */
function partnerWhere(string $alias = ''): array {
    if (isPartnerUser()) {
        $col = $alias ? "$alias.partner_id" : 'partner_id';
        return [
            'condition' => "$col = ?",
            'params'    => [(int)($_SESSION['partner_id'] ?? 0)],
        ];
    }
    return ['condition' => '1=1', 'params' => []];
}

function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['full_name']    = $user['full_name'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['user_role']    = $user['role'];
    $_SESSION['partner_id']   = $user['partner_id'];
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
    $_SESSION['is_first_login'] = (bool)$user['is_first_login'];
    $_SESSION['otp_verified'] = (bool)$user['otp_verified'];
    $_SESSION['last_activity'] = time();
}

function logoutUser(): void {
    startSecureSession();
    session_unset();
    session_destroy();
}

function auditLog(string $action, string $module = '', int $recordId = 0, string $old = '', string $new = ''): void {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, module, record_id, old_value, new_value, ip_address) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $module,
            $recordId ?: null,
            $old ?: null,
            $new ?: null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        // Non-fatal: don't crash on audit failure
    }
}
