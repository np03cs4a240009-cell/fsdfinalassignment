<?php
require '../config/db.php';
require 'auth.php'; // Protect this page
include '../includes/header.php';

$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
?>

<style>
.search-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.search-container input {
    width: 100%;
    padding: 12px;
    border: 2px solid #007bff;
    border-radius: 4px;
    font-size: 16px;
}
.search-container input:focus {
    outline: none;
    border-color: #0056b3;
}
.search-info {
    margin-top: 10px;
    color: #666;
    font-size: 14px;
}
</style>

<div class="search-container">
    <input type="text" id="search" placeholder="🔍 Start typing to search events (title, location, category, description)...">
    <div class="search-info">Search updates automatically as you type - no button needed!</div>
</div>

<div id="results">
    <table border="1">
    <tr>
        <th>Title</th>
        <th>Category</th>
        <th>Date</th>
        <th>Location</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($events as $event): ?>
    <tr>
        <td><?= htmlspecialchars($event['title']) ?></td>
        <td><?= htmlspecialchars($event['category'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($event['event_date']) ?></td>
        <td><?= htmlspecialchars($event['location']) ?></td>
        <td>
            <a href="edit.php?id=<?= $event['id'] ?>">Edit</a> |
            <a href="delete.php?id=<?= $event['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </table>
</div>

<script>
// Debug: Check if script is loading
console.log('Index page loaded');
console.log('Search input exists:', document.getElementById('search') !== null);
</script>

<?php include '../includes/footer.php'; ?>
