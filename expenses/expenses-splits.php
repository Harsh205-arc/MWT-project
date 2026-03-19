<?php
// expenses/expenses-splits.php — View detailed splits for a single expense
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT e.*, u.username as payer_name, u.avatar_color as payer_color
    FROM expenses e
    JOIN users u ON u.id = e.paid_by
    WHERE e.id = ? AND e.house_id = ?
");
$stmt->execute([$id, $houseId]);
$expense = $stmt->fetch();

if (!$expense) {
    setFlash('error', 'Expense not found.');
    redirect('/roomatehub/expenses/expenses-list.php');
}

// Get all splits with user info
$splitStmt = $db->prepare("
    SELECT es.*, u.username, u.avatar_color, u.email
    FROM expense_splits es
    JOIN users u ON u.id = es.user_id
    WHERE es.expense_id = ?
    ORDER BY es.amount_owed DESC
");
$splitStmt->execute([$id]);
$splits = $splitStmt->fetchAll();

$pageTitle = 'Expense Detail';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>🧾 <?= e($expense['title']) ?></h1>
        <p>Detailed split breakdown for this expense</p>
    </div>
    <div class="page-header-actions">
        <a href="/roomatehub/expenses/expenses-edit.php?id=<?= $expense['id'] ?>" class="btn btn-outline">✏️ Edit</a>
        <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost">← Back</a>
    </div>
</div>

<!-- Expense Summary -->
<div class="card mb-24">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px">
        <div>
            <div class="stat-label">Total Amount</div>
            <div style="font-size:1.8rem;font-weight:700;color:var(--purple)"><?= formatMoney($expense['total_amount']) ?></div>
        </div>
        <div>
            <div class="stat-label">Paid By</div>
            <div style="font-weight:600;margin-top:4px;display:flex;align-items:center;gap:8px">
                <span class="avatar-small" style="background:<?= e($expense['payer_color']) ?>"><?= getInitials($expense['payer_name']) ?></span>
                <?= e($expense['payer_name']) ?>
            </div>
        </div>
        <div>
            <div class="stat-label">Split Type</div>
            <div style="margin-top:4px"><span class="badge badge-<?= e($expense['split_type']) ?>"><?= ucfirst($expense['split_type']) ?></span></div>
        </div>
        <div>
            <div class="stat-label">Category</div>
            <div style="font-weight:500;margin-top:4px;text-transform:capitalize"><?= e($expense['category']) ?></div>
        </div>
        <div>
            <div class="stat-label">Date</div>
            <div style="font-weight:500;margin-top:4px"><?= formatDate($expense['expense_date']) ?></div>
        </div>
        <div>
            <div class="stat-label">Status</div>
            <div style="margin-top:4px">
                <?php if ($expense['is_archived']): ?>
                <span class="badge badge-archived">Archived</span>
                <?php else: ?>
                <span class="badge badge-complete">Active</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($expense['description']): ?>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);color:var(--text-muted);font-size:0.875rem">
        <?= e($expense['description']) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Splits Table -->
<div class="card">
    <div class="card-title">Individual Splits</div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Amount Owed</th>
                    <?php if ($expense['split_type'] === 'percentage'): ?>
                    <th>Percentage</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th>Settled At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalSettled   = 0;
                $totalUnsettled = 0;
                foreach ($splits as $split):
                    if ($split['is_settled']) $totalSettled   += $split['amount_owed'];
                    else                      $totalUnsettled += $split['amount_owed'];
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="avatar-small" style="background:<?= e($split['avatar_color']) ?>"><?= getInitials($split['username']) ?></span>
                            <div>
                                <div style="font-weight:500"><?= e($split['username']) ?><?= $split['user_id'] == $userId ? ' <span class="text-muted text-sm">(you)</span>' : '' ?></div>
                                <div class="text-muted text-sm"><?= e($split['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><strong><?= formatMoney($split['amount_owed']) ?></strong></td>
                    <?php if ($expense['split_type'] === 'percentage'): ?>
                    <td><?= $split['percentage'] !== null ? $split['percentage'] . '%' : '—' ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if ($split['is_settled']): ?>
                        <span class="badge badge-complete">✓ Settled</span>
                        <?php else: ?>
                        <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted text-sm">
                        <?= $split['settled_at'] ? formatDate($split['settled_at']) : '—' ?>
                    </td>
                    <td>
                        <a href="/roomatehub/expenses/expenses-settle.php?split_id=<?= $split['id'] ?>"
                           class="btn btn-ghost btn-sm"
                           data-confirm="<?= $split['is_settled'] ? 'Mark as unsettled?' : 'Mark this as settled?' ?>">
                            <?= $split['is_settled'] ? '↩️ Unsettle' : '✓ Settle' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary bar -->
    <div style="display:flex;gap:20px;padding:14px 16px;background:var(--bg);border-radius:var(--radius-sm);margin-top:16px;flex-wrap:wrap">
        <span class="text-sm"><span class="text-muted">Settled:</span> <strong class="text-success"><?= formatMoney($totalSettled) ?></strong></span>
        <span class="text-sm"><span class="text-muted">Outstanding:</span> <strong class="text-danger"><?= formatMoney($totalUnsettled) ?></strong></span>
        <span class="text-sm"><span class="text-muted">Total:</span> <strong><?= formatMoney($expense['total_amount']) ?></strong></span>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
