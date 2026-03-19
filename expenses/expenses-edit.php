<?php
// expenses/expenses-edit.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);
$members = getHouseMembers($houseId);
$errors  = [];
$categories = ['general','food','utilities','rent','groceries','transport','entertainment','other'];

// Fetch expense — must belong to this house
$stmt = $db->prepare("SELECT * FROM expenses WHERE id = ? AND house_id = ?");
$stmt->execute([$id, $houseId]);
$expense = $stmt->fetch();

if (!$expense) {
    setFlash('error', 'Expense not found.');
    redirect('/roomatehub/expenses/expenses-list.php');
}

// Fetch existing splits
$splitStmt = $db->prepare("SELECT * FROM expense_splits WHERE expense_id = ?");
$splitStmt->execute([$id]);
$existingSplits = [];
foreach ($splitStmt->fetchAll() as $s) {
    $existingSplits[$s['user_id']] = $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $totalAmount = (float)($_POST['total_amount'] ?? 0);
    $paidBy      = (int)($_POST['paid_by'] ?? 0);
    $splitType   = $_POST['split_type'] ?? 'equal';
    $category    = $_POST['category'] ?? 'general';
    $expDate     = $_POST['expense_date'] ?? date('Y-m-d');
    $splits      = $_POST['splits'] ?? [];

    if (!$title)           $errors[] = 'Expense title is required.';
    if ($totalAmount <= 0) $errors[] = 'Amount must be greater than zero.';
    if (!$paidBy)          $errors[] = 'Please select who paid.';
    if (!$expDate)         $errors[] = 'Expense date is required.';

    $splitData = [];

    if (!$errors) {
        if ($splitType === 'equal') {
            $share     = round($totalAmount / count($members), 2);
            $remainder = round($totalAmount - ($share * count($members)), 2);
            foreach ($members as $i => $m) {
                $amt = $share + ($i === 0 ? $remainder : 0);
                $splitData[$m['id']] = ['amount' => round($amt, 2), 'percentage' => null];
            }

        } elseif ($splitType === 'custom') {
            $sumCheck = 0;
            foreach ($members as $m) {
                $amt = round((float)($splits[$m['id']] ?? 0), 2);
                $splitData[$m['id']] = ['amount' => $amt, 'percentage' => null];
                $sumCheck += $amt;
            }
            if (abs($sumCheck - $totalAmount) > 0.02) {
                $errors[] = 'Custom amounts must add up to ' . formatMoney($totalAmount) . '. Currently: ' . formatMoney($sumCheck);
            }

        } elseif ($splitType === 'percentage') {
            $pctSum = 0;
            foreach ($members as $m) {
                $pct    = round((float)($splits[$m['id']] ?? 0), 2);
                $pctSum += $pct;
                $amt    = round($totalAmount * ($pct / 100), 2);
                $splitData[$m['id']] = ['amount' => $amt, 'percentage' => $pct];
            }
            if (abs($pctSum - 100) > 0.1) {
                $errors[] = "Percentages must add up to 100%. Currently: {$pctSum}%";
            }
        }
    }

    if (!$errors) {
        // Update expense
        $upd = $db->prepare("
            UPDATE expenses
            SET title=?, description=?, total_amount=?, paid_by=?, split_type=?, category=?, expense_date=?
            WHERE id=? AND house_id=?
        ");
        $upd->execute([$title, $description ?: null, $totalAmount, $paidBy, $splitType, $category, $expDate, $id, $houseId]);

        // Delete old splits and re-insert
        $db->prepare("DELETE FROM expense_splits WHERE expense_id = ?")->execute([$id]);
        foreach ($splitData as $memberId => $data) {
            $ins = $db->prepare("INSERT INTO expense_splits (expense_id, user_id, amount_owed, percentage) VALUES (?, ?, ?, ?)");
            $ins->execute([$id, $memberId, $data['amount'], $data['percentage']]);
        }

        setFlash('success', 'Expense updated successfully!');
        redirect('/roomatehub/expenses/expenses-list.php');
    }

    // Repopulate from POST on error
    $expense = array_merge($expense, [
        'title'        => $title,       'description'  => $description,
        'total_amount' => $totalAmount, 'paid_by'      => $paidBy,
        'split_type'   => $splitType,   'category'     => $category,
        'expense_date' => $expDate,
    ]);
    // Also repopulate existingSplits from POST
    foreach ($members as $m) {
        $existingSplits[$m['id']] = ['amount_owed' => $splits[$m['id']] ?? '', 'percentage' => $splits[$m['id']] ?? ''];
    }
}

$pageTitle = 'Edit Expense';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>✏️ Edit Expense</h1>
        <p>Update the details and split for this expense</p>
    </div>
    <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost">← Back</a>
</div>

<div style="max-width:640px">
    <div class="card">
        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
        <?php endforeach; ?>

        <?php if ($expense['is_archived']): ?>
        <div class="flash flash-warning">This expense is archived. Saving will keep it archived.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="title">Expense Title *</label>
                <input class="form-control" type="text" id="title" name="title"
                       value="<?= e($expense['title']) ?>" required autofocus>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="total_amount">Total Amount (₹) *</label>
                    <input class="form-control" type="number" id="total_amount" name="total_amount"
                           value="<?= e($expense['total_amount']) ?>" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="expense_date">Date *</label>
                    <input class="form-control" type="date" id="expense_date" name="expense_date"
                           value="<?= e($expense['expense_date']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="paid_by">Paid By *</label>
                    <select class="form-control" id="paid_by" name="paid_by" required>
                        <option value="">— Select —</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($expense['paid_by'] == $m['id']) ? 'selected' : '' ?>>
                            <?= e($m['username']) ?><?= $m['id'] == $userId ? ' (You)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select class="form-control" id="category" name="category">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($expense['category'] === $cat) ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Notes (optional)</label>
                <textarea class="form-control" id="description" name="description"><?= e($expense['description'] ?? '') ?></textarea>
            </div>

            <hr class="divider">

            <!-- Split Builder -->
            <div id="split-section">
                <div class="form-label" style="margin-bottom:10px">How to split?</div>
                <input type="hidden" name="split_type" id="split_type" value="<?= e($expense['split_type']) ?>">

                <div class="split-tabs">
                    <button type="button" class="split-tab <?= $expense['split_type'] === 'equal'      ? 'active' : '' ?>" data-mode="equal">⚖️ Split Equally</button>
                    <button type="button" class="split-tab <?= $expense['split_type'] === 'custom'     ? 'active' : '' ?>" data-mode="custom">✏️ Custom Amounts</button>
                    <button type="button" class="split-tab <?= $expense['split_type'] === 'percentage' ? 'active' : '' ?>" data-mode="percentage">% By Percentage</button>
                </div>

                <div id="split-members">
                    <?php foreach ($members as $m):
                        $existingAmt = $existingSplits[$m['id']]['amount_owed']  ?? '';
                        $existingPct = $existingSplits[$m['id']]['percentage']   ?? '';
                        // Pre-fill value depending on current split mode
                        $prefillVal  = ($expense['split_type'] === 'percentage') ? $existingPct : $existingAmt;
                    ?>
                    <div class="split-member-row">
                        <div class="split-member-name">
                            <span class="avatar-small" style="background:<?= e($m['avatar_color'] ?? '#7c3aed') ?>">
                                <?= getInitials($m['username']) ?>
                            </span>
                            &nbsp;<?= e($m['username']) ?><?= $m['id'] == $userId ? ' <span class="text-muted text-sm">(you)</span>' : '' ?>
                        </div>
                        <span class="split-unit"><?= $expense['split_type'] === 'percentage' ? '%' : '₹' ?></span>
                        <input type="number"
                               class="split-input"
                               name="splits[<?= $m['id'] ?>]"
                               value="<?= e($prefillVal) ?>"
                               step="0.01" min="0">
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="split-total-bar" class="split-total-bar">Calculating...</div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost">Cancel</a>
                <?php if (!$expense['is_archived']): ?>
                <a href="/roomatehub/expenses/expenses-archive.php?id=<?= $expense['id'] ?>"
                   class="btn btn-ghost" data-confirm="Archive this expense?"
                   style="margin-left:auto">📦 Archive</a>
                <?php else: ?>
                <a href="/roomatehub/expenses/expenses-archive.php?id=<?= $expense['id'] ?>&restore=1"
                   class="btn btn-ghost" style="margin-left:auto">↩️ Restore</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
