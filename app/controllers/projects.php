<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

// Access: System Admin, BSA, Project Team, Engineering Coordinator
if (!hasRole('BSA', 'Project Team', 'Engineering Coordinator') && !isAdmin()) {
    http_response_code(403);
    include APP_DIR . '/views/errors/403.php';
    exit;
}

// ------------------------------------------------------------------
// POST: Create project from order
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verifyCsrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $projectName = trim($_POST['project_name'] ?? '');
    $targetDate = $_POST['target_date'] ?? null;

    // Verify order exists
    $ordStmt = $db->prepare("SELECT id, order_number, partner_id, customer_name, service_type FROM orders WHERE id = ?");
    $ordStmt->execute([$orderId]);
    $order = $ordStmt->fetch();
    if (!$order) { setFlash('danger', 'Order not found.'); header('Location: ' . APP_URL . '/?page=orders'); exit; }

    // Check if project already exists
    $chk = $db->prepare("SELECT id FROM projects WHERE order_id = ?");
    $chk->execute([$orderId]);
    if ($chk->fetch()) { setFlash('danger', 'A project already exists for this order.'); header('Location: ' . APP_URL . '/?page=order_detail&id=' . $orderId); exit; }

    if (!$projectName) {
        $projectName = $order['customer_name'] . ' — ' . $order['service_type'] . ' (' . $order['order_number'] . ')';
    }

    $db->prepare("INSERT INTO projects (order_id, partner_id, project_name, target_date, assigned_to) VALUES (?,?,?,?,?)")
       ->execute([$orderId, $order['partner_id'], $projectName, $targetDate, $user['id']]);
    $projectId = $db->lastInsertId();

    // Timeline
    $db->prepare("INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES (?,?,?,?)")
       ->execute([$orderId, 'Provisioning', 'Project created: ' . $projectName, $user['id']]);

    auditLog("Created project for order {$order['order_number']}", 'projects', $projectId);
    setFlash('success', "Project <strong>" . e($projectName) . "</strong> created.");
    header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . $projectId);
    exit;
}

// ------------------------------------------------------------------
// POST: Add task
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_task') {
    verifyCsrf();
    $projectId = (int)($_POST['project_id'] ?? 0);
    $taskName  = trim($_POST['task_name'] ?? '');
    $assignedTo = (int)($_POST['assigned_to'] ?? 0);
    $dueDate   = $_POST['due_date'] ?? null;

    if (!$taskName) { setFlash('danger', 'Task name is required.'); header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . $projectId); exit; }

    $db->prepare("INSERT INTO project_tasks (project_id, task_name, assigned_to, due_date) VALUES (?,?,?,?)")
       ->execute([$projectId, $taskName, $assignedTo ?: null, $dueDate ?: null]);
    auditLog("Added task to project #{$projectId}: {$taskName}", 'projects', $projectId);
    setFlash('success', 'Task added.');
    header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . $projectId);
    exit;
}

// ------------------------------------------------------------------
// POST: Update task status
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_task') {
    verifyCsrf();
    $taskId   = (int)($_POST['task_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowedTaskStatuses = ['Pending','In Progress','Completed','Blocked'];
    if (!in_array($newStatus, $allowedTaskStatuses)) { setFlash('danger', 'Invalid task status.'); header('Location: ' . APP_URL . '/?page=projects'); exit; }

    $extra = $newStatus === 'Completed' ? ', completed_at = NOW()' : '';
    $db->prepare("UPDATE project_tasks SET status = ? {$extra} WHERE id = ?")->execute([$newStatus, $taskId]);
    auditLog("Updated task #{$taskId} to {$newStatus}", 'projects', $taskId);
    setFlash('success', 'Task status updated.');
    header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . ($_POST['project_id'] ?? 0));
    exit;
}

// ------------------------------------------------------------------
// POST: Add milestone
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_milestone') {
    verifyCsrf();
    $projectId    = (int)($_POST['project_id'] ?? 0);
    $milestoneName = trim($_POST['milestone_name'] ?? '');
    $targetDate   = $_POST['target_date'] ?? null;
    if (!$milestoneName) { setFlash('danger', 'Milestone name is required.'); header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . $projectId); exit; }

    $db->prepare("INSERT INTO project_milestones (project_id, milestone_name, target_date) VALUES (?,?,?)")
       ->execute([$projectId, $milestoneName, $targetDate ?: null]);
    auditLog("Added milestone to project #{$projectId}: {$milestoneName}", 'projects', $projectId);
    setFlash('success', 'Milestone added.');
    header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . $projectId);
    exit;
}

