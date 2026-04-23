<?php
// components/export.php — export zápisov z MySQL

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Prístup zamietnutý.');
}

$userId = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'json';
$dennikId = $_GET['dennik'] ?? null;

// Načítanie zápisov — voliteľne filtrovať podľa denníka
if ($dennikId) {
    $stmt = $pdo->prepare("
        SELECT z.PK_ID_zapis AS id, z.nazov AS title, z.obsah AS content,
               z.datum_vytvorenia AS created, z.datum_upravy AS updated,
               k.nazov AS category, d.nazov AS diary
        FROM Zapis z
        JOIN Dennik d ON z.FK_ID_dennik = d.PK_ID_dennik
        LEFT JOIN Kategoria k ON z.FK_ID_kategoria = k.PK_ID_kategoria
        WHERE z.FK_ID_dennik = ? AND d.FK_ID_pouzivatel = ?
        ORDER BY z.datum_upravy DESC
    ");
    $stmt->execute([$dennikId, $userId]);
} else {
    $stmt = $pdo->prepare("
        SELECT z.PK_ID_zapis AS id, z.nazov AS title, z.obsah AS content,
               z.datum_vytvorenia AS created, z.datum_upravy AS updated,
               k.nazov AS category, d.nazov AS diary
        FROM Zapis z
        JOIN Dennik d ON z.FK_ID_dennik = d.PK_ID_dennik
        LEFT JOIN Kategoria k ON z.FK_ID_kategoria = k.PK_ID_kategoria
        WHERE d.FK_ID_pouzivatel = ?
        ORDER BY d.nazov, z.datum_upravy DESC
    ");
    $stmt->execute([$userId]);
}

$entries = $stmt->fetchAll();
$date    = date('Y-m-d');

if ($format === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"export-denniok-$date.txt\"");

    foreach ($entries as $e) {
        echo "=== " . ($e['title'] ?: 'Bez názvu') . " ===\n";
        echo "Denník:    " . $e['diary'] . "\n";
        echo "Kategória: " . ($e['category'] ?: '—') . "\n";
        echo "Dátum:     " . date('j. n. Y H:i', strtotime($e['updated'])) . "\n";
        echo "\n";
        echo $e['content'];
        echo "\n\n" . str_repeat('-', 60) . "\n\n";
    }
    exit;
}

if ($format === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"export-denniok-$date.html\"");

    echo "<!DOCTYPE html><html lang='sk'><head><meta charset='UTF-8'>";
    echo "<title>Export denníka – $date</title>";
    echo "<style>body{font-family:Georgia,serif;max-width:800px;margin:40px auto;padding:0 20px;color:#333}";
    echo "h1{color:#6366f1}h2{border-bottom:2px solid #6366f1;padding-bottom:8px}";
    echo ".meta{color:#888;font-size:.9em;margin-bottom:16px}.entry{margin-bottom:48px}</style></head><body>";
    echo "<h1>Export denníka</h1><p style='color:#888'>Exportované: " . date('j. n. Y H:i') . "</p><hr>";

    foreach ($entries as $e) {
        echo "<div class='entry'>";
        echo "<h2>" . htmlspecialchars($e['title'] ?: 'Bez názvu') . "</h2>";
        echo "<div class='meta'>";
        echo "Denník: <strong>" . htmlspecialchars($e['diary']) . "</strong>";
        if ($e['category']) echo " &nbsp;·&nbsp; Kategória: <strong>" . htmlspecialchars($e['category']) . "</strong>";
        echo " &nbsp;·&nbsp; " . date('j. n. Y H:i', strtotime($e['updated']));
        echo "</div>";
        echo "<div>" . nl2br(htmlspecialchars($e['content'])) . "</div>";
        echo "</div>";
    }

    echo "</body></html>";
    exit;
}

// Predvolený formát: JSON
header('Content-Type: application/json; charset=utf-8');
header("Content-Disposition: attachment; filename=\"export-denniok-$date.json\"");
echo json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
