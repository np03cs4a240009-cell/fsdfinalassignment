<?php
session_start();
require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $seats_requested = (int)$_POST['seats'];
    $user_id = $_SESSION['user_id'];
    
    // Validate seats requested
    if ($seats_requested <= 0 || $seats_requested > 10) {
        $_SESSION['error'] = "Invalid number of seats requested";
        header("Location: user_dashboard.php");
        exit;
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get event details and current bookings
        $stmt = $pdo->prepare("
            SELECT e.*, COALESCE(SUM(b.seats_booked), 0) as seats_booked
            FROM events e
            LEFT JOIN bookings b ON e.id = b.event_id AND b.status = 'confirmed'
            WHERE e.id = ?
            GROUP BY e.id
        ");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            throw new Exception("Event not found");
        }
        
        $seats_available = $event['total_seats'] - $event['seats_booked'];
        
        // Check if enough seats are available
        if ($seats_requested > $seats_available) {
            throw new Exception("Only {$seats_available} seats available");
        }
        
        // Create booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (event_id, user_id, seats_booked, status)
            VALUES (?, ?, ?, 'confirmed')
        ");
        $stmt->execute([$event_id, $user_id, $seats_requested]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Booking successful! You have booked {$seats_requested} seat(s) for {$event['title']}";
        header("Location: user_dashboard.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = "Booking failed: " . $e->getMessage();
        header("Location: user_dashboard.php");
        exit;
    }
} else {
    header("Location: user_dashboard.php");
    exit;
}
?>
