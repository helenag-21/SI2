<?php
// components/import.php — import zápisov do MySQL

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nie si prihlásený.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input) || empty($input)) {
    echo json_encode(['success' => false, 'error' => 'Neplatný JSON formát.']);
    exit;
}

// Zisti názov denníka z prvého záznamu
$diaryName = null;
foreach ($input as $item) {
    if (is_array($item) && !empty($item['diary'])) {
        $diaryName = trim($item['diary']) . ' [import]';
        break;
    }
}
$diaryName = $diaryName ?? 'Import';

// Nájsť alebo vytvoriť denník
$stmt = $pdo->prepare("SELECT PK_ID_dennik FROM Dennik WHERE FK_ID_pouzivatel = ? AND nazov = ? LIMIT 1");
$stmt->execute([$userId, $diaryName]);
$importDiary = $stmt->fetchColumn();

if (!$importDiary) {
    $pdo->prepare("INSERT INTO Dennik (FK_ID_pouzivatel, nazov) VALUES (?, ?)")
        ->execute([$userId, $diaryName]);
    $importDiary = $pdo->lastInsertId();
}

$imported = 0;

try {
    $pdo->beginTransaction();

    foreach ($input as $item) {
        if (!is_array($item)) continue;

        $title   = trim($item['title'] ?? $item['nazov'] ?? '');
        $content = $item['content'] ?? $item['obsah'] ?? '';
        $date    = $item['updated'] ?? $item['created'] ?? $item['date'] ?? date('Y-m-d H:i:s');

        if ($content === '') continue;

        $pdo->prepare("
            INSERT INTO Zapis (FK_ID_dennik, nazov, obsah, datum_vytvorenia, datum_upravy)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$importDiary, $title, $content, $date, $date]);

        $imported++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'imported' => $imported, 'diary' => $diaryName]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Import error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Chyba pri ukladaní zápisov.']);
}
exit;
