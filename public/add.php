<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO events (title, category, event_date, location, description, total_seats) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['title'],
        $_POST['category'],
        $_POST['event_date'],
        $_POST['location'],
        $_POST['description'],
        $_POST['total_seats'] ?? 100
    ]);
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Event - Event Management</title>
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
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .breadcrumb {
            color: #666;
            margin-bottom: 30px;
        }
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            font-family: inherit;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
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
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        small {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>➕ Add New Event</h1>
        <div class="breadcrumb">
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>
        
        <form method="post">
            <div class="form-group">
                <label for="title">Event Title *</label>
                <input type="text" id="title" name="title" placeholder="Enter event title" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="e.g., Music, Sports, Conference">
            </div>
            
            <div class="form-group">
                <label for="event_date">Event Date *</label>
                <input type="date" id="event_date" name="event_date" required>
            </div>
            
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" placeholder="Enter event location" required>
            </div>
            
            <div class="form-group">
                <label for="total_seats">Total Seats *</label>
                <input type="number" id="total_seats" name="total_seats" min="1" value="100" required>
                <small>Maximum capacity for this event</small>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Enter event description"></textarea>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-primary">Add Event</button>
                <a href="admin_dashboard.php" style="flex: 1; text-decoration: none;">
                    <button type="button" class="btn-secondary" style="width: 100%;">Cancel</button>
                </a>
            </div>
        </form>
    </div>
</body>
</html>
