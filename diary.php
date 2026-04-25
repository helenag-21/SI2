<?php
require_once 'config/db.php';
require_once 'components/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Vynuluj odomknuté záznamy pri návrate na denník
unset($_SESSION["unlocked_entries"]);

// --- ID denníka ---
$dennikId = $_GET['id'] ?? null;
if (!$dennikId) die('Chýba ID denníka.');

// --- Vyhľadávací výraz ---
$query = trim($_GET['q'] ?? '');

// --- Overenie vlastníctva + načítanie denníka ---
$stmt = $pdo->prepare("
    SELECT d.*, z.hash_hesla IS NOT NULL AS is_locked, z.PK_ID_zabezpecenie
    FROM Dennik d
    LEFT JOIN Zabezpecenie z ON d.FK_ID_zabezpecenie = z.PK_ID_zabezpecenie
    WHERE d.PK_ID_dennik = ? AND d.FK_ID_pouzivatel = ?
");
$stmt->execute([$dennikId, $userId]);
$dennik = $stmt->fetch();

if (!$dennik) die('Denník neexistuje alebo nemáš prístup.');

// --- ODOMKNUTIE (ak bolo zamknuté) ---
if (isset($_POST['unlock_diary'])) {
    $password = $_POST['password'] ?? '';
    if ($dennik['hash_hesla'] && password_verify($password, $dennik['hash_hesla'])) {
        $_SESSION['unlocked_diaries'][$dennikId] = true;
    }
}
$isUnlocked = !empty($_SESSION['unlocked_diaries'][$dennikId]);

// --- ZAMKNUTIE DENNÍKA HESLOM ---
if (isset($_POST['lock_diary'])) {
    $password = $_POST['lock_password'] ?? '';
    if (strlen($password) >= 4) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($dennik['PK_ID_zabezpecenie']) {
            $pdo->prepare("UPDATE Zabezpecenie SET hash_hesla = ? WHERE PK_ID_zabezpecenie = ?")
                    ->execute([$hash, $dennik['PK_ID_zabezpecenie']]);
        } else {
            $pdo->prepare("INSERT INTO Zabezpecenie (FK_ID_pouzivatel, typ_zabezpecenia, hash_hesla) VALUES (?, 'password', ?)")
                    ->execute([$userId, $hash]);
            $secId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE Dennik SET FK_ID_zabezpecenie = ? WHERE PK_ID_dennik = ?")
                    ->execute([$secId, $dennikId]);
        }
        unset($_SESSION['unlocked_diaries'][$dennikId]);
        header("Location: diary.php?id=$dennikId");
        exit;
    }
}

