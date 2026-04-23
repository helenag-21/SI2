<?php
require_once __DIR__ . '/../entities/Category.php';

class CategoryRepository implements ICategoryRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findAll(int $userId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM Kategoria WHERE FK_ID_pouzivatel = ? ORDER BY nazov");
        $stmt->execute([$userId]);
        return array_map([Category::class, 'fromArray'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Category {
        $stmt = $this->pdo->prepare("SELECT * FROM Kategoria WHERE PK_ID_kategoria = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Category::fromArray($row) : null;
    }

    public function save(Category $category): Category {
        if ($category->id) {
            $this->pdo->prepare("UPDATE Kategoria SET nazov=? WHERE PK_ID_kategoria=?")->execute([$category->title, $category->id]);
        } else {
            $this->pdo->prepare("INSERT INTO Kategoria (FK_ID_pouzivatel, nazov, typ) VALUES (?,?,?)")
                ->execute([$category->userId, $category->title, $category->type]);
            $category->id = (int)$this->pdo->lastInsertId();
        }
        return $category;
    }

    public function delete(int $id): bool {
        return (bool)$this->pdo->prepare("DELETE FROM Kategoria WHERE PK_ID_kategoria=?")->execute([$id]);
    }

    public function count(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Kategoria WHERE FK_ID_pouzivatel = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
