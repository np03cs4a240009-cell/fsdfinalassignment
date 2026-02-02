<?php
session_start();
require '../config/db.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Get user's bookings
$user_bookings = $pdo->prepare("
    SELECT b.*, e.title, e.event_date, e.location, e.category
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ? AND b.status = 'confirmed'
    ORDER BY e.event_date DESC
");
$user_bookings->execute([$_SESSION['user_id']]);
$my_bookings = $user_bookings->fetchAll();

// Get upcoming events with availability
$upcoming_events = $pdo->query("
    SELECT e.*, 
           COALESCE(SUM(b.seats_booked), 0) as seats_booked,
           (e.total_seats - COALESCE(SUM(b.seats_booked), 0)) as seats_available
    FROM events e
    LEFT JOIN bookings b ON e.id = b.event_id AND b.status = 'confirmed'
    WHERE e.event_date >= CURDATE()
    GROUP BY e.id
    ORDER BY e.event_date ASC
")->fetchAll();

// Get statistics
$total_bookings = count($my_bookings);
$total_seats_booked = array_sum(array_column($my_bookings, 'seats_booked'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard - Event Management</title>
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
        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .event-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            background: white;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .event-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .event-info {
            margin: 8px 0;
            color: #666;
            font-size: 14px;
        }
        .event-info strong {
            color: #333;
        }
        .availability {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .availability-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        .availability-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s;
        }
        .availability-fill.low {
            background: linear-gradient(90deg, #ffc107, #fd7e14);
        }
        .availability-fill.full {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        .book-btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .book-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .book-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
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
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .cancel-btn {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }
        .cancel-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>🎉 Event Booking Portal</h1>
                <p style="margin-top: 5px; opacity: 0.9;">Browse and book your favorite events</p>
            </div>
            <div class="user-info">
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>🎫 My Bookings</h3>
                <div class="number"><?= $total_bookings ?></div>
            </div>
            <div class="stat-card">
                <h3>💺 Total Seats Booked</h3>
                <div class="number"><?= $total_seats_booked ?></div>
            </div>
            <div class="stat-card">
                <h3>📅 Available Events</h3>
                <div class="number"><?= count($upcoming_events) ?></div>
            </div>
        </div>

        <div class="section">
            <h2>🎪 Upcoming Events</h2>
            <div class="search-box">
                <input type="text" id="searchEvents" placeholder="🔍 Search events by title, category, or location...">
            </div>
            <div class="events-grid" id="eventsGrid">
                <?php if (count($upcoming_events) > 0): ?>
                    <?php foreach ($upcoming_events as $event): 
                        $percentage_available = ($event['seats_available'] / $event['total_seats']) * 100;
                        $fill_class = '';
                        if ($percentage_available < 20) $fill_class = 'full';
                        elseif ($percentage_available < 50) $fill_class = 'low';
                    ?>
                        <div class="event-card" data-title="<?= strtolower($event['title']) ?>" 
                             data-category="<?= strtolower($event['category'] ?? '') ?>" 
                             data-location="<?= strtolower($event['location']) ?>">
                            <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                            <div class="event-info">
                                <strong>📅 Date:</strong> <?= date('M d, Y', strtotime($event['event_date'])) ?>
                            </div>
                            <div class="event-info">
                                <strong>📍 Location:</strong> <?= htmlspecialchars($event['location']) ?>
                            </div>
                            <?php if ($event['category']): ?>
                                <div class="event-info">
                                    <strong>🏷️ Category:</strong> <?= htmlspecialchars($event['category']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="availability">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                                    <span><strong>Available:</strong> <?= $event['seats_available'] ?> seats</span>
                                    <span><strong>Total:</strong> <?= $event['total_seats'] ?></span>
                                </div>
                                <div class="availability-bar">
                                    <div class="availability-fill <?= $fill_class ?>" 
                                         style="width: <?= $percentage_available ?>%"></div>
                                </div>
                            </div>
                            
                            <?php if ($event['seats_available'] > 0): ?>
                                <form action="book_event.php" method="post" style="margin-top: 15px;">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <div style="margin-bottom: 10px;">
                                        <input type="number" name="seats" min="1" max="<?= min(10, $event['seats_available']) ?>" 
                                               value="1" required 
                                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        <small style="color: #666;">Number of seats (max: <?= min(10, $event['seats_available']) ?>)</small>
                                    </div>
                                    <button type="submit" class="book-btn">Book Now</button>
                                </form>
                            <?php else: ?>
                                <button class="book-btn" disabled>Fully Booked</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">No upcoming events available</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>📋 My Bookings</h2>
            <?php if (count($my_bookings) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Seats</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_bookings as $booking): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($booking['title']) ?></strong></td>
                                <td><?= htmlspecialchars($booking['category'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($booking['event_date'])) ?></td>
                                <td><?= htmlspecialchars($booking['location']) ?></td>
                                <td><strong><?= $booking['seats_booked'] ?></strong></td>
                                <td><span class="badge badge-success">✓ Confirmed</span></td>
                                <td>
                                    <a href="cancel_booking.php?id=<?= $booking['id'] ?>" 
                                       class="cancel-btn" 
                                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        Cancel
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>You haven't booked any events yet.</p>
                    <p>Browse the upcoming events above to make your first booking!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchEvents').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const title = card.dataset.title;
                const category = card.dataset.category;
                const location = card.dataset.location;
                
                if (title.includes(searchTerm) || 
                    category.includes(searchTerm) || 
                    location.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
