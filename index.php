<?php
require_once 'config/db.php';
require_once 'components/functions.php';

$error = $success = '';

// ====================== REGISTRÁCIA ======================
if (isset($_POST['register'])) {
    $meno       = trim($_POST['meno'] ?? '');
    $priezvisko = trim($_POST['priezvisko'] ?? '');
    // Používame filter_var pre validáciu e-mailu
    $email_raw  = trim($_POST["email"] ?? "");
    $email      = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
    $pass1      = $_POST['password'] ?? '';
    $pass2      = $_POST['password2'] ?? '';

    if (!$email) {
        $error = "Neplatný e-mail!";
    } elseif (empty($meno) || empty($priezvisko)) {
        $error = "Vyplňte Meno aj Priezvisko!";
    } elseif ($pass1 !== $pass2) {
        $error = "Heslá sa nezhodujú!";
    } elseif (strlen($pass1) < 8) {
        $error = "Heslo musí mať aspoň 8 znakov.";
    } elseif (!preg_match('/[A-Z]/', $pass1)) {
        $error = "Heslo musí obsahovať aspoň jedno veľké písmeno.";
    } elseif (!preg_match('/[a-z]/', $pass1)) {
        $error = "Heslo musí obsahovať aspoň jedno malé písmeno.";
    } elseif (!preg_match('/[0-9]/', $pass1)) {
        $error = "Heslo musí obsahovať aspoň jedno číslo.";
    } elseif (!preg_match('/[\W_]/', $pass1)) {
        $error = "Heslo musí obsahovať aspoň jeden špeciálny znak."; //
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
                <div class="relative">
                    <input type="password" id="login-pass" name="password" required placeholder="Heslo" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none pr-14">
                    <button type="button" onclick="togglePassword('login-pass', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/60 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <button name="login" class="w-full bg-primary py-4 rounded-xl font-bold text-white hover:bg-indigo-700 transition text-lg">Prihlásiť sa</button>
            </form>
        </div>

        <div class="bg-white/10 rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-white text-center mb-8">Registrácia</h2>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white/80 text-sm mb-1">Meno <span class="text-red-400">*</span></label>
                        <input type="text" name="meno" required placeholder="Meno" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none" value="<?= htmlspecialchars($meno ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-white/80 text-sm mb-1">Priezvisko <span class="text-red-400">*</span></label>
                        <input type="text" name="priezvisko" required placeholder="Priezvisko" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none" value="<?= htmlspecialchars($priezvisko ?? '') ?>">
                    </div>
                </div>
                <div>
                    <label class="block text-white/80 text-sm mb-1">E-mail <span class="text-red-400">*</span></label>
                    <input type="email" name="email" required placeholder="vas@email.com" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none" value="<?= htmlspecialchars($email_raw ?? $email ?? "") ?>">
                </div>
                <div>
                    <label class="block text-white/80 text-sm mb-1">Heslo <span class="text-red-400">*</span></label>
                    <div class="relative">
                    <input type="password" id="reg-pass" name="password" required placeholder="Min. 8 znakov, A-z, 0-9, špeciálny znak" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none pr-14">
                    <button type="button" onclick="togglePassword('reg-pass', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/60 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                </div>
                <div>
                    <label class="block text-white/80 text-sm mb-1">Heslo znova <span class="text-red-400">*</span></label>
                    <div class="relative">
                    <input type="password" id="reg-pass2" name="password2" required placeholder="Zopakuj heslo" class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/30 text-white placeholder-white/60 focus:ring-4 focus:ring-primary focus:outline-none pr-14">
                    <button type="button" onclick="togglePassword('reg-pass2', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/60 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                </div>
                <button name="register" class="w-full bg-green-600 py-4 rounded-xl font-bold text-white hover:bg-green-700 transition text-lg">Vytvoriť účet</button>
            </form>
        </div>
    </div>
</div>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword
        ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>'
        : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
}
</script>

<!-- Upozornenie pre malé obrazovky -->
<div id="screen-warning" class="hidden fixed inset-0 bg-gray-900 flex flex-col items-center justify-center z-50 p-8 text-center">
    <div class="text-8xl mb-6">📱</div>
    <h2 class="text-2xl font-bold text-white mb-4">Takto to nebude fungovať!</h2>
    <p class="text-gray-300 max-w-sm">Je nám ľúto, ale táto aplikácia je optimalizovaná pre väčšie obrazovky. Skúste to znova na tablete alebo počítači.</p>
</div>
<script>
function checkScreenSize() {
    const warning = document.getElementById('screen-warning');
    if (window.innerWidth < 1024) {
        warning.classList.remove('hidden');
    } else {
        warning.classList.add('hidden');
    }
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
</script>
</body>
</html>