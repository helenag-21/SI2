<?php
require_once 'config/db.php';
require_once 'components/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$userId = $_SESSION['user_id'];

// ---------- PARAMETRE ----------
$zapisId  = $_GET['id'] ?? null;
$dennikId = $_GET['dennik'] ?? null;
$isEdit   = ($zapisId !== null);

if (!$isEdit && !$dennikId) {
    header('Location: diaries.php');
    exit;
}

// ---------- NAČÍTANIE ZÁPISU ----------
$entry = null;
$attachments = [];
$isLocked = false;

if ($isEdit) {
    $stmt = $pdo->prepare("
        SELECT z.*, k.nazov AS kategoria_nazov, zb.hash_hesla IS NOT NULL AS locked
        FROM Zapis z
        LEFT JOIN Kategoria k ON z.FK_ID_kategoria = k.PK_ID_kategoria
        LEFT JOIN Zabezpecenie zb ON z.FK_ID_zabezpecenie = zb.PK_ID_zabezpecenie
        WHERE z.PK_ID_zapis = ? AND z.FK_ID_dennik IN (
            SELECT PK_ID_dennik FROM Dennik WHERE FK_ID_pouzivatel = ?
        )
    ");
    $stmt->execute([$zapisId, $userId]);
    $entry = $stmt->fetch();

    if (!$entry) die('Zápis neexistuje alebo nemáš prístup.');
    $dennikId = $entry['FK_ID_dennik'];
    $isLocked = $entry['locked'] && empty($_SESSION['unlocked_entries'][$zapisId]);

    // Načítanie príloh
    $stmt = $pdo->prepare("SELECT * FROM Priloha WHERE FK_ID_zapis = ?");
    $stmt->execute([$zapisId]);
    $attachments = $stmt->fetchAll();
}

// ---------- ODOMKNUTIE ZÁPISU ----------
if (isset($_POST['unlock_entry'])) {
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT z2.hash_hesla FROM Zapis z LEFT JOIN Zabezpecenie z2 ON z.FK_ID_zabezpecenie = z2.PK_ID_zabezpecenie WHERE z.PK_ID_zapis = ?");
    $stmt->execute([$zapisId]);
    $hash = $stmt->fetchColumn();

    if ($hash && password_verify($password, $hash)) {
        $_SESSION['unlocked_entries'][$zapisId] = true;
        $isLocked = false;
    }
}

// ---------- ZAMKNUTIE ZÁPISU ----------
if (isset($_POST['lock_entry'])) {
    $password = $_POST['lock_password'] ?? '';
    if (strlen($password) >= 4) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        if ($entry['FK_ID_zabezpecenie'] ?? null) {
            $pdo->prepare("UPDATE Zabezpecenie SET hash_hesla = ? WHERE PK_ID_zabezpecenie = ?")
                    ->execute([$hash, $entry['FK_ID_zabezpecenie']]);
        } else {
            $pdo->prepare("INSERT INTO Zabezpecenie (FK_ID_pouzivatel, typ_zabezpecenia, hash_hesla) VALUES (?, 'password', ?)")
                    ->execute([$userId, $hash]);
            $secId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE Zapis SET FK_ID_zabezpecenie = ? WHERE PK_ID_zapis = ?")
                    ->execute([$secId, $zapisId]);
        }
        unset($_SESSION['unlocked_entries'][$zapisId]);
        header("Location: entry.php?id=$zapisId&dennik=$dennikId");
        exit;
    }
}

// ---------- NAČÍTANIE KATEGÓRIÍ A ŠABLÓN ----------
$categories = $pdo->query("SELECT PK_ID_kategoria, nazov FROM Kategoria ORDER BY nazov")->fetchAll();
$templates  = $pdo->query("SELECT PK_ID_sablona AS id, nazov AS name, struktura AS content FROM Sablona ORDER BY nazov")->fetchAll();

