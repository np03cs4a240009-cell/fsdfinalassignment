<!DOCTYPE html>
<html>
<head>
<title>Event Management</title>
<link rel="stylesheet" href="../assests/js/css/styles.css">
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background: #f4f4f4;
    }
    .header-container {
        background: #fff;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    h1 {
        margin: 0 0 15px 0;
        color: #333;
    }
    nav {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    nav a {
        text-decoration: none;
        color: #007bff;
        padding: 8px 16px;
        border-radius: 4px;
    }
    nav a:hover {
        background: #007bff;
        color: white;
    }
    .logout-link {
        margin-left: auto;
        color: #dc3545 !important;
    }
    .logout-link:hover {
        background: #dc3545 !important;
    }
    hr {
        display: none;
    }
</style>
</head>
<body>
<div class="header-container">
<h1>Event Management System</h1>
<nav>
<a href="dashboard.php">Dashboard</a>
<a href="index.php">All Events</a>
<a href="add.php">Add Event</a>
<?php if (isset($_SESSION['user_id'])): ?>
    <a href="logout.php" class="logout-link">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
<?php endif; ?>
</nav>
</div>
<div style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
