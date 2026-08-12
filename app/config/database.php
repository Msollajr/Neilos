<?php
// ============================================================
// Neilos Partner Portal — Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'neilos_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Neilos Partner Portal');
define('APP_URL', 'http://localhost/Neilos/public');
define('UPLOAD_DIR', __DIR__ . '/../../public/uploads/');
define('PUBLIC_DIR', __DIR__ . '/../../public');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'csv', 'doc', 'docx']);

// USD → TZS conversion rate (fixed per spec)
define('USD_TZS_RATE', 2585);

// VAT rate
define('VAT_RATE', 0.18);

// Default NRC values
define('DEFAULT_BASE_NRC', 60.00);
define('REMOTE_HANDS_NRC', 30.00);

// KAM list
define('KAM_LIST', ['Gloria Entebbe', 'Michael Corss']);

// Session
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

$pdo = null;

function getDB(): PDO {
    global $pdo;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            try {
                $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'Partner'");
                $pdo->exec("UPDATE users SET role = 'Partner' WHERE (role IS NULL OR role = '' OR role = 'Partner User') AND partner_id IN (SELECT id FROM partners WHERE kyc_type = 'Partner' OR partner_type IN ('ISP','Reseller'))");
                $pdo->exec("UPDATE evidence_checklist_config SET is_mandatory = 1 WHERE evidence_type = 'Latency Test'");
                $pdo->exec("
                    INSERT IGNORE INTO evidence_checklist_config (service_type, evidence_type, is_mandatory) VALUES
                    ('FTTH', 'Site Photo', 1), ('FTTH', 'ONT/ONU Serial', 1), ('FTTH', 'Signal Test', 1), ('FTTH', 'Speed Test', 1), ('FTTH', 'Latency Test', 1), ('FTTH', 'UAT Sign-off', 1), ('FTTH', 'Installation Remarks', 1),
                    ('FTTB', 'Site Photo', 1), ('FTTB', 'ONT/ONU Serial', 1), ('FTTB', 'Signal Test', 1), ('FTTB', 'Speed Test', 1), ('FTTB', 'Latency Test', 1), ('FTTB', 'UAT Sign-off', 1), ('FTTB', 'Installation Remarks', 1),
                    ('FTTE', 'Site Photo', 1), ('FTTE', 'ONT/ONU Serial', 1), ('FTTE', 'Signal Test', 1), ('FTTE', 'Speed Test', 1), ('FTTE', 'Latency Test', 1), ('FTTE', 'UAT Sign-off', 1), ('FTTE', 'Installation Remarks', 1),
                    ('DIA', 'Site Photo', 1), ('DIA', 'ONT/ONU Serial', 1), ('DIA', 'Signal Test', 1), ('DIA', 'Speed Test', 1), ('DIA', 'Latency Test', 1), ('DIA', 'UAT Sign-off', 1), ('DIA', 'Installation Remarks', 1),
                    ('BIA', 'Site Photo', 1), ('BIA', 'ONT/ONU Serial', 1), ('BIA', 'Signal Test', 1), ('BIA', 'Speed Test', 1), ('BIA', 'Latency Test', 1), ('BIA', 'UAT Sign-off', 1), ('BIA', 'Installation Remarks', 1),
                    ('BIA (Broadband Internet Access)', 'Site Photo', 1), ('BIA (Broadband Internet Access)', 'ONT/ONU Serial', 1), ('BIA (Broadband Internet Access)', 'Signal Test', 1), ('BIA (Broadband Internet Access)', 'Speed Test', 1), ('BIA (Broadband Internet Access)', 'Latency Test', 1), ('BIA (Broadband Internet Access)', 'UAT Sign-off', 1), ('BIA (Broadband Internet Access)', 'Installation Remarks', 1),
                    ('Layer 2 ( last mile)', 'Site Photo', 1), ('Layer 2 ( last mile)', 'Signal Test', 1), ('Layer 2 ( last mile)', 'Speed Test', 1), ('Layer 2 ( last mile)', 'Latency Test', 1), ('Layer 2 ( last mile)', 'UAT Sign-off', 1), ('Layer 2 ( last mile)', 'Installation Remarks', 1),
                    ('Dedicated Layer 2', 'Site Photo', 1), ('Dedicated Layer 2', 'Signal Test', 1), ('Dedicated Layer 2', 'Speed Test', 1), ('Dedicated Layer 2', 'Latency Test', 1), ('Dedicated Layer 2', 'UAT Sign-off', 1), ('Dedicated Layer 2', 'Installation Remarks', 1),
                    ('Remote Hands Only', 'Site Photo', 1), ('Remote Hands Only', 'Installation Remarks', 1)
                ");
            } catch (Throwable $t) {}
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Database Connection Error</h2><p>Could not connect to the database. Please check your configuration.</p><p><small>' . htmlspecialchars($e->getMessage()) . '</small></p></div>');
        }
    }
    return $pdo;
}
