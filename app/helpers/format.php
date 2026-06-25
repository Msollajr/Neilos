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
        'Submitted'                      => 'badge-info',
        'Feasibility Review'             => 'badge-warning',
        'Awaiting BSA Approval'          => 'badge-warning',
        'Awaiting Commercial Approval'   => 'badge-warning',
        'Awaiting Management Approval'   => 'badge-warning',
        'Approved'                       => 'badge-primary',
        'Provisioning'                   => 'badge-primary',
        'Installation'                   => 'badge-primary',
        'Testing'                        => 'badge-primary',
        'UAT'                            => 'badge-info',
        'UAT - Awaiting Confirmation'    => 'badge-warning',
        'Activated'                      => 'badge-success',
        'Billing Triggered'              => 'badge-success',
        'Closed'                         => 'badge-secondary',
        'Cancelled'                      => 'badge-danger',
        default                          => 'badge-secondary',
    };
}

/**
 * Return a CSS class for ticket status badge.
 */
function ticketStatusClass(string $status): string {
    return match($status) {
        'Open'                                     => 'badge-danger',
        'Assigned'                                 => 'badge-warning',
        'In Progress'                              => 'badge-primary',
        'Resolved - Awaiting Customer Confirmation'=> 'badge-info',
        'Closed'                                   => 'badge-success',
        'Reopened'                                 => 'badge-danger',
        default                                    => 'badge-secondary',
    };
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
 * Compute FTTx pricing.
 */
function getFTTxPricing(string $package): array {
    $prices = [
        '20 Mbps'  => 10.40,
        '30 Mbps'  => 12.48,
        '40 Mbps'  => 13.52,
        '50 Mbps'  => 16.22,
        '60 Mbps'  => 19.47,
        '80 Mbps'  => 23.36,
        '100 Mbps' => 28.04,
    ];
    $usdMrr = $prices[$package] ?? 0;
    $tzsMrc = round($usdMrr * USD_TZS_RATE, 2);
    return ['usd_mrr' => $usdMrr, 'tzs_mrc' => $tzsMrc];
}

/**
 * Compute Dedicated Layer 2 pricing.
 */
function getL2Pricing(string $capacity): float {
    $prices = [
        '1 Gbps'  => 3000.00,
        '1.5 Gbps'=> 4000.00,
        '2 Gbps'  => 5500.00,
        '3 Gbps'  => 8000.00,
        '4 Gbps'  => 10500.00,
        '5 Gbps'  => 13000.00,
        '6 Gbps'  => 15500.00,
        '7 Gbps'  => 18000.00,
        '8 Gbps'  => 21000.00,
        '9 Gbps'  => 24000.00,
        '10 Gbps' => 27000.00,
    ];
    return $prices[$capacity] ?? 0.00;
}

/**
 * Calculate commercial summary server-side.
 */
function calculateCommercials(array $data): array {
    $serviceType = $data['service_type'] ?? '';
    $isRemoteHands = ($serviceType === 'Remote Hands Only');

    $baseNRC = $isRemoteHands ? REMOTE_HANDS_NRC : DEFAULT_BASE_NRC;
    $remoteHandsNRC = (!$isRemoteHands && !empty($data['remote_hands_required'])) ? REMOTE_HANDS_NRC : 0;
    $nrcSubtotal = $baseNRC + $remoteHandsNRC;
    $vatNRC = round($nrcSubtotal * VAT_RATE, 2);
    $totalNRC = round($nrcSubtotal + $vatNRC, 2);

    $baseMRC = 0;
    $mrcCurrency = 'TZS';

    if (!$isRemoteHands) {
        if (in_array($serviceType, ['FTTH', 'FTTB'])) {
            $pricing = getFTTxPricing($data['fttx_package'] ?? '');
            $baseMRC = $pricing['tzs_mrc'];
            $mrcCurrency = 'TZS';
        } elseif ($serviceType === 'DIA') {
            $baseMRC = (float)($data['dia_mrc'] ?? 0);
            $mrcCurrency = 'USD';
        } elseif ($serviceType === 'Dedicated Layer 2') {
            $baseMRC = getL2Pricing($data['aggregate_capacity'] ?? '');
            $mrcCurrency = 'USD';
        }
    }

    $discountPct = (float)($data['discount_pct'] ?? 0);
    $discountAmt = round($baseMRC * ($discountPct / 100), 2);
    $mrcAfterDiscount = $baseMRC - $discountAmt;
    $vatMRC = round($mrcAfterDiscount * VAT_RATE, 2);
    $totalMRC = round($mrcAfterDiscount + $vatMRC, 2);

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
