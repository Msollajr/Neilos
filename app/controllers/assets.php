<?php
requireLogin();

$db   = getDB();
$user = currentUser();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();
    $db->prepare("INSERT INTO assets (partner_id, active_service_id, order_id, asset_type, serial_number, model, customer_name, site_location, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $_POST['partner_id'] ?: null,
           $_POST['active_service_id'] ?: null,
           $_POST['order_id'] ?: null,
           $_POST['asset_type'],
           $_POST['serial_number'],
           $_POST['model'] ?? null,
           $_POST['customer_name'] ?? null,
           $_POST['site_location'] ?? null,
           $_POST['status'] ?? 'In Stock',
           $_POST['notes'] ?? null
       ]);
    auditLog('Created asset', 'assets', $db->lastInsertId());
    setFlash('success', 'Asset created.');
    header('Location: ' . APP_URL . '/?page=assets');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("UPDATE assets SET asset_type=?, serial_number=?, model=?, customer_name=?, site_location=?, status=?, notes=?, updated_at=NOW() WHERE id=?")
       ->execute([$_POST['asset_type'], $_POST['serial_number'], $_POST['model'] ?? null, $_POST['customer_name'] ?? null, $_POST['site_location'] ?? null, $_POST['status'] ?? 'In Stock', $_POST['notes'] ?? null, $id]);
    auditLog('Updated asset', 'assets', $id);
    setFlash('success', 'Asset updated.');
    header('Location: ' . APP_URL . '/?page=assets');
    exit;
}

if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT a.*, p.name as partner_name, s.service_id as svc_id FROM assets a LEFT JOIN partners p ON a.partner_id = p.id LEFT JOIN active_services s ON a.active_service_id = s.id WHERE a.id = ?");
    $stmt->execute([$id]);
    $asset = $stmt->fetch();
    if (!$asset) { http_response_code(404); echo '<p style="padding:40px">Asset not found.</p>'; exit; }

    $pageTitle = 'Asset - ' . $asset['serial_number'];
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/assets/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// List
$pw = partnerWhere('a');
$where = "WHERE {$pw['condition']}";
$params = $pw['params'];

$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['q'] ?? '';

if ($filterType)   { $where .= " AND a.asset_type = ?"; $params[] = $filterType; }
if ($filterStatus) { $where .= " AND a.status = ?"; $params[] = $filterStatus; }
if ($filterSearch) { $where .= " AND (a.serial_number LIKE ? OR a.model LIKE ? OR a.customer_name LIKE ?)"; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; $params[] = "%$filterSearch%"; }

$total = $db->prepare("SELECT COUNT(*) FROM assets a $where");
$total->execute($params);
$total = (int)$total->fetchColumn();

$limit = 25;
$pg = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT a.*, p.name as partner_name, s.service_id as svc_id FROM assets a LEFT JOIN partners p ON a.partner_id = p.id LEFT JOIN active_services s ON a.active_service_id = s.id $where ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$assets = $stmt->fetchAll();

$partners = $db->query("SELECT id, name FROM partners WHERE status = 'Active' ORDER BY name")->fetchAll();
$assetTypes = $db->query("SELECT DISTINCT asset_type FROM assets ORDER BY asset_type")->fetchAll(PDO::FETCH_COLUMN);
$statuses = ['In Stock','Deployed','Faulty','Returned','Retired'];

$pageTitle = 'Asset Inventory';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/assets/list.php';
include APP_DIR . '/views/layout/footer.php';
