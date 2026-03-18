<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_id = (int) $_POST['expense_id'];

    $stmt = $conn->prepare(
        "UPDATE expense SET archived = 1 WHERE e_id = ?"
    );
    $stmt->bind_param("i", $expense_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../public/expenses.php");
    exit;
}
