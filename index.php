<?php
require_once 'config/db.php';
require_once 'components/functions.php';

$error = $success = '';

// ====================== REGISTRÁCIA ======================
if (isset($_POST['register'])) {
    $meno       = trim($_POST['meno'] ?? '');
    $priezvisko = trim($_POST['priezvisko'] ?? '');
    // Používame filter_var pre validáciu e-mailu
    $email      = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass1      = $_POST['password'] ?? '';
    $pass2      = $_POST['password2'] ?? '';

    if (!$email) {
        $error = "Neplatný e-mail!";
    } elseif (empty($meno) || empty($priezvisko)) {
        $error = "Vyplňte Meno aj Priezvisko!";
    } elseif ($pass1 !== $pass2) {
        $error = "Heslá sa nezhodujú!";
    } elseif (strlen($pass1) < 4) { // Zvýšenie minimálnej dĺžky hesla pre lepšie zabezpečenie
        $error = "Heslo musí mať aspoň 4 znaky!";
    } else {
        // 1. Kontrola existencie e-mailu v tabuľke Pouzivatel
        $check = $pdo->prepare("SELECT 1 FROM Pouzivatel WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Tento e-mail už existuje!";
        } else {
            try {
                // Začiatok transakcie na zabezpečenie zápisu do oboch tabuliek
                $pdo->beginTransaction();

                // 2. Vytvorenie záznamu v tabuľke Pouzivatel
                $stmt = $pdo->prepare("INSERT INTO Pouzivatel (meno, priezvisko, email) VALUES (?, ?, ?)");
                $stmt->execute([$meno, $priezvisko, $email]);
                $userId = $pdo->lastInsertId();

                // 3. Vytvorenie záznamu v tabuľke Zabezpecenie
                $hash = password_hash($pass1, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO Zabezpecenie (FK_ID_pouzivatel, typ_zabezpecenia, hash_hesla) VALUES (?, 'password', ?)")
                        ->execute([$userId, $hash]);

                $pdo->commit();
                $success = "Účet bol úspešne vytvorený! Teraz sa prihláste.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Chyba pri registrácii: " . $e->getMessage();
            }
        }
    }
}

// ====================== PRIHLÁSENIE ======================
if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass  = $_POST['password'] ?? '';

    if (!$email) {
        $error = "Zadajte platný e-mail!";
    } else {
        // Vyberieme PK_ID_pouzivatel a hash_hesla pre overenie
        $stmt = $pdo->prepare("
            SELECT p.PK_ID_pouzivatel, z.hash_hesla
            FROM Pouzivatel p
            LEFT JOIN Zabezpecenie z ON p.PK_ID_pouzivatel = z.FK_ID_pouzivatel
            WHERE p.email = ? AND z.typ_zabezpecenia = 'password'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Overenie, či používateľ existuje a či sa zhoduje heslo
        if ($user && password_verify($pass, $user['hash_hesla'])) {
            $_SESSION['user_id'] = $user['PK_ID_pouzivatel'];
            header('Location: diaries.php');
            exit;
        } else {
            $error = "Nesprávny e-mail alebo heslo!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sk" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Môj Denník • Prihlásenie/Registrácia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#6366f1' }}}}</script>
</head>
<body class="h-full bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-800 flex items-center justify-center p-4">
<div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl p-10 w-full max-w-5xl">
    <h1 class="text-6xl font-bold text-white text-center mb-4">Môj Denník</h1>
    <p class="text-white/80 text-center text-xl mb-10">Tvoj súkromný priestor</p>

    <?php if ($error): ?>
        <div class="bg-red-600 text-white p-4 rounded-lg mb-6 text-center font-bold" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-600 text-white p-4 rounded-lg mb-6 text-center font-bold" role="alert"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-10">
        <div class="bg-white/10 rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-white text-center mb-8">Prihlásenie</h2>
            <form method="POST" class="space-y-6">
                <input type="email" name="email" required placeholder="E-mail" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none">
                <input type="password" name="password" required placeholder="Heslo" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none">
                <button name="login" class="w-full bg-primary py-4 rounded-xl font-bold text-white hover:bg-indigo-700 transition text-lg">Prihlásiť sa</button>
            </form>
        </div>

        <div class="bg-white/10 rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-white text-center mb-8">Registrácia</h2>
            <form method="POST" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="meno" required placeholder="Meno" class="px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none" value="<?= htmlspecialchars($meno ?? '') ?>">
                    <input type="text" name="priezvisko" required placeholder="Priezvisko" class="px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none" value="<?= htmlspecialchars($priezvisko ?? '') ?>">
                </div>
                <input type="email" name="email" required placeholder="E-mail" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none" value="<?= htmlspecialchars($email ?? '') ?>">
                <input type="password" name="password" required placeholder="Heslo (min. 8 znakov)" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none">
                <input type="password" name="password2" required placeholder="Heslo znova" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none">
                <button name="register" class="w-full bg-green-600 py-4 rounded-xl font-bold text-white hover:bg-green-700 transition text-lg">Vytvoriť účet</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>