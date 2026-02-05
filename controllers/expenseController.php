<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $paid_by  = (int) $_POST['paid_by'];
    $amount   = (float) $_POST['amount'];
    $name     = $_POST['name'];
    $house_id = (int) $_POST['house_id'];

    /* -----------------------------
       1. Insert expense
    ------------------------------*/
    $stmt = $conn->prepare(
        "INSERT INTO expense (paid_by, amount, name, house_id)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("idsi", $paid_by, $amount, $name, $house_id);
    $stmt->execute();

    // Get the new expense ID
    $expense_id = $stmt->insert_id;
    $stmt->close();

    /* -----------------------------
       2. Get all users in the house
    ------------------------------*/
    $stmt = $conn->prepare(
        "SELECT user_id FROM house_members WHERE house_id = ?"
    );
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row['user_id'];
    }
    $stmt->close();

    /* -----------------------------
       3. Split amount equally
    ------------------------------*/
    $count = count($users);
    if ($count === 0) {
        die("No users in house");
    }

    $split_amount = round($amount / $count, 2);

    /* -----------------------------
       4. Insert splits
    ------------------------------*/
    $stmt = $conn->prepare(
        "INSERT INTO expense_split (es_id, user_id, split, settled)
         VALUES (?, ?, ?, 0)"
    );

    foreach ($users as $user_id) {
        $stmt->bind_param("iid", $expense_id, $user_id, $split_amount);
        $stmt->execute();
    }

    $stmt->close();

    echo "Expense and splits added successfully";
}
?>
