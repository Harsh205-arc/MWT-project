<?php
$host = "127.0.0.1";
$port = "3307";
$user = "root";
$password = "";
$dbname = "roomate_db";

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
