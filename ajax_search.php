<?php
require '../config/db.php';

$q = "%" . $_GET['q'] . "%";
$stmt = $pdo->prepare("SELECT * FROM events WHERE title LIKE ? OR location LIKE ? OR category LIKE ? OR description LIKE ? ORDER BY event_date DESC");
$stmt->execute([$q, $q, $q, $q]);
$results = $stmt->fetchAll();

if (count($results) > 0) {
    echo '<table border="1" style="width: 100%; border-collapse: collapse;">';
    echo '<tr>';
    echo '<th style="text-align: left; padding: 12px; background: #007bff; color: white;">Title</th>';
    echo '<th style="text-align: left; padding: 12px; background: #007bff; color: white;">Category</th>';
    echo '<th style="text-align: left; padding: 12px; background: #007bff; color: white;">Date</th>';
    echo '<th style="text-align: left; padding: 12px; background: #007bff; color: white;">Location</th>';
    echo '<th style="text-align: left; padding: 12px; background: #007bff; color: white;">Actions</th>';
    echo '</tr>';
    
    foreach ($results as $event) {
        echo '<tr style="border-bottom: 1px solid #ddd;">';
        echo '<td style="padding: 12px;">' . htmlspecialchars($event['title']) . '</td>';
        echo '<td style="padding: 12px;">' . htmlspecialchars($event['category'] ?? 'N/A') . '</td>';
        echo '<td style="padding: 12px;">' . htmlspecialchars($event['event_date']) . '</td>';
        echo '<td style="padding: 12px;">' . htmlspecialchars($event['location']) . '</td>';
        echo '<td style="padding: 12px;">';
        echo '<a href="edit.php?id=' . $event['id'] . '">Edit</a> | ';
        echo '<a href="delete.php?id=' . $event['id'] . '" onclick="return confirm(\'Delete this event?\')">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else {
    echo '<p>No events found.</p>';
}
?>
