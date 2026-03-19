<?php
// expenses/expenses-create.php
require_once __DIR__ . '/../includes/helpers.php';
requireHouse();
startSession();

$db      = getDB();
$houseId = $_SESSION['house_id'];
$userId  = $_SESSION['user_id'];
$members = getHouseMembers($houseId);
$errors  = [];
$categories = ['general','food','utilities','rent','groceries','transport','entertainment','other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $totalAmount = (float)($_POST['total_amount'] ?? 0);
    $paidBy      = (int)($_POST['paid_by'] ?? 0);
    $splitType   = $_POST['split_type'] ?? 'equal';
    $category    = $_POST['category'] ?? 'general';
    $expDate     = $_POST['expense_date'] ?? date('Y-m-d');
    $splits      = $_POST['splits'] ?? [];      // [user_id => amount] for custom/percentage

    if (!$title)          $errors[] = 'Expense title is required.';
    if ($totalAmount <= 0) $errors[] = 'Amount must be greater than zero.';
    if (!$paidBy)         $errors[] = 'Please select who paid.';
    if (!$expDate)        $errors[] = 'Expense date is required.';

    // Validate splits
    $memberIds = array_column($members, 'id');
    $splitData = []; // [user_id => ['amount' => x, 'percentage' => y]]

    if (!$errors) {
        if ($splitType === 'equal') {
            $share = round($totalAmount / count($members), 2);
            $remainder = $totalAmount - ($share * count($members));
            foreach ($members as $i => $m) {
                $amt = $share + ($i === 0 ? $remainder : 0); // handle rounding remainder
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
                $errors[] = 'Custom split amounts must add up to the total (' . formatMoney($totalAmount) . '). Currently: ' . formatMoney($sumCheck);
            }

        } elseif ($splitType === 'percentage') {
            $pctSum = 0;
            foreach ($members as $m) {
                $pct = round((float)($splits[$m['id']] ?? 0), 2);
                $pctSum += $pct;
                $amt = round($totalAmount * ($pct / 100), 2);
                $splitData[$m['id']] = ['amount' => $amt, 'percentage' => $pct];
            }
            if (abs($pctSum - 100) > 0.1) {
                $errors[] = "Percentages must add up to 100%. Currently: {$pctSum}%";
            }
        }
    }

    if (!$errors) {
        // Insert expense
        $ins = $db->prepare("
            INSERT INTO expenses (house_id, title, description, total_amount, paid_by, split_type, category, expense_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$houseId, $title, $description ?: null, $totalAmount, $paidBy, $splitType, $category, $expDate, $userId]);
        $expenseId = $db->lastInsertId();

        // Insert splits
        foreach ($splitData as $memberId => $data) {
            $insSplit = $db->prepare("
                INSERT INTO expense_splits (expense_id, user_id, amount_owed, percentage)
                VALUES (?, ?, ?, ?)
            ");
            $insSplit->execute([$expenseId, $memberId, $data['amount'], $data['percentage']]);
        }

        setFlash('success', 'Expense added and split recorded!');
        redirect('/roomatehub/expenses/expenses-list.php');
    }
}

$pageTitle = 'Add Expense';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>➕ Add Expense</h1>
        <p>Log a shared cost and choose how to split it</p>
    </div>
    <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost">← Back</a>
</div>

<div style="max-width:640px">
    <div class="card">
        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <!-- Basic details -->
            <div class="form-group">
                <label class="form-label" for="title">Expense Title *</label>
                <input class="form-control" type="text" id="title" name="title"
                       value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Monthly electricity bill" required autofocus>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="total_amount">Total Amount (₹) *</label>
                    <input class="form-control" type="number" id="total_amount" name="total_amount"
                           value="<?= e($_POST['total_amount'] ?? '') ?>"
                           placeholder="0.00" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="expense_date">Date *</label>
                    <input class="form-control" type="date" id="expense_date" name="expense_date"
                           value="<?= e($_POST['expense_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="paid_by">Paid By *</label>
                    <select class="form-control" id="paid_by" name="paid_by" required>
                        <option value="">— Select —</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"
                                <?= (($_POST['paid_by'] ?? $userId) == $m['id']) ? 'selected' : '' ?>>
                            <?= e($m['username']) ?><?= $m['id'] == $userId ? ' (You)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select class="form-control" id="category" name="category">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= (($_POST['category'] ?? 'general') === $cat) ? 'selected' : '' ?>>
                            <?= ucfirst($cat) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Notes (optional)</label>
                <textarea class="form-control" id="description" name="description"
                          placeholder="Any extra info..."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <hr class="divider">

            <!-- Split Builder -->
            <div id="split-section">
                <div class="form-label" style="margin-bottom:10px">How to split?</div>
                <input type="hidden" name="split_type" id="split_type" value="<?= e($_POST['split_type'] ?? 'equal') ?>">

                <div class="split-tabs">
                    <button type="button" class="split-tab <?= (($_POST['split_type'] ?? 'equal') === 'equal') ? 'active' : '' ?>"
                            data-mode="equal">⚖️ Split Equally</button>
                    <button type="button" class="split-tab <?= (($_POST['split_type'] ?? '') === 'custom') ? 'active' : '' ?>"
                            data-mode="custom">✏️ Custom Amounts</button>
                    <button type="button" class="split-tab <?= (($_POST['split_type'] ?? '') === 'percentage') ? 'active' : '' ?>"
                            data-mode="percentage">% By Percentage</button>
                </div>

                <div id="split-members">
                    <?php foreach ($members as $m): ?>
                    <div class="split-member-row">
                        <div class="split-member-name">
                            <span class="avatar-small" style="background:<?= e($m['avatar_color'] ?? '#7c3aed') ?>">
                                <?= getInitials($m['username']) ?>
                            </span>
                            &nbsp;<?= e($m['username']) ?><?= $m['id'] == $userId ? ' <span class="text-muted text-sm">(you)</span>' : '' ?>
                        </div>
                        <span class="split-unit">₹</span>
                        <input type="number"
                               class="split-input"
                               name="splits[<?= $m['id'] ?>]"
                               value="<?= e($_POST['splits'][$m['id']] ?? '') ?>"
                               step="0.01" min="0">
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="split-total-bar" class="split-total-bar">
                    Calculating...
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px">
                <button type="submit" class="btn btn-primary">💾 Save Expense</button>
                <a href="/roomatehub/expenses/expenses-list.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
