<?php
$host = "localhost";
$db = "np03cs4a240009";
$user = "np03cs4a240009"; 
$pass = "e4UjoEudQY";


try {
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
die("Database connection failed");
}
?>