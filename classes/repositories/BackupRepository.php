<?php
require_once __DIR__ . '/../entities/Backup.php';

class BackupRepository implements IBackupRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findByDiary(int $diaryId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM Zaloha WHERE FK_ID_dennik = ? ORDER BY datum_vytvorenia DESC");
        $stmt->execute([$diaryId]);
        return array_map([Backup::class, 'fromArray'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Backup {
        $stmt = $this->pdo->prepare("SELECT * FROM Zaloha WHERE PK_ID_zaloha = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Backup::fromArray($row) : null;
    }

    public function save(Backup $backup): Backup {
        $this->pdo->prepare("INSERT INTO Zaloha (FK_ID_dennik, nazov, balik_dat) VALUES (?,?,?)")
            ->execute([$backup->diaryId, $backup->title, $backup->data]);
        $backup->id = (int)$this->pdo->lastInsertId();
        return $backup;
    }

    public function delete(int $id): bool {
        return (bool)$this->pdo->prepare("DELETE FROM Zaloha WHERE PK_ID_zaloha=?")->execute([$id]);
    }

    public function validate(int $id): bool {
        $backup = $this->findById($id);
        if (!$backup) return false;
        $data = json_decode($backup->data, true);
        return is_array($data) && isset($data['zapisy']);
    }
}
