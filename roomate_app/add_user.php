xxo
x<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';

$name = $_POST['name'];
$email =$_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$age =$_POST['age'];

$sql = "INSERT INTO users (u_name, email, passwords, age)
        VALUES (:name, :email, :password, :age)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password' => $password,
    ':age' => $age
]);

echo "User added successfully";

