<?php

declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_NAME = 'pemweb_db';
$DB_USER = 'root';
$DB_PASS = 'Qwerty12!';
$DB_CHARSET = 'utf8mb4';

/**
 * Buat koneksi PDO.
 *
 * @throws PDOException
 */
function db_connect(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    /** @var string $DB_HOST */
    /** @var string $DB_NAME */
    /** @var string $DB_USER */
    /** @var string $DB_PASS */
    /** @var string $DB_CHARSET */
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;

    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
