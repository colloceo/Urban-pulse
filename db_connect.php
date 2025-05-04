<?php
$host = '127.0.0.1';
$dbname = 'ecomerce';
$username = 'root'; // Replace with your MariaDB username
$password = ''; // Replace with your MariaDB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>