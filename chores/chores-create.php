<?php
// chores/chores-create.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$members = getHouseMembers($houseId);
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignedTo  = $_POST['assigned_to'] ?: null;
    $dueDate     = $_POST['due_date'] ?: null;
    $status      = $_POST['status'] ?? 'pending';
    $recurrence  = $_POST['recurrence'] ?? 'none';

    if (!$title) $errors[] = 'Chore title is required.';

    if (!$errors) {
        $stmt = $db->prepare("
            INSERT INTO chores (house_id, title, description, assigned_to, due_date, status, recurrence, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$houseId, $title, $description ?: null, $assignedTo, $dueDate, $status, $recurrence, $userId]);
        setFlash('success', 'Chore added successfully!');
        redirect('/roomatehub/chores/chores-list.php');
    }
}

$pageTitle = 'Add Chore';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>➕ Add Chore</h1>
        <p>Create a new task for your house</p>
    </div>
    <a href="/roomatehub/chores/chores-list.php" class="btn btn-ghost">← Back</a>
</div>

<div style="max-width:580px">
    <div class="card">
        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="title">Chore Title *</label>
                <input class="form-control" type="text" id="title" name="title"
                       value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Take out the bins" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description"
                          placeholder="Optional details..."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="assigned_to">Assign To</label>
                    <select class="form-control" id="assigned_to" name="assigned_to">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"
                                <?= (($_POST['assigned_to'] ?? '') == $m['id']) ? 'selected' : '' ?>>
                            <?= e($m['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input class="form-control" type="date" id="due_date" name="due_date"
                           value="<?= e($_POST['due_date'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending"     <?= (($_POST['status'] ?? 'pending') === 'pending')     ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= (($_POST['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="complete"    <?= (($_POST['status'] ?? '') === 'complete')    ? 'selected' : '' ?>>Complete</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="recurrence">Recurrence</label>
                    <select class="form-control" id="recurrence" name="recurrence">
                        <option value="none"    <?= (($_POST['recurrence'] ?? 'none') === 'none')    ? 'selected' : '' ?>>Does not repeat</option>
                        <option value="daily"   <?= (($_POST['recurrence'] ?? '') === 'daily')   ? 'selected' : '' ?>>Daily</option>
                        <option value="weekly"  <?= (($_POST['recurrence'] ?? '') === 'weekly')  ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= (($_POST['recurrence'] ?? '') === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary">✅ Save Chore</button>
                <a href="/roomatehub/chores/chores-list.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
