<?php
session_start();
require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Verify the booking belongs to this user
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception("Booking not found or unauthorized");
        }
        
        // Cancel the booking by updating status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        $_SESSION['success'] = "Booking cancelled successfully";
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Cancellation failed: " . $e->getMessage();
    }
}

header("Location: user_dashboard.php");
exit;
?>
