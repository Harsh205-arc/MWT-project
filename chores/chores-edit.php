<?php
// chores/chores-edit.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);
$members = getHouseMembers($houseId);
$errors  = [];

// Fetch the chore — must belong to this house
$stmt = $db->prepare("SELECT * FROM chores WHERE id = ? AND house_id = ?");
$stmt->execute([$id, $houseId]);
$chore = $stmt->fetch();

if (!$chore) {
    setFlash('error', 'Chore not found.');
    redirect('/roomatehub/chores/chores-list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignedTo  = $_POST['assigned_to'] ?: null;
    $dueDate     = $_POST['due_date'] ?: null;
    $status      = $_POST['status'] ?? 'pending';
    $recurrence  = $_POST['recurrence'] ?? 'none';

    if (!$title) $errors[] = 'Chore title is required.';

    if (!$errors) {
        $upd = $db->prepare("
            UPDATE chores
            SET title=?, description=?, assigned_to=?, due_date=?, status=?, recurrence=?
            WHERE id=? AND house_id=?
        ");
        $upd->execute([$title, $description ?: null, $assignedTo, $dueDate, $status, $recurrence, $id, $houseId]);
        setFlash('success', 'Chore updated!');
        redirect('/roomatehub/chores/chores-list.php');
    }

    // Repopulate $chore from POST for re-render
    $chore = array_merge($chore, [
        'title' => $title, 'description' => $description,
        'assigned_to' => $assignedTo, 'due_date' => $dueDate,
        'status' => $status, 'recurrence' => $recurrence
    ]);
}

$pageTitle = 'Edit Chore';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>✏️ Edit Chore</h1>
        <p>Update the details for this task</p>
    </div>
    <a href="/roomatehub/chores/chores-list.php" class="btn btn-ghost">← Back</a>
</div>

<div style="max-width:580px">
    <div class="card">
        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
        <?php endforeach; ?>

        <?php if ($chore['is_archived']): ?>
        <div class="flash flash-warning">This chore is archived. You can still edit it.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="title">Chore Title *</label>
                <input class="form-control" type="text" id="title" name="title"
                       value="<?= e($chore['title']) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description">
<?= e($chore['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="assigned_to">Assign To</label>
                    <select class="form-control" id="assigned_to" name="assigned_to">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($chore['assigned_to'] == $m['id']) ? 'selected' : '' ?>>
                            <?= e($m['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input class="form-control" type="date" id="due_date" name="due_date"
                           value="<?= e($chore['due_date'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending"     <?= $chore['status'] === 'pending'     ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= $chore['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="complete"    <?= $chore['status'] === 'complete'    ? 'selected' : '' ?>>Complete</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="recurrence">Recurrence</label>
                    <select class="form-control" id="recurrence" name="recurrence">
                        <option value="none"    <?= $chore['recurrence'] === 'none'    ? 'selected' : '' ?>>Does not repeat</option>
                        <option value="daily"   <?= $chore['recurrence'] === 'daily'   ? 'selected' : '' ?>>Daily</option>
                        <option value="weekly"  <?= $chore['recurrence'] === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= $chore['recurrence'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="/roomatehub/chores/chores-list.php" class="btn btn-ghost">Cancel</a>
                <?php if (!$chore['is_archived']): ?>
                <a href="/roomatehub/chores/chores-archive.php?id=<?= $chore['id'] ?>"
                   class="btn btn-ghost"
                   data-confirm="Archive this chore?"
                   style="margin-left:auto">📦 Archive</a>
                <?php else: ?>
                <a href="/roomatehub/chores/chores-archive.php?id=<?= $chore['id'] ?>&restore=1"
                   class="btn btn-ghost" style="margin-left:auto">↩️ Restore</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
