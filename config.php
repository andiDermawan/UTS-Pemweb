<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pemweb_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PDO Connection Options
$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Function to get database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $GLOBALS['db_options']);
        return $pdo;
    } catch (PDOException $e) {
        die('<div style="padding:2rem;font-family:monospace;color:#e74c3c;">
            <b>Koneksi database gagal:</b><br>' . htmlspecialchars($e->getMessage()) . '
        </div>');
    }
}
?>
