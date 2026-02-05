<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$_SESSION['house_id'] = 1; // TEMP
$houseId = $_SESSION['house_id'];

/*
  Fetch archived expenses
*/
$expenseSql = "
    SELECT 
        e.e_id,
        e.name,
        e.amount,
        u.u_name AS paid_by
    FROM expense e
    JOIN users u ON u.u_id = e.paid_by
    WHERE e.house_id = ?
      AND e.archived = 1
    ORDER BY e.created_at DESC
";

$stmt = $conn->prepare($expenseSql);
$stmt->bind_param("i", $houseId);
$stmt->execute();
$expenses = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Archived Expenses</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body>

<h1>Archived Expenses</h1>

<a class="btn" href="expenses.php">← Back to Active Expenses</a>

<table>
    <tr>
        <th>Expense</th>
        <th>Paid By</th>
        <th>Total</th>
        <th>Who Owed</th>
    </tr>

<?php if ($expenses->num_rows === 0): ?>
<tr>
    <td colspan="4">No archived expenses</td>
</tr>
<?php endif; ?>

<?php while ($exp = $expenses->fetch_assoc()): ?>

<?php
    // Fetch splits for this expense
    $splitSql = "
        SELECT users.u_name, expense_split.split
        FROM expense_split
        JOIN users ON users.u_id = expense_split.user_id
        WHERE expense_split.es_id = ?
    ";
    $s = $conn->prepare($splitSql);
    $s->bind_param("i", $exp['e_id']);
    $s->execute();
    $splits = $s->get_result();

    $breakdown = [];
    while ($row = $splits->fetch_assoc()) {
        $breakdown[] = $row['u_name'] . " owes " . number_format($row['split'], 2);
    }
?>

<tr>
    <td><?= htmlspecialchars($exp['name']) ?></td>
    <td><?= htmlspecialchars($exp['paid_by']) ?></td>
    <td><?= number_format($exp['amount'], 2) ?></td>
    <td><?= implode(", ", $breakdown) ?></td>
</tr>

<?php endwhile; ?>

</table>

</body>
</html>
