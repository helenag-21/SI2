<?php
require_once 'config/db.php';
require_once 'components/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$userId = $_SESSION['user_id'];

// === PRIDANIE KATEGÓRIE ===
if (!empty($_POST['new_category'])) {
    $nazov = trim($_POST['new_category']);
    if ($nazov !== '') {
        // Skontroluj duplicitu
        $check = $pdo->prepare("SELECT 1 FROM Kategoria WHERE nazov = ?");
        $check->execute([$nazov]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO Kategoria (FK_ID_pouzivatel, nazov) VALUES (?, ?)")->execute([$userId, $nazov]);
        }
    }
    header('Location: settings.php');
    exit;
}

// === PRIDANIE ŠABLÓNY ===
if (!empty($_POST['template_name']) && !empty($_POST['template_content'])) {
    $nazov    = trim($_POST['template_name']);
    $title    = trim($_POST['template_title'] ?? '');
    $content  = $_POST['template_content'];

    if ($nazov !== '') {
        $check = $pdo->prepare("SELECT 1 FROM Sablona WHERE nazov = ?");
        $check->execute([$nazov]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO Sablona (FK_ID_pouzivatel, nazov, struktura) VALUES (?, ?, ?)")
                    ->execute([$userId, $nazov, $content]);
        }
    }
    header('Location: settings.php');
    exit;
}

// === VYMAZANIE KATEGÓRIE ===
if (isset($_GET['del_cat'])) {
    $nazov = $_GET['del_cat'];
    $pdo->prepare("DELETE FROM Kategoria WHERE nazov = ?")->execute([$userId, $nazov]);
    header('Location: settings.php');
    exit;
}

// === VYMAZANIE ŠABLÓNY ===
if (isset($_GET['del_tpl'])) {
    $id = (int)$_GET['del_tpl'];
    $pdo->prepare("DELETE FROM Sablona WHERE PK_ID_sablona = ?")->execute([$id]);
    header('Location: settings.php');
    exit;
}

// === NAČÍTANIE DÁT Z DB ===
$categories = $pdo->query("SELECT PK_ID_kategoria, nazov FROM Kategoria ORDER BY nazov")->fetchAll();
$templates  = $pdo->query("SELECT PK_ID_sablona, nazov, struktura FROM Sablona ORDER BY nazov")->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'sk' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nastavenia – Môj Denník</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#6366f1' }}}}
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
<?php include 'components/header.php'; ?>

<div class="flex-1 max-w-5xl mx-auto w-full px-6 py-10">
    <h1 class="text-4xl font-bold text-gray-800 mb-10">
        <?= t("manage_categories_templates") ?>
    </h1>

    <div class="grid md:grid-cols-2 gap-10">

        <!-- KATEGÓRIE -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-8"><?= t("categories") ?></h2>

            <form method="POST" class="flex gap-4 mb-8">
                <input type="text" name="new_category" required placeholder="<?= t("new_category_placeholder") ?>"
                       class="flex-1 px-6 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-primary outline-none text-lg">
                <button type="submit" class="px-8 py-4 bg-primary text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg">
                    <?= t("add") ?>
                </button>
            </form>

            <div class="space-y-3">
                <?php if (empty($categories)): ?>
                    <p class="text-gray-500 italic text-center py-8">Zatiaľ nemáš žiadne kategórie</p>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="flex justify-between items-center bg-gray-50 px-6 py-4 rounded-xl hover:bg-gray-100 transition">
                            <span class="font-medium text-gray-800"><?= htmlspecialchars($cat['nazov']) ?></span>
                            <a href="settings.php?del_cat=<?= urlencode($cat['nazov']) ?>"
                               onclick="return confirm('Naozaj vymazať kategóriu „<?= addslashes(htmlspecialchars($cat['nazov'])) ?>“?')"
                               class="text-red-600 hover:text-red-800 font-medium text-sm"><?= t("delete") ?></a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ŠABLÓNY -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-8"><?= t("templates") ?></h2>

            <form method="POST" class="space-y-6 mb-8">
                <input type="text" name="template_name" required placeholder="<?= t("template_name") ?>"
                       class="w-full px-6 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-primary outline-none text-lg">

                <textarea name="template_content" required rows="8" placeholder="<?= t("template_content") ?>"
                          class="w-full p-6 border border-gray-300 rounded-xl focus:ring-4 focus:ring-primary outline-none text-base resize-none"></textarea>

                <button type="submit" class="w-full px-8 py-5 bg-primary text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg text-lg">
                    <?= t("create_template") ?>
                </button>
            </form>

            <div class="space-y-4">
                <?php if (empty($templates)): ?>
                    <p class="text-gray-500 italic text-center py-8">Zatiaľ nemáš žiadne šablóny</p>
                <?php else: ?>
                    <?php foreach ($templates as $tpl): ?>
                        <div class="bg-gray-50 p-6 rounded-xl hover:bg-gray-100 transition">
                            <div class="flex justify-between items-start gap-4">
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg text-gray-800 mb-2">
                                        <?= htmlspecialchars($tpl['nazov']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 line-clamp-3 leading-relaxed">
                                        <?= nl2br(htmlspecialchars(substr($tpl['struktura'], 0, 150))) ?>
                                        <?= strlen($tpl['struktura']) > 150 ? '...' : '' ?>
                                    </p>
                                </div>
                                <a href="settings.php?del_tpl=<?= $tpl['PK_ID_sablona'] ?>"
                                   onclick="return confirm('Naozaj vymazať šablónu „<?= addslashes(htmlspecialchars($tpl['nazov'])) ?>“?')"
                                   class="text-red-600 hover:text-red-800 font-medium text-sm whitespace-nowrap self-start">
                                    Vymazať
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<!-- Zmena hesla -->
<div id="password" class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6"><?= t('change_password') ?></h2>
        <?php
        $pwMsg = '';
        $pwErr = '';
        if (isset($_POST['change_password'])) {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $stmt = $pdo->prepare("SELECT z.PK_ID_zabezpecenie, z.hash_hesla FROM Zabezpecenie z WHERE z.FK_ID_pouzivatel = ? AND z.typ_zabezpecenia = 'password'");
            $stmt->execute([$userId]);
            $sec = $stmt->fetch();

            if (!$sec || !password_verify($current, $sec['hash_hesla'])) {
                $pwErr = 'Aktuálne heslo je nesprávne.';
            } elseif (strlen($new) < 6) {
                $pwErr = 'Nové heslo musí mať aspoň 6 znakov.';
            } elseif ($new !== $confirm) {
                $pwErr = 'Heslá sa nezhodujú.';
            } else {
                $pdo->prepare("UPDATE Zabezpecenie SET hash_hesla = ? WHERE PK_ID_zabezpecenie = ?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $sec['PK_ID_zabezpecenie']]);
                $pwMsg = 'Heslo bolo úspešne zmenené.';
            }
        }
        ?>
        <?php if ($pwMsg): ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= $pwMsg ?></div>
        <?php endif; ?>
        <?php if ($pwErr): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= $pwErr ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4 max-w-md">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aktuálne heslo</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nové heslo</label>
                <input type="password" name="new_password" required minlength="6"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Potvrdiť nové heslo</label>
                <input type="password" name="confirm_password" required minlength="6"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" name="change_password"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition">
                <?= t('change_password') ?>
            </button>
        </form>
    </div>
</div>
</body>
</html>
