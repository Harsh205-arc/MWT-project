<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/add_expense.php");
    exit;
}

$paid_by   = (int) $_POST['paid_by'];
$amount    = (float) $_POST['amount'];
$name      = trim($_POST['name']);
$house_id  = (int) $_POST['house_id'];
$splitType = $_POST['split_type'];

/* 1. Insert expense */
$stmt = $conn->prepare("
    INSERT INTO expense (paid_by, amount, name, house_id)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("idsi", $paid_by, $amount, $name, $house_id);
$stmt->execute();
$expense_id = $stmt->insert_id;
$stmt->close();

/* 2. Get house members */
$members = [];
$res = $conn->query("
    SELECT user_id FROM house_members WHERE house_id = $house_id
");
while ($r = $res->fetch_assoc()) {
    $members[] = $r['user_id'];
}

/* 3. Prepare splits */
$splits = [];

if ($splitType === 'equal') {
    $each = round($amount / count($members), 2);
    foreach ($members as $u) {
        $splits[$u] = $each;
    }
} else {
    $inputSplits = $_POST['splits'];
    $total = array_sum($inputSplits);

    if (round($total, 2) != round($amount, 2)) {
        die("Split amounts do not match total amount");
    }

    $splits = $inputSplits;
}

/* 4. Insert splits */
$stmt = $conn->prepare("
    INSERT INTO expense_split (es_id, user_id, split, settled)
    VALUES (?, ?, ?, ?)
");

foreach ($splits as $user_id => $split) {
    $settled = ($user_id == $paid_by) ? 1 : 0;
    $stmt->bind_param("iidi", $expense_id, $user_id, $split, $settled);
    $stmt->execute();
}

$stmt->close();

/* 5. Redirect */
header("Location: ../public/expenses.php");
exit;
