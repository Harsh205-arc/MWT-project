<?php
// index.php — Landing page / Login
require_once __DIR__ . '/includes/helpers.php';
startSession();

// Already logged in? Send to dashboard or house selection
if (!empty($_SESSION['user_id'])) {
    if (!empty($_SESSION['house_id'])) {
        redirect('/roomatehub/house/house-dashboard.php');
    } else {
        redirect('/roomatehub/house/house-create.php');
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE email = ? AND is_archived = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Check if user belongs to a house
            $hstmt = $db->prepare("SELECT house_id FROM house_members WHERE user_id = ? LIMIT 1");
            $hstmt->execute([$user['id']]);
            $hm = $hstmt->fetch();
            if ($hm) {
                $_SESSION['house_id'] = $hm['house_id'];
                redirect('/roomatehub/house/house-dashboard.php');
            } else {
                redirect('/roomatehub/house/house-create.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomateHub — Split Chores & Expenses</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/roomatehub/assets/css/style.css">
</head>
<body>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🏠</div>
            <h1>RoomateHub</h1>
            <p>Split chores & expenses with your housemates</p>
        </div>

        <?php if ($error): ?>
        <div class="flash flash-error">
            <?= e($error) ?>
            <button class="flash-close" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" type="email" id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>

        <hr class="divider">
        <p class="text-muted text-sm" style="text-align:center">
            Don't have an account?
            <a href="/roomatehub/auth/register.php">Register here</a>
        </p>
    </div>
</main>
<script src="/roomatehub/assets/js/main.js"></script>
</body>
</html>
