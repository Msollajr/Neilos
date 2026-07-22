<?php
// ============================================================
// Activity Logs Controller — Admin Audit Trail & User Actions
// ============================================================
requireLogin();

if (!isAdmin()) {
    setFlash('danger', 'Access denied. System Administrator permission required.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

$db = getDB();
$currentUser = currentUser();

// ------------------------------------------------------------------
// Fetch dropdown options for filters
// ------------------------------------------------------------------
$usersList = $db->query("SELECT id, full_name, username, role FROM users ORDER BY full_name ASC")->fetchAll();
$modulesList = $db->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);

// ------------------------------------------------------------------
// Parse Filters
// ------------------------------------------------------------------
$filterUser     = (int)($_GET['user_id'] ?? 0);
$filterModule   = trim($_GET['module'] ?? '');
$filterSearch   = trim($_GET['q'] ?? '');
$filterDateFrom = trim($_GET['date_from'] ?? '');
$filterDateTo   = trim($_GET['date_to'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($filterUser > 0) {
    $where .= " AND a.user_id = ?";
    $params[] = $filterUser;
}

if ($filterModule !== '') {
    $where .= " AND a.module = ?";
    $params[] = $filterModule;
}

if ($filterSearch !== '') {
    $where .= " AND (a.action LIKE ? OR u.full_name LIKE ? OR u.username LIKE ? OR a.ip_address LIKE ?)";
    $searchWild = "%{$filterSearch}%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}

if ($filterDateFrom !== '') {
    $where .= " AND DATE(a.created_at) >= ?";
    $params[] = $filterDateFrom;
}

if ($filterDateTo !== '') {
    $where .= " AND DATE(a.created_at) <= ?";
    $params[] = $filterDateTo;
}

// ------------------------------------------------------------------
// Export CSV Action
// ------------------------------------------------------------------
$action = $_GET['action'] ?? 'list';

if ($action === 'export') {
    $exportSql = "SELECT a.id, 
                         a.created_at, 
                         u.full_name as user_name, 
                         u.username, 
                         u.role as user_role, 
                         p.name as partner_name, 
                         a.module, 
                         a.action, 
                         a.record_id, 
                         a.ip_address, 
                         a.old_value, 
                         a.new_value 
                  FROM audit_logs a 
                  LEFT JOIN users u ON a.user_id = u.id 
                  LEFT JOIN partners p ON u.partner_id = p.id 
                  $where 
                  ORDER BY a.created_at DESC";

    $exportStmt = $db->prepare($exportSql);
    $exportStmt->execute($params);
    $exportLogs = $exportStmt->fetchAll();

    $filename = "neilos_activity_logs_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Log ID',
        'Timestamp',
        'User Name',
        'Username',
        'User Role',
        'Partner',
        'Module',
        'Action Description',
        'Record ID',
        'IP Address',
        'Previous State (Old Value)',
        'New State (New Value)'
    ]);

    foreach ($exportLogs as $row) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['user_name'] ?? 'System User',
            $row['username'] ?? 'system',
            $row['user_role'] ?? 'System',
            $row['partner_name'] ?? 'Neilos Internal',
            $row['module'] ?: 'General',
            $row['action'],
            $row['record_id'] ?: 'N/A',
            $row['ip_address'] ?: '127.0.0.1',
            $row['old_value'] ?: '',
            $row['new_value'] ?: ''
        ]);
    }
    fclose($output);
    auditLog('Downloaded activity logs CSV export', 'activity_logs', 0);
    exit;
}
$statsTotalLogs  = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$statsTodayLogs  = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE()")->fetchColumn();
$statsActiveUsers= (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM audit_logs WHERE user_id IS NOT NULL")->fetchColumn();
$statsIpCount    = (int)$db->query("SELECT COUNT(DISTINCT ip_address) FROM audit_logs WHERE ip_address IS NOT NULL")->fetchColumn();

// ------------------------------------------------------------------
// Pagination & Query Exec
// ------------------------------------------------------------------
$totalStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$pg = max(1, (int)($_GET['p'] ?? 1));
$limit = 25;
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$sql = "SELECT a.*, 
               u.full_name as user_name, 
               u.username, 
               u.email as user_email, 
               u.role as user_role, 
               u.profile_picture, 
               p.name as partner_name 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        LEFT JOIN partners p ON u.partner_id = p.id 
        $where 
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Render view
$pageTitle = 'Activity & Audit Logs';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/activity_logs/index.php';
include APP_DIR . '/views/layout/footer.php';
