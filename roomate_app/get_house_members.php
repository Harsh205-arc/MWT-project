<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';

if(!isset($_GET['house_id'])){
    echo json_encode([]);
    exit;
}

$house_id=$_GET['house_id'];

$sql="SELECT users.u_id,users.u_name
      FORM house_members
      JOIN users ON house_members.user_id = user.u_id
      WHERE house_members.house_id = :house_id
";

$stmt = $pdo -> perpare($sql);
$stmt->execute(['house_id' => $house_id]);
$members =$stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type :application/json');
echo json_encode($members);