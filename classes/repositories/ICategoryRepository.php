<?php
interface ICategoryRepository {
    public function findAll(int $userId): array;
    public function findById(int $id): ?Category;
    public function save(Category $category): Category;
    public function delete(int $id): bool;
    public function count(int $userId): int;
}
