<?php
session_start();
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    header("Location: admin_dashboard.php");
    exit;
}

// Get event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: admin_dashboard.php");
    exit;
}

// Get bookings for this event
$bookings = $pdo->prepare("
    SELECT b.*, u.username, u.email
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.event_id = ? AND b.status = 'confirmed'
    ORDER BY b.booking_date DESC
");
$bookings->execute([$event_id]);
$event_bookings = $bookings->fetchAll();

// Calculate statistics
$total_booked = array_sum(array_column($event_bookings, 'seats_booked'));
$seats_available = $event['total_seats'] - $total_booked;
$percentage_booked = ($total_booked / $event['total_seats']) * 100;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Bookings - <?= htmlspecialchars($event['title']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .breadcrumb {
            color: #666;
            margin-bottom: 20px;
        }
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        .event-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-item strong {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
            color: #667eea;
        }
        .bookings-table {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .bookings-table h2 {
            margin: 0 0 20px 0;
            color: #333;
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
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <h1>📊 Bookings for: <?= htmlspecialchars($event['title']) ?></h1>
            
            <div class="event-info">
                <div class="info-item">
                    <strong>Date</strong>
                    <?= date('M d, Y', strtotime($event['event_date'])) ?>
                </div>
                <div class="info-item">
                    <strong>Location</strong>
                    <?= htmlspecialchars($event['location']) ?>
                </div>
                <div class="info-item">
                    <strong>Category</strong>
                    <?= htmlspecialchars($event['category'] ?? 'N/A') ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Capacity</h3>
                <div class="number"><?= $event['total_seats'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Seats Booked</h3>
                <div class="number" style="color: #ff6b6b;"><?= $total_booked ?></div>
            </div>
            <div class="stat-card">
                <h3>Seats Available</h3>
                <div class="number" style="color: #28a745;"><?= $seats_available ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="number" style="color: #ffc107;"><?= count($event_bookings) ?></div>
            </div>
        </div>

        <div class="bookings-table">
            <h2>Booking Details</h2>
            <div>
                <strong>Occupancy: <?= number_format($percentage_booked, 1) ?>%</strong>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $percentage_booked ?>%"></div>
                </div>
            </div>
            
            <?php if (count($event_bookings) > 0): ?>
                <table style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Seats Booked</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($event_bookings as $booking): ?>
                            <tr>
                                <td><strong>#<?= $booking['id'] ?></strong></td>
                                <td><?= htmlspecialchars($booking['username']) ?></td>
                                <td><?= htmlspecialchars($booking['email'] ?? 'N/A') ?></td>
                                <td><strong><?= $booking['seats_booked'] ?></strong></td>
                                <td><?= date('M d, Y h:i A', strtotime($booking['booking_date'])) ?></td>
                                <td><span style="color: #28a745; font-weight: 600;">✓ Confirmed</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No bookings yet for this event</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
