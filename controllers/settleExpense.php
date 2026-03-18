<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $es_id = $_POST['es_id'];

    $sql = "UPDATE expense_split SET settled = 1 WHERE es_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $es_id);
    $stmt->execute();
}

header("Location: expenseController.php");
exit;
