<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$_SESSION['house_id'] = 1; // TEMP until auth
$houseId = $_SESSION['house_id'];

$sql = "
SELECT 
    e.e_id,
    e.name AS expense_name,
    e.amount,
    u.u_name AS paid_by,
    GROUP_CONCAT(
        CONCAT(
            split_user.u_name, ' owes ', 
            payer.u_name, ' ',
            es.split
        ) SEPARATOR '<br>'
    ) AS breakdown
FROM expense e
JOIN users payer ON payer.u_id = e.paid_by
JOIN expense_split es ON es.es_id = e.e_id
JOIN users split_user ON split_user.u_id = es.user_id
JOIN users u ON u.u_id = e.paid_by
WHERE e.house_id = ?
AND e.archived = 0
AND es.user_id != e.paid_by
GROUP BY e.e_id
ORDER BY e.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $houseId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expenses</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body>

<h1>Expense Summary</h1>

<a class="btn" href="add_expense.php">+ Add Expense</a>
<a class="btn" href="final_summary.php">View Final Summary</a>
<a class="btn" href="archived_expenses.php">View Archived Expenses</a>


<table>
    <tr>
        <th>Expense</th>
        <th>Paid By</th>
        <th>Total</th>
        <th>Who Owes</th>
        <th>Action</th>
    </tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['expense_name']) ?></td>
    <td><?= htmlspecialchars($row['paid_by']) ?></td>
    <td><?= number_format($row['amount'], 2) ?></td>
    <td><?= $row['breakdown'] ?: 'Fully settled' ?></td>
    <td>
        <form method="POST" action="../controllers/archiveExpense.php">
            <input type="hidden" name="expense_id" value="<?= $row['e_id'] ?>">
            <button class="danger">Archive</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
