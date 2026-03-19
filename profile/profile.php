<?php
// profile/index.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
startSession();

$db     = getDB();
$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch latest user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch house & membership info
$houseInfo = null;
$memberSince = null;
if (!empty($_SESSION['house_id'])) {
    $hStmt = $db->prepare("
        SELECT h.name, h.invite_code, hm.role, hm.joined_at
        FROM houses h
        JOIN house_members hm ON hm.house_id = h.id
        WHERE h.id = ? AND hm.user_id = ?
    ");
    $hStmt->execute([$_SESSION['house_id'], $userId]);
    $houseInfo = $hStmt->fetch();
}

// Personal expense summary
$myExpenses = $db->prepare("
    SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total
    FROM expenses
    WHERE paid_by = ? AND is_archived = 0
");
$myExpenses->execute([$userId]);
$expSummary = $myExpenses->fetch();

// My chores summary
$myChores = $db->prepare("
    SELECT COUNT(*) as total,
           SUM(status = 'complete') as done,
           SUM(status = 'pending') as pending
    FROM chores WHERE assigned_to = ? AND is_archived = 0
");
$myChores->execute([$userId]);
$choreSummary = $myChores->fetch();

// Available avatar colours
$avatarColors = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#0891b2','#db2777','#65a30d'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $color    = $_POST['avatar_color'] ?? $user['avatar_color'];

        if (!$username || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (!in_array($color, $avatarColors)) $color = $user['avatar_color'];

        if (!$errors) {
            // Check uniqueness (excluding self)
            $chk = $db->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $chk->execute([$email, $username, $userId]);
            if ($chk->fetch()) {
                $errors[] = 'That email or username is already in use.';
            } else {
                $db->prepare("UPDATE users SET username=?, email=?, avatar_color=? WHERE id=?")
                   ->execute([$username, $email, $color, $userId]);
                $_SESSION['username'] = $username;
                setFlash('success', 'Profile updated successfully!');
                redirect('/roomatehub/profile/profile.php');
            }
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current)             $errors[] = 'Current password is required.';
        if (strlen($new) < 6)      $errors[] = 'New password must be at least 6 characters.';
        if ($new !== $confirm)     $errors[] = 'New passwords do not match.';

        if (!$errors) {
            if (!password_verify($current, $user['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $userId]);
                setFlash('success', 'Password changed successfully!');
                redirect('/roomatehub/profile/profile.php');
            }
        }
    }

    // Re-fetch user in case something changed
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>👤 My Profile</h1>
        <p>Manage your account details and preferences</p>
    </div>
</div>

<?php foreach ($errors as $err): ?>
<div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
<?php endforeach; ?>

<!-- Stats row -->
<div class="card-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <span class="stat-icon">💸</span>
        <span class="stat-label">Expenses Paid</span>
        <span class="stat-value"><?= (int)$expSummary['cnt'] ?></span>
        <span class="stat-sub"><?= formatMoney($expSummary['total']) ?> total</span>
    </div>
    <div class="stat-card">
        <span class="stat-icon">✅</span>
        <span class="stat-label">Chores Assigned</span>
        <span class="stat-value"><?= (int)$choreSummary['total'] ?></span>
        <span class="stat-sub"><?= (int)$choreSummary['done'] ?> done · <?= (int)$choreSummary['pending'] ?> pending</span>
    </div>
    <?php if ($houseInfo): ?>
    <div class="stat-card">
        <span class="stat-icon">🏠</span>
        <span class="stat-label">House</span>
        <span class="stat-value" style="font-size:1.1rem"><?= e($houseInfo['name']) ?></span>
        <span class="stat-sub"><?= ucfirst($houseInfo['role']) ?> · Joined <?= formatDate($houseInfo['joined_at']) ?></span>
    </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    <!-- Edit Profile -->
    <div class="card">
        <div class="card-title">Edit Profile</div>

        <!-- Avatar preview -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
            <span class="avatar avatar-lg" id="avatar-preview" style="background:<?= e($user['avatar_color']) ?>">
                <?= getInitials($user['username']) ?>
            </span>
            <div>
                <div style="font-size:0.8rem;font-weight:600;margin-bottom:6px">Avatar Colour</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php foreach ($avatarColors as $col): ?>
                    <label style="cursor:pointer">
                        <input type="radio" name="avatar_color_preview" value="<?= $col ?>"
                               <?= ($user['avatar_color'] === $col) ? 'checked' : '' ?>
                               onchange="document.getElementById('avatar-preview').style.background='<?= $col ?>';
                                         document.getElementById('avatar_color_field').value='<?= $col ?>'"
                               style="display:none">
                        <span style="display:inline-block;width:24px;height:24px;border-radius:50%;background:<?= $col ?>;
                                     border:2px solid <?= ($user['avatar_color'] === $col) ? '#1e1b2e' : 'transparent' ?>;
                                     transition:border-color 0.15s"></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="avatar_color" id="avatar_color_field" value="<?= e($user['avatar_color']) ?>">

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username"
                       value="<?= e($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email"
                       value="<?= e($user['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-title">Change Password</div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input class="form-control" type="password" id="current_password" name="current_password"
                       placeholder="Enter current password">
            </div>
            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input class="form-control" type="password" id="new_password" name="new_password"
                       placeholder="Min. 6 characters">
            </div>
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password"
                       placeholder="Repeat new password">
            </div>
            <button type="submit" class="btn btn-primary">🔒 Change Password</button>
        </form>

        <?php if ($houseInfo): ?>
        <hr class="divider">
        <div class="card-title" style="margin-bottom:8px">House Invite Code</div>
        <p class="text-muted text-sm" style="margin-bottom:10px">Share this with your roommates so they can join.</p>
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-family:var(--font-mono);font-size:1.4rem;font-weight:700;
                         color:var(--purple);letter-spacing:0.15em;background:var(--purple-light);
                         padding:10px 16px;border-radius:var(--radius-sm)">
                <?= e($houseInfo['invite_code']) ?>
            </span>
            <button onclick="navigator.clipboard.writeText('<?= e($houseInfo['invite_code']) ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',2000)"
                    class="btn btn-outline btn-sm">Copy</button>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
