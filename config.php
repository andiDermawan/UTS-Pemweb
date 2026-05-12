<?php
session_start();
// Pengaman: config.php ditujukan untuk di-include, bukan diakses langsung lewat URL.
// Jika diakses langsung, selalu redirect ke halaman login.

// $_SERVER['SCRIPT_FILENAME']
// = /var/www/html/test.php

// __FILE__
// = /var/www/html/test.php
$isDirectAccess = false;
if (isset($_SERVER['SCRIPT_FILENAME'])) {
    $isDirectAccess = (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__));
}

if ($isDirectAccess) {
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }

    header('Location: index.php');
    exit;
}

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'pemweb_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Opsi Koneksi PDO
$db_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Fungsi untuk mengambil koneksi database
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
