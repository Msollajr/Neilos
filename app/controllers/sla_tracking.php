<?php
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

function formatSlaDuration(?int $seconds, bool $isOngoing = false): string {
    if ($seconds === null || $seconds < 0) {
        return '—';
    }
    
    $minutes = (int)round($seconds / 60);
    if ($minutes < 1) {
        return '0 min' . ($isOngoing ? ' (ongoing)' : '');
    }
    
    $days = (int)floor($minutes / (24 * 60));
    $remainingMin = $minutes % (24 * 60);
    $hours = (int)floor($remainingMin / 60);
    $mins = $remainingMin % 60;
    
    $parts = [];
    if ($days > 0) {
        $parts[] = $days === 1 ? '1 day' : "$days days";
    }
    if ($hours > 0) {
        $parts[] = $hours === 1 ? '1 hr' : "$hours hrs";
    }
    if ($mins > 0 || empty($parts)) {
        $parts[] = "$mins min";
    }
    
    $formatted = implode(' ', $parts);
    return $isOngoing ? "$formatted (ongoing)" : $formatted;
}

function computeOrderSlaTimestamps(array $o, array $timeline): array {
    $submittedTs = strtotime($o['created_at']);
    $now = time();
    
    $bsaTs = null;
    foreach ($timeline as $t) {
        if (in_array($t['status'], ['Await Commercial Approval', 'Management Approval', 'Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed'])) {
            $bsaTs = strtotime($t['changed_at']);
            break;
        }
    }
    
    $approvedTs = null;
    foreach ($timeline as $t) {
        if (in_array($t['status'], ['Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT', 'Closed'])) {
            $approvedTs = strtotime($t['changed_at']);
            break;
        }
    }
    
    $activatedTs = null;
    if (in_array($o['status'], ['Closed', 'UAT'])) {
        if (!empty($o['uat_accepted_at'])) {
            $activatedTs = strtotime($o['uat_accepted_at']);
        } elseif (!empty($o['closed_date'])) {
            $activatedTs = strtotime($o['closed_date']);
        }
        if (!$activatedTs) {
            foreach ($timeline as $t) {
                if (in_array($t['status'], ['Closed', 'UAT'])) {
                    $activatedTs = strtotime($t['changed_at']);
                    break;
                }
            }
        }
    }
    
    // Stage 1: Submitted → BSA
    $dur1 = null; $isDur1Ongoing = false;
    if ($bsaTs && $submittedTs) {
        $dur1 = max(0, $bsaTs - $submittedTs);
    } elseif ($o['status'] === 'Feasibility Review' && $submittedTs) {
        $dur1 = max(0, $now - $submittedTs);
        $isDur1Ongoing = true;
    }

    // Stage 2: BSA → Approved
    $dur2 = null; $isDur2Ongoing = false;
    if ($approvedTs && $bsaTs) {
        $dur2 = max(0, $approvedTs - $bsaTs);
    } elseif (in_array($o['status'], ['Await Commercial Approval', 'Management Approval'])) {
        $startRef = $bsaTs ?: $submittedTs;
        if ($startRef) {
            $dur2 = max(0, $now - $startRef);
            $isDur2Ongoing = true;
        }
    }

    // Stage 3: Approved → Activated
    $dur3 = null; $isDur3Ongoing = false;
    if ($activatedTs && $approvedTs) {
        $dur3 = max(0, $activatedTs - $approvedTs);
    } elseif (in_array($o['status'], ['Pending SOF', 'SOF Review', 'Installation', 'Testing', 'UAT'])) {
        $startRef = $approvedTs ?: ($bsaTs ?: $submittedTs);
        if ($startRef) {
            $dur3 = max(0, $now - $startRef);
            $isDur3Ongoing = true;
        }
    }

    // Total Duration: ALWAYS populated for all orders!
    $totalDur = null; $isTotalOngoing = false;
    if ($o['status'] === 'Closed' && $activatedTs && $submittedTs) {
        $totalDur = max(0, $activatedTs - $submittedTs);
    } elseif ($submittedTs) {
        $totalDur = max(0, $now - $submittedTs);
        $isTotalOngoing = true;
    }

    $stDisplay = !empty($o['service_type']) ? $o['service_type'] : '';
    if (!$stDisplay) {
        if (!empty($o['fttx_package'])) {
            $stDisplay = 'FTTH';
        } elseif (!empty($o['aggregate_capacity'])) {
            $stDisplay = 'Layer 2 (last mile)';
        } elseif (!empty($o['bandwidth'])) {
            $stDisplay = 'BIA (Broadband Internet Access)';
        } elseif ((float)($o['remote_hands_nrc_usd'] ?? 0) > 0 || (float)($o['base_nrc_usd'] ?? 0) == 80000) {
            $stDisplay = 'Remote Hands Only';
        }
    }
    
    return [
        'service_type_display'    => $stDisplay,
        'dur_submitted_bsa'       => $dur1,
        'is_dur1_ongoing'         => $isDur1Ongoing,
        'dur_bsa_approved'        => $dur2,
        'is_dur2_ongoing'         => $isDur2Ongoing,
        'dur_approved_activated'  => $dur3,
        'is_dur3_ongoing'         => $isDur3Ongoing,
        'dur_total'               => $totalDur,
        'is_total_ongoing'        => $isTotalOngoing
    ];
}

