<?php
require_once __DIR__ . '/../entities/Entry.php';

class EntryRepository implements IEntryRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(int $id): ?Entry {
        $stmt = $this->pdo->prepare("SELECT * FROM Zapis WHERE PK_ID_zapis = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Entry::fromArray($row) : null;
    }

    public function findByDiary(int $diaryId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM Zapis WHERE FK_ID_dennik = ? ORDER BY datum_upravy DESC");
        $stmt->execute([$diaryId]);
        return array_map([Entry::class, 'fromArray'], $stmt->fetchAll());
    }

    public function search(string $keyword, int $diaryId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM Zapis WHERE FK_ID_dennik = ? AND (nazov LIKE ? OR obsah LIKE ?) ORDER BY datum_upravy DESC");
        $like = "%$keyword%";
        $stmt->execute([$diaryId, $like, $like]);
        return array_map([Entry::class, 'fromArray'], $stmt->fetchAll());
    }

    public function save(Entry $entry): Entry {
        if ($entry->id) {
            $this->pdo->prepare("UPDATE Zapis SET nazov=?, obsah=?, FK_ID_kategoria=?, FK_ID_sablona=?, datum_upravy=NOW() WHERE PK_ID_zapis=?")
                ->execute([$entry->title, $entry->content, $entry->categoryId, $entry->templateId, $entry->id]);
        } else {
            $this->pdo->prepare("INSERT INTO Zapis (FK_ID_dennik, nazov, obsah, FK_ID_kategoria, FK_ID_sablona) VALUES (?,?,?,?,?)")
                ->execute([$entry->diaryId, $entry->title, $entry->content, $entry->categoryId, $entry->templateId]);
            $entry->id = (int)$this->pdo->lastInsertId();
        }
        return $entry;
    }

    public function delete(int $id): bool {
        return (bool)$this->pdo->prepare("DELETE FROM Zapis WHERE PK_ID_zapis=?")->execute([$id]);
    }
}
