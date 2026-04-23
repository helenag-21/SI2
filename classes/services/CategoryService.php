<?php
require_once __DIR__ . '/../repositories/ICategoryRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../entities/Category.php';

class CategoryService {
    private ICategoryRepository $categoryRepo;
    private const MAX_CATEGORIES = 10;
    private const MAX_TEMPLATES = 15;

    public function __construct(ICategoryRepository $categoryRepo) {
        $this->categoryRepo = $categoryRepo;
    }

    public function createCategory(int $userId, string $title): Category {
        if (trim($title) === '') throw new InvalidArgumentException('Názov kategórie nesmie byť prázdny.');
        if ($this->categoryRepo->count($userId) >= self::MAX_CATEGORIES) {
            throw new RuntimeException('Dosiahli ste maximálny počet kategórií (' . self::MAX_CATEGORIES . ').');
        }
        $category = new Category($userId, $title);
        return $this->categoryRepo->save($category);
    }

    public function deleteCategory(int $id): bool {
        $category = $this->categoryRepo->findById($id);
        if (!$category) throw new RuntimeException('Kategória neexistuje.');
        return $this->categoryRepo->delete($id);
    }

    public function listCategories(int $userId): array {
        return $this->categoryRepo->findAll($userId);
    }
}
