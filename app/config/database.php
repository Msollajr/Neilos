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

// Financial limits (TZS)
define('MAX_NRC_AMOUNT', 100000000.0); // TZS 100 Million
define('MAX_MRC_AMOUNT', 500000000.0); // TZS 500 Million
define('MIN_NRC_AMOUNT', 0.0);
define('MIN_MRC_AMOUNT', 0.0);

// KAM list
define('KAM_LIST', ['Gloria Entebbe', 'Michael Corss']);

// Session
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

$pdo = null;

/**
 * Returns a live PDO connection, automatically reconnecting if MySQL
 * has closed the connection (SQLSTATE HY000 / error 2006 "server has gone away").
 */
function getDB(): PDO {
    global $pdo;

    // If we have a cached connection, verify it is still alive before returning it.
    // A cheap SELECT 1 ping catches error 2006 without an extra round-trip on every call
    // because PDO buffers the exception rather than crashing.
    if ($pdo !== null) {
        try {
            $pdo->query('SELECT 1');
        } catch (PDOException $e) {
            // Connection is stale (e.g. MySQL wait_timeout or server restart).
            // Discard it so the block below creates a fresh one.
            $pdo = null;
        }
    }

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Keep the MySQL session alive for 8 hours to match SESSION_LIFETIME.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=28800, interactive_timeout=28800",
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            try {
                $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'Partner'");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN site_category VARCHAR(100) NULL");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN service_type VARCHAR(100) NOT NULL DEFAULT 'FTTH'");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN fttx_package VARCHAR(100) NULL");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN bandwidth VARCHAR(100) NULL");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN nni_location VARCHAR(200) NULL");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN aggregate_capacity VARCHAR(100) NULL");
                $pdo->exec("ALTER TABLE orders MODIFY COLUMN status VARCHAR(100) NOT NULL DEFAULT 'Feasibility Review'");
                $pdo->exec("ALTER TABLE contractor_assignments MODIFY COLUMN status VARCHAR(100) NOT NULL DEFAULT 'Assigned'");
                $pdo->exec("ALTER TABLE contractor_progress_updates MODIFY COLUMN progress_status VARCHAR(100) NOT NULL DEFAULT 'In Progress'");
                $pdo->exec("ALTER TABLE contractor_evidence MODIFY COLUMN evidence_type VARCHAR(100) NOT NULL");
                $pdo->exec("ALTER TABLE evidence_checklist_config MODIFY COLUMN evidence_type VARCHAR(100) NOT NULL");
                $pdo->exec("CREATE TABLE IF NOT EXISTS active_services (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    service_id VARCHAR(100) NOT NULL UNIQUE,
                    order_id INT UNSIGNED NOT NULL,
                    partner_id INT UNSIGNED NOT NULL,
                    customer_name VARCHAR(200) NOT NULL,
                    service_type VARCHAR(100) NOT NULL,
                    circuit_id VARCHAR(100) NULL,
                    bandwidth_capacity VARCHAR(100) NULL,
                    location VARCHAR(255) NULL,
                    building_name VARCHAR(200) NULL,
                    kam_id INT UNSIGNED NULL,
                    activation_date DATE NULL,
                    billing_start_date DATE NULL,
                    status VARCHAR(100) NOT NULL DEFAULT 'Active',
                    monitoring_status VARCHAR(100) DEFAULT 'Unknown',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_order (order_id),
                    INDEX idx_partner (partner_id)
                ) ENGINE=InnoDB");
                $pdo->exec("ALTER TABLE active_services MODIFY COLUMN service_type VARCHAR(100) NOT NULL");
                $pdo->exec("ALTER TABLE active_services MODIFY COLUMN bandwidth_capacity VARCHAR(100) NULL");
                $pdo->exec("ALTER TABLE active_services MODIFY COLUMN status VARCHAR(100) NOT NULL DEFAULT 'Active'");
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
                $orderCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
                $neededOrderCols = [
                    'kam_remarks'                  => "ALTER TABLE orders ADD COLUMN kam_remarks TEXT NULL",
                    'kam_proposed_nrc'             => "ALTER TABLE orders ADD COLUMN kam_proposed_nrc DECIMAL(14,2) NULL",
                    'kam_proposed_mrc'             => "ALTER TABLE orders ADD COLUMN kam_proposed_mrc DECIMAL(14,2) NULL",
                    'kam_commercial_justification' => "ALTER TABLE orders ADD COLUMN kam_commercial_justification TEXT NULL",
                    'kam_approved_at'              => "ALTER TABLE orders ADD COLUMN kam_approved_at DATETIME NULL",
                    'kam_approved_by'              => "ALTER TABLE orders ADD COLUMN kam_approved_by INT UNSIGNED NULL",
                    'assigned_kam_name'            => "ALTER TABLE orders ADD COLUMN assigned_kam_name VARCHAR(100) NULL",
                    'kam_id'                       => "ALTER TABLE orders ADD COLUMN kam_id INT UNSIGNED NULL",
                    'management_final_nrc'         => "ALTER TABLE orders ADD COLUMN management_final_nrc DECIMAL(14,2) NULL",
                    'management_final_mrc'         => "ALTER TABLE orders ADD COLUMN management_final_mrc DECIMAL(14,2) NULL",
                    'management_decision'          => "ALTER TABLE orders ADD COLUMN management_decision VARCHAR(100) NULL",
                    'management_remarks'           => "ALTER TABLE orders ADD COLUMN management_remarks TEXT NULL",
                    'management_return_remarks'    => "ALTER TABLE orders ADD COLUMN management_return_remarks TEXT NULL",
                    'management_remarks_visible'   => "ALTER TABLE orders ADD COLUMN management_remarks_visible TINYINT(1) DEFAULT 0",
                    'management_approved_at'       => "ALTER TABLE orders ADD COLUMN management_approved_at DATETIME NULL",
                    'management_approved_by'       => "ALTER TABLE orders ADD COLUMN management_approved_by INT UNSIGNED NULL",
                    'management_approved_price'    => "ALTER TABLE orders ADD COLUMN management_approved_price DECIMAL(12,2) NULL",
                    'sla_paused'                   => "ALTER TABLE orders ADD COLUMN sla_paused TINYINT(1) DEFAULT 0",
                    'sla_paused_at'                => "ALTER TABLE orders ADD COLUMN sla_paused_at DATETIME NULL",
                    'sla_paused_hours'             => "ALTER TABLE orders ADD COLUMN sla_paused_hours DECIMAL(10,2) DEFAULT 0.00",
                    'sla_pause_reason'             => "ALTER TABLE orders ADD COLUMN sla_pause_reason TEXT NULL",
                ];
                foreach ($neededOrderCols as $colName => $alterSql) {
                    if (!in_array($colName, $orderCols)) {
                        $pdo->exec($alterSql);
                    }
                }
            } catch (Throwable $t) {}
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Database Connection Error</h2><p>Could not connect to the database. Please check your configuration.</p><p><small>' . htmlspecialchars($e->getMessage()) . '</small></p></div>');
        }
    }
    return $pdo;
}

