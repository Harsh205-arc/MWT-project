<?php
// house/create.php
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
startSession();

// If user already has a house, redirect to dashboard
if (!empty($_SESSION['house_id'])) {
    redirect('/roomatehub/house/house-dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db   = getDB();
    $name = trim($_POST['house_name'] ?? '');

    if (!$name)              $errors[] = 'House name is required.';
    elseif (strlen($name) < 2) $errors[] = 'House name is too short.';

    if (!$errors) {
        // Generate a unique invite code
        do {
            $code = generateInviteCode(6);
            $chk  = $db->prepare("SELECT id FROM houses WHERE invite_code = ?");
            $chk->execute([$code]);
        } while ($chk->fetch());

        $ins = $db->prepare("INSERT INTO houses (name, invite_code, created_by) VALUES (?, ?, ?)");
        $ins->execute([$name, $code, $_SESSION['user_id']]);
        $houseId = $db->lastInsertId();

        // Add creator as admin
        $mem = $db->prepare("INSERT INTO house_members (house_id, user_id, role) VALUES (?, ?, 'admin')");
        $mem->execute([$houseId, $_SESSION['user_id']]);

        $_SESSION['house_id'] = $houseId;
        setFlash('success', "House \"$name\" created! Share code <strong>$code</strong> with your roommates.");
        redirect('/roomatehub/house/house-dashboard.php');
    }
}

$pageTitle = 'Create a House';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Create Your House</h1>
        <p>Set up a new shared home and invite your roommates</p>
    </div>
</div>

<div style="max-width:500px">
    <div class="card">
        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?><button class="flash-close" onclick="this.parentElement.remove()">×</button></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="house_name">House Name</label>
                <input class="form-control" type="text" id="house_name" name="house_name"
                       value="<?= e($_POST['house_name'] ?? '') ?>"
                       placeholder="e.g. The Purple Palace" required autofocus>
                <span class="form-hint">This is what all your roommates will see.</span>
            </div>
            <button type="submit" class="btn btn-primary btn-full">🏠 Create House</button>
        </form>

        <hr class="divider">
        <p class="text-muted text-sm" style="text-align:center">
            Already have a house code? <a href="/roomatehub/house/house-join.php">Join an existing house</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
