<?php
require_once __DIR__ . '/../config/db.php';

/*
 STEP 1: Fetch all expenses + splits
*/
$sql = "
SELECT 
    e.e_id,
    e.name AS expense,
    e.amount AS total,
    payer.u_name AS paid_by,
    u.u_name AS user,
    es.split
FROM expense e
JOIN users payer ON e.paid_by = payer.u_id
JOIN expense_split es ON es.es_id = e.e_id
JOIN users u ON es.user_id = u.u_id
ORDER BY e.e_id
";

$res = $conn->query($sql);

$expenses = [];
$balances = []; // who owes whom

while ($r = $res->fetch_assoc()) {
    $id = $r['e_id'];

    if (!isset($expenses[$id])) {
        $expenses[$id] = [
            'expense' => $r['expense'],
            'total' => $r['total'],
            'paid_by' => $r['paid_by'],
            'splits' => []
        ];
    }

    $expenses[$id]['splits'][] = [
        'user' => $r['user'],
        'amount' => $r['split']
    ];

    // Balance calculation
    if ($r['user'] !== $r['paid_by']) {
        $key = $r['user'] . ' -> ' . $r['paid_by'];
        $balances[$key] = ($balances[$key] ?? 0) + $r['split'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Roommate Expense Clarity</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f7f3ff;
    padding: 40px;
}

h1, h2 {
    color: #5b2aa8;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    margin-bottom: 40px;
}

th {
    background: #5b2aa8;
    color: white;
    padding: 12px;
    text-align: left;
}

td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

tr:nth-child(even) {
    background: #f2ecff;
}

.note {
    color: #444;
    font-size: 14px;
}
</style>
</head>

<body>

<h1>Expense Summary</h1>

<table>
<tr>
    <th>Expense</th>
    <th>Paid By</th>
    <th>Total</th>
    <th>Who Owes</th>
</tr>

<?php foreach ($expenses as $e): ?>
<tr>
    <td><?= $e['expense'] ?></td>
    <td><?= $e['paid_by'] ?></td>
    <td><?= number_format($e['total'], 2) ?></td>
    <td>
        <?php
        $all_paid = true;
        foreach ($e['splits'] as $s) {
            if ($s['user'] !== $e['paid_by']) {
                echo "{$s['user']} owes {$e['paid_by']} " . number_format($s['amount'],2) . "<br>";
                $all_paid = false;
            }
        }
        if ($all_paid) echo "Fully settled";
        ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<h2>Final Balances (Who Owes Whom)</h2>

<table>
<tr>
    <th>Owes</th>
    <th>To</th>
    <th>Amount</th>
</tr>

<?php foreach ($balances as $k => $amt): 
    [$from, $to] = explode(' -> ', $k);
?>
<tr>
    <td><?= $from ?></td>
    <td><?= $to ?></td>
    <td><?= number_format($amt,2) ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
