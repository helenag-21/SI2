<?php
require_once __DIR__ . '/../entities/Security.php';

class SecurityRepository implements ISecurityRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findByUser(int $userId): ?Security {
        $stmt = $this->pdo->prepare("SELECT * FROM Zabezpecenie WHERE FK_ID_pouzivatel = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? Security::fromArray($row) : null;
    }

    public function findById(int $id): ?Security {
        $stmt = $this->pdo->prepare("SELECT * FROM Zabezpecenie WHERE PK_ID_zabezpecenie = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Security::fromArray($row) : null;
    }

    public function save(Security $security): Security {
        if ($security->id) {
            $this->pdo->prepare("UPDATE Zabezpecenie SET hash_hesla=?, typ_zabezpecenia=?, datum_upravy=NOW() WHERE PK_ID_zabezpecenie=?")
                ->execute([$security->passwordHash, $security->type, $security->id]);
        } else {
            $this->pdo->prepare("INSERT INTO Zabezpecenie (FK_ID_pouzivatel, typ_zabezpecenia, hash_hesla) VALUES (?,?,?)")
                ->execute([$security->userId, $security->type, $security->passwordHash]);
            $security->id = (int)$this->pdo->lastInsertId();
        }
        return $security;
    }
}
