<?php
// chores/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];

// Filters
$filterStatus   = $_GET['status'] ?? '';
$filterAssigned = $_GET['assigned'] ?? '';

$sql = "
    SELECT c.*, u.username as assignee_name, u.avatar_color as assignee_color,
           cu.username as creator_name
    FROM chores c
    LEFT JOIN users u  ON u.id  = c.assigned_to
    LEFT JOIN users cu ON cu.id = c.created_by
    WHERE c.house_id = ?
";
$params = [$houseId];

if ($filterStatus) {
    $sql .= " AND c.status = ?";
    $params[] = $filterStatus;
}
if ($filterAssigned) {
    $sql .= " AND c.assigned_to = ?";
    $params[] = $filterAssigned;
}

$sql .= " ORDER BY c.is_archived ASC, c.due_date ASC, c.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$chores = $stmt->fetchAll();
$members = getHouseMembers($houseId);

$pageTitle = 'Chores';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>✅ Chores</h1>
        <p>Manage and track your house tasks</p>
    </div>
    <div class="page-header-actions">
        <a href="/roomatehub/chores/chores-create.php" class="btn btn-primary">+ Add Chore</a>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending"     <?= $filterStatus === 'pending'     ? 'selected' : '' ?>>Pending</option>
            <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="complete"    <?= $filterStatus === 'complete'    ? 'selected' : '' ?>>Complete</option>
        </select>
        <select name="assigned" class="form-control" onchange="this.form.submit()">
            <option value="">All Members</option>
            <?php foreach ($members as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $filterAssigned == $m['id'] ? 'selected' : '' ?>>
                <?= e($m['username']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterStatus || $filterAssigned): ?>
        <a href="/roomatehub/chores/chores-list.php" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <label class="toggle-row" style="margin-left:auto">
        <span class="toggle-switch">
            <input type="checkbox" id="show-archived">
            <span class="toggle-slider"></span>
        </span>
        Show archived
    </label>
</div>

<!-- Chore Cards -->
<?php if (!$chores): ?>
<div class="empty-state">
    <div class="empty-state-icon">🧹</div>
    <h3>No chores found</h3>
    <p>Start by adding your first house chore.</p>
    <a href="/roomatehub/chores/chores-create.php" class="btn btn-primary">+ Add Chore</a>
</div>
<?php else: ?>
<div class="chore-grid">
    <?php foreach ($chores as $chore): ?>
    <div class="chore-card <?= $chore['is_archived'] ? 'row-archived' : '' ?>" style="<?= $chore['is_archived'] ? 'display:none' : '' ?>">
        <div class="chore-card-header">
            <span class="chore-card-title"><?= e($chore['title']) ?></span>
            <span class="badge badge-<?= e($chore['status']) ?>"><?= e(str_replace('_',' ',$chore['status'])) ?></span>
        </div>

        <?php if ($chore['description']): ?>
        <p class="text-muted text-sm"><?= e($chore['description']) ?></p>
        <?php endif; ?>

        <div class="chore-card-meta">
            <?php if ($chore['assignee_name']): ?>
            <span>👤 <?= e($chore['assignee_name']) ?></span>
            <?php else: ?>
            <span class="text-muted">Unassigned</span>
            <?php endif; ?>
            <?php if ($chore['due_date']): ?>
            · <span>📅 <?= formatDate($chore['due_date']) ?></span>
            <?php endif; ?>
            <?php if ($chore['recurrence'] !== 'none'): ?>
            · <span>🔄 <?= e(ucfirst($chore['recurrence'])) ?></span>
            <?php endif; ?>
        </div>

        <div class="chore-card-actions">
            <a href="/roomatehub/chores/chores-edit.php?id=<?= $chore['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
            <?php if (!$chore['is_archived']): ?>
            <a href="/roomatehub/chores/chores-archive.php?id=<?= $chore['id'] ?>"
               class="btn btn-ghost btn-sm"
               data-confirm="Archive this chore? It won't be deleted.">📦 Archive</a>
            <?php else: ?>
            <a href="/roomatehub/chores/chores-archive.php?id=<?= $chore['id'] ?>&restore=1"
               class="btn btn-ghost btn-sm">↩️ Restore</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
