<?php
// chores/chores-archive.php — Soft delete handler (no HTML output)
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$id      = (int)($_GET['id'] ?? 0);
$restore = !empty($_GET['restore']);

$stmt = $db->prepare("SELECT id, is_archived FROM chores WHERE id = ? AND house_id = ?");
$stmt->execute([$id, $houseId]);
$chore = $stmt->fetch();

if (!$chore) {
    setFlash('error', 'Chore not found.');
} else {
    $newState = $restore ? 0 : 1;
    $upd = $db->prepare("UPDATE chores SET is_archived = ? WHERE id = ?");
    $upd->execute([$newState, $id]);
    setFlash('success', $restore ? 'Chore restored.' : 'Chore archived.');
}

redirect('/roomatehub/chores/chores-list.php');
?>
