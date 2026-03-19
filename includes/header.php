<?php
// includes/header.php
require_once __DIR__ . '/helpers.php';
startSession();
$currentUser = getCurrentUser();
$currentHouse = getCurrentHouse();
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — RoomateHub' : 'RoomateHub' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/roomatehub/assets/css/style.css">
</head>
<body>

<?php if ($currentUser): ?>
<!-- Sidebar navigation (shown when logged in) -->
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🏠</span>
            <span class="brand-name">RoomateHub</span>
        </div>

        <?php if ($currentHouse): ?>
        <div class="sidebar-house">
            <span class="house-label">YOUR HOUSE</span>
            <span class="house-name"><?= e($currentHouse['name']) ?></span>
            <span class="invite-code">Code: <?= e($currentHouse['invite_code']) ?></span>
        </div>
        <?php endif; ?>

        <nav class="sidebar-nav">
            <?php if ($currentHouse): ?>
            <a href="/roomatehub/house/house-dashboard.php"
               class="nav-item <?= ($currentPage === 'house-dashboard.php' && $currentDir === 'house') ? 'active' : '' ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="/roomatehub/chores/chores-list.php"
               class="nav-item <?= ($currentDir === 'chores') ? 'active' : '' ?>">
                <span class="nav-icon">✅</span> Chores
            </a>
            <a href="/roomatehub/expenses/expenses-list.php"
               class="nav-item <?= ($currentDir === 'expenses') ? 'active' : '' ?>">
                <span class="nav-icon">💸</span> Expenses
            </a>
            <?php else: ?>
            <a href="/roomatehub/house/house-create.php"
               class="nav-item <?= ($currentPage === 'house-create.php') ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span> Create House
            </a>
            <a href="/roomatehub/house/house-join.php"
               class="nav-item <?= ($currentPage === 'house-join.php') ? 'active' : '' ?>">
                <span class="nav-icon">🔑</span> Join House
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="/roomatehub/profile/profile.php"
               class="nav-item <?= ($currentDir === 'profile') ? 'active' : '' ?>">
                <span class="avatar-small" style="background:<?= e($currentUser['avatar_color']) ?>">
                    <?= getInitials($currentUser['username']) ?>
                </span>
                <?= e($currentUser['username']) ?>
            </a>
            <a href="/roomatehub/auth/logout.php" class="nav-item nav-logout">
                <span class="nav-icon">🚪</span> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
<?php else: ?>
<!-- No sidebar — public pages (login/register) -->
<main class="auth-page">
<?php endif; ?>

<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>">
    <?= e($flash['message']) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>
