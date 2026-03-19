<?php
// expenses/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$members = getHouseMembers($houseId);

// Filters
$filterCategory = $_GET['category'] ?? '';
$filterPaidBy   = $_GET['paid_by']  ?? '';

$sql = "
    SELECT e.*, u.username as payer_name
    FROM expenses e
    JOIN users u ON u.id = e.paid_by
    WHERE e.house_id = ?
";
$params = [$houseId];
if ($filterCategory) { $sql .= " AND e.category = ?"; $params[] = $filterCategory; }
if ($filterPaidBy)   { $sql .= " AND e.paid_by = ?";  $params[] = $filterPaidBy; }
$sql .= " ORDER BY e.is_archived ASC, e.expense_date DESC, e.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Balances
$balances = calculateBalances($houseId);

// Map user IDs to names
$memberMap = [];
foreach ($members as $m) $memberMap[$m['id']] = $m;

$categories = ['general','food','utilities','rent','groceries','transport','entertainment','other'];

$pageTitle = 'Expenses';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>💸 Expenses</h1>
        <p>Track and split shared costs with your housemates</p>
    </div>
    <div class="page-header-actions">
        <a href="/roomatehub/expenses/expenses-create.php" class="btn btn-primary">+ Add Expense</a>
    </div>
</div>

<!-- Balances -->
<?php
$hasBalances = false;
foreach ($balances as $owerId => $payers) {
    foreach ($payers as $payerId => $amt) {
        if ($amt > 0.005) { $hasBalances = true; break 2; }
    }
}
?>
<?php if ($hasBalances): ?>
<div class="card mb-24">
    <div class="card-title">💰 Who Owes What</div>
    <div class="balance-list">
        <?php foreach ($balances as $owerId => $payers): ?>
            <?php foreach ($payers as $payerId => $amt): ?>
                <?php if ($amt < 0.005) continue; ?>
                <?php
                    $owerName = $memberMap[$owerId]['username'] ?? 'Unknown';
                    $payerName = $memberMap[$payerId]['username'] ?? 'Unknown';
                    $isMe = ($owerId == $userId);
                    $owesMe = ($payerId == $userId);
                ?>
                <div class="balance-item <?= $isMe ? 'balance-owe' : ($owesMe ? 'balance-owed' : 'balance-settle') ?>">
                    <span>
                        <?php if ($isMe): ?>
                            <strong>You</strong> owe <strong><?= e($payerName) ?></strong>
                        <?php elseif ($owesMe): ?>
                            <strong><?= e($owerName) ?></strong> owes <strong>you</strong>
                        <?php else: ?>
                            <strong><?= e($owerName) ?></strong> owes <strong><?= e($payerName) ?></strong>
                        <?php endif; ?>
                    </span>
                    <span class="<?= $isMe ? 'amount-owe' : ($owesMe ? 'amount-owed' : '') ?>">
                        <?= formatMoney($amt) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <select name="category" class="form-control" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="paid_by" class="form-control" onchange="this.form.submit()">
            <option value="">All Members</option>
            <?php foreach ($members as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $filterPaidBy == $m['id'] ? 'selected' : '' ?>><?= e($m['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterCategory || $filterPaidBy): ?>
        <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost btn-sm">Clear</a>
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

<!-- Expenses Table -->
<?php if (!$expenses): ?>
<div class="empty-state">
    <div class="empty-state-icon">🧾</div>
    <h3>No expenses yet</h3>
    <p>Log your first shared expense to start tracking who owes what.</p>
    <a href="/roomatehub/expenses/expenses-create.php" class="btn btn-primary">+ Add Expense</a>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Paid By</th>
                    <th>Split</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $exp): ?>
                <tr class="<?= $exp['is_archived'] ? 'row-archived' : '' ?>">
                    <td>
                        <div style="font-weight:500">
                            <a href="/roomatehub/expenses/expenses-splits.php?id=<?= $exp['id'] ?>" style="color:var(--text);text-decoration:none" onmouseover="this.style.color='var(--purple)'" onmouseout="this.style.color='var(--text)'">
                                <?= e($exp['title']) ?>
                            </a>
                        </div>
                        <?php if ($exp['description']): ?>
                        <div class="text-muted text-sm"><?= e(substr($exp['description'], 0, 50)) ?><?= strlen($exp['description']) > 50 ? '…' : '' ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span style="text-transform:capitalize"><?= e($exp['category']) ?></span></td>
                    <td><strong><?= formatMoney($exp['total_amount']) ?></strong></td>
                    <td><?= e($exp['payer_name']) ?></td>
                    <td><span class="badge badge-<?= e($exp['split_type']) ?>"><?= e(ucfirst($exp['split_type'])) ?></span></td>
                    <td><?= formatDate($exp['expense_date']) ?></td>
                    <td>
                        <?php if ($exp['is_archived']): ?>
                        <span class="badge badge-archived">Archived</span>
                        <?php else: ?>
                        <span class="badge badge-complete">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="/roomatehub/expenses/expenses-edit.php?id=<?= $exp['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
                            <?php if (!$exp['is_archived']): ?>
                            <a href="/roomatehub/expenses/expenses-archive.php?id=<?= $exp['id'] ?>"
                               class="btn btn-ghost btn-sm"
                               data-confirm="Archive this expense?">📦</a>
                            <?php else: ?>
                            <a href="/roomatehub/expenses/expenses-archive.php?id=<?= $exp['id'] ?>&restore=1"
                               class="btn btn-ghost btn-sm">↩️</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