// ---------- ULOŽENIE ZÁPISU ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['lock_entry']) && !isset($_POST['unlock_entry'])) {
    $title   = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $catId   = $_POST['category'] === '' ? null : (int)$_POST['category'];

    if ($isEdit) {
        $pdo->prepare("UPDATE Zapis SET nazov = ?, obsah = ?, FK_ID_kategoria = ?, datum_upravy = NOW() WHERE PK_ID_zapis = ?")
                ->execute([$title, $content, $catId, $zapisId]);
    } else {
        $pdo->prepare("INSERT INTO Zapis (FK_ID_dennik, nazov, obsah, FK_ID_kategoria, datum_upravy) VALUES (?, ?, ?, ?, NOW())")
                ->execute([$dennikId, $title, $content, $catId]);
        $zapisId = $pdo->lastInsertId();
        $isEdit = true;
    }

    // Prílohy
    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp) {
            if ($tmp && $_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['attachments']['name'][$key], PATHINFO_EXTENSION);
                $filename = uniqid('att_') . '.' . $ext;
                move_uploaded_file($tmp, __DIR__ . "/assets/uploads/$filename");
                $pdo->prepare("INSERT INTO Priloha (FK_ID_zapis, nazov_suboru, typ_suboru) VALUES (?, ?, ?)")
                        ->execute([$zapisId, $filename, $_FILES['attachments']['type'][$key]]);
            }
        }
    }

    header("Location: diary.php?id=$dennikId");
    exit;
}

// ---------- VYMAZANIE ----------

// ---------- VYMAZANIE PRÍLOHY ----------
if ($isEdit && isset($_GET["del_att"])) {
    $attId = (int)$_GET["del_att"];
    $att = $pdo->prepare("SELECT nazov_suboru FROM Priloha WHERE PK_ID_priloha = ? AND FK_ID_zapis = ?");
    $att->execute([$attId, $zapisId]);
    $file = $att->fetchColumn();
    if ($file) {
        @unlink(__DIR__ . "/assets/uploads/" . $file);
        $pdo->prepare("DELETE FROM Priloha WHERE PK_ID_priloha = ?")->execute([$attId]);
    }
    header("Location: entry.php?id=$zapisId&dennik=$dennikId");
    exit;
}
if ($isEdit && isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM Zapis WHERE PK_ID_zapis = ?")->execute([$zapisId]);
    $pdo->prepare("DELETE FROM Priloha WHERE FK_ID_zapis = ?")->execute([$zapisId]);
    header("Location: diary.php?id=$dennikId");
    exit;
}
?>

<!DOCTYPE html>
<html lang="sk" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Úprava zápisu' : 'Nový zápis' ?> – Môj Denník</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#6366f1' }}}}
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
<?php include 'components/header.php'; ?>