$pw = partnerWhere('o');
$where  = "WHERE {$pw['condition']}";
$params = $pw['params'];

$filterSearch = $_GET['q'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$sortKey      = $_GET['sort'] ?? 'newest';

if ($filterSearch) { $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ?)"; $pS = "%$filterSearch%"; $params[] = $pS; $params[] = $pS; }
if ($filterStatus) { $where .= " AND o.status = ?"; $params[] = $filterStatus; }

if ($action === 'export') {
    $stmt = $db->prepare("SELECT o.* FROM orders o $where ORDER BY o.created_at DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sla_tracking.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Order #','Customer','Service Type','Status','Created At','Submitted → BSA','BSA → Approved','Approved → Activated','Total Duration']);

    foreach ($orders as $o) {
        $tlSt = $db->prepare("SELECT status, changed_at FROM order_timeline WHERE order_id = ? ORDER BY changed_at ASC");
        $tlSt->execute([$o['id']]);
        $tl = $tlSt->fetchAll();
        $metrics = computeOrderSlaTimestamps($o, $tl);

        fputcsv($f, [
            $o['order_number'],
            $o['customer_name'],
            $metrics['service_type_display'] ?: '—',
            $o['status'],
            $o['created_at'],
            formatSlaDuration($metrics['dur_submitted_bsa'], $metrics['is_dur1_ongoing']),
            formatSlaDuration($metrics['dur_bsa_approved'], $metrics['is_dur2_ongoing']),
            formatSlaDuration($metrics['dur_approved_activated'], $metrics['is_dur3_ongoing']),
            formatSlaDuration($metrics['dur_total'], $metrics['is_total_ongoing'])
        ]);
    }
    fclose($f);
    exit;
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM orders o $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$limit = 25;
$pg  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pg - 1) * $limit;
$pages = (int)ceil($total / $limit);

$sqlOrder = "ORDER BY o.created_at DESC";
if ($sortKey === 'oldest') {
    $sqlOrder = "ORDER BY o.created_at ASC";
}

$stmt = $db->prepare("SELECT o.* FROM orders o $where $sqlOrder LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$slaData = [];
foreach ($orders as $o) {
    $tlSt = $db->prepare("SELECT status, changed_at FROM order_timeline WHERE order_id = ? ORDER BY changed_at ASC");
    $tlSt->execute([$o['id']]);
    $tl = $tlSt->fetchAll();
    
    $metrics = computeOrderSlaTimestamps($o, $tl);
    $o['service_type_display']   = $metrics['service_type_display'];
    $o['dur_submitted_bsa']      = $metrics['dur_submitted_bsa'];
    $o['is_dur1_ongoing']        = $metrics['is_dur1_ongoing'];
    $o['dur_bsa_approved']       = $metrics['dur_bsa_approved'];
    $o['is_dur2_ongoing']        = $metrics['is_dur2_ongoing'];
    $o['dur_approved_activated'] = $metrics['dur_approved_activated'];
    $o['is_dur3_ongoing']        = $metrics['is_dur3_ongoing'];
    $o['dur_total']              = $metrics['dur_total'];
    $o['is_total_ongoing']       = $metrics['is_total_ongoing'];
    
    $slaData[] = $o;
}

if (in_array($sortKey, ['total_desc', 'total_asc', 'bsa_desc', 'bsa_asc', 'approved_desc', 'approved_asc', 'activated_desc', 'activated_asc'])) {
    usort($slaData, function($a, $b) use ($sortKey) {
        $keyMap = [
            'total_desc'      => ['dur_total', -1],
            'total_asc'       => ['dur_total', 1],
            'bsa_desc'        => ['dur_submitted_bsa', -1],
            'bsa_asc'         => ['dur_submitted_bsa', 1],
            'approved_desc'   => ['dur_bsa_approved', -1],
            'approved_asc'    => ['dur_bsa_approved', 1],
            'activated_desc'  => ['dur_approved_activated', -1],
            'activated_asc'   => ['dur_approved_activated', 1],
        ];
        [$field, $dir] = $keyMap[$sortKey];
        $valA = $a[$field] ?? -1;
        $valB = $b[$field] ?? -1;
        if ($valA === $valB) return 0;
        return ($valA < $valB ? -1 : 1) * $dir;
    });
}

$allStatuses = ['Feasibility Review','Await Commercial Approval','Management Approval','Pending SOF','SOF Review','Installation','Testing','UAT','Closed','Not Feasible','Cancelled'];

$pageTitle = 'SLA Tracking';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/sla_tracking/index.php';
include APP_DIR . '/views/layout/footer.php';
