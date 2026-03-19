<?php
// includes/helpers.php
// Shared utility functions used across the app

require_once __DIR__ . '/../config/db.php';

// Start session safely
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirect helper
function redirect($path) {
    header("Location: $path");
    exit();
}

// Require user to be logged in
function requireLogin() {
    startSession();
    if (empty($_SESSION['user_id'])) {
        redirect('/roomatehub/login.php');
    }
}

// Require user to be in a house
function requireHouse() {
    requireLogin();
    if (empty($_SESSION['house_id'])) {
        redirect('/roomatehub/house/house-create.php');
    }
}

// Sanitise output to prevent XSS
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Format currency
function formatMoney($amount) {
    return '₹' . number_format($amount, 2);
}

// Format date nicely
function formatDate($date) {
    if (!$date) return '—';
    return date('d M Y', strtotime($date));
}

// Generate a random invite code
function generateInviteCode($length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

// Get all members of the current house
function getHouseMembers($house_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.avatar_color, hm.role
        FROM house_members hm
        JOIN users u ON u.id = hm.user_id
        WHERE hm.house_id = ?
        ORDER BY hm.role DESC, u.username ASC
    ");
    $stmt->execute([$house_id]);
    return $stmt->fetchAll();
}

// Get current user info
function getCurrentUser() {
    startSession();
    if (empty($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, avatar_color FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Get current house info
function getCurrentHouse() {
    startSession();
    if (empty($_SESSION['house_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM houses WHERE id = ?");
    $stmt->execute([$_SESSION['house_id']]);
    return $stmt->fetch();
}

// Flash message system
function setFlash($type, $message) {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    startSession();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Get initials from username for avatar
function getInitials($username) {
    $parts = explode(' ', trim($username));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($username, 0, 2));
}

// Check if user is admin of current house
function isHouseAdmin() {
    startSession();
    if (empty($_SESSION['user_id']) || empty($_SESSION['house_id'])) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT role FROM house_members WHERE house_id = ? AND user_id = ?");
    $stmt->execute([$_SESSION['house_id'], $_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row && $row['role'] === 'admin';
}

// Calculate balances for all members in a house
function calculateBalances($house_id) {
    $db = getDB();
    // Get all unsettled splits for non-archived expenses
    $stmt = $db->prepare("
        SELECT es.user_id, es.amount_owed, e.paid_by
        FROM expense_splits es
        JOIN expenses e ON e.id = es.expense_id
        WHERE e.house_id = ? AND e.is_archived = 0 AND es.is_settled = 0
    ");
    $stmt->execute([$house_id]);
    $splits = $stmt->fetchAll();

    $balances = []; // balances[ower][payer] = amount

    foreach ($splits as $split) {
        $ower  = $split['user_id'];
        $payer = $split['paid_by'];
        if ($ower === $payer) continue; // skip if same person paid

        if (!isset($balances[$ower][$payer])) {
            $balances[$ower][$payer] = 0;
        }
        $balances[$ower][$payer] += $split['amount_owed'];
    }

    return $balances;
}
?>
