<?php
// fix-login.php  ← open once, then delete!

require_once 'config/db.php';

$email = 'admin@example.com';
$password = '1234';   // ← this is the password you will type to log in

// 1. Create or get the user
$stmt = $pdo->prepare("INSERT IGNORE INTO Pouzivatel (meno, priezvisko, email) VALUES ('Admin', 'Admin', ?)");
$stmt->execute([$email]);

$stmt = $pdo->prepare("SELECT PK_ID_pouzivatel FROM Pouzivatel WHERE email = ?");
$stmt->execute([$email]);
$userId = $stmt->fetchColumn();

// 2. Generate REAL hash with PHP
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<pre style='background:#000;color:#0f0;padding:30px;font-size:18px;'>";
echo "SUCCESS! Login with password: 1234\n\n";
echo "User ID: $userId\n";
echo "Freshly generated hash for '1234':\n\n";
echo $hash . "\n\n";

// 3. Save it to database
$pdo->prepare("DELETE FROM Zabezpecenie WHERE FK_ID_pouzivatel = ?")->execute([$userId]);
$pdo->prepare("INSERT INTO Zabezpecenie (FK_ID_pouzivatel, typ_zabezpecenia, hash_hesla) VALUES (?, 'password', ?)")
    ->execute([$userId, $hash]);

echo "Hash saved to database.\n";
echo "Now go to index.php and type 1234 → you are in!\n";
echo "Delete this file now → fix-login.php\n";
echo "</pre>";
?>