<div class="flex-1 max-w-4xl mx-auto w-full px-4 py-8">
    <div class="bg-white rounded-2xl shadow-xl p-8">

        <?php if ($isEdit && $isLocked): ?>
            <!-- ZAMKNUTÝ ZÁPIS -->
            <div class="text-center py-20">
                <div class="w-32 h-32 mx-auto mb-8 bg-orange-100 rounded-full flex items-center justify-center">
                    <svg class="w-20 h-20 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2V5c0-1.104-.896-2-2-2s-2 .896-2 2v4c0 1.104.896 2 2 2z M12 15v4m-4 0h8"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Tento zápis je zamknutý</h2>
                <form method="POST" class="max-w-sm mx-auto">
                    <input type="password" name="password" required placeholder="Zadaj heslo"
                           class="w-full px-6 py-4 rounded-xl border border-gray-300 focus:ring-4 focus:ring-primary outline-none text-center text-lg mb-6">
                    <button name="unlock_entry" class="w-full bg-primary text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition text-lg">
                        Odomknúť zápis
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- NORMÁLNY EDITOR -->
            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="text" name="title" id="title" placeholder="Názov zápisu (voliteľné)"
                       value="<?= htmlspecialchars($entry['nazov'] ?? '') ?>"
                       class="w-full text-3xl font-bold border-0 border-b-4 border-transparent focus:border-primary outline-none pb-3">

                <textarea name="content" id="content" rows="18" required placeholder="Začnite písať..."
                          class="w-full p-5 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-indigo-100 outline-none text-lg leading-relaxed resize-none"><?= htmlspecialchars($entry['obsah'] ?? '') ?></textarea>

                <!-- ... (kategórie, šablóny, prílohy – rovnaké ako predtým) ... -->
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- ĽAVÁ STRANA -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kategória</label>
                            <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-4 focus:ring-primary">
                                <option value="">Žiadna kategória</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['PK_ID_kategoria'] ?>" <?= ($entry && $entry['FK_ID_kategoria'] == $cat['PK_ID_kategoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nazov']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Šablóna</label>
                            <select id="template-select" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-4 focus:ring-primary">
                                <option value="">— Bez šablóny —</option>
                                <?php foreach ($templates as $tpl): ?>
                                    <option value="<?= htmlspecialchars(json_encode(['content' => $tpl['content']]), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($tpl['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- PRAVÁ STRANA – PRÍLOHY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prílohy</label>
                        <div class="mt-1 px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-primary transition">
                            <div class="space-y-3 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">...</svg>
                                <label for="file-upload" class="cursor-pointer inline-flex items-center gap-2 bg-primary text-white rounded-lg px-6 py-2.5 font-medium hover:bg-indigo-700 transition text-sm shadow">
                                    Vybrať súbory
                                    <input id="file-upload" name="attachments[]" type="file" multiple class="sr-only">
                                </label>
                                <p class="text-xs text-gray-500">PNG, JPG, PDF do 10MB</p>
                            </div>
                        </div>
                        <div id="file-list" class="mt-4 space-y-1 text-sm"></div>
                        <?php if (!empty($attachments)): ?>
                            <div class="mt-5">
                                <p class="text-xs font-medium text-gray-600 mb-2">Aktuálne prílohy:</p>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($attachments as $att): ?>
                                        <div class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded text-xs">
                                            <a href="/assets/uploads/<?= htmlspecialchars($att['nazov_suboru']) ?>" target="_blank" class="hover:underline">
                                                <?= htmlspecialchars(pathinfo($att['nazov_suboru'], PATHINFO_BASENAME)) ?>
                                            </a>
                                            <a href="entry.php?id=<?= $zapisId ?>&dennik=<?= $dennikId ?>&del_att=<?= $att['PK_ID_priloha'] ?>"
                                               onclick="return confirm('Odstrániť prílohu?')"
                                               class="ml-1 text-red-400 hover:text-red-600 font-bold leading-none">×</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TLAČIDLÁ -->
                <div class="flex justify-between items-center pt-8 border-t">
                    <div class="flex flex-col gap-6">
                        <a href="diary.php?id=<?= $dennikId ?>" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                            Zrušiť
                        </a>

                        <div class="space-y-4">
                            <p class="text-sm font-semibold text-gray-700">Chrániť tento zápis</p>
                            <div class="flex flex-wrap gap-3">
                                <button type="button" onclick="document.getElementById('lockEntryModal').classList.remove('hidden')"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 font-medium text-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">...</svg>
                                    Zamknúť heslom
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <?php if ($isEdit): ?>
                            <button type="button" onclick="if(confirm('Naozaj vymazať?')) location.href='entry.php?id=<?= $zapisId ?>&delete=1'"
                                    class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                                Vymazať
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-indigo-700 font-medium shadow-lg">
                            <?= $isEdit ? 'Uložiť zmeny' : 'Vytvoriť zápis' ?>
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- MODÁL – ZAMKNÚŤ ZÁPIS -->
<div id="lockEntryModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Zamknúť zápis</h2>
        <form method="POST">
            <input type="password" name="lock_password" required placeholder="Nové heslo (min. 4 znaky)"
                   class="w-full px-6 py-4 rounded-xl border border-gray-300 focus:ring-4 focus:ring-primary outline-none text-center text-lg mb-6">
            <div class="flex gap-4">
                <button name="lock_entry" class="flex-1 bg-orange-600 text-white py-4 rounded-xl font-bold hover:bg-orange-700 transition">
                    Zamknúť heslom
                </button>
                <button type="button" onclick="document.getElementById('lockEntryModal').classList.add('hidden')"
                        class="flex-1 bg-gray-300 text-gray-800 py-4 rounded-xl font-bold hover:bg-gray-400 transition">
                    Zrušiť
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Šablóny + súbory (rovnaké ako predtým)
    document.getElementById('template-select')?.addEventListener('change', function () {
        if (!this.value) return;
        try {
            const data = JSON.parse(this.value);
            document.getElementById('content').value = data.content || '';
        } catch (e) {}
        this.selectedIndex = 0;
    });

    document.getElementById('file-upload')?.addEventListener('change', function () {
        const list = document.getElementById('file-list');
        list.innerHTML = '';
        Array.from(this.files).forEach(f => {
            list.innerHTML += `<div class="text-xs text-gray-600">${f.name} <span class="text-gray-400">(${(f.size/1024/1024).toFixed(2)} MB)</span></div>`;
        });
    });
</script>
</body>
</html>