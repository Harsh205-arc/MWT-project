<?php
// auth/logout.php
require_once __DIR__ . '/../includes/helpers.php';
startSession();
session_destroy();
redirect('/roomatehub/login.php');
?>
