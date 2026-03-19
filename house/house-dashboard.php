<?php
// house/dashboard.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];

// Stats
$choreStats = $db->prepare("
    SELECT
        COUNT(*) as total,
        SUM(status = 'complete') as done,
        SUM(status = 'pending') as pending,
        SUM(status = 'in_progress') as inprogress
    FROM chores WHERE house_id = ? AND is_archived = 0
");
$choreStats->execute([$houseId]);
$cs = $choreStats->fetch();

$expenseStats = $db->prepare("
    SELECT COUNT(*) as total, SUM(total_amount) as sum
    FROM expenses WHERE house_id = ? AND is_archived = 0
");
$expenseStats->execute([$houseId]);
$es = $expenseStats->fetch();

// How much does current user owe / is owed
$balances = calculateBalances($houseId);
$iOwe   = 0;
$iAmOwed = 0;
foreach ($balances as $owerId => $payers) {
    foreach ($payers as $payerId => $amt) {
        if ($owerId == $userId)  $iOwe    += $amt;
        if ($payerId == $userId) $iAmOwed += $amt;
    }
}

// Recent chores (5)
$recentChores = $db->prepare("
    SELECT c.*, u.username as assignee_name
    FROM chores c
    LEFT JOIN users u ON u.id = c.assigned_to
    WHERE c.house_id = ? AND c.is_archived = 0
    ORDER BY c.created_at DESC LIMIT 5
");
$recentChores->execute([$houseId]);
$recentChoresList = $recentChores->fetchAll();

// Recent expenses (5)
$recentExp = $db->prepare("
    SELECT e.*, u.username as payer_name
    FROM expenses e
    JOIN users u ON u.id = e.paid_by
    WHERE e.house_id = ? AND e.is_archived = 0
    ORDER BY e.created_at DESC LIMIT 5
");
$recentExp->execute([$houseId]);
$recentExpList = $recentExp->fetchAll();

// Members
$members = getHouseMembers($houseId);
$house   = getCurrentHouse();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>👋 Welcome to <?= e($house['name']) ?></h1>
        <p>Here's what's happening in your house today</p>
    </div>
    <div class="page-header-actions">
        <a href="/roomatehub/chores/chores-create.php"  class="btn btn-outline">+ Add Chore</a>
        <a href="/roomatehub/expenses/expenses-create.php" class="btn btn-primary">+ Add Expense</a>
    </div>
</div>

<!-- Stat cards -->
<div class="card-grid">
    <div class="stat-card">
        <span class="stat-icon">✅</span>
        <span class="stat-label">Active Chores</span>
        <span class="stat-value"><?= (int)$cs['total'] ?></span>
        <span class="stat-sub"><?= (int)$cs['done'] ?> done · <?= (int)$cs['pending'] ?> pending</span>
    </div>
    <div class="stat-card">
        <span class="stat-icon">💸</span>
        <span class="stat-label">Total Expenses</span>
        <span class="stat-value"><?= formatMoney($es['sum'] ?? 0) ?></span>
        <span class="stat-sub"><?= (int)$es['total'] ?> expense(s) logged</span>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📤</span>
        <span class="stat-label">You Owe</span>
        <span class="stat-value text-danger"><?= formatMoney($iOwe) ?></span>
        <span class="stat-sub">Across all expenses</span>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📥</span>
        <span class="stat-label">You're Owed</span>
        <span class="stat-value text-success"><?= formatMoney($iAmOwed) ?></span>
        <span class="stat-sub">Waiting to be settled</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

    <!-- Recent Chores -->
    <div class="card">
        <div class="flex items-center justify-between mb-16">
            <span class="card-title" style="margin:0">Recent Chores</span>
            <a href="/roomatehub/chores/chores-list.php" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php if (!$recentChoresList): ?>
            <p class="text-muted text-sm">No chores yet. <a href="/roomatehub/chores/chores-create.php">Add one!</a></p>
        <?php else: ?>
            <?php foreach ($recentChoresList as $c): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <div>
                    <div style="font-size:0.875rem;font-weight:500"><?= e($c['title']) ?></div>
                    <div class="text-muted text-sm">
                        <?= $c['assignee_name'] ? 'Assigned to ' . e($c['assignee_name']) : 'Unassigned' ?>
                        <?= $c['due_date'] ? ' · Due ' . formatDate($c['due_date']) : '' ?>
                    </div>
                </div>
                <span class="badge badge-<?= e($c['status']) ?>"><?= e(str_replace('_', ' ', $c['status'])) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Expenses -->
    <div class="card">
        <div class="flex items-center justify-between mb-16">
            <span class="card-title" style="margin:0">Recent Expenses</span>
            <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php if (!$recentExpList): ?>
            <p class="text-muted text-sm">No expenses yet. <a href="/roomatehub/expenses/expenses-create.php">Add one!</a></p>
        <?php else: ?>
            <?php foreach ($recentExpList as $exp): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                <div>
                    <div style="font-size:0.875rem;font-weight:500"><?= e($exp['title']) ?></div>
                    <div class="text-muted text-sm">Paid by <?= e($exp['payer_name']) ?> · <?= formatDate($exp['expense_date']) ?></div>
                </div>
                <span style="font-weight:700;color:var(--purple)"><?= formatMoney($exp['total_amount']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Members -->
<div class="card">
    <div class="flex items-center justify-between mb-16">
        <span class="card-title" style="margin:0">Housemates (<?= count($members) ?>)</span>
        <span class="text-muted text-sm">Invite code: <strong style="font-family:var(--font-mono);color:var(--purple)"><?= e($house['invite_code']) ?></strong></span>
    </div>
    <div class="members-list">
        <?php foreach ($members as $m): ?>
        <div class="member-row">
            <span class="avatar" style="background:<?= e($m['avatar_color'] ?? '#7c3aed') ?>">
                <?= getInitials($m['username']) ?>
            </span>
            <div class="member-info">
                <div class="member-name"><?= e($m['username']) ?></div>
                <div class="member-email"><?= e($m['email']) ?></div>
            </div>
            <span class="member-role"><?= e($m['role']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
