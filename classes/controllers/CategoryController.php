<?php
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';

class CategoryController {
    private CategoryService $categoryService;

    public function __construct(PDO $pdo) {
        $this->categoryService = new CategoryService(new CategoryRepository($pdo));
    }

    public function createCategory(int $userId, string $title): Category {
        return $this->categoryService->createCategory($userId, $title);
    }

    public function deleteCategory(int $id): bool {
        return $this->categoryService->deleteCategory($id);
    }

    public function listCategories(int $userId): array {
        return $this->categoryService->listCategories($userId);
    }
}
