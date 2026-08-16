<?php
// ============================================================
// Neilos Partner Portal — Front Controller
// ============================================================

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR',  ROOT_DIR . '/app');

// Load core
require_once APP_DIR . '/config/database.php';
require_once APP_DIR . '/helpers/auth.php';
require_once APP_DIR . '/helpers/format.php';
require_once APP_DIR . '/helpers/audit.php';
require_once APP_DIR . '/helpers/sla.php';
require_once APP_DIR . '/helpers/icons.php';
require_once APP_DIR . '/helpers/notifications.php';
require_once APP_DIR . '/helpers/workflow.php';

// Start session
startSecureSession();

// Route
$page = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['page'] ?? 'dashboard'));

// Public routes (no login required)
$publicRoutes = ['login', 'logout'];

if (!in_array($page, $publicRoutes)) {
    requireLogin();
    checkUatAutoAccept(getDB());
    try {
        ensurePermissionsSchema(getDB());
        getDB()->exec("UPDATE users SET email = 'comfortmnyinga@gmail.com' WHERE role = 'BSA'");
        getDB()->exec("UPDATE users SET role = 'Partner' WHERE (role IS NULL OR role = '' OR role = 'Partner User') AND partner_id IN (SELECT id FROM partners WHERE kyc_type = 'Partner' OR partner_type IN ('ISP','Reseller'))");
        getDB()->exec("UPDATE users SET role = 'Partner' WHERE id IN (8, 9)");
        getDB()->exec("UPDATE users SET role = 'Contractor' WHERE (role IS NULL OR role = '' OR role = 'Contractor User') AND partner_id IN (SELECT id FROM partners WHERE kyc_type = 'Contractor' OR partner_type = 'Contractor')");
    } catch (Exception $e) {}

    // Backend Route Security Guard: Enforce module-level permission
    $routePermissions = [
        'dashboard'     => 'dashboard.view',
        'new_order'     => 'orders.create',
        'orders'        => 'orders.view',
        'order_detail'  => 'orders.view',
        'generate_sof'  => 'orders.view',
        'contractor'    => 'contractors.view',
        'kyc'           => 'kyc.view',
        'sla_tracking'  => 'sla.view',
        'reports'       => 'reports.view',
        'partners'      => 'partners.view',
        'contractors'   => 'contractors.manage',
        'users'         => 'users.view',
        'activity_logs' => 'activity_logs.view',
    ];

    if (isset($routePermissions[$page])) {
        requirePermission($routePermissions[$page]);
    }
}

// Dispatch
$controllerMap = [
    // Auth
    'login'           => 'auth',
    'logout'          => 'auth',
    'change_password' => 'auth',
    'otp_verify'      => 'auth',
    // Core Workflow & Spec Modules
    'dashboard'       => 'dashboard',
    'orders'          => 'orders',
    'new_order'       => 'orders',
    'order_detail'    => 'orders',
    'generate_sof'    => 'orders',
    'contractor'      => 'contractor',
    'kyc'             => 'kyc',
    'sla_tracking'    => 'sla_tracking',
    'reports'         => 'reports',
    'profile'         => 'profile',
    'download'        => 'download',
    'sse'             => 'sse',
    // Administration Module
    'partners'        => 'partners',
    'contractors'     => 'contractors',
    'users'           => 'users',
    'activity_logs'   => 'activity_logs',
    'api_account_info'=> 'api_account_info',
    'settings'        => 'settings',
    'ftth_bulk'       => 'ftth_bulk',
    'price_book'      => 'price_book',
];

$controller = $controllerMap[$page] ?? 'dashboard';
$controllerFile = APP_DIR . '/controllers/' . $controller . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
} else {
    // 404
    http_response_code(404);
    require_once APP_DIR . '/views/errors/404.php';
}
