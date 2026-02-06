<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

var_dump($_POST);

$title = $_POST['title'] ?? '';
$assigned = $_POST['assigned_to'] ?? '';

echo "<br>Title: $title";
echo "<br>Assigned: $assigned";

$sql = "INSERT INTO chores (title, assinged_to, completed)
        VALUES (?, ?, 0)";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$title, $assigned]);

var_dump($result);
