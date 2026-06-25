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
require_once APP_DIR . '/helpers/sla.php';
require_once APP_DIR . '/helpers/icons.php';
require_once APP_DIR . '/helpers/notifications.php';

// Start session
startSecureSession();

// Route
$page = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['page'] ?? 'dashboard'));

// Public routes (no login required)
$publicRoutes = ['login', 'logout'];

if (!in_array($page, $publicRoutes)) {
    requireLogin();
}

// Dispatch
$controllerMap = [
    // Auth
    'login'           => 'auth',
    'logout'          => 'auth',
    'change_password' => 'auth',
    'otp_verify'      => 'auth',
    // Main modules
    'dashboard'       => 'dashboard',
    'partners'        => 'partners',
    'users'           => 'users',
    'orders'          => 'orders',
    'new_order'       => 'orders',
    'order_detail'    => 'orders',
    'bulk_upload'     => 'bulk_upload',
    'sla_tracking'    => 'sla_tracking',
    'kyc'             => 'kyc',
    'coverage'        => 'coverage',
    'tickets'         => 'tickets',
    'ticket_detail'   => 'tickets',
    'active_services' => 'active_services',
    'projects'        => 'projects',
    'reports'         => 'reports',
    'profile'         => 'profile',
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
