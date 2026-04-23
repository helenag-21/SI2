<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/components/functions.php';

if (empty($_SESSION["user_id"])) { header("Location: index.php"); exit; }
$userId = (int)$_SESSION["user_id"];
$flash = getFlash();

if (isset($_POST['create_backup'])) {
    $dennikId = (int)($_POST['dennik_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT z.*, k.nazov AS kategoria FROM Zapis z LEFT JOIN Kategoria k ON z.FK_ID_kategoria = k.PK_ID_kategoria WHERE z.FK_ID_dennik = ?");
    $stmt->execute([$dennikId]);
    $zapisy = $stmt->fetchAll();
    $dennik = $pdo->prepare("SELECT nazov FROM Dennik WHERE PK_ID_dennik = ? AND FK_ID_pouzivatel = ?");
    $dennik->execute([$dennikId, $userId]);
    $dennikRow = $dennik->fetch();
    if ($dennikRow) {
        $data = json_encode(['dennik' => $dennikRow['nazov'], 'datum' => date('Y-m-d H:i:s'), 'zapisy' => $zapisy], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $pdo->prepare("INSERT INTO Zaloha (FK_ID_dennik, nazov, balik_dat) VALUES (?, ?, ?)")->execute([$dennikId, 'Záloha ' . date('j. n. Y H:i'), $data]);
        setFlash('success', 'Záloha bola úspešne vytvorená.');
    }
    header('Location: backup.php'); exit;
}

if (isset($_POST['restore_backup'])) {
    $zalohaId = (int)($_POST['zaloha_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT z.*, d.FK_ID_pouzivatel FROM Zaloha z LEFT JOIN Dennik d ON z.FK_ID_dennik = d.PK_ID_dennik WHERE z.PK_ID_zaloha = ? AND (d.FK_ID_pouzivatel = ? OR z.FK_ID_dennik IS NULL)");
    $stmt->execute([$zalohaId, $userId]);
    $zaloha = $stmt->fetch();
    if ($zaloha) {
        // Ak dennik neexistuje, vytvor novy
        if (!$zaloha["FK_ID_dennik"]) {
            $data2 = json_decode($zaloha["balik_dat"], true);
            $pdo->prepare("INSERT INTO Dennik (FK_ID_pouzivatel, nazov) VALUES (?, ?)")->execute([$userId, $data2["dennik"] ?? "Obnoveny dennik"]);
            $newId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE Zaloha SET FK_ID_dennik = ? WHERE PK_ID_zaloha = ?")->execute([$newId, $zalohaId]);
            $zaloha["FK_ID_dennik"] = $newId;
        }
        $data = json_decode($zaloha['balik_dat'], true);
        if ($data && isset($data['zapisy'])) {
            $pdo->prepare("DELETE FROM Zapis WHERE FK_ID_dennik = ?")->execute([$zaloha['FK_ID_dennik']]);
            foreach ($data['zapisy'] as $z) {
                $pdo->prepare("INSERT INTO Zapis (FK_ID_dennik, nazov, obsah, datum_vytvorenia, datum_upravy) VALUES (?, ?, ?, ?, ?)")->execute([$zaloha['FK_ID_dennik'], $z['nazov'] ?? '', $z['obsah'] ?? '', $z['datum_vytvorenia'] ?? date('Y-m-d H:i:s'), $z['datum_upravy'] ?? date('Y-m-d H:i:s')]);
            }
            setFlash('success', 'Dáta boli úspešne obnovené.');
        }
    }
    header('Location: backup.php'); exit;
}

if (isset($_POST['delete_backup'])) {
    $zalohaId = (int)($_POST['zaloha_id'] ?? 0);
    $pdo->prepare("DELETE z FROM Zaloha z JOIN Dennik d ON z.FK_ID_dennik = d.PK_ID_dennik WHERE z.PK_ID_zaloha = ? AND (d.FK_ID_pouzivatel = ? OR z.FK_ID_dennik IS NULL)")->execute([$zalohaId, $userId]);
    setFlash('success', 'Záloha bola vymazaná.');
    header('Location: backup.php'); exit;
}

$dennikStmt = $pdo->prepare("SELECT PK_ID_dennik, nazov FROM Dennik WHERE FK_ID_pouzivatel = ? ORDER BY nazov");
$dennikStmt->execute([$userId]);
$denniky = $dennikStmt->fetchAll();

$zalohaStmt = $pdo->prepare("SELECT z.*, d.nazov AS dennik_nazov FROM Zaloha z LEFT JOIN Dennik d ON z.FK_ID_dennik = d.PK_ID_dennik WHERE (d.FK_ID_pouzivatel = ? OR z.FK_ID_dennik IS NULL) ORDER BY z.datum_vytvorenia DESC");
$zalohaStmt->execute([$userId]);
$zalohy = $zalohaStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'sk' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('backup') ?> – <?= t('app_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<?php include __DIR__ . '/components/header.php'; ?>
<div class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?= t('backup_data') ?></h1>
    <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded-xl <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Vytvoriť zálohu</h2>
        <form method="POST" class="flex gap-4">
            <select name="dennik_id" required class="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Vyber denník...</option>
                <?php foreach ($denniky as $d): ?>
                    <option value="<?= $d['PK_ID_dennik'] ?>"><?= htmlspecialchars($d['nazov']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="create_backup" class="px-6 py-2 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition">Zálohovať teraz</button>
        </form>
    </div>
    <div id="restore" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4"><?= t('recovery_data') ?></h2>
        <?php if (empty($zalohy)): ?>
            <p class="text-gray-500">Zatiaľ nemáte žiadne zálohy.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($zalohy as $z): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($z['nazov']) ?></p>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($z["dennik_nazov"] ?? "Vymazaný denník") ?> · <?= date('j. n. Y H:i', strtotime($z['datum_vytvorenia'])) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" onsubmit="return confirm('Obnoviť? Aktuálne záznamy budú nahradené.')">
                                <input type="hidden" name="zaloha_id" value="<?= $z['PK_ID_zaloha'] ?>">
                                <button type="submit" name="restore_backup" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">Obnoviť</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Vymazať zálohu?')">
                                <input type="hidden" name="zaloha_id" value="<?= $z['PK_ID_zaloha'] ?>">
                                <button type="submit" name="delete_backup" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 transition">Vymazať</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
