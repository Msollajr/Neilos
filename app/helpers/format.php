<?php
// ============================================================
// Neilos Partner Portal — Format & Upload Helpers
// ============================================================

/**
 * Format a number with commas and 2 decimal places.
 */
function money(float $amount, string $currency = ''): string {
    $formatted = number_format($amount, 2, '.', ',');
    return $currency ? "$currency $formatted" : $formatted;
}

/**
 * Format a numeric amount as TZS with thousands separator.
 * e.g. 240000 → "TZS 240,000"
 * Handles null gracefully with fallback.
 */
function formatTZS(?float $amount, bool $showSymbol = true, string $fallback = '—'): string {
    if ($amount === null) {
        return $fallback;
    }
    $formatted = number_format($amount, 0, '.', ',');
    return $showSymbol ? "TZS $formatted" : $formatted;
}

/**
 * Parse and validate a TZS amount input string.
 * Normalizes user input (strips "TZS", commas, spaces).
 * Strictly validates:
 *  - Non-empty when not nullable (blocks blank approval bypass)
 *  - Non-negative (blocks negative inputs)
 *  - Valid numeric format (rejects alphabetic / malformed text)
 *  - Non-zero unless allowZero is true (preserves distinction between blank & zero)
 *  - Configurable maximum threshold (blocks unreasonable values)
 * 
 * Returns clean numeric float or null.
 * Throws RuntimeException with clear error message on validation failure.
 */
function parseTZSInput(
    ?string $raw,
    bool $nullable = false,
    bool $allowZero = false,
    ?float $max = null,
    string $fieldLabel = 'Amount'
): ?float {
    if ($raw === null) {
        if ($nullable) return null;
        throw new RuntimeException("$fieldLabel is required.");
    }

    $trimmed = trim($raw);
    if ($trimmed === '') {
        if ($nullable) return null;
        throw new RuntimeException("$fieldLabel is required.");
    }

    // Check for negative sign upfront
    if (str_contains($trimmed, '-')) {
        throw new RuntimeException("$fieldLabel cannot be negative.");
    }

    // Strip currency symbols, spaces, commas, and formatting characters
    $clean = str_ireplace(['TZS', ',', ' ', "\xc2\xa0", "\t", "\n", "\r"], '', $trimmed);

    // Validate that cleaned string is strictly numeric digits (with optional 1-2 decimal places)
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
        throw new RuntimeException("$fieldLabel must be a valid numeric amount.");
    }

    $val = (float)$clean;

    if ($val < 0) {
        throw new RuntimeException("$fieldLabel cannot be negative.");
    }

    if (!$allowZero && $val == 0.0) {
        throw new RuntimeException("$fieldLabel cannot be zero.");
    }

    if ($max !== null && $val > $max) {
        throw new RuntimeException("$fieldLabel exceeds maximum allowed limit of " . formatTZS($max) . ".");
    }

    return $val;
}

/**
 * Generate an SO-prefixed Service Order Form number.
 * Format: SO-YYMMDD-NNN (auto-increments within the same day).
 */
