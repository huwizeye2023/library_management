<?php
require_once 'config.php';
requireLogin();

// Redirect based on role
$role = getUserRole();

if ($role === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
} elseif ($role === 'librarian') {
    header("Location: librarian_dashboard.php");
    exit();
} elseif ($role === 'member') {
    header("Location: member_dashboard.php");
    exit();
} else {
    // Invalid role, logout
    logout();
}
?>
