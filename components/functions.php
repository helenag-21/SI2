<?php
// components/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Ochrana stránok
if ($current_page !== 'index.php' && empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Zmena jazyka
if (isset($_GET['lang']) && file_exists(__DIR__ . '/../lang/' . $_GET['lang'] . '.json')) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['lang'] ?? 'sk';
$translations = [];

// Absolútna cesta — funguje z ľubovoľného adresára
$langFile = __DIR__ . "/../lang/$lang.json";
if (file_exists($langFile)) {
    $translations = json_decode(file_get_contents($langFile), true) ?: [];
}

if (!function_exists('t')) {
    function t(string $key): string {
        global $translations;
        return $translations[$key] ?? $key;
    }
}

// Vytvor potrebné priečinky
foreach (['assets/uploads', 'assets/backups'] as $dir) {
    $path = __DIR__ . '/../' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
