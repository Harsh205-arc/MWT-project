<?php
// expenses/expenses-archive.php — Soft delete handler
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$id      = (int)($_GET['id'] ?? 0);
$restore = !empty($_GET['restore']);

$stmt = $db->prepare("SELECT id FROM expenses WHERE id = ? AND house_id = ?");
$stmt->execute([$id, $houseId]);
$expense = $stmt->fetch();

if (!$expense) {
    setFlash('error', 'Expense not found.');
} else {
    $newState = $restore ? 0 : 1;
    $db->prepare("UPDATE expenses SET is_archived = ? WHERE id = ?")->execute([$newState, $id]);
    setFlash('success', $restore ? 'Expense restored.' : 'Expense archived.');
}

redirect('/roomatehub/expenses/expenses-list.php');
?>
