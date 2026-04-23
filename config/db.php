<?php
// config/db.php

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: 'projekt_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('DB spojenie zlyhalo: ' . $e->getMessage());
    http_response_code(500);
    die('Databáza nie je dostupná. Skúste neskôr.');
}
