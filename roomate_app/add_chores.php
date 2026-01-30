<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $house_id = $_POST['house_id'];
    $assigned_to = $_POST['assigned_to'];
    $title = $_POST['title'];

    $sql = "INSERT INTO chores (house_id, assinged_to, title)
            VALUES (:house_id, :assinged_to, :title)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':house_id' => $house_id,
        ':assinged_to' => $assigned_to,
        ':title' => $title
    ]);

    echo "Chore added successfully";
}
?>

<form method="POST" action="">
    <input type="number" name="house_id" placeholder="House ID" required><br><br>
    <input type="number" name="assigned_to" placeholder="User ID" required><br><br>
    <input type="text" name="title" placeholder="Chore title" required><br><br>
    <button type="submit">Add Chore</button>
</form>
