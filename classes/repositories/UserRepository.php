<?php
require_once __DIR__ . '/../entities/User.php';

class UserRepository implements IUserRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(int $id): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM Pouzivatel WHERE PK_ID_pouzivatel = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM Pouzivatel WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function save(User $user): User {
        if ($user->id) {
            $this->pdo->prepare("UPDATE Pouzivatel SET meno=?, priezvisko=?, email=? WHERE PK_ID_pouzivatel=?")
                ->execute([$user->firstName, $user->lastName, $user->email, $user->id]);
        } else {
            $this->pdo->prepare("INSERT INTO Pouzivatel (meno, priezvisko, email) VALUES (?,?,?)")
                ->execute([$user->firstName, $user->lastName, $user->email]);
            $user->id = (int)$this->pdo->lastInsertId();
        }
        return $user;
    }
}
