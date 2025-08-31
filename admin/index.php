<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] && ($_SESSION['role'] ?? '') === 'admin') {
    // Redirect to dashboard if logged in
    header("Location: dashboard.php");
    exit();
} else {
    // Redirect to login if not logged in
    header("Location: login.php");
    exit();
}
?>
