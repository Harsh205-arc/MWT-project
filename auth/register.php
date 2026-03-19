<?php
// auth/register.php
require_once __DIR__ . '/../includes/helpers.php';
startSession();

if (!empty($_SESSION['user_id'])) redirect('/roomatehub/login.php');

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db       = getDB();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$username)              $errors[] = 'Username is required.';
    elseif (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email is required.';

    if (strlen($password) < 6)  $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)  $errors[] = 'Passwords do not match.';

    if (!$errors) {
        // Check uniqueness
        $chk = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $chk->execute([$email, $username]);
        if ($chk->fetch()) {
            $errors[] = 'Email or username is already taken.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $colors = ['#7c3aed','#2563eb','#059669','#d97706','#dc2626','#0891b2','#7c3aed'];
            $color  = $colors[array_rand($colors)];

            $ins = $db->prepare("INSERT INTO users (username, email, password_hash, avatar_color) VALUES (?, ?, ?, ?)");
            $ins->execute([$username, $email, $hash, $color]);

            setFlash('success', 'Account created! Please sign in.');
            redirect('/roomatehub/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — RoomateHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/roomatehub/assets/css/style.css">
</head>
<body>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🏠</div>
            <h1>Create Account</h1>
            <p>Join RoomateHub and manage your shared home</p>
        </div>

        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error">
            <?= e($err) ?>
            <button class="flash-close" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       placeholder="coolroomate" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" type="email" id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password"
                           placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input class="form-control" type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>

        <hr class="divider">
        <p class="text-muted text-sm" style="text-align:center">
            Already have an account? <a href="/roomatehub/login.php">Sign in</a>
        </p>
    </div>
</main>
<script src="/roomatehub/assets/js/main.js"></script>
</body>
</html>
