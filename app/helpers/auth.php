<?php
// ============================================================
// Neilos Partner Portal — Auth Helper
// ============================================================

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');

        $currentSavePath = session_save_path();
        if (!$currentSavePath || !is_dir($currentSavePath) || !is_writable($currentSavePath)) {
            $fallbackDir = sys_get_temp_dir();
            if (is_dir($fallbackDir) && is_writable($fallbackDir)) {
                session_save_path($fallbackDir);
            }
        }

        @session_start();

        if (session_status() === PHP_SESSION_NONE) {
            $fallbackDir = sys_get_temp_dir();
            session_save_path($fallbackDir);
            @session_start();
        }
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

function ensurePermissionsSchema(PDO $db): void {
    static $done = false;
    if ($done) return;
    try {
        $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('permissions', $cols)) {
            $db->exec("ALTER TABLE users ADD COLUMN permissions TEXT NULL");
        }
        $done = true;
    } catch (Exception $e) {}
}

function currentUser(): array {
    return [
        'id'              => $_SESSION['user_id']         ?? 0,
        'full_name'       => $_SESSION['full_name']       ?? '',
        'username'        => $_SESSION['username']        ?? '',
        'email'           => $_SESSION['email']           ?? '',
        'role'            => $_SESSION['user_role']       ?? '',
        'partner_id'      => $_SESSION['partner_id']      ?? null,
        'permissions'     => $_SESSION['permissions']     ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null,
    ];
}

function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'System Admin';
}

function isPartnerUser(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'Partner' || $role === 'Partner User';
}

function isContractorUser(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'Contractor' || $role === 'Contractor User';
}

function isManagement(): bool {
    return ($_SESSION['user_role'] ?? '') === 'Management';
}

function isProjectManager(): bool {
    return ($_SESSION['user_role'] ?? '') === 'Project Manager';
}

function isFinance(): bool {
    return ($_SESSION['user_role'] ?? '') === 'Billing';
}

function isExternalUser(): bool {
    return isPartnerUser() || isContractorUser();
}

function hasRole(string ...$roles): bool {
    $role = $_SESSION['user_role'] ?? '';
    return $role === 'System Admin' || in_array($role, $roles);
}

function partnerWhere(string $alias = ''): array {
    if (isPartnerUser() || isContractorUser()) {
        $col = $alias ? "$alias.partner_id" : 'partner_id';
        return [
            'condition' => "$col = ?",
            'params'    => [(int)($_SESSION['partner_id'] ?? 0)],
        ];
    }
    return ['condition' => '1=1', 'params' => []];
}

/**
 * All available permission keys in the application.
 */
function getAllPermissionCatalog(): array {
    return [
        'Dashboard' => [
            'dashboard.view'     => 'View Dashboard',
        ],
        'Order Lifecycle' => [
            'orders.create'      => 'Create New Service Order',
            'orders.view'        => 'View Order Tracking & Details',
            'orders.edit'        => 'Edit Service Orders',
            'orders.approve'     => 'Approve / Commercial Management',
            'orders.delete'      => 'Delete Service Orders',
        ],
        'Field & Vendor Delivery' => [
            'contractors.view'   => 'View Contractors / Jobs',
            'contractors.assign' => 'Assign Contractor Jobs',
        ],
        'Compliance & SLAs' => [
            'kyc.view'           => 'View KYC Applications',
            'kyc.create'         => 'Create / Edit Draft KYC',
            'kyc.approve'        => 'Approve KYC Applications',
            'kyc.reject'         => 'Reject KYC Applications',
            'sla.view'           => 'View SLA Tracking',
        ],
        'Administration' => [
            'partners.view'      => 'View Partner Management',
            'partners.manage'    => 'Manage Partners (Create/Edit/Delete)',
            'contractors.manage' => 'Manage Contractors Profile',
            'users.view'         => 'View User Management',
            'users.manage'       => 'Manage Users & Configure Permissions',
            'activity_logs.view' => 'View Activity Logs',
        ],
    ];
}

/**
 * Default permission mappings by Role.
 */
