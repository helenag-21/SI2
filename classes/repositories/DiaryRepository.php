<?php
require_once __DIR__ . '/../entities/Diary.php';

class DiaryRepository implements IDiaryRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(int $id): ?Diary {
        $stmt = $this->pdo->prepare("SELECT * FROM Dennik WHERE PK_ID_dennik = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Diary::fromArray($row) : null;
    }

    public function findByUser(int $userId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM Dennik WHERE FK_ID_pouzivatel = ? ORDER BY datum_vytvorenia DESC");
        $stmt->execute([$userId]);
        return array_map([Diary::class, 'fromArray'], $stmt->fetchAll());
    }

    public function save(Diary $diary): Diary {
        if ($diary->id) {
            $this->pdo->prepare("UPDATE Dennik SET nazov=?, datum_upravy=NOW() WHERE PK_ID_dennik=?")
                ->execute([$diary->title, $diary->id]);
        } else {
            $this->pdo->prepare("INSERT INTO Dennik (FK_ID_pouzivatel, nazov) VALUES (?,?)")
                ->execute([$diary->userId, $diary->title]);
            $diary->id = (int)$this->pdo->lastInsertId();
        }
        return $diary;
    }

    public function delete(int $id): bool {
        return (bool)$this->pdo->prepare("DELETE FROM Dennik WHERE PK_ID_dennik=?")->execute([$id]);
    }
}
