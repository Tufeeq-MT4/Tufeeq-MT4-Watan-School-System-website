<?php
date_default_timezone_set('Asia/Karachi');

$host     = "localhost";
$username = "root";
$password = "";
$database = "watan_school";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo->exec("SET time_zone = '+05:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}