function getRoleDefaultPermissions(string $role): array {
    return match ($role) {
        'System Admin', 'Admin' => [
            'dashboard.view', 'orders.create', 'orders.view', 'orders.edit', 'orders.approve', 'orders.delete',
            'contractors.view', 'contractors.assign',
            'kyc.view', 'kyc.create', 'kyc.approve', 'kyc.reject', 'sla.view',
            'partners.view', 'partners.manage', 'contractors.manage', 'users.view', 'users.manage', 'activity_logs.view'
        ],
        'Management' => [
            'dashboard.view', 'orders.create', 'orders.view', 'orders.edit', 'orders.approve',
            'contractors.view', 'contractors.assign',
            'kyc.view', 'kyc.create', 'kyc.approve', 'kyc.reject', 'sla.view',
            'partners.view', 'partners.manage', 'contractors.manage', 'users.view', 'users.manage', 'activity_logs.view'
        ],
        'KAM' => [
            'dashboard.view', 'orders.create', 'orders.view', 'orders.edit',
            'kyc.view', 'kyc.create', 'sla.view'
        ],
        'BSA' => [
            'dashboard.view', 'orders.view', 'orders.edit',
            'kyc.view', 'kyc.create', 'sla.view'
        ],
        'Project Manager' => [
            'dashboard.view', 'orders.view', 'orders.edit',
            'contractors.view', 'contractors.assign',
            'kyc.view', 'kyc.create', 'sla.view'
        ],
        'Partner', 'Partner User' => [
            'dashboard.view', 'orders.create', 'orders.view',
            'kyc.view', 'kyc.approve', 'kyc.reject', 'sla.view'
        ],
        'Contractor', 'Contractor User' => [
            'dashboard.view', 'contractors.view',
            'kyc.view', 'kyc.approve', 'kyc.reject'
        ],
        'Billing', 'Finance' => [
            'dashboard.view', 'orders.view', 'orders.approve', 'sla.view', 'kyc.view'
        ],
        default => [
            'dashboard.view', 'orders.view'
        ]
    };
}

/**
 * Get resolved array of permission keys for a user.
 * Prefers explicit user permissions JSON, falling back to role defaults.
 */
function getUserPermissions(?array $user = null): array {
    if (!$user) {
        $user = currentUser();
    }
    if (!$user || empty($user['id'])) return [];

    if (!empty($user['permissions'])) {
        $decoded = is_array($user['permissions']) ? $user['permissions'] : json_decode($user['permissions'], true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return getRoleDefaultPermissions($user['role'] ?? 'User');
}

/**
 * Check if the current user (or specified user) has a given permission key.
 */
function hasPermission(string $permissionKey, ?array $user = null): bool {
    if ($permissionKey === 'reports.view' || str_starts_with($permissionKey, 'reports.')) {
        return false;
    }
    if (!$user) {
        $user = currentUser();
    }
    if (!$user || empty($user['id'])) return false;

    $userRole = $user['role'] ?? '';
    if (($userRole === 'System Admin' || $userRole === 'Admin') && empty($user['permissions'])) {
        return true;
    }

    $perms = getUserPermissions($user);
    if (in_array('*', $perms, true)) {
        return true;
    }

    return in_array($permissionKey, $perms, true);
}

/**
 * Enforce permission on backend server-side.
 */
function requirePermission(string $permissionKey): void {
    requireLogin();
    if (!hasPermission($permissionKey)) {
        http_response_code(403);
        $pageTitle = 'Access Denied';
        include __DIR__ . '/../views/layout/header.php';
        ?>
        <div class="container" style="max-width:620px;margin:60px auto">
          <div class="card" style="padding:40px;text-align:center;border-top:4px solid var(--danger)">
            <div style="font-size:3rem;margin-bottom:16px">🚫</div>
            <h2 style="color:var(--danger);font-weight:700;margin-bottom:12px;font-size:1.4rem">Access Denied</h2>
            <p style="color:var(--text-secondary);font-size:0.95rem;line-height:1.6;margin-bottom:24px">
              You do not have permission to access this module or action.<br>
              <span style="font-size:0.85rem;color:var(--text-muted)">(Required Permission: <code><?= e($permissionKey) ?></code>)</span>
            </p>
            <div style="display:flex;gap:12px;justify-content:center">
              <a href="<?= APP_URL ?>/?page=dashboard" class="btn btn-primary">Go to Dashboard</a>
              <button onclick="history.back()" class="btn btn-secondary">Go Back</button>
            </div>
          </div>
        </div>
        <?php
        include __DIR__ . '/../views/layout/footer.php';
        exit;
    }
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
    $_SESSION['permissions']  = $user['permissions'] ?? null;
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
