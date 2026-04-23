<?php
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/SecurityRepository.php';
require_once __DIR__ . '/../entities/User.php';
require_once __DIR__ . '/../entities/Security.php';

class AuthController {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function login(string $email, string $password): bool {
        $userRepo = new UserRepository($this->pdo);
        $secRepo  = new SecurityRepository($this->pdo);

        $user = $userRepo->findByEmail($email);
        if (!$user) return false;

        $security = $secRepo->findByUser($user->id);
        if (!$security || !password_verify($password, $security->passwordHash)) return false;

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user->id;
        $_SESSION['user_name'] = $user->firstName . ' ' . $user->lastName;
        return true;
    }

    public function register(string $firstName, string $lastName, string $email, string $password): User {
        $userRepo = new UserRepository($this->pdo);
        $secRepo  = new SecurityRepository($this->pdo);

        if ($userRepo->findByEmail($email)) {
            throw new RuntimeException('Účet s týmto e-mailom už existuje.');
        }

        $user = $userRepo->save(new User($firstName, $lastName, $email));
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sec  = new Security($user->id, 'password', $hash);
        $secRepo->save($sec);
        return $user;
    }

    public function logout(): void {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
