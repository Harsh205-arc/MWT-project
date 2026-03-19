<?php
// house/join.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
startSession();

if (!empty($_SESSION['house_id'])) {
    redirect('/roomatehub/house/house-dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db   = getDB();
    $code = strtoupper(trim($_POST['invite_code'] ?? ''));

    if (!$code) {
        $errors[] = 'Please enter an invite code.';
    } else {
        $stmt = $db->prepare("SELECT id, name FROM houses WHERE invite_code = ?");
        $stmt->execute([$code]);
        $house = $stmt->fetch();

        if (!$house) {
            $errors[] = 'Invalid invite code. Please check and try again.';
        } else {
            // Check already a member
            $chk = $db->prepare("SELECT id FROM house_members WHERE house_id = ? AND user_id = ?");
            $chk->execute([$house['id'], $_SESSION['user_id']]);
            if ($chk->fetch()) {
                $_SESSION['house_id'] = $house['id'];
                redirect('/roomatehub/house/house-dashboard.php');
            }

            $mem = $db->prepare("INSERT INTO house_members (house_id, user_id, role) VALUES (?, ?, 'member')");
            $mem->execute([$house['id'], $_SESSION['user_id']]);
            $_SESSION['house_id'] = $house['id'];

            setFlash('success', 'You joined ' . $house['name'] . '! Welcome home 🎉');
            redirect('/roomatehub/house/house-dashboard.php');
        }
    }
}

$pageTitle = 'Join a House';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Join a House</h1>
        <p>Enter the invite code your housemate shared with you</p>
    </div>
</div>

<div style="max-width:500px">
    <div class="card">
        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="invite_code">Invite Code</label>
                <input class="form-control" type="text" id="invite_code" name="invite_code"
                       value="<?= e($_POST['invite_code'] ?? '') ?>"
                       placeholder="e.g. ABC123"
                       style="text-transform:uppercase;font-family:var(--font-mono);letter-spacing:0.1em;font-size:1.1rem"
                       maxlength="8" required autofocus>
                <span class="form-hint">Codes are 6 characters long — ask your roommate for theirs.</span>
            </div>
            <button type="submit" class="btn btn-primary btn-full">🔑 Join House</button>
        </form>

        <hr class="divider">
        <p class="text-muted text-sm" style="text-align:center">
            Don't have a code? <a href="/roomatehub/house/house-create.php">Create a new house</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
