<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Get statistics
$total_events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
$past_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date < CURDATE()")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();

// Get recent events with booking info
$recent_events = $pdo->query("
    SELECT e.*, 
           COALESCE(SUM(b.seats_booked), 0) as seats_booked,
           (e.total_seats - COALESCE(SUM(b.seats_booked), 0)) as seats_available
    FROM events e
    LEFT JOIN bookings b ON e.id = b.event_id AND b.status = 'confirmed'
    GROUP BY e.id
    ORDER BY e.event_date DESC 
    LIMIT 10
")->fetchAll();

// Get events by category
$categories = $pdo->query("SELECT category, COUNT(*) as count FROM events WHERE category IS NOT NULL AND category != '' GROUP BY category")->fetchAll();

// Get recent bookings
$recent_bookings = $pdo->query("
    SELECT b.*, e.title as event_title, u.username
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'confirmed'
    ORDER BY b.booking_date DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Event Management</title>
    <link rel="stylesheet" href="../assests/js/css/styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: white;
        }
        .dashboard-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .logout-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.9);
            color: #667eea;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: white;
            transform: translateY(-2px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-card .number {
            font-size: 42px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-card.upcoming .number {
            color: #28a745;
        }
        .stat-card.past .number {
            color: #6c757d;
        }
        .stat-card.bookings .number {
            color: #ff6b6b;
        }
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 14px 28px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .action-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .action-btn.secondary {
            background: #6c757d;
        }
        .action-btn.secondary:hover {
            background: #5a6268;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .events-section, .bookings-section, .category-stats {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .events-section h2, .bookings-section h2, .category-stats h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .seats-info {
            display: flex;
            gap: 10px;
            align-items: center;
            font-size: 14px;
        }
        .seats-booked {
            color: #ff6b6b;
            font-weight: 600;
        }
        .seats-available {
            color: #28a745;
            font-weight: 600;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .category-item:last-child {
            border-bottom: none;
        }
        .category-name {
            font-weight: 500;
        }
        .category-count {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .action-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 5px;
            font-weight: 500;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .delete-link {
            color: #dc3545 !important;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>🎯 Admin Dashboard</h1>
                <p style="margin-top: 5px; opacity: 0.9;">Manage events and monitor bookings</p>
            </div>
            <div class="user-info">
                <span class="admin-badge">ADMIN</span>
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📅 Total Events</h3>
                <div class="number"><?= $total_events ?></div>
            </div>
            <div class="stat-card upcoming">
                <h3>⏰ Upcoming Events</h3>
                <div class="number"><?= $upcoming_events ?></div>
            </div>
            <div class="stat-card past">
                <h3>📜 Past Events</h3>
                <div class="number"><?= $past_events ?></div>
            </div>
            <div class="stat-card bookings">
                <h3>🎫 Total Bookings</h3>
                <div class="number"><?= $total_bookings ?></div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add.php" class="action-btn">➕ Add New Event</a>
            <a href="index.php" class="action-btn secondary">📋 View All Events</a>
            <a href="manage_bookings.php" class="action-btn secondary">🎫 Manage Bookings</a>
        </div>

        <div class="content-grid">
            <div class="events-section">
                <h2>Recent Events & Seat Availability</h2>
                <?php if (count($recent_events) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Total Seats</th>
                                <th>Booked</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_events as $event): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($event['title']) ?></strong></td>
                                    <td><?= htmlspecialchars($event['event_date']) ?></td>
                                    <td><?= $event['total_seats'] ?></td>
                                    <td><span class="seats-booked"><?= $event['seats_booked'] ?></span></td>
                                    <td><span class="seats-available"><?= $event['seats_available'] ?></span></td>
                                    <td class="action-links">
                                        <a href="edit.php?id=<?= $event['id'] ?>">Edit</a> |
                                        <a href="view_bookings.php?event_id=<?= $event['id'] ?>">Bookings</a> |
                                        <a href="delete.php?id=<?= $event['id'] ?>" class="delete-link" onclick="return confirm('Delete this event?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No events found. <a href="add.php">Add your first event</a></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="category-stats">
                <h2>Events by Category</h2>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="category-item">
                            <span class="category-name"><?= htmlspecialchars($cat['category']) ?></span>
                            <span class="category-count"><?= $cat['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data">No categories yet</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bookings-section">
            <h2>Recent Bookings</h2>
            <?php if (count($recent_bookings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>User</th>
                            <th>Seats</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['event_title']) ?></td>
                                <td><?= htmlspecialchars($booking['username']) ?></td>
                                <td><strong><?= $booking['seats_booked'] ?></strong></td>
                                <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                <td><span style="color: #28a745; font-weight: 600;">✓ Confirmed</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No bookings yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
