<?php
require '../config/db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
$stmt->execute([$id]);
header("Location: index.php");