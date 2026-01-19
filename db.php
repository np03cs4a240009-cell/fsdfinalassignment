<?php
$host = "localhost";
$db = "event_management";
$user = "root"; // change for server
$pass = ""; // change for server


try {
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
die("Database connection failed");
}
?>