<?php
require_once 'config/db.php';
require_once 'components/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Vynuluj odomknuté denníky pri návrate na prehľad
unset($_SESSION["unlocked_diaries"]);

// === PREMENOVANIE DENNÍKA ===
if (isset($_POST["rename"])) {
    $id    = (int)($_POST["id"] ?? 0);
    $nazov = trim($_POST["nazov"] ?? "");
    if ($id > 0 && $nazov !== "") {
        $pdo->prepare("UPDATE Dennik SET nazov = ? WHERE PK_ID_dennik = ? AND FK_ID_pouzivatel = ?")
            ->execute([$nazov, $id, $userId]);
    }
    header("Location: diaries.php");
    exit;
}

// === VYTVORENIE DENNÍKA ===
if (isset($_POST['create'])) {
    $nazov = trim($_POST['nazov'] ?? '');
    if ($nazov !== '') {
        $pdo->prepare("INSERT INTO Dennik (FK_ID_pouzivatel, nazov) VALUES (?, ?)")
            ->execute([$userId, $nazov]);
    }
    header('Location: diaries.php');
    exit;
}

// === VYMAZANIE DENNÍKA ===
if (isset($_POST['delete'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("DELETE FROM Dennik WHERE PK_ID_dennik = ? AND FK_ID_pouzivatel = ?")
            ->execute([$id, $userId]);
    }
    header('Location: diaries.php');
    exit;
}


// === ODOMKNUTIE DENNÍKA (heslo) ===
if (isset($_POST['unlock_diary'])) {
    $diaryId = (int)$_POST['diary_id'];
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT d.PK_ID_dennik, z.hash_hesla 
        FROM Dennik d
        LEFT JOIN Zabezpecenie z ON d.FK_ID_zabezpecenie = z.PK_ID_zabezpecenie
        WHERE d.PK_ID_dennik = ? AND d.FK_ID_pouzivatel = ?
    ");
    $stmt->execute([$diaryId, $userId]);
    $diary = $stmt->fetch();

    if ($diary && $diary['hash_hesla'] && password_verify($password, $diary['hash_hesla'])) {
        $_SESSION['unlocked_diaries'][$diaryId] = true;
    }
}

// === NAČÍTANIE DENNÍKOV ===
$stmt = $pdo->prepare("
    SELECT d.*, z.hash_hesla IS NOT NULL AS is_locked
    FROM Dennik d
    LEFT JOIN Zabezpecenie z ON d.FK_ID_zabezpecenie = z.PK_ID_zabezpecenie
    WHERE d.FK_ID_pouzivatel = ?
    ORDER BY d.datum_vytvorenia DESC
");
$stmt->execute([$userId]);
$diaries = $stmt->fetchAll();

$userStmt = $pdo->prepare("SELECT CONCAT(meno, ' ', priezvisko) FROM Pouzivatel WHERE PK_ID_pouzivatel = ?");
$userStmt->execute([$userId]);
$userName = $userStmt->fetchColumn() ?: 'Používateľ';
?>

<!DOCTYPE html>
<html lang="sk" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t("my_diaries") ?> – <?= t("app_name") ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#6366f1' }}}}
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
<?php include 'components/header.php'; ?>

<div class="flex-1 max-w-6xl mx-auto w-full px-6 py-10">
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-4xl font-bold text-gray-800"><?= t("my_diaries") ?></h1>
            <p class="text-gray-600 mt-2"><?= t("hello") ?>, <strong><?= htmlspecialchars($userName) ?></strong></p>
        </div>
    </div>

    <!-- Vytvorenie nového denníka -->
    <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
        <form method="POST" class="flex flex-col sm:flex-row gap-4">
            <input type="text" name="nazov" required placeholder="<?= t("diary_name_placeholder") ?>"
                   class="flex-1 px-6 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-primary focus:border-primary outline-none text-lg">
            <button name="create" class="px-10 py-4 bg-primary text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg">
                <?= t("create_diary") ?>
            </button>
        </form>
    </div>

    <!-- Zoznam denníkov -->
    <?php if (empty($diaries)): ?>
        <div class="text-center py-20">
            <p class="text-2xl text-gray-600">Zatiaľ nemáš žiadny denník.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($diaries as $d):
                $isLocked = $d['is_locked'] && empty($_SESSION['unlocked_diaries'][$d['PK_ID_dennik']]);
                ?>
                <div class="bg-white rounded-2xl shadow hover:shadow-xl transition <?= $isLocked ? 'opacity-90' : '' ?>">
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-800" id="title-<?= $d['PK_ID_dennik'] ?>">
                                    <?= htmlspecialchars($d['nazov']) ?>
                                </h3>
                                <form method="POST" class="hidden mt-1" id="rename-form-<?= $d['PK_ID_dennik'] ?>">
                                    <input type="hidden" name="id" value="<?= $d['PK_ID_dennik'] ?>">
                                    <div class="flex gap-2">
                                        <input type="text" name="nazov" value="<?= htmlspecialchars($d['nazov']) ?>"
                                               class="flex-1 px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <button type="submit" name="rename" class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-sm">✓</button>
                                        <button type="button" onclick="cancelRename(<?= $d['PK_ID_dennik'] ?>)" class="px-3 py-1 bg-gray-200 rounded-lg text-sm">✕</button>
                                    </div>
                                </form>
                            </div>
                            <button onclick="startRename(<?= $d['PK_ID_dennik'] ?>, '<?= addslashes(htmlspecialchars($d['nazov'])) ?>')"
                                    class="text-gray-400 hover:text-indigo-600 transition ml-2" title="Premenovať">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <?php if ($d['is_locked']): ?>
                                <span class="text-orange-600">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-sm text-gray-500 mb-8">
                            <?= t("created") ?>: <?= date('j. n. Y', strtotime($d['datum_vytvorenia'])) ?>
                        </p>

                        <?php if ($isLocked): ?>
                            <button onclick="openLockModal(<?= $d['PK_ID_dennik'] ?>)"
                                    class="w-full py-4 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 transition shadow-lg text-lg">
                                Odomknúť denník
                            </button>
                        <?php else: ?>
                            <div class="flex gap-4">
                                <a href="diary.php?id=<?= $d['PK_ID_dennik'] ?>"
                                   class="flex-1 text-center bg-primary text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition">
                                    <?= t("open") ?>
                                </a>
                                <form method="POST" onsubmit="return confirm('Naozaj zmazať?')">
                                    <input type="hidden" name="id" value="<?= $d['PK_ID_dennik'] ?>">
                                    <button name="delete" class="px-6 py-4 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition">
                                        <?= t("delete") ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODÁLNE OKNO PRE HESLO -->
<div id="lockModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full">
        <h2 class="text-3xl font-bold text-gray-800 mb-4 text-center">Odomknúť denník</h2>
        <p class="text-gray-600 text-center mb-8">Zadaj heslo pre prístup</p>

        <form method="POST">
            <input type="hidden" name="diary_id" id="modalDiaryId">
            <input type="password" name="password" required placeholder="Heslo"
                   class="w-full px-6 py-4 rounded-xl border border-gray-300 focus:ring-4 focus:ring-primary outline-none text-lg text-center mb-6">

            <div class="flex gap-4">
                <button type="submit" name="unlock_diary"
                        class="flex-1 bg-primary text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition">
                    Odomknúť
                </button>
                <button type="button" onclick="document.getElementById('lockModal').classList.add('hidden')"
                        class="flex-1 bg-gray-300 text-gray-800 py-4 rounded-xl font-bold hover:bg-gray-400 transition">
                    Zrušiť
                </button>
            </div>
        </form>

        <div class="mt-8 text-center">
            <button onclick="alert('Biometria zatiaľ nie je podporovaná')"
                    class="text-indigo-600 hover:text-indigo-800 font-medium">
                Použiť odtlačok prsta / Face ID
            </button>
        </div>
    </div>
</div>

<script>
    function openLockModal(diaryId) {
        document.getElementById('modalDiaryId').value = diaryId;
        document.getElementById('lockModal').classList.remove('hidden');
        document.querySelector('#lockModal input[type=password]').focus();
    }
</script>
<script>
function startRename(id, current) {
    document.getElementById('title-' + id).classList.add('hidden');
    const form = document.getElementById('rename-form-' + id);
    form.classList.remove('hidden');
    form.querySelector('input[name="nazov"]').focus();
}
function cancelRename(id) {
    document.getElementById('title-' + id).classList.remove('hidden');
    document.getElementById('rename-form-' + id).classList.add('hidden');
}
</script>
</body>
</html>