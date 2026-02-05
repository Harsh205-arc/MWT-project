<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* TEMP SESSION (until auth module exists) */
$_SESSION['user_id']  = 1;
$_SESSION['house_id'] = 1;

$houseId = $_SESSION['house_id'];

/* Fetch house members */
$stmt = $conn->prepare("
    SELECT users.u_id, users.u_name
    FROM house_members
    JOIN users ON users.u_id = house_members.user_id
    WHERE house_members.house_id = ?
");
$stmt->bind_param("i", $houseId);
$stmt->execute();
$members = $stmt->get_result();
$memberList = $members->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Expense</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #f6f2ff;
            padding: 40px;
        }
        h1 { color: #5b2dbd; }
        .card {
            background: #fff;
            max-width: 550px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        label {
            font-weight: 600;
            margin-top: 15px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .split-box {
            background: #f3edff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        button {
            margin-top: 20px;
            background: #5b2dbd;
            color: #fff;
            border: none;
            padding: 12px 18px;
            border-radius: 6px;
            cursor: pointer;
        }
        .secondary {
            background: #ddd;
            color: #333;
            margin-left: 10px;
        }
    </style>

    <script>
        function toggleSplit(type) {
            document.getElementById('customSplits').style.display =
                type === 'custom' ? 'block' : 'none';
        }
    </script>
</head>
<body>

<h1>Add Expense</h1>

<div class="card">
<form method="POST" action="../controllers/expenseController.php">

    <label>Expense Name</label>
    <input type="text" name="name" required>

    <label>Total Amount</label>
    <input type="number" step="0.01" name="amount" required>

    <label>Paid By</label>
    <select name="paid_by" required>
        <option value="">Select</option>
        <?php foreach ($memberList as $m): ?>
            <option value="<?= $m['u_id'] ?>">
                <?= htmlspecialchars($m['u_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Split Type</label>
    <select name="split_type" onchange="toggleSplit(this.value)" required>
        <option value="equal">Equal Split</option>
        <option value="custom">Custom Split</option>
    </select>

    <div id="customSplits" class="split-box">
        <strong>Custom Split</strong>
        <?php foreach ($memberList as $m): ?>
            <label><?= htmlspecialchars($m['u_name']) ?></label>
            <input type="number" step="0.01" name="splits[<?= $m['u_id'] ?>]">
        <?php endforeach; ?>
    </div>

    <input type="hidden" name="house_id" value="<?= $houseId ?>">

    <button type="submit">Add Expense</button>
    <a href="expenses.php">
        <button type="button" class="secondary">View Expenses</button>
    </a>

</form>
</div>

</body>
</html>
