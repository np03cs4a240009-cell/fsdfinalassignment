<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// rest of your code
session_start();
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle booking cancellation by admin
if (isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$booking_id]);
    $_SESSION['success'] = "Booking cancelled successfully";
    header("Location: manage_bookings.php");
    exit;
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'confirmed';
$filter_event = $_GET['event'] ?? '';
$search_user = $_GET['user'] ?? '';

// Build query based on filters
$query = "
    SELECT b.*, 
           e.title as event_title, 
           e.event_date, 
           e.location,
           e.total_seats,
           u.username, 
           u.email
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";

$params = [];

if ($filter_status && $filter_status !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
}

if ($filter_event) {
    $query .= " AND b.event_id = ?";
    $params[] = $filter_event;
}

if ($search_user) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search_user%";
    $params[] = "%$search_user%";
}

$query .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get all events for filter dropdown
$events = $pdo->query("SELECT id, title FROM events ORDER BY title")->fetchAll();

// Calculate statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN seats_booked ELSE 0 END) as total_seats_booked
    FROM bookings
")->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings - Event Management</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            color: white;
        }
        .header h1 {
            margin-bottom: 10px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .back-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.9);
            color: #667eea;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: white;
            transform: translateY(-2px);
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
            font-weight: 600;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-card.confirmed .number {
            color: #28a745;
        }
        .stat-card.cancelled .number {
            color: #dc3545;
        }
        .stat-card.seats .number {
            color: #ffc107;
        }
        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .filters-section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
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
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .export-btn {
            padding: 8px 16px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }
        .export-btn:hover {
            background: #218838;
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
            font-size: 14px;
            color: #495057;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .action-btn {
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .action-btn.cancel {
            background: #dc3545;
            color: white;
        }
        .action-btn.cancel:hover {
            background: #c82333;
        }
        .action-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .info-text {
            color: #666;
            font-size: 14px;
        }
        .event-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .event-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Manage All Bookings</h1>
            <p style="opacity: 0.9; margin-top: 5px;">View, filter, and manage event bookings</p>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
                <span style="opacity: 0.9;">Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                ✓ <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Bookings</h3>
                <div class="number"><?= $stats['total_bookings'] ?></div>
            </div>
            <div class="stat-card confirmed">
                <h3>✓ Confirmed</h3>
                <div class="number"><?= $stats['confirmed_bookings'] ?></div>
            </div>
            <div class="stat-card cancelled">
                <h3>✗ Cancelled</h3>
                <div class="number"><?= $stats['cancelled_bookings'] ?></div>
            </div>
            <div class="stat-card seats">
                <h3>💺 Total Seats Booked</h3>
                <div class="number"><?= $stats['total_seats_booked'] ?></div>
            </div>
        </div>

        <div class="filters-section">
            <h2>🔍 Filter Bookings</h2>
            <form method="get" action="manage_bookings.php">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="event">Event</label>
                        <select name="event" id="event">
                            <option value="">All Events</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= $event['id'] ?>" <?= $filter_event == $event['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="user">Search User</label>
                        <input type="text" name="user" id="user" placeholder="Username or email" value="<?= htmlspecialchars($search_user) ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="manage_bookings.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; text-align: center;">Reset Filters</a>
                </div>
            </form>
        </div>

        <div class="bookings-table">
            <div class="table-controls">
                <h2>Booking Records (<?= count($bookings) ?> results)</h2>
                <a href="export_bookings.php?<?= http_build_query($_GET) ?>" class="export-btn">📥 Export to CSV</a>
            </div>
            
            <?php if (count($bookings) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Seats</th>
                                <th>Event Date</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong>#<?= $booking['id'] ?></strong></td>
                                    <td>
                                        <a href="view_bookings.php?event_id=<?= $booking['event_id'] ?>" class="event-link">
                                            <?= htmlspecialchars($booking['event_title']) ?>
                                        </a>
                                        <br>
                                        <small class="info-text">📍 <?= htmlspecialchars($booking['location']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($booking['username']) ?></td>
                                    <td><?= htmlspecialchars($booking['email'] ?? 'N/A') ?></td>
                                    <td><strong><?= $booking['seats_booked'] ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($booking['event_date'])) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($booking['booking_date'])) ?></td>
                                    <td>
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                            <span class="badge badge-success">✓ Confirmed</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">✗ Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                <button type="submit" name="cancel_booking" class="action-btn cancel">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn" disabled>Cancelled</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>No bookings found matching your criteria.</p>
                    <p>Try adjusting your filters or <a href="manage_bookings.php">reset them</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>