// --- Načítanie zápisov (s vyhľadávaním) ---
if ($query !== '') {
    $stmt = $pdo->prepare("
        SELECT z.PK_ID_zapis AS id, z.nazov AS title, z.obsah AS content, z.datum_upravy AS date,
               k.nazov AS category,
               zb.hash_hesla IS NOT NULL AS locked
        FROM Zapis z
        LEFT JOIN Kategoria k ON z.FK_ID_kategoria = k.PK_ID_kategoria
        LEFT JOIN Zabezpecenie zb ON z.FK_ID_zabezpecenie = zb.PK_ID_zabezpecenie
        WHERE z.FK_ID_dennik = ? AND (z.nazov LIKE ? OR z.obsah LIKE ?)
        ORDER BY z.datum_upravy DESC
    ");
    $like = "%$query%";
    $stmt->execute([$dennikId, $like, $like]);
} else {
    $stmt = $pdo->prepare("
        SELECT z.PK_ID_zapis AS id, z.nazov AS title, z.obsah AS content, z.datum_upravy AS date,
               k.nazov AS category,
               zb.hash_hesla IS NOT NULL AS locked
        FROM Zapis z
        LEFT JOIN Kategoria k ON z.FK_ID_kategoria = k.PK_ID_kategoria
        LEFT JOIN Zabezpecenie zb ON z.FK_ID_zabezpecenie = zb.PK_ID_zabezpecenie
        WHERE z.FK_ID_dennik = ?
        ORDER BY z.datum_upravy DESC
    ");
    $stmt->execute([$dennikId]);
}
$entries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sk" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dennik['nazov']) ?> – Môj Denník</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#6366f1' }}}}
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
<?php include 'components/header.php'; ?>

<div class="flex-1 max-w-6xl mx-auto w-full px-6 py-10">

    <!-- Horná lišta s názvom a tlačidlami -->
    <div class="flex justify-between items-center mb-10">
        <div>
            <a href="diaries.php" class="text-gray-500 hover:text-gray-700 text-sm mb-2 inline-block">← Späť na denníky</a>
            <h1 class="text-4xl font-bold text-gray-800"><?= htmlspecialchars($dennik['nazov']) ?></h1>
        </div>

        <div class="flex gap-4">
            <a href="entry.php?dennik=<?= $dennikId ?>"
               class="px-8 py-4 bg-primary text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg">
                + Nový zápis
            </a>

            <button onclick="document.getElementById('lockModal').classList.remove('hidden')"
                    class="px-8 py-4 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 transition shadow-lg flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 11c1.104 0 2-.896 2-2V5c0-1.104-.896-2-2-2s-2 .896-2 2v4c0 1.104.896 2 2 2z M12 15v4m-4 0h8"/>
                </svg>
                Zamknúť denník
            </button>
        </div>
    </div>

    <?php if ($dennik['is_locked'] && !$isUnlocked): ?>
        <!-- ZAMKNUTÝ DENNÍK -->
        <div class="text-center py-20">
            <div class="w-32 h-32 mx-auto mb-8 bg-orange-100 rounded-full flex items-center justify-center">
                <svg class="w-20 h-20 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 11c1.104 0 2-.896 2-2V5c0-1.104-.896-2-2-2s-2 .896-2 2v4c0 1.104.896 2 2 2z M12 15v4m-4 0h8"/>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Tento denník je zamknutý</h2>
            <form method="POST" class="max-w-sm mx-auto">
                <input type="password" name="password" required placeholder="Zadaj heslo"
                       class="w-full px-6 py-4 rounded-xl border border-gray-300 focus:ring-4 focus:ring-primary outline-none text-center text-lg mb-4">
                <button name="unlock_diary" class="w-full bg-primary text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition text-lg">
                    Odomknúť
                </button>
            </form>
        </div>
    <?php else: ?>

        <!-- VYHĽADÁVANIE -->
        <form method="GET" class="mb-8 flex gap-3">
            <input type="hidden" name="id" value="<?= $dennikId ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>"
                   placeholder="Vyhľadať v zápisoch..."
                   class="flex-1 px-5 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-400 outline-none">
            <button type="submit"
                    class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition">
                Hľadať
            </button>
            <?php if ($query): ?>
                <a href="diary.php?id=<?= $dennikId ?>"
                   class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition">
                    Zrušiť filter
                </a>
            <?php endif; ?>
        </form>

        <!-- ZOZNAM ZÁPISOV -->
        <?php if ($query && empty($entries)): ?>
            <div class="text-center py-20">
                <p class="text-xl text-gray-500">
                    Neboli nájdené žiadne zápisy pre „<?= htmlspecialchars($query) ?>".
                </p>
                <a href="diary.php?id=<?= $dennikId ?>" class="mt-4 inline-block text-indigo-600 hover:underline">
                    Zobraziť všetky zápisy
                </a>
            </div>
        <?php elseif (empty($entries)): ?>
            <div class="text-center py-20">
                <p class="text-2xl text-gray-600 mb-8">Zatiaľ nemáš žiadny zápis.</p>
                <a href="entry.php?dennik=<?= $dennikId ?>" class="inline-block bg-primary text-white px-10 py-4 rounded-xl hover:bg-indigo-700 transition shadow-lg font-bold text-lg">
                    Vytvoriť prvý zápis
                </a>
            </div>
        <?php else: ?>
            <?php if ($query): ?>
                <p class="text-sm text-gray-500 mb-4">
                    Výsledky pre „<?= htmlspecialchars($query) ?>": <?= count($entries) ?> zápisov
                </p>
            <?php endif; ?>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($entries as $e): ?>
                    <div class="bg-white rounded-2xl shadow hover:shadow-xl transition cursor-pointer"
                         onclick="location.href='entry.php?id=<?= $e['id'] ?>&dennik=<?= $dennikId ?>'">
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-3">
                                <?= htmlspecialchars($e['title'] ?: 'Bez názvu') ?>
                                <?php if ($e['locked']): ?>
                                    <span class="text-orange-600 text-sm ml-2">[Zamknuté]</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                                <?= nl2br(htmlspecialchars(substr(strip_tags($e['content']), 0, 120))) ?>...
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= date('j. n. Y H:i', strtotime($e['date'])) ?>
                                <?php if ($e['category']): ?> • <?= htmlspecialchars($e['category']) ?><?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- MODÁLNE OKNO – ZAMKNÚŤ DENNÍK -->
<div id="lockModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Zamknúť denník</h2>
        <form method="POST">
            <input type="password" name="lock_password" required placeholder="Nové heslo (min. 4 znaky)"
                   class="w-full px-6 py-4 rounded-xl border border-gray-300 focus:ring-4 focus:ring-primary outline-none text-center text-lg mb-6">
            <div class="flex gap-4">
                <button name="lock_diary" class="flex-1 bg-orange-600 text-white py-4 rounded-xl font-bold hover:bg-orange-700 transition">
                    Zamknúť heslom
                </button>
                <button type="button" onclick="document.getElementById('lockModal').classList.add('hidden')"
                        class="flex-1 bg-gray-300 text-gray-800 py-4 rounded-xl font-bold hover:bg-gray-400 transition">
                    Zrušiť
                </button>
            </div>
        </form>
        <div class="mt-8 text-center">
            <button onclick="alert('Biometria bude v ďalšej verzii')"
                    class="text-indigo-600 hover:text-indigo-800 font-medium">
                Použiť odtlačok prsta / Face ID
            </button>
        </div>
    </div>
</div>
</body>
</html>