// ------------------------------------------------------------------
// POST: Update milestone status
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_milestone') {
    verifyCsrf();
    $msId      = (int)($_POST['milestone_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowedMsStatuses = ['Pending','Achieved','Missed'];
    if (!in_array($newStatus, $allowedMsStatuses)) { setFlash('danger', 'Invalid milestone status.'); header('Location: ' . APP_URL . '/?page=projects'); exit; }

    $extra = $newStatus === 'Achieved' ? ', actual_date = CURDATE()' : '';
    $db->prepare("UPDATE project_milestones SET status = ? {$extra} WHERE id = ?")->execute([$newStatus, $msId]);
    auditLog("Updated milestone #{$msId} to {$newStatus}", 'projects', $msId);
    setFlash('success', 'Milestone status updated.');
    header('Location: ' . APP_URL . '/?page=projects&action=detail&id=' . ($_POST['project_id'] ?? 0));
    exit;
}

// ------------------------------------------------------------------
// Detail view
// ------------------------------------------------------------------
if ($action === 'detail') {
    $projectId = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT p.*, o.order_number, o.customer_name, o.service_type, o.status as order_status, p2.name as partner_name FROM projects p JOIN orders o ON p.order_id = o.id JOIN partners p2 ON p.partner_id = p2.id WHERE p.id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    if (!$project) { http_response_code(404); echo '<p style="padding:40px">Project not found.</p>'; exit; }

    $tasks = $db->prepare("SELECT pt.*, u.full_name as assigned_name FROM project_tasks pt LEFT JOIN users u ON pt.assigned_to = u.id WHERE pt.project_id = ? ORDER BY pt.created_at DESC");
    $tasks->execute([$projectId]);
    $tasks = $tasks->fetchAll();

    $milestones = $db->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY target_date ASC");
    $milestones->execute([$projectId]);
    $milestones = $milestones->fetchAll();

    // Users for assignment dropdown
    $assignable = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 AND role IN ('BSA','Project Team','Engineering Coordinator','NOC Support','NOC Core','NOC Level 3') ORDER BY full_name")->fetchAll();

    $pageTitle = 'Project: ' . e($project['project_name']);
    include APP_DIR . '/views/layout/header.php';
    include APP_DIR . '/views/projects/detail.php';
    include APP_DIR . '/views/layout/footer.php';
    exit;
}

// ------------------------------------------------------------------
// List
// ------------------------------------------------------------------
$where  = "WHERE 1=1";
$params = [];
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['q'] ?? '';

if ($filterStatus) { $where .= " AND p.status = ?"; $params[] = $filterStatus; }
if ($filterSearch) { $where .= " AND (p.project_name LIKE ? OR o.order_number LIKE ? OR o.customer_name LIKE ?)"; $ps = "%$filterSearch%"; $params = array_merge($params, [$ps, $ps, $ps]); }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM projects p JOIN orders o ON p.order_id = o.id $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 20;
$pg  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare("SELECT p.*, o.order_number, o.customer_name, o.service_type, p2.name as partner_name FROM projects p JOIN orders o ON p.order_id = o.id JOIN partners p2 ON p.partner_id = p2.id $where ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Task counts per project
$pIds = array_column($projects, 'id');
$taskCounts = [];
if (!empty($pIds)) {
    $in = implode(',', array_fill(0, count($pIds), '?'));
    $tcStmt = $db->prepare("SELECT project_id, COUNT(*) as cnt, SUM(status = 'Completed') as done FROM project_tasks WHERE project_id IN ($in) GROUP BY project_id");
    $tcStmt->execute($pIds);
    foreach ($tcStmt->fetchAll() as $row) {
        $taskCounts[(int)$row['project_id']] = ['total' => (int)$row['cnt'], 'done' => (int)$row['done']];
    }
}

$statusOptions = ['Not Started','In Progress','On Hold','Completed','Cancelled'];

$pageTitle = 'Project Delivery';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/projects/list.php';
include APP_DIR . '/views/layout/footer.php';
