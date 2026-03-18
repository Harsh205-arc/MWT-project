<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$_SESSION['house_id'] = 1; // TEMP until auth
$houseId = $_SESSION['house_id'];

/*
 Step 1: Get all unsettled splits (who owes whom)
*/
$sql = "
SELECT 
    es.user_id AS debtor_id,
    e.paid_by AS creditor_id,
    es.split AS amount,
    debtor.u_name AS debtor_name,
    creditor.u_name AS creditor_name
FROM expense_split es
JOIN expense e ON e.e_id = es.es_id
JOIN users debtor ON debtor.u_id = es.user_id
JOIN users creditor ON creditor.u_id = e.paid_by
WHERE e.house_id = ?
AND es.settled = 0
AND es.user_id != e.paid_by
AND e.archived = 0
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $houseId);
$stmt->execute();
$result = $stmt->get_result();

/*
 Step 2: Net balances
 balances[A][B] = A owes B
*/
$balances = [];

while ($row = $result->fetch_assoc()) {
    $debtor   = $row['debtor_name'];
    $creditor = $row['creditor_name'];
    $amount   = (float)$row['amount'];

    if (!isset($balances[$debtor][$creditor])) {
        $balances[$debtor][$creditor] = 0;
    }

    $balances[$debtor][$creditor] += $amount;
}

/*
 Step 3: Net opposite directions
*/
$final = [];

foreach ($balances as $from => $targets) {
    foreach ($targets as $to => $amount) {
        $reverse = $balances[$to][$from] ?? 0;
        $net = $amount - $reverse;

        if ($net > 0) {
            $final[] = [
                'from' => $from,
                'to' => $to,
                'amount' => $net
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Final Balances</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body>

<h1>Final Balances (Who Owes Whom)</h1>

<a class="btn" href="expenses.php">← Back to Expenses</a>

<table>
    <tr>
        <th>Owes</th>
        <th>To</th>
        <th>Amount</th>
    </tr>

<?php if (empty($final)): ?>
<tr>
    <td colspan="3">All expenses are settled 🎉</td>
</tr>
<?php else: ?>
<?php foreach ($final as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['from']) ?></td>
    <td><?= htmlspecialchars($row['to']) ?></td>
    <td><?= number_format($row['amount'], 2) ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>

</table>

</body>
</html>
