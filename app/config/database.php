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
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Database Connection Error</h2><p>Could not connect to the database. Please check your configuration.</p><p><small>' . htmlspecialchars($e->getMessage()) . '</small></p></div>');
        }
    }
    return $pdo;
}
