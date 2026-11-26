<?php
// Change these values if your local MySQL/XAMPP setup differs.
$host = 'localhost'; $db = 'warehouse_maintenance'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { exit('Database connection failed. Import database/warehouse_maintenance.sql and check config/database.php.'); }