function generateSofNumber(int $orderId = 0): string {
    $ymd  = date('ymd');
    $base = 'SO-' . $ymd . '-';
    try {
        $db = getDB();
        // Ensure the row has an SO number stored before generating a new one
        if ($orderId) {
            $existing = $db->prepare("SELECT sof_number FROM orders WHERE id = ?");
            $existing->execute([$orderId]);
            $sofNum = $existing->fetchColumn();
            if ($sofNum) return $sofNum;
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE sof_number LIKE ?");
        $stmt->execute([$base . '%']);
        $count = (int)$stmt->fetchColumn();
        return $base . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $base . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}

/**
 * Format bytes to human-readable size.
 */
function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

/**
 * Return a CSS class for order status badge.
 */
function orderStatusClass(string $status): string {
    return match($status) {
        // v1.0 lifecycle
        'Feasibility Review'          => 'badge-warning',
        'Await Commercial Approval'   => 'badge-warning',
        'Management Approval'         => 'badge-danger',
        'Pending SOF'                 => 'badge-info',
        'SOF Review'                  => 'badge-primary',
        'Installation'                => 'badge-primary',
        'Testing'                     => 'badge-primary',
        'UAT'                         => 'badge-info',
        'Closed'                      => 'badge-success',
        'Not Feasible'                => 'badge-danger',
        'Cancelled'                   => 'badge-danger',
        // Legacy statuses (kept for data continuity)
        'Submitted'                   => 'badge-warning',
        'Awaiting BSA Approval'       => 'badge-warning',
        'Awaiting Commercial Approval'=> 'badge-warning',
        'Awaiting Management Approval'=> 'badge-danger',
        'Approved'                    => 'badge-primary',
        'Provisioning'                => 'badge-primary',
        'UAT - Awaiting Confirmation' => 'badge-info',
        'Activated'                   => 'badge-success',
        'Billing Triggered'           => 'badge-success',
        default                       => 'badge-secondary',
    };
}

/**
 * Return a human-readable label and icon class for order status.
 */
function orderStatusIcon(string $status): string {
    return match($status) {
        'Feasibility Review'         => 'search',
        'Await Commercial Approval'  => 'dollar',
        'Management Approval'        => 'users',
        'Pending SOF'                => 'document',
        'SOF Review'                 => 'edit',
        'Installation'               => 'project',
        'Testing'                    => 'check',
        'UAT'                        => 'check',
        'Closed'                     => 'server',
        'Not Feasible'               => 'x',
        'Cancelled'                  => 'x',
        default                      => 'info',
    };
}

/**
 * Generate a Feasibility Request order number.
 */
function generateOrderNumber(): string {
    $ymd = date('ymd');
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 100");
        $stmt->execute(["FR-{$ymd}-%"]);
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxSeq = 0;
        foreach ($existing as $ordNum) {
            $parts = explode('-', $ordNum);
            $seqPart = end($parts);
            if (is_numeric($seqPart)) {
                $maxSeq = max($maxSeq, (int)$seqPart);
            }
        }

        $nextSeq = $maxSeq + 1;
        
        do {
            $candidate = "FR-{$ymd}-" . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE order_number = ?");
            $checkStmt->execute([$candidate]);
            $exists = (int)$checkStmt->fetchColumn();
            if ($exists > 0) {
                $nextSeq++;
            }
        } while ($exists > 0);

        return $candidate;
    } catch (Exception $e) {
        return 'FR-' . $ymd . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}



/**
 * Relative time string, e.g. "2 hours ago".
 */
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' day(s) ago';
}

/**
 * Format a datetime string for display.
 */
function fmtDate(string|null $dt, string $format = 'd M Y'): string {
    if (!$dt) return '—';
    return date($format, strtotime($dt));
}

function fmtDateTime(string|null $dt): string {
    return fmtDate($dt, 'd M Y H:i');
}

/**
 * Flash message helpers (stored in session).
 */
function setFlash(string $type, string $message): void {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): array|null {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize string for safe HTML output.
 */
function e(string|null $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * CSRF token generation and validation.
 */
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ============================================================
// Upload Helper
// ============================================================

/**
 * Handle a file upload, validate, and return stored path info.
 * Returns ['path' => string, 'name' => string, 'size' => int] or throws RuntimeException.
 */
function uploadFile(array $file, string $subdir = 'general'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error: ' . $file['error']);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('File is too large. Maximum allowed size is ' . formatBytes(MAX_FILE_SIZE) . '.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        throw new RuntimeException('File type ".' . e($ext) . '" is not allowed.');
    }

    $dir = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $fullPath   = $dir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }

    return [
        'path' => 'uploads/' . $subdir . '/' . $storedName,
        'name' => $file['name'],
        'size' => $file['size'],
    ];
}

/**
 * Compute FTTx pricing (MRC in TZS, Standard NRC in TZS).
 */
function getFTTxPricing(string $package): array {
    $packages = [
        'FTTx-40'  => ['mrc' => 27500.00, 'nrc' => 140000.00],
        'FTTx-50'  => ['mrc' => 33000.00, 'nrc' => 140000.00],
        'FTTx-60'  => ['mrc' => 39500.00, 'nrc' => 140000.00],
        'FTTx-70'  => ['mrc' => 46000.00, 'nrc' => 140000.00],
        'FTTx-80'  => ['mrc' => 52500.00, 'nrc' => 140000.00],
        'FTTx-90'  => ['mrc' => 59000.00, 'nrc' => 140000.00],
        'FTTx-100' => ['mrc' => 65500.00, 'nrc' => 140000.00],
    ];
    if (isset($packages[$package])) {
        return $packages[$package];
    }
    return ['mrc' => 0.00, 'nrc' => 140000.00];
}

/**
 * Compute Layer 2 ( last mile) pricing.
 * Capacity <= 100Mbps -> 110,000 TZS MRC, >= 101Mbps -> 220,000 TZS MRC.
 * Standard NRR = 250,000 TZS, Remote Hands NRR = 80,000 TZS.
 */
function getL2Pricing(string $capacity): array {
    $val = 0;
    if (preg_match('/(\d+)\s*(Gbps|Gb)/i', $capacity, $m)) {
        $val = (float)$m[1] * 1000;
    } elseif (preg_match('/(\d+)\s*(Mbps|Mb)/i', $capacity, $m)) {
        $val = (float)$m[1];
    } elseif (is_numeric($capacity)) {
        $val = (float)$capacity;
    }

    $mrc = ($val > 0 && $val <= 100) ? 110000.00 : ($val > 100 ? 220000.00 : 0.00);
    return [
        'mrc' => $mrc,
        'nrc' => 250000.00,
        'remote_hands_nrc' => 80000.00
    ];
}
/**
 * Canonical Commercial Summary for an order (Single Source of Truth across Neilos Portal).
 * Matches order detail Commercial Summary logic exactly.
 */
function getOrderCommercialSummary(array $order): array {
    // 1. NRC Calculation
    $hasRevNrc = ($order['revised_nrc'] !== null && $order['revised_nrc'] !== '');
    $stdNrc    = (float)($order['standard_nrc'] ?? $order['base_nrc_usd'] ?? 0);
    $revNrc    = $hasRevNrc ? (float)$order['revised_nrc'] : 0.00;
    
    // Active Base NRC is Revised NRC if present, otherwise Standard NRC
    $baseNrc   = $hasRevNrc ? $revNrc : $stdNrc;
    $rhNrc     = (float)($order['remote_hands_nrc_usd'] ?? 0);
    
    $nrcSubtotal = round($baseNrc + $rhNrc, 2);
    $vatNrc      = round($nrcSubtotal * 0.18, 2);
    $totalNrc    = round($nrcSubtotal + $vatNrc, 2);

    // 2. MRC Calculation
    $hasMgmtPrice = ($order['management_approved_price'] !== null && $order['management_approved_price'] !== '');
    $hasRevMrc    = ($order['revised_mrc'] !== null && $order['revised_mrc'] !== '');
    $stdMrc       = (float)($order['standard_mrc'] ?? $order['base_mrc'] ?? 0);
    $revMrc       = $hasRevMrc ? (float)$order['revised_mrc'] : 0.00;
    $mgmtMrc      = $hasMgmtPrice ? (float)$order['management_approved_price'] : 0.00;

    // Effective MRC value: Management Approved Price > Revised MRC > Standard MRC
    $mrcVal   = $hasMgmtPrice ? $mgmtMrc : ($hasRevMrc ? $revMrc : $stdMrc);
    $vatMrc   = round($mrcVal * 0.18, 2);
    $totalMrc = round($mrcVal + $vatMrc, 2);

    // Total Combined Revenue & VAT
    $totalRevenue = round($totalNrc + $totalMrc, 2);
    $totalVat     = round($vatNrc + $vatMrc, 2);

    return [
        'has_rev_nrc'   => $hasRevNrc,
        'std_nrc'       => $stdNrc,
        'rev_nrc'       => $revNrc,
        'base_nrc'      => $baseNrc,
        'rh_nrc'        => $rhNrc,
        'nrc_subtotal'  => $nrcSubtotal,
        'vat_nrc'       => $vatNrc,
        'total_nrc'     => $totalNrc,     // Total NRC Incl. VAT

        'has_rev_mrc'   => $hasRevMrc,
        'has_mgmt_mrc'  => $hasMgmtPrice,
        'std_mrc'       => $stdMrc,
        'rev_mrc'       => $revMrc,
        'mgmt_mrc'      => $mgmtMrc,
        'mrc_val'       => $mrcVal,       // Effective Base MRC
        'vat_mrc'       => $vatMrc,
        'total_mrc'     => $totalMrc,     // Effective Total MRC Incl. VAT

        'vat_total'     => $totalVat,
        'total_revenue' => $totalRevenue  // Total NRC Incl. VAT + Total MRC Incl. VAT
    ];
}

/**
 * Calculate commercial summary server-side.
 */
function calculateCommercials(array $data): array {
    $serviceType = $data['service_type'] ?? '';
    $vatRate     = VAT_RATE;

    $baseNRC        = 0.00;
    $remoteHandsNRC = 0.00;
    $baseMRC        = 0.00;
    $mrcCurrency    = 'TZS';

    if (in_array($serviceType, ['FTTH', 'FTTB'])) {
        $pricing = getFTTxPricing($data['fttx_package'] ?? '');
        $baseMRC = $pricing['mrc'];
        $baseNRC = $pricing['nrc'];
        $mrcCurrency = 'TZS';
    } elseif ($serviceType === 'Layer 2 ( last mile)' || $serviceType === 'Dedicated Layer 2' || str_contains($serviceType, 'Layer 2')) {
        $pricing = getL2Pricing($data['aggregate_capacity'] ?? $data['bandwidth'] ?? '');
        $baseMRC = $pricing['mrc'];
        $baseNRC = $pricing['nrc'];
        $mrcCurrency = 'TZS';
    } elseif ($serviceType === 'Remote Hands Only' || $serviceType === 'Remote Hands') {
        $baseNRC = 80000.00;
        $baseMRC = 0.00;
        $mrcCurrency = 'TZS';
    } else {
        // BIA (Broadband Internet Access) or other products:
        // Prices inserted by BSA (NRC) and KAM (MRC)
        $baseNRC = 0.00;
        $baseMRC = 0.00;
        $mrcCurrency = 'TZS';
    }

    // Add Remote Hands NRC (80,000 TZS) if requested
    if (!empty($data['remote_hands_required']) && ($data['remote_hands_required'] == '1' || strtolower($data['remote_hands_required']) === 'yes')) {
        $remoteHandsNRC = 80000.00;
    } elseif (!empty($data['remote_hands_nrc_usd']) && (float)$data['remote_hands_nrc_usd'] > 0) {
        $remoteHandsNRC = (float)$data['remote_hands_nrc_usd'];
    }

    // Term discount logic removed per specification requirement (KAM applies custom discount / revised pricing)
    $discountPct    = 0.00;
    $discountAmt    = 0.00;

    $nrcSubtotal = $baseNRC + $remoteHandsNRC;
    $vatNRC      = round($nrcSubtotal * $vatRate, 2);
    $totalNRC    = round($nrcSubtotal + $vatNRC, 2);

    $mrcAfterDiscount = $baseMRC - $discountAmt;
    $vatMRC         = round($mrcAfterDiscount * $vatRate, 2);
    $totalMRC       = round($mrcAfterDiscount + $vatMRC, 2);

    return [
        'base_nrc_usd'        => $baseNRC,
        'remote_hands_nrc_usd'=> $remoteHandsNRC,
        'nrc_subtotal_usd'    => $nrcSubtotal,
        'vat_on_nrc'          => $vatNRC,
        'total_nrc_incl_vat'  => $totalNRC,
        'base_mrc'            => $baseMRC,
        'mrc_currency'        => $mrcCurrency,
        'discount_pct'        => $discountPct,
        'discount_amount'     => $discountAmt,
        'vat_on_mrc'          => $vatMRC,
        'total_mrc_incl_vat'  => $totalMRC,
        'usd_tzs_rate'        => USD_TZS_RATE,
    ];
}

function profilePictureUrl(?string $path): string {
    if ($path && file_exists(__DIR__ . '/../../public/' . $path)) {
        return APP_URL . '/' . $path;
    }
    return '';
}

/**
 * Safely renders system notification messages.
 * Parses and strips raw/escaped HTML tags so literal strings like <strong> are never displayed as plaintext.
 * Allows safe inline formatting tags (strong, b, em, i, span, br) to render as rich-text HTML elements.
 */
function renderNotificationMessage(string $message): string {
    if (empty($message)) return '';

    // If message contains htmlspecialchars-encoded tags (&lt;strong&gt;), decode them
    $decoded = htmlspecialchars_decode($message, ENT_QUOTES);

    // Strip unsafe tags (script, iframe, style, etc.) while keeping safe formatting tags
    $safe = strip_tags($decoded, '<strong><b><em><i><span><br>');

    return $safe;
}

/**
 * Render standard View | Download | (Replace) | (Delete) file action buttons system-wide.
 */
function renderFileActions(array $options): string {
    $fileUrl     = $options['file_url'] ?? '';
    $fileName    = $options['file_name'] ?? 'File';
    $downloadUrl = $options['download_url'] ?? (APP_URL . '/?page=download&file=' . urlencode($fileUrl));
    $metadata    = $options['metadata'] ?? [];
    $canEdit     = !empty($options['can_edit']);
    $replaceUrl  = $options['replace_url'] ?? '';
    $deleteUrl   = $options['delete_url'] ?? '';
    $deleteId    = $options['delete_id'] ?? 0;
    $csrfToken   = csrfToken();

    $jsonMeta = htmlspecialchars(json_encode(array_merge([
        'file_name' => $fileName
    ], $metadata)), ENT_QUOTES, 'UTF-8');

    $fullFileUrl = (strpos($fileUrl, 'http') === 0) ? $fileUrl : (APP_URL . '/' . ltrim($fileUrl, '/\\'));

    $html = '<div style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap">';

    // 1. View Button (Opens in-app preview modal)
    $html .= '<button type="button" class="btn-file-action btn-file-view" onclick="event.stopPropagation(); viewSystemFile(\'' . e($fullFileUrl) . '\', \'' . e($fileName) . '\', \'' . e($downloadUrl) . '\', ' . $jsonMeta . ')" title="View in-app document preview">';
    $html .= svgIcon('eye', 13) . ' View';
    $html .= '</button>';

    // 2. Download Button (Direct forced attachment download to local computer)
    $html .= '<a href="' . e($downloadUrl) . '" class="btn-file-action btn-file-download" style="text-decoration:none;" title="Download file to computer">';
    $html .= svgIcon('download', 13) . ' Download';
    $html .= '</a>';

    // 3. Edit / Replace & Delete if authorized
    if ($canEdit) {
        if ($replaceUrl) {
            $html .= '<button type="button" class="btn-file-action btn-file-replace" onclick="event.stopPropagation(); window.location.href=\'' . e($replaceUrl) . '\'" title="Upload replacement file">';
            $html .= svgIcon('edit', 13) . ' Replace';
            $html .= '</button>';
        }

        if ($deleteId && $deleteUrl) {
            $html .= '<form method="POST" action="' . e($deleteUrl) . '" style="display:inline" data-confirm="Delete file \'' . e($fileName) . '\'? This action cannot be undone." data-confirm-title="Delete File?" data-confirm-btn="Delete" data-confirm-class="btn-danger" data-confirm-icon="🗑️" onclick="event.stopPropagation()">';
            $html .= '<input type="hidden" name="csrf_token" value="' . $csrfToken . '">';
            $html .= '<input type="hidden" name="id" value="' . (int)$deleteId . '">';
            $html .= '<button type="submit" class="btn-file-action btn-file-delete" title="Delete file">';
            $html .= svgIcon('trash', 13) . ' Delete';
            $html .= '</button>';
            $html .= '</form>';
        }
    }

    $html .= '</div>';
    return $html;
}


