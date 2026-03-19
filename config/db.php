<?php
// config/db.php
// Database connection settings — change these to match your XAMPP setup

define('DB_HOST', '127.0.0.1;port=3307');
define('DB_USER', 'root');       // Default XAMPP MySQL username
define('DB_PASS', '');           // Default XAMPP MySQL password (empty)
define('DB_NAME', 'roomatehub');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>
