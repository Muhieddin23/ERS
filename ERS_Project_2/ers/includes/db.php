<?php
// ============================================================
//  includes/db.php  —  Database Connection
//  Uses PDO with prepared statements (SQL injection safe)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'ers_db');
define('DB_USER', 'root');       // change to your MySQL user
define('DB_PASS', '');           // change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST, DB_NAME, DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // In production, log this — never expose details to the browser
    error_log('DB connection failed: ' . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
}
