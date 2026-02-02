<?php
// auth.php - Include this at the top of protected pages
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Optional: Redirect users to appropriate dashboards based on role
function checkAdminAccess() {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: user_dashboard.php");
        exit;
    }
}

function checkUserAccess() {
    if ($_SESSION['role'] !== 'user') {
        header("Location: admin_dashboard.php");
        exit;
    }
}
?>
