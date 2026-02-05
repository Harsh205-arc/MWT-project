<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/*
 TEMPORARY SESSION CONTEXT (until auth module exists)
 This simulates a logged-in user and active house.
*/
$_SESSION['user_id']  = 1; // Sneha
$_SESSION['house_id'] = 1; // House ID

$userId  = $_SESSION['user_id'];
$houseId = $_SESSION['house_id'];

/*
 Fetch house members so humans can pick names, not IDs
*/
$sql = "
    SELECT users.u_id, users.u_name
    FROM house_members
    JOIN users ON users.u_id = house_members.user_id
    WHERE house_members.house_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $houseId);
$stmt->execute();
$members = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Expense</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f2ff;
            padding: 40px;
        }

        h1 {
            color: #5b2dbd;
            margin-bottom: 10px;
        }

        p {
            color: #555;
            margin-bottom: 25px;
        }

        .card {
            background: #fff;
            max-width: 500px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            margin-top: 25px;
            background: #5b2dbd;
            color: #fff;
            border: none;
            padding: 12px 18px;
            font-size: 15px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #4a24a3;
        }
    </style>
</head>
<body>

<h1>Add Expense</h1>
<p>Enter the expense details. Splits are handled automatically for all house members.</p>

<div class="card">
    <form method="POST" action="../controllers/expenseController.php">

        <label>Who Paid?</label>
        <select name="paid_by" required>
            <option value="">Select user</option>
            <?php while ($row = $members->fetch_assoc()): ?>
                <option value="<?= $row['u_id'] ?>">
                    <?= htmlspecialchars($row['u_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Total Amount</label>
        <input type="number" step="0.01" name="amount" required>

        <label>Expense Name</label>
        <input type="text" name="name" placeholder="e.g. Groceries, Cleaning" required>

        <!-- house_id is hidden and automatic -->
        <input type="hidden" name="house_id" value="<?= $houseId ?>">

        <button type="submit">Add Expense</button>
    </form>
</div>

</body>
</html>
