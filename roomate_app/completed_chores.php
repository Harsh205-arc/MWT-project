<?php
require 'db.php';

$id = $_POST['id'];

$sql = "UPDATE chores SET completed = 1 WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
