<?php
// ============================================================
// Neilos Partner Portal — System-wide Secure File Download Controller
// ============================================================

requireLogin();

$db = getDB();
$user = currentUser();
$table = trim($_GET['table'] ?? '');
$column = trim($_GET['column'] ?? 'file_path');
$id = (int)($_GET['id'] ?? 0);
$rawFile = trim($_GET['file'] ?? '');

$filePath = '';
$fileName = '';
$mimeType = '';

if ($table && $id > 0) {
    // 1. Contractor Evidence
    if ($table === 'contractor_evidence') {
        $stmt = $db->prepare("SELECT * FROM contractor_evidence WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $filePath = $row['file_path'];
            $fileName = $row['file_name'] ?: 'evidence_' . $id;
        }
    }
    // 2. Order Documents
    elseif ($table === 'order_documents') {
        $stmt = $db->prepare("SELECT * FROM order_documents WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $filePath = $row['file_path'];
            $fileName = $row['file_name'] ?: 'order_document_' . $id;
        }
    }
    // 3. KYC Documents
    elseif ($table === 'partner_kyc_application_documents') {
        $stmt = $db->prepare("SELECT * FROM partner_kyc_application_documents WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $filePath = $row['file_path'];
            $fileName = $row['file_name'] ?: 'kyc_document_' . $id;
        }
    }
    // 4. Orders Table (SOF signed / countersigned)
    elseif ($table === 'orders') {
        $validColumns = ['sof_signed_file', 'countersigned_sof_file', 'signed_sof_file'];
        if (in_array($column, $validColumns)) {
            $stmt = $db->prepare("SELECT $column, order_number FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && !empty($row[$column])) {
                $filePath = $row[$column];
                $fileName = basename($filePath);
            }
        }
    }
    // 5. KYC Applications Table (All KYC compliance & legal documents)
    elseif ($table === 'partner_kyc_applications') {
        $validColumns = [
            'cert_incorporation', 'tin_certificate', 'vat_certificate', 
            'business_license', 'tax_clearance', 'osha_compliance', 
            'wcf_registration', 'board_resolution', 'nda_doc', 
            'countersigned_kyc_file', 'brela_search_doc', 'power_of_attorney', 'director_ids'
        ];
        if (in_array($column, $validColumns)) {
            $stmt = $db->prepare("SELECT $column, countersigned_kyc_filename FROM partner_kyc_applications WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && !empty($row[$column])) {
                $filePath = $row[$column];
                $fileName = ($column === 'countersigned_kyc_file' && !empty($row['countersigned_kyc_filename'])) 
                    ? $row['countersigned_kyc_filename'] 
                    : basename($filePath);
            }
        }
    }
} elseif (!empty($rawFile)) {
    // Direct sanitized relative path fallback
    $sanitized = ltrim(str_replace(['..', "\0"], '', $rawFile), '/\\');
    if (strpos($sanitized, 'uploads/') === 0 || strpos($sanitized, 'orders/') === 0 || strpos($sanitized, 'kyc/') === 0 || strpos($sanitized, 'assets/') === 0) {
        $filePath = $sanitized;
        $fileName = basename($filePath);
    }
}

if (empty($filePath)) {
    setFlash('danger', 'Invalid file download request or record not found.');
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

$relativePath = ltrim(str_replace('\\', '/', $filePath), '/');
$fullPath = PUBLIC_DIR . '/' . $relativePath;

if (!file_exists($fullPath) || !is_file($fullPath)) {
    $fullPath = dirname(APP_DIR) . '/public/' . $relativePath;
}

if (!file_exists($fullPath) || !is_file($fullPath)) {
    $fullPath = dirname(APP_DIR) . '/' . $relativePath;
}

if (!file_exists($fullPath) || !is_file($fullPath)) {
    setFlash('danger', 'Physical file non-existent on server: ' . e(basename($filePath)));
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (APP_URL . '/?page=dashboard')));
    exit;
}

if (!$fileName) {
    $fileName = basename($fullPath);
}

$mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
$isInline = (isset($_GET['inline']) && ($_GET['inline'] === '1' || $_GET['inline'] === 'true')) || (isset($_GET['view']) && $_GET['view'] === '1');
$disposition = $isInline ? 'inline' : 'attachment';

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace(['"', "'", "\r", "\n"], '', $fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

readfile($fullPath);
exit;
