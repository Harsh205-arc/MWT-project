<?php
require 'db.php';

$sql = "
SELECT c.id, c.title, c.completed, u.u_name
FROM chores c
JOIN users u ON c.assinged_to = u.u_id
";

$stmt = $pdo->query($sql);
$chores = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($chores);
