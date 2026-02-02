<?php
session_start();
require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get statistics
$total_events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
$past_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date < CURDATE()")->fetchColumn();

// Get recent events
$recent_events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC LIMIT 5")->fetchAll();

// Get events by category
$categories = $pdo->query("SELECT category, COUNT(*) as count FROM events WHERE category IS NOT NULL AND category != '' GROUP BY category")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Event Management</title>
    <link rel="stylesheet" href="../assests/js/css/styles.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dashboard-header h1 {
            margin: 0;
            color: #333;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-card.upcoming .number {
            color: #28a745;
        }
        .stat-card.past .number {
            color: #6c757d;
        }
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .action-btn {
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }
        .action-btn:hover {
            background: #0056b3;
        }
        .action-btn.secondary {
            background: #6c757d;
        }
        .action-btn.secondary:hover {
            background: #545b62;
        }
        .recent-events {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .recent-events h2 {
            margin: 0 0 20px 0;
            color: #333;
        }
        .recent-events table {
            width: 100%;
            border-collapse: collapse;
        }
        .recent-events th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .recent-events td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .recent-events tr:hover {
            background: #f8f9fa;
        }
        .category-stats {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .category-stats h2 {
            margin: 0 0 20px 0;
            color: #333;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .category-item:last-child {
            border-bottom: none;
        }
        .category-name {
            font-weight: 500;
        }
        .category-count {
            background: #007bff;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Events</h3>
                <div class="number"><?= $total_events ?></div>
            </div>
            <div class="stat-card upcoming">
                <h3>Upcoming Events</h3>
                <div class="number"><?= $upcoming_events ?></div>
            </div>
            <div class="stat-card past">
                <h3>Past Events</h3>
                <div class="number"><?= $past_events ?></div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add.php" class="action-btn">+ Add New Event</a>
            <a href="index.php" class="action-btn secondary">View All Events</a>
        </div>

        <div class="recent-events">
            <h2>Recent Events</h2>
            <?php if (count($recent_events) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_events as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title']) ?></td>
                                <td><?= htmlspecialchars($event['category'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($event['event_date']) ?></td>
                                <td><?= htmlspecialchars($event['location']) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $event['id'] ?>">Edit</a> |
                                    <a href="delete.php?id=<?= $event['id'] ?>" onclick="return confirm('Delete this event?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No events found. <a href="add.php">Add your first event</a></p>
            <?php endif; ?>
        </div>

        <?php if (count($categories) > 0): ?>
            <div class="category-stats">
                <h2>Events by Category</h2>
                <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <span class="category-name"><?= htmlspecialchars($cat['category']) ?></span>
                        <span class="category-count"><?= $cat['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
