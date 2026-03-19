<?php
// expenses/expenses-settle.php — Mark a split as settled
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$splitId = (int)($_GET['split_id'] ?? 0);

// Verify the split belongs to an expense in this house
$stmt = $db->prepare("
    SELECT es.id, es.is_settled, e.house_id
    FROM expense_splits es
    JOIN expenses e ON e.id = es.expense_id
    WHERE es.id = ? AND e.house_id = ?
");
$stmt->execute([$splitId, $houseId]);
$split = $stmt->fetch();

if (!$split) {
    setFlash('error', 'Split record not found.');
} else {
    $newState = $split['is_settled'] ? 0 : 1;
    $settled_at = $newState ? date('Y-m-d H:i:s') : null;
    $db->prepare("UPDATE expense_splits SET is_settled = ?, settled_at = ? WHERE id = ?")
       ->execute([$newState, $settled_at, $splitId]);
    setFlash('success', $newState ? 'Marked as settled ✓' : 'Marked as unsettled.');
}

redirect('/roomatehub/expenses/expenses-list.php');
?>
